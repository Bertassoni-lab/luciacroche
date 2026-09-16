<?php
/* Recebe o aviso do Mercado Pago quando um pagamento muda de situação.
   Confere a assinatura antes de acreditar em qualquer coisa. */

declare(strict_types=1);

require __DIR__ . '/lib.php';
require __DIR__ . '/mercadopago.php';

$mp = lmc_mp_config();
$corpo = lmc_corpo_json();
$idPagamento = (string) ($corpo['data']['id'] ?? $_GET['data_id'] ?? '');

if ($idPagamento === '') {
    http_response_code(400);
    exit('sem id');
}

/* Confere a assinatura x-signature, se o segredo estiver configurado. */
if (!empty($mp['webhook_chave'])) {
    $assinatura = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $requestId  = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    $ts = null;
    $v1 = null;
    foreach (explode(',', $assinatura) as $parte) {
        $par = explode('=', trim($parte), 2);
        if (count($par) === 2) {
            if ($par[0] === 'ts') { $ts = $par[1]; }
            if ($par[0] === 'v1') { $v1 = $par[1]; }
        }
    }
    $modelo = "id:{$idPagamento};request-id:{$requestId};ts:{$ts};";
    $esperado = hash_hmac('sha256', $modelo, (string) $mp['webhook_chave']);
    if (!is_string($v1) || !hash_equals($esperado, $v1)) {
        http_response_code(401);
        exit('assinatura inválida');
    }
}

/* Vai buscar a verdade na API, em vez de confiar no que veio no aviso. */
[$status, $pagamento] = lmc_mp_chamar('GET', '/v1/payments/' . rawurlencode($idPagamento));
if ($status >= 300) {
    http_response_code(202);
    exit('nao consegui consultar');
}

$referencia = (string) ($pagamento['external_reference'] ?? '');
$situacao = (string) ($pagamento['status'] ?? '');

$pedidos = lmc_ler('pedidos.json', []);
$mudou = false;

foreach ($pedidos as $i => $p) {
    if (($p['id'] ?? '') !== $referencia) {
        continue;
    }
    $novo = match ($situacao) {
        'approved'   => 'pago',
        'in_process' => 'em_analise',
        'rejected'   => 'recusado',
        'cancelled'  => 'cancelado',
        'refunded'   => 'estornado',
        default      => $p['status'] ?? 'aguardando_pagamento',
    };
    if ($novo !== ($p['status'] ?? '')) {
        $pedidos[$i]['status'] = $novo;
        $pedidos[$i]['pagamentoId'] = $idPagamento;
        if ($novo === 'pago') {
            $pedidos[$i]['pagoEm'] = date('c');
        }
        $mudou = true;
    }
}

if ($mudou) {
    lmc_gravar('pedidos.json', $pedidos);
}

http_response_code(200);
echo 'ok';
