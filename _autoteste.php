<?php
/* Autoteste temporário — roda no servidor e confere se o site está de pé.
   É apagado depois da verificação. */

declare(strict_types=1);

$base = 'https://croche.okgo.dev.br';
$raiz = __DIR__;
$ok = 0; $falhas = 0;

function checa(string $nome, bool $passou, string $detalhe = ''): void {
    global $ok, $falhas;
    $passou ? $ok++ : $falhas++;
    echo ($passou ? '  OK   ' : '  FALHA') . "  $nome" . ($detalhe !== '' ? "  ($detalhe)" : '') . "\n";
}

function buscar(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_TIMEOUT => 25, CURLOPT_HEADER => true]);
    $r = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tamCab = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    if ($r === false) return [0, '', ''];
    return [$status, substr((string) $r, 0, $tamCab), substr((string) $r, $tamCab)];
}

echo "=== PÁGINAS PÚBLICAS ===\n";
foreach (['/' => 'Lúcia Maria Crochê', '/produtos.html' => 'As peças', '/checkout.html' => 'checkout',
          '/blog.html' => 'Diário do ateliê', '/sobre.html' => 'A Lúcia e a Aurora',
          '/feiras.html' => 'Onde nos encontrar', '/cuidados.html' => 'Cuidados'] as $caminho => $esperado) {
    [$s, $cab, $corpo] = buscar($base . $caminho);
    checa("GET $caminho", $s === 200 && stripos($corpo, $esperado) !== false, "HTTP $s");
}

echo "\n=== HTTPS E CABEÇALHOS ===\n";
[$s, $cab] = buscar($base . '/');
checa('HTTPS responde', $s === 200, "HTTP $s");
checa('noindex no domínio temporário', stripos($cab, 'X-Robots-Tag: noindex') !== false);
checa('X-Content-Type-Options', stripos($cab, 'nosniff') !== false);

echo "\n=== ARQUIVOS QUE NÃO PODEM APARECER ===\n";
foreach (['/docs/PAINEL.md', '/README.md', '/dados-privados/pedidos.json',
          '/api/config.php', '/.gitignore'] as $caminho) {
    [$s, , $corpo] = buscar($base . $caminho);
    $vazou = stripos($corpo, 'senha') !== false || stripos($corpo, 'access_token') !== false
          || stripos($corpo, 'painel_senha_hash') !== false;
    checa("bloqueado: $caminho", ($s === 403 || $s === 404 || $corpo === '') && !$vazou, "HTTP $s");
}

echo "\n=== PAINEL ===\n";
foreach (['/painel/', '/painel/financeiro.php', '/painel/marketing.php', '/painel/blog.php'] as $caminho) {
    [$s, , $corpo] = buscar($base . $caminho);
    $pedeSenha = stripos($corpo, 'class="login"') !== false || stripos($corpo, 'Painel do ateliê') !== false;
    $vazou = stripos($corpo, 'Lançar uma despesa') !== false || stripos($corpo, 'Chamar de volta') !== false;
    checa("protegido: $caminho", $s === 200 && $pedeSenha && !$vazou, "HTTP $s");
}

echo "\n=== API ===\n";
[$s, , $corpo] = buscar($base . '/api/config-publica.php');
checa('config-publica responde', $s === 200 && stripos($corpo, 'DRISANA') !== false, "HTTP $s");
checa('access_token não vaza', stripos($corpo, 'access_token') === false);

$ch = curl_init($base . '/api/frete.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 25,
    CURLOPT_POSTFIELDS => json_encode(['cep' => '11600-000', 'peso' => 520, 'subtotal' => 189])]);
$frete = json_decode((string) curl_exec($ch), true);
curl_close($ch);
checa('frete calcula pelo CEP', ($frete['valor'] ?? null) === 22, 'R$ ' . ($frete['valor'] ?? '?'));

$ch = curl_init($base . '/api/pedido.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 25,
    CURLOPT_POSTFIELDS => json_encode([
        'cliente' => ['nome' => 'AUTOTESTE (apagar)', 'whatsapp' => '12999999999'],
        'entrega' => ['tipo' => 'retirada'],
        'itens' => [['id' => 'bolsa-tote-vermelha', 'qtd' => 1, 'preco' => 1]],
        'pagamento' => 'pix'])]);
$pedido = json_decode((string) curl_exec($ch), true);
curl_close($ch);
checa('pedido é registrado', !empty($pedido['pedido']), (string) ($pedido['pedido'] ?? 'sem id'));
checa('preço vem do servidor, não do navegador', ($pedido['total'] ?? 0) == 179.55,
      'cobrou R$ ' . ($pedido['total'] ?? '?') . ' (o teste mandou preco=1)');
$pix = $pedido['pixCodigo'] ?? '';
checa('código Pix gerado', strlen($pix) > 100);
checa('Pix tem a chave certa', str_contains($pix, '+5512996148324'));
checa('Pix tem o recebedor certo', str_contains($pix, 'DRISANA HOLLAND'));

echo "\n=== GRAVAÇÃO DE DADOS ===\n";
checa('dados-privados é gravável', is_writable($raiz . '/dados-privados'));
checa('dados é gravável (painel publica o blog)', is_writable($raiz . '/dados'));
checa('pedido foi mesmo salvo em disco', is_file($raiz . '/dados-privados/pedidos.json'));

echo "\n=== AMBIENTE ===\n";
checa('PHP 8.1 ou mais novo', version_compare(PHP_VERSION, '8.1', '>='), PHP_VERSION);
foreach (['curl', 'zip', 'mbstring', 'iconv', 'json'] as $ext) {
    checa("extensão $ext", extension_loaded($ext));
}

/* limpa o pedido de teste */
$arq = $raiz . '/dados-privados/pedidos.json';
if (is_file($arq)) {
    $lista = json_decode((string) file_get_contents($arq), true) ?: [];
    $limpo = array_values(array_filter($lista, fn($p) => ($p['cliente']['nome'] ?? '') !== 'AUTOTESTE (apagar)'));
    file_put_contents($arq, json_encode($limpo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $cli = $raiz . '/dados-privados/clientes.json';
    if (is_file($cli)) { @unlink($cli); }
    echo "\n(pedido de teste removido)\n";
}

echo "\n==================================\n";
echo "PASSOU: $ok   ·   FALHOU: $falhas\n";
echo "==================================\n";
