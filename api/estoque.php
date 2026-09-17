<?php
/* Controle de peça única.
   Quase tudo aqui é peça única: quando uma sai, ninguém mais pode
   comprar aquela. Este arquivo guarda quais já foram vendidas e
   regrava dados/estoque.js, que é o que a loja lê no navegador. */

declare(strict_types=1);

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/catalogo.php';

/** @return array<string, array{vendidaEm:string, pedido:string}> */
function lmc_estoque(): array
{
    $dados = lmc_ler('estoque.json', []);
    return is_array($dados) ? $dados : [];
}

function lmc_esta_vendida(string $id): bool
{
    return isset(lmc_estoque()[$id]);
}

/** Só peça de pronta-entrega acaba; sob encomenda a Lúcia faz outra. */
function lmc_e_peca_unica(?array $produto): bool
{
    return $produto !== null && ($produto['disponibilidade'] ?? '') === 'pronta-entrega';
}

/**
 * Marca uma peça como vendida (ou volta a colocá-la à venda).
 * Devolve true quando alguma coisa mudou.
 */
function lmc_marcar_vendida(string $id, bool $vendida, string $pedido = ''): bool
{
    $estoque = lmc_estoque();
    $antes = $estoque;

    if ($vendida) {
        if (!isset($estoque[$id])) {
            $estoque[$id] = ['vendidaEm' => date('c'), 'pedido' => $pedido];
        }
    } else {
        unset($estoque[$id]);
    }

    if ($estoque === $antes) {
        return false;
    }
    lmc_gravar('estoque.json', $estoque);
    lmc_publicar_estoque($estoque);
    return true;
}

/** Marca de uma vez as peças únicas de um pedido. Devolve os nomes marcados. */
function lmc_baixar_estoque_do_pedido(array $pedido): array
{
    $marcadas = [];
    foreach ($pedido['itens'] ?? [] as $item) {
        $id = (string) ($item['id'] ?? '');
        $produto = lmc_produto($id);
        if (lmc_e_peca_unica($produto) && !lmc_esta_vendida($id)) {
            lmc_marcar_vendida($id, true, (string) ($pedido['id'] ?? ''));
            $marcadas[] = $produto['nome'];
        }
    }
    return $marcadas;
}

/** Devolve ao estoque as peças que aquele pedido tinha consumido. */
function lmc_devolver_estoque_do_pedido(array $pedido): array
{
    $devolvidas = [];
    $estoque = lmc_estoque();
    foreach ($pedido['itens'] ?? [] as $item) {
        $id = (string) ($item['id'] ?? '');
        if (isset($estoque[$id]) && ($estoque[$id]['pedido'] ?? '') === ($pedido['id'] ?? '')) {
            lmc_marcar_vendida($id, false);
            $produto = lmc_produto($id);
            $devolvidas[] = $produto['nome'] ?? $id;
        }
    }
    return $devolvidas;
}

/** Regrava dados/estoque.js, que a loja lê para esconder o que já saiu. */
function lmc_publicar_estoque(?array $estoque = null): bool
{
    $estoque ??= lmc_estoque();
    $ids = array_keys($estoque);
    sort($ids);

    $json = json_encode(
        ['atualizadoEm' => date('c'), 'vendidas' => $ids],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $cabecalho = "/* ============================================================\n"
        . "   PEÇAS JÁ VENDIDAS\n"
        . "   ------------------------------------------------------------\n"
        . "   Gerado pelo painel. Cada peça aqui some da loja, porque é\n"
        . "   única e já foi embora com alguém. Não edite à mão.\n"
        . "   ============================================================ */\n\n"
        . "window.ESTOQUE = ";

    return file_put_contents(__DIR__ . '/../dados/estoque.js', $cabecalho . $json . ";\n", LOCK_EX) !== false;
}
