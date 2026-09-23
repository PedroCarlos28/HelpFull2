<?php
// Roteador central para a Vercel Serverless
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// Se for a raiz ou index.php, carrega o index.php da raiz
if ($uri === '' || $uri === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$file = __DIR__ . '/../' . $uri;

// Se for um arquivo PHP existente (ex: Comeco.php, login_proc.php, oauth_firebase.php, etc.)
if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    exit;
}

// Fallback para qualquer outra rota
if (is_file(__DIR__ . '/../index.php')) {
    require __DIR__ . '/../index.php';
    exit;
}

http_response_code(404);
echo "Página não encontrada.";
?>
