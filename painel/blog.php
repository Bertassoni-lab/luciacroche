<?php
/* Diário do ateliê: escrever, editar e publicar textos.
   Ao salvar, o arquivo público dados/blog.js é gerado de novo. */

declare(strict_types=1);

require __DIR__ . '/comum.php';
lmc_exige_login();

$recado = '';
$erro = '';

/* ---------------- Carrega os textos ----------------
   A primeira vez traz os textos que já estão no site público. */
function b_carregar(): array
{
    $posts = lmc_ler('posts.json', []);
    if ($posts) {
        return $posts;
    }
    $arquivo = __DIR__ . '/../dados/blog.js';
    $bruto = is_file($arquivo) ? (string) file_get_contents($arquivo) : '';
    $inicio = strpos($bruto, '{');
    $fim = strrpos($bruto, '}');
    if ($inicio === false || $fim === false) {
        return [];
    }
    $dados = json_decode(substr($bruto, $inicio, $fim - $inicio + 1), true);
    return $dados['posts'] ?? [];
}

function b_apelido(string $texto): string
{
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $t = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $t) ?? '');
    return trim($t, '-') ?: 'texto-' . date('ymd-His');
}

/** Regrava dados/blog.js com o que está publicado. */
function b_publicar(array $posts): bool
{
    $publicados = array_values(array_filter($posts, fn($p) => !empty($p['publicado'])));
    usort($publicados, fn($a, $b) => strcmp((string) ($b['data'] ?? ''), (string) ($a['data'] ?? '')));

    $json = json_encode(
        ['atualizadoEm' => date('Y-m-d'), 'posts' => $publicados],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $cabecalho = "/* ============================================================\n"
        . "   DIÁRIO DO ATELIÊ — textos publicados\n"
        . "   ------------------------------------------------------------\n"
        . "   Gerado pelo painel (painel/blog.php). Não edite à mão:\n"
        . "   o painel regrava este arquivo a cada publicação.\n"
        . "   ============================================================ */\n\n"
        . "window.BLOG = ";

    $destino = __DIR__ . '/../dados/blog.js';
    return file_put_contents($destino, $cabecalho . $json . ";\n", LOCK_EX) !== false;
}

$posts = b_carregar();

/* ---------------- Salvar / apagar ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    lmc_confere_token();
    $acao = $_POST['acao'] ?? 'salvar';

    if ($acao === 'apagar') {
        $slug = lmc_texto($_POST['slug'] ?? '', 120);
        $posts = array_values(array_filter($posts, fn($p) => ($p['slug'] ?? '') !== $slug));
        lmc_gravar('posts.json', $posts);
        b_publicar($posts);
        $recado = 'Texto apagado.';
    } else {
        $titulo = lmc_texto($_POST['titulo'] ?? '', 160);
        $corpo = lmc_texto($_POST['corpo'] ?? '', 30000);

        if ($titulo === '' || $corpo === '') {
            $erro = 'Escreva pelo menos um título e um texto.';
        } else {
            $slugAntigo = lmc_texto($_POST['slug_antigo'] ?? '', 120);
            $slug = lmc_texto($_POST['slug'] ?? '', 120);
            $slug = $slug !== '' ? b_apelido($slug) : b_apelido($titulo);

            $novo = [
                'slug'       => $slug,
                'titulo'     => $titulo,
                'data'       => lmc_texto($_POST['data'] ?? date('Y-m-d'), 10) ?: date('Y-m-d'),
                'autor'      => lmc_texto($_POST['autor'] ?? 'Lúcia', 60),
                'tag'        => lmc_texto($_POST['tag'] ?? '', 40),
                'resumo'     => lmc_texto($_POST['resumo'] ?? '', 300),
                'imagem'     => lmc_texto($_POST['imagem'] ?? '', 200),
                'imagemAlt'  => lmc_texto($_POST['imagemAlt'] ?? '', 200),
                'publicado'  => !empty($_POST['publicado']),
                'corpo'      => $corpo,
            ];

            $achou = false;
            foreach ($posts as $i => $p) {
                if (($p['slug'] ?? '') === ($slugAntigo ?: $slug)) {
                    $posts[$i] = $novo;
                    $achou = true;
                }
            }
            if (!$achou) {
                $posts[] = $novo;
            }

            lmc_gravar('posts.json', $posts);
            if (b_publicar($posts)) {
                $recado = $novo['publicado']
                    ? 'Texto publicado. Já está no ar no diário do site.'
                    : 'Rascunho salvo. Ele só vai para o site quando você marcar “publicado”.';
            } else {
                $erro = 'Salvei o texto, mas não consegui escrever em dados/blog.js. '
                      . 'Confira as permissões da pasta dados no servidor (precisa ser 755 e o arquivo 644).';
            }
        }
    }
    $posts = b_carregar();
}

/* ---------------- Qual texto está sendo editado ---------------- */
$editandoSlug = lmc_texto($_GET['editar'] ?? '', 120);
$atual = null;
foreach ($posts as $p) {
    if (($p['slug'] ?? '') === $editandoSlug) {
        $atual = $p;
    }
}
$novoTexto = $atual === null;

usort($posts, fn($a, $b) => strcmp((string) ($b['data'] ?? ''), (string) ($a['data'] ?? '')));

p_cabecalho('Diário', 'blog.php');
?>

<h1>Diário do ateliê</h1>
<?php if ($recado): ?><p class="recado recado--ok"><?= e($recado) ?></p><?php endif; ?>
<?php if ($erro): ?><p class="recado recado--erro"><?= e($erro) ?></p><?php endif; ?>

<div class="tres-colunas">
  <div>
    <div class="painel-caixa">
      <h2><?= $novoTexto ? 'Escrever um texto novo' : 'Editando: ' . e($atual['titulo']) ?></h2>
      <form method="post">
        <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="slug_antigo" value="<?= e($atual['slug'] ?? '') ?>">

        <label for="titulo">Título</label>
        <input id="titulo" name="titulo" required value="<?= e($atual['titulo'] ?? '') ?>"
               placeholder="Ex.: Por que uma bolsa leva vinte horas">

        <label for="resumo">Chamada (aparece na lista do diário)</label>
        <input id="resumo" name="resumo" value="<?= e($atual['resumo'] ?? '') ?>"
               placeholder="Uma frase que faça a pessoa querer ler">

        <label for="corpo">Texto</label>
        <textarea id="corpo" name="corpo" class="grande" required><?= e($atual['corpo'] ?? '') ?></textarea>
        <p class="dica">
          Linha começando com <code>## </code> vira subtítulo · <code>- </code> vira item de lista ·
          <code>&gt; </code> vira destaque · <code>**palavra**</code> fica em negrito.
          Deixe uma linha em branco entre os parágrafos.
        </p>

        <div class="linha-campos">
          <div>
            <label for="data">Data</label>
            <input id="data" name="data" type="date" value="<?= e($atual['data'] ?? date('Y-m-d')) ?>">
          </div>
          <div>
            <label for="autor">Quem escreveu</label>
            <input id="autor" name="autor" value="<?= e($atual['autor'] ?? 'Lúcia') ?>">
          </div>
          <div>
            <label for="tag">Assunto</label>
            <input id="tag" name="tag" value="<?= e($atual['tag'] ?? '') ?>" placeholder="Bastidores, Cuidados, Ateliê" list="assuntos">
            <datalist id="assuntos">
              <option value="Bastidores"><option value="Cuidados"><option value="Ateliê">
              <option value="Feira"><option value="Peças novas">
            </datalist>
          </div>
        </div>

        <label for="imagem">Foto de capa (caminho do arquivo)</label>
        <input id="imagem" name="imagem" value="<?= e($atual['imagem'] ?? '') ?>"
               placeholder="assets/img/produtos/bolsa-tote-vermelha-600.jpg">
        <label for="imagemAlt">Descrição da foto</label>
        <input id="imagemAlt" name="imagemAlt" value="<?= e($atual['imagemAlt'] ?? '') ?>">

        <label class="dica" style="display:flex;gap:10px;align-items:center;margin-top:18px;font-size:.95rem">
          <input type="checkbox" name="publicado" style="width:auto"<?= !empty($atual['publicado']) || $novoTexto ? ' checked' : '' ?>>
          Publicar no site (desmarque para guardar como rascunho)
        </label>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
          <button class="botao botao--principal" type="submit">Salvar e publicar</button>
          <?php if (!$novoTexto): ?>
            <a class="botao botao--contorno" href="blog.php">Escrever outro</a>
            <a class="botao botao--contorno" target="_blank" rel="noopener"
               href="../post.html?slug=<?= e($atual['slug']) ?>">Ver no site ↗</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div>
    <div class="painel-caixa">
      <h2>Textos</h2>
      <?php if (!$posts): ?>
        <p class="dica">Nenhum texto ainda.</p>
      <?php else: ?>
        <ul class="lista-simples">
          <?php foreach ($posts as $p): ?>
            <li style="flex-direction:column;align-items:stretch;gap:5px">
              <span>
                <a href="blog.php?editar=<?= e(rawurlencode($p['slug'])) ?>"><strong><?= e($p['titulo']) ?></strong></a>
                <?php if (empty($p['publicado'])): ?>
                  <span class="selo selo--aguardando">rascunho</span>
                <?php endif; ?>
              </span>
              <span style="display:flex;justify-content:space-between;gap:10px;color:var(--tinta-fraca);font-size:.84rem">
                <span><?= e(p_data($p['data'] ?? null)) ?> · <?= e($p['tag'] ?? 'sem assunto') ?></span>
                <form method="post" onsubmit="return confirm('Apagar “<?= e($p['titulo']) ?>”? Não tem como desfazer.')">
                  <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
                  <input type="hidden" name="acao" value="apagar">
                  <input type="hidden" name="slug" value="<?= e($p['slug']) ?>">
                  <button type="submit" style="background:none;border:0;color:var(--tinta-fraca);cursor:pointer;
                          text-decoration:underline;font:inherit;font-size:.84rem;padding:0">apagar</button>
                </form>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <p style="margin-top:16px"><a class="botao botao--contorno botao--pequeno" href="../blog.html" target="_blank" rel="noopener">Ver o diário no site ↗</a></p>
    </div>

    <div class="painel-caixa">
      <h2>Sobre o que escrever</h2>
      <ul class="lista-simples">
        <li><span>Quantas horas leva uma peça, e por quê</span></li>
        <li><span>Como lavar e guardar sem estragar</span></li>
        <li><span>As cores que a Aurora escolheu desta vez</span></li>
        <li><span>Um dia de feira, do começo ao fim</span></li>
        <li><span>De onde veio um ponto que você usa</span></li>
        <li><span>O que fazer com as sobras de barbante</span></li>
      </ul>
      <p class="dica">Um texto por mês já basta. O diário serve para o Google achar vocês e para quem
        comprou continuar por perto — não é para virar obrigação.</p>
    </div>
  </div>
</div>

<?php p_rodape();
