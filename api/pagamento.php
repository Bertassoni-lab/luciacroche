<?php
/* Checkout dentro do site: Pix (sempre) e cartão (quando o
   Mercado Pago estiver configurado em api/config.php). */

declare(strict_types=1);

require __DIR__ . '/lib.php';
require __DIR__ . '/mercadopago.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    lmc_json_resposta(['erro' => 'Use POST.'], 405);
}

$entrada = lmc_corpo_json();
$acao = $entrada['acao'] ?? '';
$idPedido = lmc_texto($entrada['pedido'] ?? '', 40);

$pedidos = lmc_ler('pedidos.json', []);
$indice = null;
foreach ($pedidos as $i => $p) {
    if (($p['id'] ?? '') === $idPedido) {
        $indice = $i;
        break;
    }
}
if ($indice === null) {
    lmc_json_resposta(['erro' => 'Pedido não encontrado.'], 404);
}
$pedido = $pedidos[$indice];

switch ($acao) {

    /* ---- Pix ------------------------------------------------ */
    case 'pix':
        if (lmc_mp_ativo()) {
            $r = lmc_mp_pix($pedido);
            if ($r['ok']) {
                $pedidos[$indice]['pagamentoId'] = $r['pagamentoId'];
                $pedidos[$indice]['pixCodigo'] = $r['copiaECola'] ?: $pedido['pixCodigo'] ?? null;
                $pedidos[$indice]['gateway'] = 'mercadopago';
                lmc_gravar('pedidos.json', $pedidos);
                lmc_json_resposta([
                    'ok' => true, 'modo' => 'mercadopago',
                    'copiaECola' => $pedidos[$indice]['pixCodigo'],
                    'qrBase64' => $r['qrBase64'],
                    'baixaAutomatica' => true,
                ]);
            }
            // Se o Mercado Pago falhar, cai no Pix direto em vez de derrubar a venda.
        }

        $codigo = $pedido['pixCodigo'] ?? lmc_pix_codigo((float) $pedido['total'], $pedido['id'], 'Lucia Maria Croche');
        $pedidos[$indice]['pixCodigo'] = $codigo;
        $pedidos[$indice]['gateway'] = 'pix-direto';
        lmc_gravar('pedidos.json', $pedidos);
        lmc_json_resposta([
            'ok' => true, 'modo' => 'pix-direto',
            'copiaECola' => $codigo,
            'favorecido' => lmc_config()['pix']['favorecido'],
            'baixaAutomatica' => false,
        ]);
        // no break

    /* ---- Cartão --------------------------------------------- */
    case 'cartao':
        if (!lmc_mp_ativo()) {
            lmc_json_resposta(['erro' => 'O cartão ainda não está ativo nesta loja. Use o Pix ou fale no WhatsApp.'], 503);
        }
        $r = lmc_mp_cartao($pedido, $entrada['cartao'] ?? []);
        if (!$r['ok']) {
            lmc_json_resposta(['erro' => $r['erro']], 402);
        }

        $aprovado = ($r['situacao'] ?? '') === 'approved';
        $pedidos[$indice]['pagamentoId'] = $r['pagamentoId'];
        $pedidos[$indice]['gateway'] = 'mercadopago';
        $pedidos[$indice]['status'] = $aprovado ? 'pago' : 'em_analise';
        $pedidos[$indice]['pagoEm'] = $aprovado ? date('c') : null;
        lmc_gravar('pedidos.json', $pedidos);

        lmc_json_resposta([
            'ok' => true,
            'aprovado' => $aprovado,
            'situacao' => $r['situacao'],
            'mensagem' => lmc_mp_explicar((string) ($r['detalhe'] ?? '')),
        ]);

    /* ---- Cliente avisa que pagou (Pix direto) ---------------- */
    case 'avisei':
        if (($pedidos[$indice]['status'] ?? '') === 'aguardando_pagamento') {
            $pedidos[$indice]['status'] = 'pagamento_informado';
            $pedidos[$indice]['informadoEm'] = date('c');
            lmc_gravar('pedidos.json', $pedidos);
        }
        lmc_json_resposta(['ok' => true]);

    /* ---- Consulta de situação -------------------------------- */
    case 'status':
        lmc_json_resposta(['ok' => true, 'status' => $pedido['status'] ?? 'aguardando_pagamento']);

    default:
        lmc_json_resposta(['erro' => 'Ação desconhecida.'], 400);
}
