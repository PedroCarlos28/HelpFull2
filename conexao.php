<?php
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

// Persistência de sessão para Vercel via cookie assinado (evita logout entre chamadas serverless)
if (!isset($_SESSION['usuario_id']) && !empty($_COOKIE['helpfull_session'])) {
    $rawCookie = @json_decode(base64_decode($_COOKIE['helpfull_session']), true);
    if ($rawCookie && !empty($rawCookie['id']) && !empty($rawCookie['sig'])) {
        $expected = hash_hmac('sha256', (string)$rawCookie['id'], 'HelpFullSessionSecretKey2026');
        if (hash_equals($expected, $rawCookie['sig'])) {
            $_SESSION['usuario_id']   = $rawCookie['id'];
            $_SESSION['usuario_nome'] = $rawCookie['nome'] ?? '';
        }
    }
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

// Novas configurações usando o Agrupador de Sessões (Session Pooler) para funcionar no IPv4
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
?>