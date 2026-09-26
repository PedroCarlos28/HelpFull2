<?php
// Desativa exibição de notices/warnings na tela (evita textos vazando na interface)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
@ini_set('display_errors', '0');

// Habilita compressão de saída para otimizar payload e evitar limites na Vercel (4.5MB)
if (!headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', 'On');
}

// Configurações de ambiente para Vercel / Serverless
if (getenv('VERCEL') || !empty($_SERVER['VERCEL'])) {
    if (session_status() === PHP_SESSION_NONE) {
        @ini_set('session.save_path', '/tmp');
    }
}

// Inicia sessão de forma segura se ainda não iniciada
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!function_exists('salvarSessaoUsuario')) {
    function salvarSessaoUsuario($id, $nome) {
        $_SESSION['usuario_id']   = $id;
        $_SESSION['usuario_nome'] = $nome;

        $sig = hash_hmac('sha256', (string)$id, 'HelpFullSessionSecretKey2026');
        $payload = base64_encode(json_encode(['id' => $id, 'nome' => $nome, 'sig' => $sig]));
        
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('helpfull_session', $payload, [
            'expires'  => time() + (30 * 24 * 60 * 60), // 30 dias de persistência
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secure,
            'samesite' => 'Lax'
        ]);
    }
}

if (!function_exists('limparSessaoUsuario')) {
    function limparSessaoUsuario() {
        unset($_SESSION['usuario_id']);
        unset($_SESSION['usuario_nome']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        setcookie('helpfull_session', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secure,
            'samesite' => 'Lax'
        ]);
    }
}

// Configurações usando o Agrupador de Sessões (Session Pooler) para funcionar no IPv4
$host = 'aws-1-us-west-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.mxqhpyzdgthzrzchhjqb';

// Senha do banco de dados Supabase
$password = 'HelpFull-2026';

// Monta a string de conexão (DSN)
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    // Tenta fazer a conexão com o banco de dados
    $pdo = new PDO($dsn, $user, $password);

    // Configura o PDO para mostrar os erros caso algo dê errado
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

// Validação e restauração de sessão persistente via cookie
if (!isset($_SESSION['usuario_id']) && !empty($_COOKIE['helpfull_session'])) {
    $rawCookie = @json_decode(base64_decode($_COOKIE['helpfull_session']), true);
    if ($rawCookie && !empty($rawCookie['id']) && !empty($rawCookie['sig'])) {
        $expected = hash_hmac('sha256', (string)$rawCookie['id'], 'HelpFullSessionSecretKey2026');
        if (hash_equals($expected, $rawCookie['sig'])) {
            try {
                $stmtVal = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = ?");
                $stmtVal->execute([$rawCookie['id']]);
                $uExist = $stmtVal->fetch();
                if ($uExist) {
                    $_SESSION['usuario_id']   = $uExist['id'];
                    $_SESSION['usuario_nome'] = $uExist['nome'];
                } else {
                    // Usuário não existe mais no banco: limpa cookie órfão
                    limparSessaoUsuario();
                }
            } catch (Exception $e) {
            }
        } else {
            limparSessaoUsuario();
        }
    }
} elseif (isset($_SESSION['usuario_id'])) {
    // Se a sessão está ativa, valida se o usuário ainda existe no banco
    try {
        $stmtVal = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = ?");
        $stmtVal->execute([$_SESSION['usuario_id']]);
        $uExist = $stmtVal->fetch();
        if (!$uExist) {
            limparSessaoUsuario();
        }
    } catch (Exception $e) {
    }
}
?>