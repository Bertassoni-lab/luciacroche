<?php
/* Pedidos: acompanhar, confirmar pagamento, marcar envio. */

declare(strict_types=1);

require __DIR__ . '/comum.php';
require __DIR__ . '/../api/estoque.php';
lmc_exige_login();

$recado = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    lmc_confere_token();
    $id = lmc_texto($_POST['pedido'] ?? '', 40);
    $novo = lmc_texto($_POST['status'] ?? '', 40);
    $rastreio = lmc_texto($_POST['rastreio'] ?? '', 60);
    $permitidos = ['aguardando_pagamento', 'pago', 'enviado', 'entregue', 'cancelado'];

    if (in_array($novo, $permitidos, true)) {
        $pedidos = lmc_ler('pedidos.json', []);
        foreach ($pedidos as $i => $p) {
            if (($p['id'] ?? '') !== $id) {
                continue;
            }
            $pedidos[$i]['status'] = $novo;
            if ($novo === 'pago' && empty($pedidos[$i]['pagoEm'])) {
                $pedidos[$i]['pagoEm'] = date('c');
            }
            if ($rastreio !== '') {
                $pedidos[$i]['rastreio'] = $rastreio;
            }
            if ($novo === 'enviado' && empty($pedidos[$i]['enviadoEm'])) {
                $pedidos[$i]['enviadoEm'] = date('c');
            }
            $recado = 'Pedido ' . $id . ' agora está como “' . p_status_nome($novo) . '”.';

            // Peça única sai do estoque quando o pedido é pago, e volta se for cancelado.
            if (in_array($novo, ['pago', 'enviado', 'entregue'], true)) {
                $marcadas = lmc_baixar_estoque_do_pedido($pedidos[$i]);
                if ($marcadas) {
                    $recado .= ' Saíram da loja: ' . implode(', ', $marcadas) . '.';
                }
            }
            if ($novo === 'cancelado') {
                $devolvidas = lmc_devolver_estoque_do_pedido($pedidos[$i]);
                if ($devolvidas) {
                    $recado .= ' Voltaram para a loja: ' . implode(', ', $devolvidas) . '.';
                }
            }
        }
        lmc_gravar('pedidos.json', $pedidos);
    }
}

$filtro = lmc_texto($_GET['filtro'] ?? 'abertos', 20);
$todos = p_pedidos();

$pedidos = match ($filtro) {
    'abertos'  => array_values(array_filter($todos, fn($p) => !in_array($p['status'] ?? '', ['entregue', 'cancelado'], true))),
    'pagar'    => array_values(array_filter($todos, fn($p) => in_array($p['status'] ?? '', ['aguardando_pagamento', 'pagamento_informado', 'em_analise'], true))),
    'enviar'   => array_values(array_filter($todos, fn($p) => ($p['status'] ?? '') === 'pago')),
    default    => $todos,
};

p_cabecalho('Pedidos', 'pedidos.php');
?>

<h1>Pedidos</h1>
<?php if ($recado): ?><p class="recado recado--ok"><?= e($recado) ?></p><?php endif; ?>

<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:22px">
  <?php foreach (['abertos' => 'Em aberto', 'pagar' => 'Esperando pagamento', 'enviar' => 'Para enviar', 'todos' => 'Todos'] as $chave => $rotulo): ?>
    <a class="botao botao--pequeno <?= $chave === $filtro ? 'botao--principal' : 'botao--contorno' ?>"
       href="pedidos.php?filtro=<?= e($chave) ?>"><?= e($rotulo) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$pedidos): ?>
  <div class="recado recado--info">Nenhum pedido nesta lista.</div>
<?php endif; ?>

<?php foreach ($pedidos as $p):
    $status = (string) ($p['status'] ?? '');
    $zap = preg_replace('/\D/', '', (string) ($p['cliente']['whatsapp'] ?? ''));
    if ($zap !== '' && strlen($zap) <= 11) { $zap = '55' . $zap; }
?>
<div class="painel-caixa" id="<?= e($p['id']) ?>">
  <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:baseline;justify-content:space-between">
    <h2 style="margin:0"><?= e($p['id']) ?>
      <span class="selo selo--<?= e(p_status_classe($status)) ?>"><?= e(p_status_nome($status)) ?></span></h2>
    <span style="color:var(--tinta-fraca);font-size:.88rem"><?= e(p_data($p['criadoEm'] ?? null, 'd/m/Y \à\s H:i')) ?></span>
  </div>

  <div class="duas-colunas" style="margin-top:18px">
    <div>
      <h3>Peças</h3>
      <table class="p-tabela">
        <tbody>
        <?php foreach ($p['itens'] ?? [] as $item): ?>
          <tr>
            <td><?= (int) $item['qtd'] ?>× <?= e($item['nome']) ?></td>
            <td class="num"><?= e(lmc_moeda((float) $item['subtotal'])) ?></td>
          </tr>
        <?php endforeach; ?>
          <tr><td>Frete <small style="color:var(--tinta-fraca)"><?= e($p['freteRegiao'] ?? '') ?></small></td>
              <td class="num"><?= e(lmc_moeda((float) ($p['frete'] ?? 0))) ?></td></tr>
        <?php if ((float) ($p['descontoPix'] ?? 0) > 0): ?>
          <tr><td>Desconto do Pix</td><td class="num">− <?= e(lmc_moeda((float) $p['descontoPix'])) ?></td></tr>
        <?php endif; ?>
          <tr><td><strong>Total</strong></td><td class="num"><strong><?= e(lmc_moeda((float) ($p['total'] ?? 0))) ?></strong></td></tr>
        </tbody>
      </table>
      <p class="dica">Estimativa: <?= e(number_format(p_horas_pedido($p), 1, ',', '.')) ?> h de crochê ·
        <?= e(lmc_moeda(p_material_pedido($p))) ?> de barbante ·
        sobra <?= e(lmc_moeda((float) ($p['total'] ?? 0) - (float) ($p['frete'] ?? 0) - p_material_pedido($p))) ?></p>
    </div>

    <div>
      <h3>Cliente e entrega</h3>
      <p style="margin:0 0 4px"><strong><?= e($p['cliente']['nome'] ?? '—') ?></strong></p>
      <p style="margin:0 0 4px">
        <?php if ($zap): ?><a href="https://wa.me/<?= e($zap) ?>" target="_blank" rel="noopener"><?= e($p['cliente']['whatsapp']) ?></a>
        <?php else: ?>sem WhatsApp<?php endif; ?>
        <?php if (!empty($p['cliente']['email'])): ?> · <?= e($p['cliente']['email']) ?><?php endif; ?>
      </p>
      <p style="margin:0 0 4px">
        <?php if (($p['entrega']['tipo'] ?? '') === 'retirada'): ?>
          <strong>Retirada na feira</strong>
        <?php else: ?>
          <?= e($p['entrega']['endereco'] ?? '—') ?><br>CEP <?= e($p['entrega']['cep'] ?? '—') ?>
          · <?= (int) ($p['pesoGramas'] ?? 0) ?> g
        <?php endif; ?>
      </p>
      <p style="margin:6px 0 0">Pagamento: <strong><?= e($p['pagamento'] ?? '—') ?></strong>
        <?php if (!empty($p['gateway'])): ?><small style="color:var(--tinta-fraca)">(<?= e($p['gateway']) ?>)</small><?php endif; ?></p>
      <?php if (!empty($p['observacao'])): ?>
        <p class="recado recado--info" style="margin-top:12px"><strong>Pedido do cliente:</strong> <?= e($p['observacao']) ?></p>
      <?php endif; ?>
      <?php if (!empty($p['temEncomenda'])): ?>
        <p class="dica"><strong>Tem peça sob encomenda</strong> — combine o prazo com o cliente.</p>
      <?php endif; ?>
    </div>
  </div>

  <form method="post" style="margin-top:20px;border-top:1px solid var(--borda);padding-top:18px">
    <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
    <input type="hidden" name="pedido" value="<?= e($p['id']) ?>">
    <div class="linha-campos" style="align-items:end">
      <div>
        <label for="s-<?= e($p['id']) ?>">Mudar situação para</label>
        <select id="s-<?= e($p['id']) ?>" name="status">
          <?php foreach (['aguardando_pagamento', 'pago', 'enviado', 'entregue', 'cancelado'] as $op): ?>
            <option value="<?= e($op) ?>"<?= $op === $status ? ' selected' : '' ?>><?= e(p_status_nome($op)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="r-<?= e($p['id']) ?>">Código de rastreio</label>
        <input id="r-<?= e($p['id']) ?>" name="rastreio" value="<?= e($p['rastreio'] ?? '') ?>" placeholder="AA123456789BR">
      </div>
      <div><button class="botao botao--principal botao--largo" type="submit" style="margin-top:14px">Salvar</button></div>
    </div>
  </form>

  <?php if ($zap): ?>
    <?php
      $msgPago = "Oi, {$p['cliente']['nome']}! Recebemos o pagamento do pedido {$p['id']} 🧶 Já vamos separar sua peça.";
      $msgEnviado = "Oi, {$p['cliente']['nome']}! Seu pedido {$p['id']} saiu para entrega. Código de rastreio: "
                  . ($p['rastreio'] ?? '(coloque aqui)');
    ?>
    <p style="margin:14px 0 0;display:flex;gap:8px;flex-wrap:wrap">
      <a class="botao botao--contorno botao--pequeno" target="_blank" rel="noopener"
         href="https://wa.me/<?= e($zap) ?>?text=<?= e(rawurlencode($msgPago)) ?>">Avisar que recebemos o pagamento</a>
      <a class="botao botao--contorno botao--pequeno" target="_blank" rel="noopener"
         href="https://wa.me/<?= e($zap) ?>?text=<?= e(rawurlencode($msgEnviado)) ?>">Mandar o rastreio</a>
    </p>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php p_rodape();
