<?php
/* Gera o hash de uma senha nova para colar em api/config.php. */
declare(strict_types=1);
require __DIR__ . '/comum.php';
lmc_exige_login();

$hash = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    lmc_confere_token();
    $nova = (string) ($_POST['nova'] ?? '');
    if (strlen($nova) < 8) {
        $erro = 'A senha precisa ter pelo menos 8 caracteres.';
    } else {
        $hash = password_hash($nova, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

p_cabecalho('Trocar a senha', '');
?>
<h1>Trocar a senha do painel</h1>
<div class="painel-caixa" style="max-width:620px">
  <?php if (!empty($erro)): ?><p class="recado recado--erro"><?= e($erro) ?></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="token" value="<?= e(lmc_token()) ?>">
    <label for="nova">Senha nova (mínimo 8 caracteres)</label>
    <input id="nova" name="nova" type="text" autocomplete="off" required>
    <button class="botao botao--principal" type="submit" style="margin-top:16px">Gerar</button>
  </form>

  <?php if ($hash): ?>
    <h2>Pronto</h2>
    <p>Abra o arquivo <code>api/config.php</code> e substitua a linha
      <code>'painel_senha_hash' =&gt; '...'</code> por esta:</p>
    <textarea readonly rows="3" onclick="this.select()">'painel_senha_hash' => '<?= e($hash) ?>',</textarea>
    <p class="dica">Depois de salvar o arquivo no servidor, a senha nova passa a valer.
      A senha antiga para de funcionar na hora.</p>
  <?php endif; ?>
</div>
<?php p_rodape();
