<?php
/* Calcula o frete a partir do CEP. É a mesma tabela usada no
   fechamento do pedido — por isso o cálculo mora só aqui. */

declare(strict_types=1);

require __DIR__ . '/lib.php';
require __DIR__ . '/catalogo.php';

$entrada = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? lmc_corpo_json() : $_GET;

$cep = lmc_texto($entrada['cep'] ?? '', 12);
$peso = max(100, min(30000, (int) ($entrada['peso'] ?? 500)));
$subtotal = max(0.0, (float) ($entrada['subtotal'] ?? 0));

$frete = lmc_frete($cep, $peso, $subtotal);

lmc_json_resposta([
    'ok'     => $frete['valor'] !== null,
    'valor'  => $frete['valor'],
    'regiao' => $frete['regiao'],
    'motivo' => $frete['motivo'],
]);
