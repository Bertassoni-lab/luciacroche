<?php
/* Gestão financeira: o que entrou, o que saiu, o que sobrou e
   quanto isso deu por hora de crochê. */

declare(strict_types=1);

require __DIR__ . '/comum.php';
lmc_exige_login();

$recado = '';

/* ---------------- Lançar ou apagar despesa ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    lmc_confere_token();

    if (($_POST['acao'] ?? '') === 'apagar') {
        $id = lmc_texto($_POST['id'] ?? '', 40);
        $custos = array_values(array_filter(lmc_ler('custos.json', []), fn($c) => ($c['id'] ?? '') !== $id));
        lmc_gravar('custos.json', $custos);
        $recado = 'Despesa apagada.';
    } else {
        $valor = lmc_dinheiro(str_replace(',', '.', (string) ($_POST['valor'] ?? 0)));
        $descricao = lmc_texto($_POST['descricao'] ?? '', 160);
        if ($valor > 0 && $descricao !== '') {
            lmc_acrescentar('custos.json', [
                'id'        => bin2hex(random_bytes(6)),
                'data'      => lmc_texto($_POST['data'] ?? date('Y-m-d'), 10) ?: date('Y-m-d'),
                'categoria' => lmc_texto($_POST['categoria'] ?? 'outro', 30),
                'descricao' => $descricao,
                'valor'     => $valor,
                'lancadoEm' => date('c'),
            ]);
            $recado = 'Despesa de ' . lmc_moeda($valor) . ' lançada.';
        } else {
            $recado = 'Preencha a descrição e um valor maior que zero.';
        }
    }
}

$periodo = lmc_texto($_GET['periodo'] ?? 'mes', 20);
if (!array_key_exists($periodo, p_periodos())) {
    $periodo = 'mes';
}
$r = p_resumo($periodo);
$mei = p_mei();

/* ---------------- Exportar para planilha ---------------- */
if (($_GET['exportar'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="lucia-maria-croche-' . $periodo . '.csv"');
    $saida = fopen('php://output', 'w');
    fwrite($saida, "\xEF\xBB\xBF"); // para o Excel entender os acentos
    fputcsv($saida, ['Tipo', 'Data', 'Referência', 'Descrição', 'Valor'], ';');
    foreach ($r['pagos'] as $p) {
        fputcsv($saida, ['Entrada', p_data($p['criadoEm'] ?? null), $p['id'],
            ($p['cliente']['nome'] ?? '') . ' — ' . count($p['itens'] ?? []) . ' peça(s)',
            number_format((float) $p['total'], 2, ',', '')], ';');
    }
    foreach ($r['custos'] as $c) {
        fputcsv($saida, ['Saída', p_data($c['data'] ?? null), $c['categoria'] ?? '',
            $c['descricao'] ?? '', '-' . number_format((float) $c['valor'], 2, ',', '')], ';');
    }
    fputcsv($saida, ['Saída', '', 'barbante', 'Barbante estimado das peças vendidas',
        '-' . number_format($r['material'], 2, ',', '')], ';');
    fputcsv($saida, ['Resultado', '', '', 'Sobrou no período', number_format($r['lucro'], 2, ',', '')], ';');
    fclose($saida);
    exit;
}

$categorias = [
    'barbante'  => 'Barbante e material',
    'aviamento' => 'Aviamentos (alça, argola, etiqueta)',
    'feira'     => 'Feira (barraca, transporte)',
    'embalagem' => 'Embalagem e envio',
    'imposto'   => 'DAS do MEI e taxas',
    'site'      => 'Domínio e hospedagem',
    'outro'     => 'Outros',
];

/* Onde o dinheiro saiu, por categoria. */
$porCategoria = [];
foreach ($r['custos'] as $c) {
    $cat = $c['categoria'] ?? 'outro';
    $porCategoria[$cat] = ($porCategoria[$cat] ?? 0) + (float) ($c['valor'] ?? 0);
}
if ($r['material'] > 0) {
    $porCategoria['barbante'] = ($porCategoria['barbante'] ?? 0) + $r['material'];
}
arsort($porCategoria);
$maiorCat = max(1.0, ...array_merge([1.0], array_values($porCategoria)));

/* Peças mais vendidas no período. */
$maisVendidas = [];
foreach ($r['pagos'] as $p) {
    foreach ($p['itens'] ?? [] as $item) {
        $chave = $item['nome'] ?? '';
        $maisVendidas[$chave] = ($maisVendidas[$chave] ?? 0) + (int) ($item['qtd'] ?? 1);
    }
}
arsort($maisVendidas);

p_cabecalho('Financeiro', 'financeiro.php');
?>

<h1>Financeiro</h1>
<?php if ($recado): ?><p class="recado recado--ok"><?= e($recado) ?></p><?php endif; ?>
<?php p_seletor_periodo($periodo, 'financeiro.php'); ?>

<div class="p-kpis">
  <div class="kpi">
    <p class="kpi__rotulo">Entrou</p>
    <p class="kpi__valor"><?= e(lmc_moeda($r['receita'])) ?></p>
    <p class="kpi__nota"><?= (int) $r['qtdPagos'] ?> pedido(s) pago(s) de <?= (int) $r['qtdPedidos'] ?></p>
  </div>
  <div class="kpi">
    <p class="kpi__rotulo">Saiu</p>
    <p class="kpi__valor"><?= e(lmc_moeda($r['custoTotal'])) ?></p>
    <p class="kpi__nota">inclui <?= e(lmc_moeda($r['material'])) ?> de barbante estimado</p>
  </div>
  <div class="kpi <?= $r['lucro'] >= 0 ? 'kpi--bom' : 'kpi--alerta' ?>">
    <p class="kpi__rotulo">Sobrou</p>
    <p class="kpi__valor"><?= e(lmc_moeda($r['lucro'])) ?></p>
    <p class="kpi__nota">margem de <?= e(number_format($r['margem'], 1, ',', '.')) ?>%</p>
  </div>
  <div class="kpi <?= $r['porHora'] >= $r['metaHora'] ? 'kpi--bom' : 'kpi--alerta' ?>">
    <p class="kpi__rotulo">Por hora de crochê</p>
    <p class="kpi__valor"><?= e(lmc_moeda($r['porHora'])) ?></p>
    <p class="kpi__nota"><?= e(number_format($r['horas'], 1, ',', '.')) ?> h no período</p>
  </div>
</div>

<?php if ($r['horas'] > 0 && $r['porHora'] < 9): ?>
  <p class="recado recado--erro"><strong>Atenção:</strong> no período, cada hora de crochê rendeu
    <?= e(lmc_moeda($r['porHora'])) ?> — menos que o salário mínimo por hora (cerca de R$ 9).
    Vale rever preço ou mix de peças. A
    <a href="../ferramentas/precificacao.html" target="_blank" rel="noopener">calculadora</a> ajuda.</p>
<?php elseif ($r['horas'] > 0 && $r['porHora'] >= $r['metaHora']): ?>
  <p class="recado recado--ok">A hora de trabalho está em <?= e(lmc_moeda($r['porHora'])) ?>,
    acima da meta de <?= e(lmc_moeda($r['metaHora'])) ?>. É este número que precisa subir ano a ano.</p>
<?php endif; ?>

<div class="tres-colunas">
  <div>
    <div class="painel-caixa">
      <h2>Resultado do período</h2>
      <table class="p-tabela">
        <tbody>
          <tr><td>Vendas</td><td class="num"><?= e(lmc_moeda($r['receita'])) ?></td></tr>
          <tr><td style="padding-left:26px;color:var(--tinta-media)">sendo frete cobrado do cliente</td>
              <td class="num" style="color:var(--tinta-media)"><?= e(lmc_moeda($r['frete'])) ?></td></tr>
          <tr><td>Barbante das peças vendidas (estimado)</td><td class="num">− <?= e(lmc_moeda($r['material'])) ?></td></tr>
          <tr><td>Frete pago aos Correios</td><td class="num">− <?= e(lmc_moeda($r['frete'])) ?></td></tr>
          <tr><td>Despesas lançadas</td><td class="num">− <?= e(lmc_moeda($r['custosLancados'])) ?></td></tr>
          <tr><td><strong>Sobrou</strong></td><td class="num"><strong><?= e(lmc_moeda($r['lucro'])) ?></strong></td></tr>
        </tbody>
      </table>
      <p class="dica">O “sobrou” já é o pagamento da Lúcia mais o lucro do negócio — as horas dela não estão
        lançadas como despesa. Divida pelo número de horas para saber quanto valeu cada hora.</p>
      <p style="margin-top:14px"><a class="botao botao--contorno botao--pequeno"
         href="financeiro.php?periodo=<?= e($periodo) ?>&amp;exportar=csv">Baixar planilha (CSV)</a></p>
    </div>

    <div class="painel-caixa">
      <h2>Para onde o dinheiro foi</h2>
      <?php if (!$porCategoria): ?>
        <p class="dica">Nenhuma despesa no período.</p>
      <?php else: ?>
        <div class="barras">
          <?php foreach ($porCategoria as $cat => $valor): ?>
            <div class="barra">
              <span><?= e($categorias[$cat] ?? $cat) ?></span>
              <span class="barra__trilho"><span class="barra__preenche"
                style="width: <?= e((string) round($valor / $maiorCat * 100)) ?>%"></span></span>
              <span class="num"><?= e(lmc_moeda($valor)) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="painel-caixa">
      <h2>Despesas lançadas</h2>
      <?php if (!$r['custos']): ?>
        <p class="dica">Nada lançado neste período.</p>
      <?php else: ?>
        <div class="rolagem"><table class="p-tabela">
          <thead><tr><th>Data</th><th>Categoria</th><th>Descrição</th><th class="num">Valor</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($r['custos'] as $c): ?>
            <tr>
              <td><?= e(p_data($c['data'] ?? null)) ?></td>
              <td><?= e($categorias[$c['categoria'] ?? 'outro'] ?? $c['categoria']) ?></td>
              <td><?= e($c['descricao'] ?? '') ?></td>
              <td class="num"><?= e(lmc_moeda((float) ($c['valor'] ?? 0))) ?></td>
              <td class="num">
                <form method="post" onsubmit="return confirm('Apagar esta despesa?')">
                  <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
                  <input type="hidden" name="acao" value="apagar">
                  <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
                  <button class="botao botao--contorno botao--pequeno" type="submit">apagar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="painel-caixa">
      <h2>Lançar uma despesa</h2>
      <form method="post">
        <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
        <label for="descricao">O que foi</label>
        <input id="descricao" name="descricao" required placeholder="Ex.: 10 novelos de barbante cru">
        <div class="linha-campos">
          <div>
            <label for="valor">Quanto custou (R$)</label>
            <input id="valor" name="valor" inputmode="decimal" required placeholder="0,00">
          </div>
          <div>
            <label for="data">Quando</label>
            <input id="data" name="data" type="date" value="<?= e(date('Y-m-d')) ?>">
          </div>
        </div>
        <label for="categoria">Categoria</label>
        <select id="categoria" name="categoria">
          <?php foreach ($categorias as $chave => $rotulo): ?>
            <option value="<?= e($chave) ?>"><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="botao botao--principal botao--largo" type="submit" style="margin-top:18px">Lançar</button>
        <p class="dica">Lance tudo: barraca, gasolina, saco kraft, DAS. O que não é lançado vira lucro que não existe.</p>
      </form>
    </div>

    <div class="painel-caixa">
      <h2>Limite do MEI em <?= e($mei['ano']) ?></h2>
      <div class="progresso <?= $mei['percento'] > 80 ? 'alerta' : '' ?>"><span style="width: <?= e((string) $mei['percento']) ?>%"></span></div>
      <p style="margin:0"><?= e(lmc_moeda($mei['receita'])) ?> de <?= e(lmc_moeda($mei['limite'])) ?></p>
      <p class="dica">Cabem mais <?= e(lmc_moeda($mei['restante'])) ?> este ano.</p>
    </div>

    <div class="painel-caixa">
      <h2>Mais vendidas no período</h2>
      <?php if (!$maisVendidas): ?>
        <p class="dica">Nenhuma venda ainda.</p>
      <?php else: ?>
        <ul class="lista-simples">
          <?php foreach (array_slice($maisVendidas, 0, 8, true) as $nome => $qtd): ?>
            <li><span><?= e($nome) ?></span><strong><?= (int) $qtd ?>×</strong></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php p_rodape();
