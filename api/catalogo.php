<?php
/* Lê o catálogo público (dados/produtos.js) do lado do servidor, para
   que o preço do pedido seja sempre conferido aqui e nunca aceito
   do jeito que veio do navegador. */

declare(strict_types=1);

function lmc_catalogo(): array
{
    static $catalogo = null;
    if ($catalogo !== null) {
        return $catalogo;
    }

    $arquivo = __DIR__ . '/../dados/produtos.js';
    $bruto = is_file($arquivo) ? (string) file_get_contents($arquivo) : '';

    $inicio = strpos($bruto, '{');
    $fim = strrpos($bruto, '}');
    if ($inicio === false || $fim === false) {
        return $catalogo = ['produtos' => [], 'categorias' => []];
    }

    $json = substr($bruto, $inicio, $fim - $inicio + 1);
    $dados = json_decode($json, true);

    return $catalogo = is_array($dados) ? $dados : ['produtos' => [], 'categorias' => []];
}

function lmc_produto(string $id): ?array
{
    foreach (lmc_catalogo()['produtos'] ?? [] as $p) {
        if (($p['id'] ?? '') === $id) {
            return $p;
        }
    }
    return null;
}

/** Frete por região, a partir do CEP. Tabela própria — reveja com o Melhor Envio. */
function lmc_frete(string $cep, int $pesoGramas, float $subtotal): array
{
    $config = require __DIR__ . '/config.php';
    $gratisAcima = 350.0;

    $digitos = preg_replace('/\D/', '', $cep) ?? '';
    if (strlen($digitos) !== 8) {
        return ['valor' => null, 'regiao' => null, 'motivo' => 'CEP incompleto'];
    }

    $faixa = (int) substr($digitos, 0, 1);
    $tabela = [
        0 => ['São Paulo', 22.0],  1 => ['São Paulo', 22.0],
        2 => ['Rio de Janeiro e Espírito Santo', 30.0],
        3 => ['Minas Gerais', 30.0],
        4 => ['Bahia e Sergipe', 44.0],
        5 => ['Pernambuco, Alagoas, Paraíba e Rio Grande do Norte', 46.0],
        6 => ['Norte e Ceará, Piauí e Maranhão', 52.0],
        7 => ['Centro-Oeste', 38.0],
        8 => ['Paraná e Santa Catarina', 32.0],
        9 => ['Rio Grande do Sul', 36.0],
    ];
    [$regiao, $base] = $tabela[$faixa] ?? ['Brasil', 46.0];

    // Acima de 1 kg os Correios sobem de faixa.
    $extra = max(0, (int) ceil(($pesoGramas - 1000) / 500)) * 6.0;
    $valor = $base + $extra;

    if ($subtotal >= $gratisAcima) {
        return ['valor' => 0.0, 'regiao' => $regiao, 'motivo' => 'Frete grátis acima de R$ 350'];
    }
    return ['valor' => round($valor, 2), 'regiao' => $regiao, 'motivo' => 'PAC, estimativa'];
}
