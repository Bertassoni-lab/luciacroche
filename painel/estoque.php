<?php
/* Estoque: quais peças ainda estão à venda e quais já saíram. */

declare(strict_types=1);

require __DIR__ . '/comum.php';
require __DIR__ . '/../api/estoque.php';
lmc_exige_login();

$recado = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    lmc_confere_token();
    $id = lmc_texto($_POST['id'] ?? '', 80);
    $vender = ($_POST['acao'] ?? '') === 'vendida';
    $produto = lmc_produto($id);

    if ($produto !== null) {
        lmc_marcar_vendida($id, $vender, 'painel');
        $recado = $vender
            ? '“' . $produto['nome'] . '” saiu da loja. Quem abrir o site não vê mais essa peça.'
            : '“' . $produto['nome'] . '” voltou para a loja.';
    }
}

$estoque = lmc_estoque();
$produtos = lmc_catalogo()['produtos'] ?? [];

$aVenda = array_values(array_filter($produtos, fn($p) => !isset($estoque[$p['id']])));
$vendidas = array_values(array_filter($produtos, fn($p) => isset($estoque[$p['id']])));
$unicas = array_values(array_filter($aVenda, fn($p) => ($p['disponibilidade'] ?? '') === 'pronta-entrega'));
$encomenda = array_values(array_filter($aVenda, fn($p) => ($p['disponibilidade'] ?? '') === 'sob-encomenda'));

$arquivoPublicado = is_file(__DIR__ . '/../dados/estoque.js');

p_cabecalho('Estoque', 'estoque.php');
?>

<h1>Estoque</h1>
<?php if ($recado): ?><p class="recado recado--ok"><?= e($recado) ?></p><?php endif; ?>

<p class="dica" style="margin-bottom:22px">
  Quase tudo aqui é peça única. Quando uma sai — vendida no site, na feira ou por encomenda —
  marque como vendida e ela some da loja na hora. Assim ninguém compra a mesma bolsa duas vezes.
  <strong>Pedido marcado como pago já dá baixa sozinho.</strong>
</p>

<?php if (!$arquivoPublicado): ?>
  <p class="recado recado--erro">Não achei o arquivo <code>dados/estoque.js</code>.
    Marque e desmarque qualquer peça para ele ser criado.</p>
<?php endif; ?>

<div class="p-kpis">
  <div class="kpi kpi--bom">
    <p class="kpi__rotulo">Prontas para vender</p>
    <p class="kpi__valor"><?= count($unicas) ?></p>
    <p class="kpi__nota">peças únicas no site agora</p>
  </div>
  <div class="kpi">
    <p class="kpi__rotulo">Sob encomenda</p>
    <p class="kpi__valor"><?= count($encomenda) ?></p>
    <p class="kpi__nota">não acabam: a Lúcia faz na hora</p>
  </div>
  <div class="kpi">
    <p class="kpi__rotulo">Já vendidas</p>
    <p class="kpi__valor"><?= count($vendidas) ?></p>
    <p class="kpi__nota">fora da loja</p>
  </div>
  <div class="kpi <?= count($unicas) <= 2 ? 'kpi--alerta' : '' ?>">
    <p class="kpi__rotulo">Valor parado na prateleira</p>
    <p class="kpi__valor"><?= e(lmc_moeda(array_sum(array_map(fn($p) => (float) $p['preco'], $unicas)))) ?></p>
    <p class="kpi__nota"><?= count($unicas) <= 2 ? 'a loja está ficando vazia' : 'somando as peças prontas' ?></p>
  </div>
</div>

<div class="painel-caixa">
  <h2>À venda agora</h2>
  <?php if (!$aVenda): ?>
    <p class="dica">Nenhuma peça à venda. Cadastre peças novas em
      <code>dados/produtos.js</code> ou devolva alguma da lista de vendidas.</p>
  <?php else: ?>
    <div class="rolagem"><table class="p-tabela">
      <thead><tr><th>Peça</th><th>Tipo</th><th class="num">Preço</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($aVenda as $p): $unica = ($p['disponibilidade'] ?? '') === 'pronta-entrega'; ?>
        <tr>
          <td>
            <a href="../produto.html?id=<?= e(rawurlencode($p['id'])) ?>" target="_blank" rel="noopener"><?= e($p['nome']) ?></a>
            <?php if (($p['colecao'] ?? '') === 'Aurora'): ?><br><small style="color:var(--verde)">Coleção Aurora</small><?php endif; ?>
          </td>
          <td><?= $unica
              ? '<span class="selo selo--pago">peça única</span>'
              : '<span class="selo selo--entregue">sob encomenda</span>' ?></td>
          <td class="num"><?= e(lmc_moeda((float) $p['preco'])) ?></td>
          <td class="num">
            <?php if ($unica): ?>
              <form method="post">
                <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
                <input type="hidden" name="acao" value="vendida">
                <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                <button class="botao botao--contorno botao--pequeno" type="submit">marcar vendida</button>
              </form>
            <?php else: ?>
              <small style="color:var(--tinta-fraca)">não acaba</small>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php if ($vendidas): ?>
<div class="painel-caixa">
  <h2>Já vendidas</h2>
  <p class="dica" style="margin-top:0">Se a Lúcia fizer outra igual, devolva para a loja.</p>
  <div class="rolagem"><table class="p-tabela">
    <thead><tr><th>Peça</th><th>Quando saiu</th><th>Pedido</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($vendidas as $p): $info = $estoque[$p['id']]; ?>
      <tr>
        <td><?= e($p['nome']) ?></td>
        <td><?= e(p_data($info['vendidaEm'] ?? null)) ?></td>
        <td><?= ($info['pedido'] ?? '') === 'painel'
            ? '<small style="color:var(--tinta-fraca)">marcada à mão</small>'
            : '<a href="pedidos.php?filtro=todos#' . e($info['pedido']) . '">' . e($info['pedido']) . '</a>' ?></td>
        <td class="num">
          <form method="post">
            <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
            <input type="hidden" name="acao" value="devolver">
            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <button class="botao botao--contorno botao--pequeno" type="submit">voltar para a loja</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<div class="painel-caixa">
  <h2>Como isto funciona</h2>
  <ul class="lista-simples">
    <li><span>Pedido marcado como <strong>pago</strong> no painel</span><span>dá baixa sozinho</span></li>
    <li><span>Pedido <strong>cancelado</strong></span><span>devolve a peça para a loja</span></li>
    <li><span>Peça vendida na <strong>feira</strong></span><span>marque aqui à mão</span></li>
    <li><span>Peça <strong>sob encomenda</strong></span><span>nunca acaba</span></li>
  </ul>
  <p class="dica">O site também confere no servidor: se alguém tentar comprar uma peça
    que acabou de sair, o pedido é recusado com um recado explicando.</p>
</div>

<?php p_rodape();
