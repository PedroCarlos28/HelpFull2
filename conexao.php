<?php
// Configura fuso horário padrão para o Brasil (America/Sao_Paulo / UTC-3)
date_default_timezone_set('America/Sao_Paulo');

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
        global $pdo;
        $_SESSION['usuario_id']   = $id;
        $_SESSION['usuario_nome'] = $nome;

        require_once __DIR__ . '/sessao_helper.php';
        $tokenSessao = null;
        if (isset($pdo)) {
            $tokenSessao = registrarNovaSessao($pdo, $id);
        }
        if (!$tokenSessao) {
            $tokenSessao = bin2hex(random_bytes(32));
        }
        $_SESSION['token_sessao'] = $tokenSessao;

        $sig = hash_hmac('sha256', (string)$id . '|' . $tokenSessao, 'HelpFullSessionSecretKey2026');
        $payload = base64_encode(json_encode([
            'id'    => $id,
            'nome'  => $nome,
            'token' => $tokenSessao,
            'sig'   => $sig
        ]));
        
        unset($_SESSION['usuario_cache']);
        unset($_SESSION['usuario_cache_time']);
        unset($_SESSION['ultima_checagem_sessao']);

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
        global $pdo;
        if (!empty($_SESSION['usuario_id']) && !empty($_SESSION['token_sessao']) && isset($pdo)) {
            try {
                require_once __DIR__ . '/sessao_helper.php';
                desconectarSessao($pdo, $_SESSION['usuario_id'], $_SESSION['token_sessao']);
            } catch (Exception $e) {}
        }
        unset($_SESSION['usuario_id']);
        unset($_SESSION['usuario_nome']);
        unset($_SESSION['token_sessao']);
        unset($_SESSION['usuario_cache']);
        unset($_SESSION['usuario_cache_time']);
        unset($_SESSION['ultima_checagem_sessao']);
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

// Monta a string de conexão (DSN) com timezone direto na conexão para evitar round-trip extra
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--timezone=America/Sao_Paulo'";

try {
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 6
    ];
    $pdo = new PDO($dsn, $user, $password, $pdoOptions);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

// Validação e restauração de sessão persistente via cookie assinado (HMAC SHA-256)
if (!isset($_SESSION['usuario_id']) && !empty($_COOKIE['helpfull_session'])) {
    $rawCookie = @json_decode(base64_decode($_COOKIE['helpfull_session']), true);
    if ($rawCookie && !empty($rawCookie['id']) && !empty($rawCookie['sig'])) {
        $token = $rawCookie['token'] ?? '';
        $expectedModern = hash_hmac('sha256', (string)$rawCookie['id'] . '|' . $token, 'HelpFullSessionSecretKey2026');
        $expectedLegacy = hash_hmac('sha256', (string)$rawCookie['id'], 'HelpFullSessionSecretKey2026');

        if (hash_equals($expectedModern, $rawCookie['sig']) || hash_equals($expectedLegacy, $rawCookie['sig'])) {
            $_SESSION['usuario_id']   = $rawCookie['id'];
            $_SESSION['usuario_nome'] = $rawCookie['nome'] ?? '';
            if (!empty($token)) {
                $_SESSION['token_sessao'] = $token;
            }
        }
    }
}

if (!function_exists('atualizarCookieSessaoHelper')) {
    function atualizarCookieSessaoHelper($id, $nome, $token) {
        $sig = hash_hmac('sha256', (string)$id . '|' . $token, 'HelpFullSessionSecretKey2026');
        $payload = base64_encode(json_encode([
            'id'    => $id,
            'nome'  => $nome,
            'token' => $token,
            'sig'   => $sig
        ]));
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        if (!headers_sent()) {
            setcookie('helpfull_session', $payload, [
                'expires'  => time() + (30 * 24 * 60 * 60),
                'path'     => '/',
                'httponly' => true,
                'secure'   => $secure,
                'samesite' => 'Lax'
            ]);
        }
    }
}

// Verificação de sessão ativa no banco (garante que dispositivo revogado seja desconectado)
// Otimização de performance: faz cache de 60s da verificação para não atrasar a navegação entre telas
if (isset($_SESSION['usuario_id']) && isset($pdo)) {
    $scriptAtual = strtolower(basename($_SERVER['PHP_SELF'] ?? ''));
    $forcarChecagem = ($scriptAtual === 'dispositivos.php' || $scriptAtual === 'sair.php');
    $agora = time();
    $deveChecar = $forcarChecagem || empty($_SESSION['ultima_checagem_sessao']) || ($agora - (int)$_SESSION['ultima_checagem_sessao'] > 60);

    if ($deveChecar) {
        $_SESSION['ultima_checagem_sessao'] = $agora;
        require_once __DIR__ . '/sessao_helper.php';
        if (!empty($_SESSION['token_sessao'])) {
            $statusSessao = verificarStatusSessao($pdo, $_SESSION['usuario_id'], $_SESSION['token_sessao']);
            if ($statusSessao === 'revogada') {
                limparSessaoUsuario();
            } elseif ($statusSessao === 'inexistente') {
                $novoToken = registrarNovaSessao($pdo, $_SESSION['usuario_id']);
                if ($novoToken) {
                    $_SESSION['token_sessao'] = $novoToken;
                    atualizarCookieSessaoHelper($_SESSION['usuario_id'], $_SESSION['usuario_nome'] ?? '', $novoToken);
                }
            }
        } else {
            $novoToken = registrarNovaSessao($pdo, $_SESSION['usuario_id']);
            if ($novoToken) {
                $_SESSION['token_sessao'] = $novoToken;
                atualizarCookieSessaoHelper($_SESSION['usuario_id'], $_SESSION['usuario_nome'] ?? '', $novoToken);
            }
        }
    }
}

// Função de carregamento com cache dos dados do usuário logado (evita SELECT * FROM usuarios em cada tela)
if (!function_exists('obterUsuarioLogado')) {
    function obterUsuarioLogado($pdo, $forcarAtualizacao = false) {
        if (empty($_SESSION['usuario_id']) || !$pdo) {
            return null;
        }
        $uid = $_SESSION['usuario_id'];
        $agora = time();

        if (!$forcarAtualizacao && !empty($_SESSION['usuario_cache']) && ($_SESSION['usuario_cache']['id'] ?? '') == $uid) {
            if (!empty($_SESSION['usuario_cache_time']) && ($agora - (int)$_SESSION['usuario_cache_time'] < 120)) {
                return $_SESSION['usuario_cache'];
            }
        }

        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, foto_perfil, onboarding_concluido, videos_assistidos, ultimo_video_data, COALESCE(dois_fatores_ativo, false) as dois_fatores_ativo, COALESCE(is_admin, false) as is_admin, oauth_provider FROM usuarios WHERE id = ?");
            $stmt->execute([$uid]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['usuario_cache'] = $user;
                $_SESSION['usuario_cache_time'] = $agora;
                return $user;
            }
        } catch (Exception $e) {}

        return null;
    }
}

if (!function_exists('limparCacheUsuarioLogado')) {
    function limparCacheUsuarioLogado() {
        unset($_SESSION['usuario_cache']);
        unset($_SESSION['usuario_cache_time']);
    }
}
?>