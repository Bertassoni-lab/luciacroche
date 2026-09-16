<?php
/* ============================================================
   CONFIGURAÇÃO DO SERVIDOR — Lúcia Maria Crochê
   ------------------------------------------------------------
   Este arquivo guarda senha e chaves. NUNCA publique o conteúdo
   dele em lugar nenhum, e não o mande por WhatsApp.
   ============================================================ */

return [

    /* ---- Painel -------------------------------------------- */
    // Senha de acesso ao painel, guardada como hash.
    // Para trocar: abra painel/senha.php no navegador, digite a nova
    // senha, copie o resultado e cole aqui dentro das aspas.
    // A senha inicial é: croche2026  (TROQUE na primeira semana)
    'painel_senha_hash' => '$2y$12$GwLNzcccV06FSfe/JP1As.ppYnM6CwaQi1JlmJzDesZvyGQWAMdka',

    /* ---- Pix ----------------------------------------------- */
    'pix' => [
        'chave'      => '+5512996148324',
        'favorecido' => 'DRISANA HOLLAND',
        'cidade'     => 'SAO SEBASTIAO',
    ],

    /* ---- Mercado Pago (opcional) ---------------------------
       Enquanto estiver vazio, o checkout funciona com Pix direto
       e com combinação por WhatsApp — sem intermediário e sem taxa.
       Preenchendo, o cartão passa a ser aceito dentro do site e o
       Pix passa a ter baixa automática.

       Pegue as chaves em: mercadopago.com.br → Seu negócio →
       Configurações → Gerenciar credenciais → Credenciais de produção.
       -------------------------------------------------------- */
    'mercadopago' => [
        'ativo'         => false,
        'public_key'    => '',   // começa com APP_USR- (pode aparecer no site)
        'access_token'  => '',   // começa com APP_USR- (NUNCA no site, só aqui)
        'webhook_chave' => '',   // segredo do webhook, gerado no painel do Mercado Pago
    ],

    /* ---- Negócio ------------------------------------------- */
    'site'            => 'https://luciamariacroche.com.br',
    'whatsapp'        => '5512996148324',
    'limite_mei_ano'  => 81000,
    'salario_minimo'  => 1621,
    'valor_hora_meta' => 15,

    /* ---- Onde os dados ficam guardados --------------------- */
    'dados' => __DIR__ . '/../dados-privados',
];
