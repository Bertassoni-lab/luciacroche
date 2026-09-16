<?php
/* Base de todas as páginas do painel: sessão, cabeçalho e funções de relatório. */

declare(strict_types=1);

require __DIR__ . '/../api/lib.php';
require __DIR__ . '/../api/catalogo.php';

/* ---------------- Leitura dos dados ---------------- */

function p_pedidos(): array
{
    $lista = lmc_ler('pedidos.json', []);
    usort($lista, fn($a, $b) => strcmp((string) ($b['criadoEm'] ?? ''), (string) ($a['criadoEm'] ?? '')));
    return $lista;
}

function p_custos(): array
{
    $lista = lmc_ler('custos.json', []);
    usort($lista, fn($a, $b) => strcmp((string) ($b['data'] ?? ''), (string) ($a['data'] ?? '')));
    return $lista;
}

function p_clientes(): array { return lmc_ler('clientes.json', []); }
function p_posts(): array    { return lmc_ler('posts.json', []); }
function p_campanhas(): array { return lmc_ler('campanhas.json', []); }

/* ---------------- Períodos ---------------- */

function p_periodos(): array
{
    return [
        'mes'      => 'Este mês',
        'mes-1'    => 'Mês passado',
        'ano'      => 'Este ano',
        'temporada'=> 'Temporada (dez a fev)',
        'tudo'     => 'Desde o começo',
    ];
}

function p_intervalo(string $periodo): array
{
    $hoje = new DateTimeImmutable('today');
    switch ($periodo) {
        case 'mes-1':
            $inicio = $hoje->modify('first day of last month')->setTime(0, 0);
            return [$inicio, $inicio->modify('last day of this month')->setTime(23, 59, 59)];
        case 'ano':
            return [$hoje->setDate((int) $hoje->format('Y'), 1, 1)->setTime(0, 0), $hoje->setTime(23, 59, 59)];
        case 'temporada':
            $ano = (int) $hoje->format('n') <= 2 ? (int) $hoje->format('Y') - 1 : (int) $hoje->format('Y');
            return [$hoje->setDate($ano, 12, 1)->setTime(0, 0), $hoje->setDate($ano + 1, 2, 28)->setTime(23, 59, 59)];
        case 'tudo':
            return [new DateTimeImmutable('2000-01-01'), $hoje->setTime(23, 59, 59)];
        case 'mes':
        default:
            return [$hoje->modify('first day of this month')->setTime(0, 0), $hoje->setTime(23, 59, 59)];
    }
}

function p_dentro(?string $iso, DateTimeImmutable $de, DateTimeImmutable $ate): bool
{
    if (!$iso) {
        return false;
    }
    try {
        $d = new DateTimeImmutable($iso);
    } catch (Throwable) {
        return false;
    }
    return $d >= $de && $d <= $ate;
}

/* ---------------- Números do negócio ---------------- */

/** Pedidos que viraram dinheiro de verdade. */
function p_pedidos_pagos(array $pedidos): array
{
    return array_values(array_filter($pedidos, fn($p) => in_array($p['status'] ?? '', ['pago', 'enviado', 'entregue'], true)));
}

/** Horas de crochê de um pedido, lendo o tempo de produção do catálogo. */
function p_horas_pedido(array $pedido): float
{
    $horas = 0.0;
    foreach ($pedido['itens'] ?? [] as $item) {
        $produto = lmc_produto($item['id'] ?? '');
        $horas += p_horas_produto($produto) * (int) ($item['qtd'] ?? 1);
    }
    return $horas;
}

function p_horas_produto(?array $produto): float
{
    if (!$produto) {
        return 0.0;
    }
    preg_match_all('/\d+/', (string) ($produto['tempoProducao'] ?? ''), $achados);
    $numeros = array_map('floatval', $achados[0] ?? []);
    if (!$numeros) {
        return 0.0;
    }
    return array_sum($numeros) / count($numeros);
}

/** Custo de barbante estimado de um pedido, a partir do peso das peças. */
function p_material_pedido(array $pedido, float $precoKg = 78.0): float
{
    $gramas = 0;
    foreach ($pedido['itens'] ?? [] as $item) {
        $produto = lmc_produto($item['id'] ?? '');
        $gramas += ((int) ($produto['pesoGramas'] ?? 400)) * (int) ($item['qtd'] ?? 1);
    }
    return round(($gramas / 1000) * $precoKg * 1.08, 2);
}

function p_resumo(string $periodo): array
{
    [$de, $ate] = p_intervalo($periodo);
    $config = lmc_config();

    $pedidos = array_values(array_filter(p_pedidos(), fn($p) => p_dentro($p['criadoEm'] ?? null, $de, $ate)));
    $pagos = p_pedidos_pagos($pedidos);

    $receita = 0.0;
    $frete = 0.0;
    $horas = 0.0;
    $material = 0.0;
    foreach ($pagos as $p) {
        $receita += (float) ($p['total'] ?? 0);
        $frete += (float) ($p['frete'] ?? 0);
        $horas += p_horas_pedido($p);
        $material += p_material_pedido($p);
    }

    $custos = array_values(array_filter(p_custos(), fn($c) => p_dentro($c['data'] ?? null, $de, $ate)));
    $custosLancados = array_sum(array_map(fn($c) => (float) ($c['valor'] ?? 0), $custos));

    // O frete cobrado do cliente é repassado aos Correios: entra e sai.
    $custoTotal = $custosLancados + $material + $frete;
    $lucro = $receita - $custoTotal;

    return [
        'periodo'        => $periodo,
        'de'             => $de,
        'ate'            => $ate,
        'pedidos'        => $pedidos,
        'pagos'          => $pagos,
        'qtdPedidos'     => count($pedidos),
        'qtdPagos'       => count($pagos),
        'receita'        => round($receita, 2),
        'frete'          => round($frete, 2),
        'material'       => round($material, 2),
        'custosLancados' => round($custosLancados, 2),
        'custoTotal'     => round($custoTotal, 2),
        'lucro'          => round($lucro, 2),
        'margem'         => $receita > 0 ? round($lucro / $receita * 100, 1) : 0.0,
        'ticket'         => count($pagos) > 0 ? round($receita / count($pagos), 2) : 0.0,
        'horas'          => round($horas, 1),
        'porHora'        => $horas > 0 ? round($lucro / $horas, 2) : 0.0,
        'metaHora'       => (float) $config['valor_hora_meta'],
        'custos'         => $custos,
    ];
}

/** Quanto do teto do MEI já foi usado no ano. */
function p_mei(): array
{
    $limite = (float) lmc_config()['limite_mei_ano'];
    $ano = date('Y');
    $receita = 0.0;
    foreach (p_pedidos_pagos(p_pedidos()) as $p) {
        if (substr((string) ($p['criadoEm'] ?? ''), 0, 4) === $ano) {
            $receita += (float) ($p['total'] ?? 0);
        }
    }
    return [
        'ano'      => $ano,
        'receita'  => round($receita, 2),
        'limite'   => $limite,
        'restante' => round($limite - $receita, 2),
        'percento' => $limite > 0 ? min(100, round($receita / $limite * 100, 1)) : 0.0,
    ];
}

function p_status_nome(string $status): string
{
    return [
        'aguardando_pagamento' => 'Aguardando pagamento',
        'pagamento_informado'  => 'Cliente disse que pagou',
        'em_analise'           => 'Em análise',
        'pago'                 => 'Pago',
        'enviado'              => 'Enviado',
        'entregue'             => 'Entregue',
        'cancelado'            => 'Cancelado',
        'recusado'             => 'Recusado',
        'estornado'            => 'Estornado',
    ][$status] ?? $status;
}

function p_status_classe(string $status): string
{
    return [
        'aguardando_pagamento' => 'aguardando',
        'pagamento_informado'  => 'informado',
        'em_analise'           => 'informado',
        'pago'                 => 'pago',
        'enviado'              => 'enviado',
        'entregue'             => 'entregue',
    ][$status] ?? 'cancelado';
}

/** Abreviação do mês em português: jan, fev, mar… */
function p_mes_curto(DateTimeInterface $d): string
{
    $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    return $meses[(int) $d->format('n') - 1] . '/' . $d->format('y');
}

function p_data(?string $iso, string $formato = 'd/m/Y'): string
{
    if (!$iso) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($iso))->format($formato);
    } catch (Throwable) {
        return '—';
    }
}

/* ---------------- Layout ---------------- */

function p_cabecalho(string $titulo, string $paginaAtual): void
{
    $menu = [
        'index.php'      => 'Visão geral',
        'pedidos.php'    => 'Pedidos',
        'financeiro.php' => 'Financeiro',
        'marketing.php'  => 'Marketing',
        'blog.php'       => 'Diário',
    ];
    ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titulo) ?> · Painel</title>
<link rel="icon" href="../assets/img/marca/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=Fraunces:opsz,wght@9..144,400;9..144,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/painel.css">
</head>
<body>
<header class="p-topo">
  <div class="env">
    <a class="p-marca" href="index.php">Lúcia Maria Crochê<small>Painel do ateliê</small></a>
    <nav class="p-menu">
      <?php foreach ($menu as $arquivo => $rotulo): ?>
        <a href="<?= e($arquivo) ?>"<?= $arquivo === $paginaAtual ? ' class="ativo"' : '' ?>><?= e($rotulo) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="p-sair" href="../index.html" target="_blank" rel="noopener">ver o site ↗</a>
    <a class="p-sair" href="sair.php">sair</a>
  </div>
</header>
<main><div class="env">
<?php
}

function p_rodape(): void
{
    ?>
</div></main>
</body>
</html>
<?php
}

/** Barrinha de seleção de período. */
function p_seletor_periodo(string $atual, string $arquivo): void
{
    ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:22px">
      <?php foreach (p_periodos() as $chave => $rotulo): ?>
        <a class="botao botao--pequeno <?= $chave === $atual ? 'botao--principal' : 'botao--contorno' ?>"
           href="<?= e($arquivo) ?>?periodo=<?= e($chave) ?>"><?= e($rotulo) ?></a>
      <?php endforeach; ?>
    </div>
    <?php
}
