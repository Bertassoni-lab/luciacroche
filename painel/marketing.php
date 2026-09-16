<?php
/* Marketing: lista de clientes, campanhas, cupons, links com
   origem e o QR code da barraca. */

declare(strict_types=1);

require __DIR__ . '/comum.php';
lmc_exige_login();

$recado = '';
$config = lmc_config();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    lmc_confere_token();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'campanha') {
        $nome = lmc_texto($_POST['nome'] ?? '', 80);
        if ($nome !== '') {
            lmc_acrescentar('campanhas.json', [
                'id'       => bin2hex(random_bytes(5)),
                'nome'     => $nome,
                'codigo'   => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', lmc_texto($_POST['codigo'] ?? '', 20)) ?? ''),
                'desconto' => (int) ($_POST['desconto'] ?? 0),
                'inicio'   => lmc_texto($_POST['inicio'] ?? date('Y-m-d'), 10),
                'fim'      => lmc_texto($_POST['fim'] ?? '', 10),
                'canal'    => lmc_texto($_POST['canal'] ?? 'instagram', 30),
                'notas'    => lmc_texto($_POST['notas'] ?? '', 300),
                'ativa'    => true,
                'criadaEm' => date('c'),
            ]);
            $recado = 'Campanha “' . $nome . '” criada.';
        }
    }

    if ($acao === 'encerrar') {
        $id = lmc_texto($_POST['id'] ?? '', 40);
        $campanhas = lmc_ler('campanhas.json', []);
        foreach ($campanhas as $i => $c) {
            if (($c['id'] ?? '') === $id) {
                $campanhas[$i]['ativa'] = false;
            }
        }
        lmc_gravar('campanhas.json', $campanhas);
        $recado = 'Campanha encerrada.';
    }
}

$clientes = p_clientes();
$campanhas = p_campanhas();
$pedidos = p_pedidos();
$pagos = p_pedidos_pagos($pedidos);

/* ---- Números dos clientes ---- */
$recorrentes = array_filter($clientes, fn($c) => (int) ($c['pedidos'] ?? 0) > 1);
$avisaveis = array_filter($clientes, fn($c) => !empty($c['aceitaAviso']) && !empty($c['whatsapp']));
$gastoTotal = array_sum(array_map(fn($c) => (float) ($c['totalGasto'] ?? 0), $clientes));

/* Quem comprou há mais de 90 dias: gente para chamar de volta. */
$sumidos = [];
$limite = (new DateTimeImmutable('today'))->modify('-90 days');
foreach ($clientes as $chave => $c) {
    try {
        if (!empty($c['ultimaEm']) && new DateTimeImmutable($c['ultimaEm']) < $limite) {
            $sumidos[$chave] = $c;
        }
    } catch (Throwable) {
    }
}
uasort($sumidos, fn($a, $b) => (float) ($b['totalGasto'] ?? 0) <=> (float) ($a['totalGasto'] ?? 0));

/* De onde vieram as vendas. */
$porOrigem = [];
foreach ($pagos as $p) {
    $o = $p['origem'] ?? 'site';
    $porOrigem[$o] = ($porOrigem[$o] ?? 0) + (float) ($p['total'] ?? 0);
}
arsort($porOrigem);
$maiorOrigem = max(1.0, ...array_merge([1.0], array_values($porOrigem)));

$site = rtrim((string) $config['site'], '/');

p_cabecalho('Marketing', 'marketing.php');
?>

<h1>Marketing</h1>
<?php if ($recado): ?><p class="recado recado--ok"><?= e($recado) ?></p><?php endif; ?>

<div class="p-kpis">
  <div class="kpi">
    <p class="kpi__rotulo">Clientes na lista</p>
    <p class="kpi__valor"><?= count($clientes) ?></p>
    <p class="kpi__nota"><?= count($avisaveis) ?> aceitam receber aviso</p>
  </div>
  <div class="kpi kpi--bom">
    <p class="kpi__rotulo">Voltaram a comprar</p>
    <p class="kpi__valor"><?= count($recorrentes) ?></p>
    <p class="kpi__nota"><?= count($clientes) > 0 ? round(count($recorrentes) / count($clientes) * 100) : 0 ?>% da lista</p>
  </div>
  <div class="kpi">
    <p class="kpi__rotulo">Gasto médio por cliente</p>
    <p class="kpi__valor"><?= e(lmc_moeda(count($clientes) > 0 ? $gastoTotal / count($clientes) : 0)) ?></p>
    <p class="kpi__nota">somando todas as compras</p>
  </div>
  <div class="kpi <?= count($sumidos) > 0 ? 'kpi--alerta' : '' ?>">
    <p class="kpi__rotulo">Sem comprar há 90 dias</p>
    <p class="kpi__valor"><?= count($sumidos) ?></p>
    <p class="kpi__nota">gente para chamar de volta</p>
  </div>
</div>

<div class="tres-colunas">
  <div>
    <div class="painel-caixa">
      <h2>Chamar de volta quem sumiu</h2>
      <p class="dica" style="margin-top:0">Quem já comprou uma vez é quem tem mais chance de comprar de novo —
        e custa zero falar com essa pessoa. Uma mensagem por vez, escrita como gente, não como loja.</p>
      <?php if (!$sumidos): ?>
        <p class="dica">Ninguém sumido por enquanto.</p>
      <?php else: ?>
        <div class="rolagem"><table class="p-tabela">
          <thead><tr><th>Cliente</th><th>Última compra</th><th class="num">Já gastou</th><th></th></tr></thead>
          <tbody>
          <?php foreach (array_slice($sumidos, 0, 12, true) as $c):
              $zap = preg_replace('/\D/', '', (string) ($c['whatsapp'] ?? ''));
              if ($zap !== '' && strlen($zap) <= 11) { $zap = '55' . $zap; }
              $primeiroNome = explode(' ', trim((string) ($c['nome'] ?? '')))[0];
              $msg = "Oi, {$primeiroNome}! Aqui é do ateliê da Lúcia 🧶 Saíram peças novas esta semana. "
                   . "Se quiser dar uma olhada: {$site}";
          ?>
            <tr>
              <td><?= e($c['nome'] ?? '') ?></td>
              <td><?= e(p_data($c['ultimaEm'] ?? null)) ?></td>
              <td class="num"><?= e(lmc_moeda((float) ($c['totalGasto'] ?? 0))) ?></td>
              <td class="num">
                <?php if ($zap): ?>
                  <a class="botao botao--contorno botao--pequeno" target="_blank" rel="noopener"
                     href="https://wa.me/<?= e($zap) ?>?text=<?= e(rawurlencode($msg)) ?>">chamar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>

    <div class="painel-caixa">
      <h2>Todos os clientes</h2>
      <?php if (!$clientes): ?>
        <p class="dica">A lista se preenche sozinha conforme os pedidos entram pelo site.
          Na feira, use o caderno: nome, WhatsApp e “posso te avisar quando tiver peça nova?”.</p>
      <?php else: ?>
        <div class="rolagem"><table class="p-tabela">
          <thead><tr><th>Nome</th><th>WhatsApp</th><th class="num">Pedidos</th><th class="num">Total</th><th>Aviso</th></tr></thead>
          <tbody>
          <?php foreach ($clientes as $c): ?>
            <tr>
              <td><?= e($c['nome'] ?? '') ?></td>
              <td><?= e($c['whatsapp'] ?? '—') ?></td>
              <td class="num"><?= (int) ($c['pedidos'] ?? 0) ?></td>
              <td class="num"><?= e(lmc_moeda((float) ($c['totalGasto'] ?? 0))) ?></td>
              <td><?= !empty($c['aceitaAviso']) ? 'sim' : 'não' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>

    <div class="painel-caixa">
      <h2>De onde vieram as vendas</h2>
      <?php if (!$porOrigem): ?>
        <p class="dica">Ainda não dá para dizer. Use os links com origem (ao lado) para descobrir.</p>
      <?php else: ?>
        <div class="barras">
          <?php foreach ($porOrigem as $origem => $valor): ?>
            <div class="barra">
              <span><?= e($origem) ?></span>
              <span class="barra__trilho"><span class="barra__preenche barra__preenche--verde"
                style="width: <?= e((string) round($valor / $maiorOrigem * 100)) ?>%"></span></span>
              <span class="num"><?= e(lmc_moeda($valor)) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="painel-caixa">
      <h2>Campanhas</h2>
      <?php if (!$campanhas): ?>
        <p class="dica">Nenhuma campanha ainda. Sugestões que funcionam para praia:
          <strong>encomendas de Natal em outubro</strong>, <strong>volta da temporada em dezembro</strong> e
          <strong>Dia das Mães em abril</strong>.</p>
      <?php else: ?>
        <div class="rolagem"><table class="p-tabela">
          <thead><tr><th>Campanha</th><th>Cupom</th><th>Quando</th><th>Canal</th><th></th></tr></thead>
          <tbody>
          <?php foreach (array_reverse($campanhas) as $c): ?>
            <tr>
              <td><?= e($c['nome'] ?? '') ?>
                <?php if (empty($c['ativa'])): ?><span class="selo selo--entregue">encerrada</span><?php endif; ?>
                <?php if (!empty($c['notas'])): ?><br><small style="color:var(--tinta-fraca)"><?= e($c['notas']) ?></small><?php endif; ?>
              </td>
              <td><?= $c['codigo'] ? '<strong>' . e($c['codigo']) . '</strong> · ' . (int) $c['desconto'] . '%' : '—' ?></td>
              <td><?= e(p_data($c['inicio'] ?? null)) ?><?= !empty($c['fim']) ? ' a ' . e(p_data($c['fim'])) : '' ?></td>
              <td><?= e($c['canal'] ?? '') ?></td>
              <td class="num">
                <?php if (!empty($c['ativa'])): ?>
                  <form method="post">
                    <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
                    <input type="hidden" name="acao" value="encerrar">
                    <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
                    <button class="botao botao--contorno botao--pequeno" type="submit">encerrar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
      <p class="dica"><strong>Importante:</strong> o cupom é combinado no WhatsApp, na hora de fechar.
        O site não desconta sozinho — assim ninguém usa cupom vencido sem vocês saberem.</p>
    </div>
  </div>

  <div>
    <div class="painel-caixa">
      <h2>QR code da barraca</h2>
      <p class="dica" style="margin-top:0">Imprima grande, plastifique e deixe na frente da barraca com a frase
        <em>“Não levou hoje? Compre pelo site.”</em></p>
      <div id="qr-barraca" style="background:#fff;padding:12px;border-radius:10px;max-width:250px;margin:14px 0"></div>
      <p class="dica" id="qr-endereco"></p>
      <button class="botao botao--contorno botao--pequeno" type="button" onclick="baixarQR()">Baixar imagem</button>
    </div>

    <div class="painel-caixa">
      <h2>Criar campanha</h2>
      <form method="post">
        <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
        <input type="hidden" name="acao" value="campanha">
        <label for="nome">Nome</label>
        <input id="nome" name="nome" required placeholder="Ex.: Encomendas de Natal">
        <div class="linha-campos">
          <div><label for="codigo">Cupom (opcional)</label><input id="codigo" name="codigo" placeholder="NATAL10"></div>
          <div><label for="desconto">Desconto (%)</label><input id="desconto" name="desconto" type="number" min="0" max="50" value="0"></div>
        </div>
        <div class="linha-campos">
          <div><label for="inicio">Começa</label><input id="inicio" name="inicio" type="date" value="<?= e(date('Y-m-d')) ?>"></div>
          <div><label for="fim">Termina</label><input id="fim" name="fim" type="date"></div>
        </div>
        <label for="canal">Onde vai rodar</label>
        <select id="canal" name="canal">
          <option value="instagram">Instagram</option>
          <option value="whatsapp">WhatsApp (lista de clientes)</option>
          <option value="feira">Feira</option>
          <option value="site">Site</option>
        </select>
        <label for="notas">Observações</label>
        <textarea id="notas" name="notas" style="min-height:70px" placeholder="O que vai ser postado, para quem, com qual oferta"></textarea>
        <button class="botao botao--principal botao--largo" type="submit" style="margin-top:16px">Criar</button>
      </form>
    </div>

    <div class="painel-caixa">
      <h2>Link com origem</h2>
      <p class="dica" style="margin-top:0">Cada canal com um link diferente. Assim o painel mostra de onde
        veio cada venda, em vez de vocês adivinharem.</p>
      <label for="origem">Onde você vai colar o link</label>
      <select id="origem" onchange="montarLink()">
        <option value="instagram-bio">Instagram — link da bio</option>
        <option value="instagram-story">Instagram — story</option>
        <option value="whatsapp">WhatsApp</option>
        <option value="feira-qr">Feira — QR da barraca</option>
        <option value="cartao">Cartão dentro do pacote</option>
      </select>
      <label for="link-pronto">Link pronto</label>
      <textarea id="link-pronto" readonly style="min-height:70px" onclick="this.select()"></textarea>
    </div>

    <div class="painel-caixa">
      <h2>O que postar esta semana</h2>
      <ul class="lista-simples">
        <li><span><strong>Segunda</strong> — vídeo curto da agulha trabalhando</span></li>
        <li><span><strong>Quarta</strong> — peça pronta, foto no lençol branco</span></li>
        <li><span><strong>Sexta</strong> — a mão da Aurora escolhendo as cores</span></li>
        <li><span><strong>Domingo</strong> — bastidor da feira</span></li>
      </ul>
      <p class="dica">Crochê em movimento é o que mais prende gente. Filme trinta segundos da agulha,
        sem edição nenhuma, e poste.</p>
    </div>
  </div>
</div>

<script src="../assets/js/vendor/qrcode.js"></script>
<script>
  var SITE = <?= json_encode($site, JSON_UNESCAPED_SLASHES) ?>;

  function montarLink() {
    var origem = document.getElementById('origem').value;
    document.getElementById('link-pronto').value = SITE + '/?utm_source=' + origem;
  }

  function desenharQR() {
    var endereco = SITE + '/?utm_source=feira-qr';
    var qr = qrcode(0, 'M');
    qr.addData(endereco, 'Byte');
    qr.make();
    var caixa = document.getElementById('qr-barraca');
    caixa.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 3, scalable: true });
    var svg = caixa.querySelector('svg');
    svg.style.width = '100%';
    svg.style.height = 'auto';
    document.getElementById('qr-endereco').textContent = endereco;
  }

  function baixarQR() {
    var svg = document.querySelector('#qr-barraca svg');
    if (!svg) return;
    var texto = new XMLSerializer().serializeToString(svg);
    var url = URL.createObjectURL(new Blob([texto], { type: 'image/svg+xml' }));
    var a = document.createElement('a');
    a.href = url;
    a.download = 'qr-barraca-lucia-maria-croche.svg';
    a.click();
    URL.revokeObjectURL(url);
  }

  desenharQR();
  montarLink();
</script>

<?php p_rodape();
