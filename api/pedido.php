<?php
/* Registra um pedido. Confere todos os preços contra o catálogo do
   servidor — o que vem do navegador é só a lista de ids e quantidades. */

declare(strict_types=1);

require __DIR__ . '/lib.php';
require __DIR__ . '/catalogo.php';

header('Access-Control-Allow-Origin: ' . (lmc_config()['site'] ?? '*'));

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    lmc_json_resposta(['erro' => 'Use POST.'], 405);
}

$entrada = lmc_corpo_json();
$itensEntrada = $entrada['itens'] ?? [];

if (!is_array($itensEntrada) || count($itensEntrada) === 0) {
    lmc_json_resposta(['erro' => 'Nenhuma peça no pedido.'], 422);
}
if (count($itensEntrada) > 30) {
    lmc_json_resposta(['erro' => 'Pedido grande demais. Fale com a gente no WhatsApp.'], 422);
}

/* ---- Confere cada item contra o catálogo do servidor ---- */
$itens = [];
$subtotal = 0.0;
$peso = 0;
$temEncomenda = false;

foreach ($itensEntrada as $bruto) {
    $id = lmc_texto($bruto['id'] ?? '', 80);
    $qtd = max(1, min(20, (int) ($bruto['qtd'] ?? 1)));
    $produto = lmc_produto($id);

    if ($produto === null) {
        lmc_json_resposta(['erro' => 'Peça não encontrada: ' . $id], 422);
    }

    $preco = lmc_dinheiro($produto['preco']);
    $subtotal += $preco * $qtd;
    $peso += ((int) ($produto['pesoGramas'] ?? 400)) * $qtd;
    if (($produto['disponibilidade'] ?? '') === 'sob-encomenda') {
        $temEncomenda = true;
    }

    $itens[] = [
        'id'       => $id,
        'nome'     => $produto['nome'],
        'preco'    => $preco,
        'qtd'      => $qtd,
        'subtotal' => lmc_dinheiro($preco * $qtd),
    ];
}

/* ---- Entrega ---- */
$tipoEntrega = ($entrada['entrega']['tipo'] ?? '') === 'retirada' ? 'retirada' : 'correios';
$cep = lmc_texto($entrada['entrega']['cep'] ?? '', 12);
$frete = ['valor' => 0.0, 'regiao' => 'Retirada na feira', 'motivo' => 'Sem frete'];

if ($tipoEntrega === 'correios') {
    $frete = lmc_frete($cep, $peso, $subtotal);
    if ($frete['valor'] === null) {
        lmc_json_resposta(['erro' => 'Informe um CEP com 8 dígitos para calcular o frete.'], 422);
    }
}

/* ---- Pagamento e desconto ---- */
$formaPagamento = in_array($entrada['pagamento'] ?? '', ['pix', 'cartao', 'whatsapp', 'dinheiro'], true)
    ? $entrada['pagamento'] : 'pix';

$descontoPix = $formaPagamento === 'pix' ? lmc_dinheiro($subtotal * 0.05) : 0.0;
$total = lmc_dinheiro($subtotal + (float) $frete['valor'] - $descontoPix);

/* ---- Cliente ---- */
$cliente = [
    'nome'     => lmc_texto($entrada['cliente']['nome'] ?? '', 120),
    'whatsapp' => lmc_texto($entrada['cliente']['whatsapp'] ?? '', 25),
    'email'    => lmc_texto($entrada['cliente']['email'] ?? '', 120),
];
if ($cliente['nome'] === '') {
    lmc_json_resposta(['erro' => 'Precisamos do seu nome.'], 422);
}

/* ---- Monta e guarda ---- */
$pedido = [
    'id'            => lmc_id_pedido(),
    'criadoEm'      => date('c'),
    'status'        => 'aguardando_pagamento',
    'origem'        => lmc_texto($entrada['origem'] ?? 'site', 40),
    'cliente'       => $cliente,
    'itens'         => $itens,
    'subtotal'      => lmc_dinheiro($subtotal),
    'frete'         => lmc_dinheiro((float) $frete['valor']),
    'freteRegiao'   => $frete['regiao'],
    'descontoPix'   => $descontoPix,
    'total'         => $total,
    'pesoGramas'    => $peso,
    'pagamento'     => $formaPagamento,
    'entrega'       => ['tipo' => $tipoEntrega, 'cep' => $cep,
                        'endereco' => lmc_texto($entrada['entrega']['endereco'] ?? '', 300)],
    'temEncomenda'  => $temEncomenda,
    'observacao'    => lmc_texto($entrada['observacao'] ?? '', 500),
    'pagoEm'        => null,
];

if ($formaPagamento === 'pix') {
    $pedido['pixCodigo'] = lmc_pix_codigo($total, $pedido['id'], 'Lucia Maria Croche');
}

try {
    lmc_acrescentar('pedidos.json', $pedido);
} catch (Throwable $erro) {
    lmc_json_resposta(['erro' => 'Não consegui registrar o pedido. Tente pelo WhatsApp.'], 500);
}

/* Guarda o cliente na lista, para o módulo de marketing. */
$clientes = lmc_ler('clientes.json', []);
$chave = $cliente['whatsapp'] !== '' ? preg_replace('/\D/', '', $cliente['whatsapp']) : mb_strtolower($cliente['nome']);
$existente = $clientes[$chave] ?? null;
$clientes[$chave] = [
    'nome'        => $cliente['nome'],
    'whatsapp'    => $cliente['whatsapp'],
    'email'       => $cliente['email'],
    'primeiraEm'  => $existente['primeiraEm'] ?? date('c'),
    'ultimaEm'    => date('c'),
    'pedidos'     => (int) ($existente['pedidos'] ?? 0) + 1,
    'totalGasto'  => lmc_dinheiro((float) ($existente['totalGasto'] ?? 0) + $total),
    'origem'      => $existente['origem'] ?? $pedido['origem'],
    'aceitaAviso' => (bool) ($entrada['aceitaAviso'] ?? false) || (bool) ($existente['aceitaAviso'] ?? false),
];
lmc_gravar('clientes.json', $clientes);

lmc_json_resposta([
    'ok'        => true,
    'pedido'    => $pedido['id'],
    'subtotal'  => $pedido['subtotal'],
    'frete'     => $pedido['frete'],
    'desconto'  => $pedido['descontoPix'],
    'total'     => $pedido['total'],
    'pixCodigo' => $pedido['pixCodigo'] ?? null,
]);
