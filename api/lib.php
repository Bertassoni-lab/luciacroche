<?php
/* ============================================================
   Funções compartilhadas — Lúcia Maria Crochê
   Guarda os dados em arquivos JSON. Sem banco de dados: para o
   volume do ateliê é mais do que suficiente, e o backup é
   simplesmente copiar a pasta dados-privados.
   ============================================================ */

declare(strict_types=1);

function lmc_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        $config['site'] = lmc_endereco_do_site($config['site'] ?? '');
    }
    return $config;
}

/**
 * Descobre em que endereço o site está rodando de verdade.
 * Assim o mesmo código funciona no domínio temporário e no oficial,
 * sem precisar editar nada quando o domínio for apontado.
 */
function lmc_endereco_do_site(string $configurado): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '' || !preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
        return $configurado;
    }
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    return $protocolo . '://' . $host;
}

/* ---------------- Armazenamento ---------------- */

function lmc_caminho(string $arquivo): string
{
    $base = lmc_config()['dados'];
    if (!is_dir($base)) {
        mkdir($base, 0750, true);
    }
    return $base . '/' . basename($arquivo);
}

function lmc_ler(string $arquivo, array $padrao = []): array
{
    $caminho = lmc_caminho($arquivo);
    if (!is_file($caminho)) {
        return $padrao;
    }
    $bruto = file_get_contents($caminho);
    $dados = json_decode((string) $bruto, true);
    return is_array($dados) ? $dados : $padrao;
}

function lmc_gravar(string $arquivo, array $dados): bool
{
    $caminho = lmc_caminho($arquivo);
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $temp = $caminho . '.tmp';
    if (file_put_contents($temp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($temp, $caminho);
}

/** Acrescenta um registro a uma lista, com trava para não perder venda simultânea. */
function lmc_acrescentar(string $arquivo, array $registro): array
{
    $caminho = lmc_caminho($arquivo);
    $fp = fopen($caminho, 'c+');
    if ($fp === false) {
        throw new RuntimeException('Não consegui abrir ' . $arquivo);
    }
    flock($fp, LOCK_EX);
    $conteudo = stream_get_contents($fp);
    $lista = json_decode((string) $conteudo, true);
    if (!is_array($lista)) {
        $lista = [];
    }
    $lista[] = $registro;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($lista, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $registro;
}

/* ---------------- Utilidades ---------------- */

function lmc_id_pedido(): string
{
    return 'LMC' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

function lmc_texto($valor, int $limite = 500): string
{
    $t = trim((string) $valor);
    $t = preg_replace('/[\x00-\x1F\x7F]/u', '', $t) ?? '';
    return mb_substr($t, 0, $limite);
}

function lmc_dinheiro($valor): float
{
    return round((float) $valor, 2);
}

function lmc_json_resposta(array $dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function lmc_corpo_json(): array
{
    $bruto = file_get_contents('php://input');
    $dados = json_decode((string) $bruto, true);
    return is_array($dados) ? $dados : [];
}

/* ---------------- Pix (mesma regra do arquivo assets/js/pix.js) ---------------- */

function lmc_pix_campo(string $id, string $valor): string
{
    return $id . str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
}

function lmc_pix_crc16(string $texto): string
{
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($texto); $i < $n; $i++) {
        $crc ^= ord($texto[$i]) << 8;
        for ($b = 0; $b < 8; $b++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function lmc_pix_limpar(string $texto, int $limite): string
{
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $t = preg_replace('/[^A-Za-z0-9 ]/', '', $t) ?? '';
    $t = trim(preg_replace('/\s+/', ' ', $t) ?? '');
    return mb_strtoupper(substr($t, 0, $limite));
}

function lmc_pix_codigo(float $valor, string $txid, string $descricao = ''): string
{
    $pix = lmc_config()['pix'];

    $conta = lmc_pix_campo('00', 'br.gov.bcb.pix') . lmc_pix_campo('01', $pix['chave']);
    if ($descricao !== '') {
        $desc = lmc_pix_limpar($descricao, 60);
        if ($desc !== '' && strlen($conta) + strlen($desc) + 4 <= 99) {
            $conta .= lmc_pix_campo('02', $desc);
        }
    }

    $txidLimpo = preg_replace('/[^A-Za-z0-9]/', '', $txid) ?? '';
    $txidLimpo = strtoupper(substr($txidLimpo, 0, 25)) ?: '***';

    $payload = lmc_pix_campo('00', '01')
        . lmc_pix_campo('01', '12')
        . lmc_pix_campo('26', $conta)
        . lmc_pix_campo('52', '0000')
        . lmc_pix_campo('53', '986')
        . ($valor > 0 ? lmc_pix_campo('54', number_format($valor, 2, '.', '')) : '')
        . lmc_pix_campo('58', 'BR')
        . lmc_pix_campo('59', lmc_pix_limpar($pix['favorecido'], 25) ?: 'RECEBEDOR')
        . lmc_pix_campo('60', lmc_pix_limpar($pix['cidade'], 15) ?: 'SAO PAULO')
        . lmc_pix_campo('62', lmc_pix_campo('05', $txidLimpo));

    $payload .= '6304';
    return $payload . lmc_pix_crc16($payload);
}

/* ---------------- Sessão do painel ---------------- */

function lmc_sessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function lmc_logado(): bool
{
    lmc_sessao();
    return !empty($_SESSION['lmc_painel']);
}

function lmc_exige_login(): void
{
    if (!lmc_logado()) {
        header('Location: index.php?e=1');
        exit;
    }
}

function lmc_token(): string
{
    lmc_sessao();
    if (empty($_SESSION['lmc_token'])) {
        $_SESSION['lmc_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['lmc_token'];
}

function lmc_confere_token(): void
{
    lmc_sessao();
    $enviado = $_POST['token'] ?? '';
    if (!is_string($enviado) || empty($_SESSION['lmc_token']) || !hash_equals($_SESSION['lmc_token'], $enviado)) {
        http_response_code(400);
        exit('Sessão expirada. Volte, atualize a página e tente de novo.');
    }
}

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function lmc_moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
