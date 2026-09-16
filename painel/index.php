<?php
/* Entrada do painel: login e visão geral. */

declare(strict_types=1);

require __DIR__ . '/comum.php';

lmc_sessao();
$erro = '';

/* ---------------- Login ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['senha'])) {
    $tentativas = (int) ($_SESSION['lmc_tentativas'] ?? 0);
    $ultima = (int) ($_SESSION['lmc_ultima_tentativa'] ?? 0);

    if ($tentativas >= 5 && (time() - $ultima) < 300) {
        $erro = 'Muitas tentativas. Espere cinco minutos.';
    } elseif (password_verify((string) $_POST['senha'], lmc_config()['painel_senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['lmc_painel'] = true;
        $_SESSION['lmc_tentativas'] = 0;
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['lmc_tentativas'] = $tentativas + 1;
        $_SESSION['lmc_ultima_tentativa'] = time();
        $erro = 'Senha errada.';
        usleep(400000);
    }
}

if (!lmc_logado()) {
    ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Painel · Lúcia Maria Crochê</title>
<link rel="icon" href="../assets/img/marca/favicon.svg" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=Fraunces:opsz,wght@9..144,400;9..144,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/painel.css">
</head>
<body>
<form class="login" method="post">
  <h1>Painel do ateliê</h1>
  <p class="sub">Só para a Lúcia, a Drisana e quem mais cuidar da loja.</p>
  <?php if ($erro): ?><p class="recado recado--erro"><?= e($erro) ?></p><?php endif; ?>
  <label for="senha">Senha</label>
  <input id="senha" name="senha" type="password" autocomplete="current-password" autofocus required>
  <button class="botao botao--principal botao--largo" type="submit" style="margin-top:18px">Entrar</button>
  <p class="dica" style="text-align:center;margin-top:16px"><a href="../index.html">← voltar para a loja</a></p>
</form>
</body>
</html>
    <?php
    exit;
}

/* ---------------- Visão geral ---------------- */

$resumo = p_resumo('mes');
$mei = p_mei();
$pedidos = p_pedidos();
$clientes = p_clientes();

$aguardando = array_values(array_filter($pedidos, fn($p) =>
    in_array($p['status'] ?? '', ['aguardando_pagamento', 'pagamento_informado', 'em_analise'], true)));
$paraEnviar = array_values(array_filter($pedidos, fn($p) => ($p['status'] ?? '') === 'pago'));

/* Receita dos últimos seis meses, para a barrinha. */
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $ref = (new DateTimeImmutable('first day of this month'))->modify("-$i month");
    $meses[$ref->format('Y-m')] = ['rotulo' => p_mes_curto($ref), 'valor' => 0.0];
}
foreach (p_pedidos_pagos($pedidos) as $p) {
    $chave = substr((string) ($p['criadoEm'] ?? ''), 0, 7);
    if (isset($meses[$chave])) {
        $meses[$chave]['valor'] += (float) ($p['total'] ?? 0);
    }
}
$maior = max(1.0, max(array_map(fn($m) => $m['valor'], $meses)));

p_cabecalho('Visão geral', 'index.php');
?>

<h1>Como está o ateliê hoje</h1>
<p class="dica" style="margin-bottom:24px">Números de <?= e(strtolower(p_periodos()['mes'])) ?>, de <?= e(p_data($resumo['de']->format('c'))) ?> até hoje.</p>

<?php if (count($pedidos) === 0): ?>
  <div class="recado recado--info">
    <strong>Nenhum pedido ainda.</strong> Assim que a primeira venda entrar pelo site, tudo aqui se preenche sozinho.
    Enquanto isso, dá para lançar as despesas no <a href="financeiro.php">financeiro</a> e escrever no <a href="blog.php">diário</a>.
  </div>
<?php endif; ?>

<div class="p-kpis">
  <div class="kpi">
    <p class="kpi__rotulo">Receita do mês</p>
    <p class="kpi__valor"><?= e(lmc_moeda($resumo['receita'])) ?></p>
    <p class="kpi__nota"><?= (int) $resumo['qtdPagos'] ?> pedido(s) pago(s)</p>
  </div>
  <div class="kpi <?= $resumo['lucro'] >= 0 ? 'kpi--bom' : 'kpi--alerta' ?>">
    <p class="kpi__rotulo">Sobrou no mês</p>
    <p class="kpi__valor"><?= e(lmc_moeda($resumo['lucro'])) ?></p>
    <p class="kpi__nota">margem de <?= e(number_format($resumo['margem'], 1, ',', '.')) ?>%</p>
  </div>
  <div class="kpi <?= $resumo['porHora'] >= $resumo['metaHora'] ? 'kpi--bom' : 'kpi--alerta' ?>">
    <p class="kpi__rotulo">Por hora de crochê</p>
    <p class="kpi__valor"><?= e(lmc_moeda($resumo['porHora'])) ?></p>
    <p class="kpi__nota">meta: <?= e(lmc_moeda($resumo['metaHora'])) ?> · <?= e(number_format($resumo['horas'], 0, ',', '.')) ?> h no mês</p>
  </div>
  <div class="kpi">
    <p class="kpi__rotulo">Ticket médio</p>
    <p class="kpi__valor"><?= e(lmc_moeda($resumo['ticket'])) ?></p>
    <p class="kpi__nota"><?= count($clientes) ?> cliente(s) na lista</p>
  </div>
</div>

<div class="tres-colunas">
  <div>
    <?php if (count($aguardando) > 0 || count($paraEnviar) > 0): ?>
      <div class="painel-caixa">
        <h2>Para resolver agora</h2>
        <?php if (count($aguardando) > 0): ?>
          <p><strong><?= count($aguardando) ?> pedido(s)</strong> esperando confirmação de pagamento.</p>
        <?php endif; ?>
        <?php if (count($paraEnviar) > 0): ?>
          <p><strong><?= count($paraEnviar) ?> pedido(s)</strong> pago(s) esperando para ser embalado(s) e enviado(s).</p>
        <?php endif; ?>
        <p style="margin-bottom:0"><a class="botao botao--principal botao--pequeno" href="pedidos.php">Abrir os pedidos</a></p>
      </div>
    <?php endif; ?>

    <div class="painel-caixa">
      <h2>Receita dos últimos seis meses</h2>
      <div class="barras">
        <?php foreach ($meses as $m): ?>
          <div class="barra">
            <span><?= e($m['rotulo']) ?></span>
            <span class="barra__trilho"><span class="barra__preenche barra__preenche--verde"
              style="width: <?= e((string) round($m['valor'] / $maior * 100)) ?>%"></span></span>
            <span class="num"><?= e(lmc_moeda($m['valor'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="dica">Em praia, o verão manda no ano. Compare dezembro com junho para ver o tamanho da sazonalidade.</p>
    </div>

    <div class="painel-caixa">
      <h2>Últimos pedidos</h2>
      <?php if (!$pedidos): ?>
        <p class="dica">Nada ainda.</p>
      <?php else: ?>
        <div class="rolagem"><table class="p-tabela">
          <thead><tr><th>Pedido</th><th>Cliente</th><th>Situação</th><th class="num">Total</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($pedidos, 0, 6) as $p): ?>
            <tr>
              <td><a href="pedidos.php#<?= e($p['id']) ?>"><?= e($p['id']) ?></a><br>
                  <small style="color:var(--tinta-fraca)"><?= e(p_data($p['criadoEm'] ?? null)) ?></small></td>
              <td><?= e($p['cliente']['nome'] ?? '—') ?></td>
              <td><span class="selo selo--<?= e(p_status_classe($p['status'] ?? '')) ?>"><?= e(p_status_nome($p['status'] ?? '')) ?></span></td>
              <td class="num"><?= e(lmc_moeda((float) ($p['total'] ?? 0))) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="painel-caixa">
      <h2>Limite do MEI em <?= e($mei['ano']) ?></h2>
      <div class="progresso <?= $mei['percento'] > 80 ? 'alerta' : '' ?>"><span style="width: <?= e((string) $mei['percento']) ?>%"></span></div>
      <p style="margin:0"><strong><?= e(lmc_moeda($mei['receita'])) ?></strong> de <?= e(lmc_moeda($mei['limite'])) ?>
        <span style="color:var(--tinta-fraca)">(<?= e(number_format($mei['percento'], 1, ',', '.')) ?>%)</span></p>
      <p class="dica">Ainda cabem <?= e(lmc_moeda($mei['restante'])) ?> este ano.
        <?php if ($mei['percento'] > 80): ?><strong>Atenção: passando do teto, o MEI é desenquadrado.</strong><?php endif; ?></p>
    </div>

    <div class="painel-caixa">
      <h2>Atalhos</h2>
      <ul class="lista-simples">
        <li><a href="../ferramentas/precificacao.html" target="_blank" rel="noopener">Calcular o preço de uma peça</a></li>
        <li><a href="../ferramentas/cadastro.html" target="_blank" rel="noopener">Cadastrar peça nova</a></li>
        <li><a href="blog.php">Escrever no diário</a></li>
        <li><a href="marketing.php">Avisar a lista de clientes</a></li>
        <li><a href="financeiro.php">Lançar uma despesa</a></li>
      </ul>
    </div>

    <div class="painel-caixa">
      <h2>Lembretes do ano</h2>
      <ul class="lista-simples">
        <li><span>Abrir encomendas de Natal</span><strong>outubro</strong></li>
        <li><span>Fechar as encomendas de Natal</span><strong>10 de nov.</strong></li>
        <li><span>Estoque pronto para a temporada</span><strong>15 de dez.</strong></li>
        <li><span>Declaração anual do MEI (DASN)</span><strong>até 31 de maio</strong></li>
      </ul>
    </div>
  </div>
</div>

<?php p_rodape();
