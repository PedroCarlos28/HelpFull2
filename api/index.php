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

// Se o arquivo direto não existir, tenta encontrar case-insensitive ou com .php
if (!is_file($file) && !is_file($file . '.php')) {
    $dir = __DIR__ . '/../';
    $targetName = strtolower(basename($uri));
    $targetNamePhp = $targetName . '.php';
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            $entryLower = strtolower($entry);
            if ($entryLower === $targetName) {
                $file = $dir . $entry;
                break;
            } elseif ($entryLower === $targetNamePhp) {
                $file = $dir . $entry;
                break;
            }
        }
        closedir($handle);
    }
}

// 1. Se for um arquivo PHP existente (ex: Comeco.php, login_proc.php, Perfil.php)
if (is_file($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
    require $file;
    exit;
}

// 2. Se for uma rota sem .php que corresponde a um arquivo .php
if (is_file($file . '.php')) {
    require $file . '.php';
    exit;
}

// 3. Se for um arquivo estático existente (ex: notificacoes.js, assets/...)
if (is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimes = [
        'js'    => 'application/javascript; charset=UTF-8',
        'css'   => 'text/css; charset=UTF-8',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'json'  => 'application/json; charset=UTF-8',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    header('Cache-Control: public, max-age=3600');
    readfile($file);
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
