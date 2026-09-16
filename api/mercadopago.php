<?php
/* Conversa com a API do Mercado Pago. Todo o código fica no servidor:
   o access_token nunca chega ao navegador do cliente. */

declare(strict_types=1);

function lmc_mp_config(): array
{
    return lmc_config()['mercadopago'] ?? ['ativo' => false];
}

function lmc_mp_ativo(): bool
{
    $mp = lmc_mp_config();
    return !empty($mp['ativo']) && !empty($mp['access_token']);
}

/** Chamada HTTP à API do Mercado Pago. Devolve [status, corpo]. */
function lmc_mp_chamar(string $metodo, string $caminho, ?array $corpo = null, array $cabecalhosExtra = []): array
{
    $mp = lmc_mp_config();
    $ch = curl_init('https://api.mercadopago.com' . $caminho);

    $cabecalhos = array_merge([
        'Authorization: Bearer ' . $mp['access_token'],
        'Content-Type: application/json',
    ], $cabecalhosExtra);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => $cabecalhos,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($corpo !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpo, JSON_UNESCAPED_UNICODE));
    }

    $resposta = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($resposta === false) {
        return [0, ['message' => 'Falha de conexão com o Mercado Pago: ' . $erroCurl]];
    }
    $dados = json_decode((string) $resposta, true);
    return [$status, is_array($dados) ? $dados : []];
}

/** Cria uma cobrança Pix com baixa automática. */
function lmc_mp_pix(array $pedido): array
{
    [$status, $resposta] = lmc_mp_chamar('POST', '/v1/payments', [
        'transaction_amount' => (float) $pedido['total'],
        'description'        => 'Pedido ' . $pedido['id'] . ' · Lúcia Maria Crochê',
        'payment_method_id'  => 'pix',
        'external_reference' => $pedido['id'],
        'notification_url'   => rtrim(lmc_config()['site'], '/') . '/api/webhook.php',
        'payer'              => [
            'email'      => $pedido['cliente']['email'] ?: 'sem-email@luciamariacroche.com.br',
            'first_name' => mb_substr($pedido['cliente']['nome'], 0, 40),
        ],
    ], ['X-Idempotency-Key: ' . $pedido['id'] . '-pix']);

    if ($status >= 300) {
        return ['ok' => false, 'erro' => $resposta['message'] ?? 'O Mercado Pago recusou a cobrança.'];
    }

    $dados = $resposta['point_of_interaction']['transaction_data'] ?? [];
    return [
        'ok'         => true,
        'pagamentoId' => $resposta['id'] ?? null,
        'copiaECola' => $dados['qr_code'] ?? null,
        'qrBase64'   => $dados['qr_code_base64'] ?? null,
        'expiraEm'   => $resposta['date_of_expiration'] ?? null,
    ];
}

/** Cobra no cartão a partir do token gerado pelo Brick, dentro do site. */
function lmc_mp_cartao(array $pedido, array $dadosCartao): array
{
    $corpo = [
        'transaction_amount' => (float) $pedido['total'],
        'token'              => $dadosCartao['token'] ?? '',
        'installments'       => (int) ($dadosCartao['installments'] ?? 1),
        'payment_method_id'  => $dadosCartao['payment_method_id'] ?? '',
        'issuer_id'          => $dadosCartao['issuer_id'] ?? null,
        'description'        => 'Pedido ' . $pedido['id'] . ' · Lúcia Maria Crochê',
        'external_reference' => $pedido['id'],
        'notification_url'   => rtrim(lmc_config()['site'], '/') . '/api/webhook.php',
        'payer'              => [
            'email'          => $dadosCartao['payer']['email'] ?? $pedido['cliente']['email'],
            'identification' => $dadosCartao['payer']['identification'] ?? null,
        ],
    ];

    [$status, $resposta] = lmc_mp_chamar('POST', '/v1/payments', $corpo,
        ['X-Idempotency-Key: ' . $pedido['id'] . '-cartao-' . substr(md5((string) ($dadosCartao['token'] ?? '')), 0, 8)]);

    if ($status >= 300) {
        return ['ok' => false, 'erro' => $resposta['message'] ?? 'Pagamento recusado.'];
    }

    return [
        'ok'          => true,
        'pagamentoId' => $resposta['id'] ?? null,
        'situacao'    => $resposta['status'] ?? 'pending',
        'detalhe'     => $resposta['status_detail'] ?? '',
    ];
}

/** Traduz o código de recusa do Mercado Pago para algo que o cliente entenda. */
function lmc_mp_explicar(string $detalhe): string
{
    return [
        'accredited'                   => 'Pagamento aprovado.',
        'pending_contingency'          => 'O banco está processando. Avisamos assim que confirmar.',
        'pending_review_manual'        => 'Pagamento em análise. Avisamos em algumas horas.',
        'cc_rejected_bad_filled_card_number' => 'Confira o número do cartão.',
        'cc_rejected_bad_filled_date'  => 'Confira a data de validade.',
        'cc_rejected_bad_filled_security_code' => 'Confira o código de segurança.',
        'cc_rejected_bad_filled_other' => 'Confira os dados do cartão.',
        'cc_rejected_insufficient_amount' => 'Saldo ou limite insuficiente.',
        'cc_rejected_high_risk'        => 'O banco recusou. Tente outro cartão ou pague no Pix.',
        'cc_rejected_call_for_authorize' => 'Ligue para o seu banco e autorize esta compra.',
        'cc_rejected_card_disabled'    => 'Cartão desativado. Fale com o banco.',
        'cc_rejected_duplicated_payment' => 'Esse pagamento já foi feito.',
    ][$detalhe] ?? 'Não deu certo. Tente outro cartão ou pague no Pix.';
}
