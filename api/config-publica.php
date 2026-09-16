<?php
/* O que o navegador pode saber sobre a configuração do servidor.
   Só entra aqui o que é público: nunca o access_token. */

declare(strict_types=1);

require __DIR__ . '/lib.php';
require __DIR__ . '/mercadopago.php';

$mp = lmc_mp_config();

lmc_json_resposta([
    'cartaoAtivo'    => lmc_mp_ativo(),
    'mpPublicKey'    => lmc_mp_ativo() ? ($mp['public_key'] ?? '') : '',
    'pixFavorecido'  => lmc_config()['pix']['favorecido'],
    'servidorOnline' => true,
]);
