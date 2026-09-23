<?php
// Silencia notices/warnings para não corromper respostas JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
ob_start();

require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson($dados) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($dados);
    exit();
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || empty($input['idToken'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Token não fornecido.']);
}

$idToken     = trim($input['idToken']);
$clientEmail = !empty($input['email']) ? trim($input['email']) : null;
$clientNome  = !empty($input['nome']) ? trim($input['nome']) : null;
$clientFoto  = !empty($input['foto']) ? trim($input['foto']) : null;

// Função auxiliar para decodificar JWT do Firebase
function decodeFirebaseJwtPayload($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    $payloadBase64 = strtr($parts[1], '-_', '+/');
    $payloadJson = base64_decode($payloadBase64);
    return json_decode($payloadJson, true);
}

$email       = null;
$nome        = null;
$foto        = $clientFoto;
$firebaseUid = null;

// 1. Tentar validação via API REST oficial do Firebase Identity Toolkit
$apiKey = "AIzaSyCm9BVm73imctbpFxFWV9YKX30zm8QB27I";
$verifyUrl = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=" . $apiKey;

if (function_exists('curl_init')) {
    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $idToken]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);
        if (!empty($data['users'][0])) {
            $u = $data['users'][0];
            $email       = $u['email'] ?? null;
            $nome        = $u['displayName'] ?? null;
            $foto        = $u['photoUrl'] ?? $foto;
            $firebaseUid = $u['localId'] ?? null;
        }
    }
}

// 2. Se a chamada cURL falhar por SSL/rede local do XAMPP/Vercel, faz fallback para validação do payload JWT
if (!$email) {
    $payload = decodeFirebaseJwtPayload($idToken);
    if ($payload && isset($payload['aud']) && $payload['aud'] === 'helpfull-e4aae') {
        if (isset($payload['exp']) && $payload['exp'] > (time() - 300)) {
            $email       = $payload['email'] ?? $clientEmail;
            $nome        = $payload['name'] ?? null;
            $foto        = $payload['picture'] ?? $foto;
            $firebaseUid = $payload['sub'] ?? ($payload['user_id'] ?? null);
        }
    }
}

// Se o nome não veio do token, usa o enviado pelo cliente ou o prefixo do e-mail
if (!$nome) {
    $nome = $clientNome ?: ($email ? explode('@', $email)[0] : 'Usuário Google');
}

if (!$email) {
    responderJson(['sucesso' => false, 'mensagem' => 'Não foi possível validar o token do Google.']);
}

try {
    // 3. Busca se o usuário já existe pelo e-mail
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Atualiza a foto de perfil caso o usuário ainda não tenha foto ou seja do Google
        if ($foto && (empty($usuario['foto_perfil']) || ($usuario['oauth_provider'] ?? '') === 'google')) {
            try {
                $updFoto = $pdo->prepare("UPDATE usuarios SET foto_perfil = :foto WHERE id = :id");
                $updFoto->execute(['foto' => $foto, 'id' => $usuario['id']]);
            } catch (\Exception $ignored) {}
        }

        // Atualiza provider/uid se as colunas existirem
        try {
            $upd = $pdo->prepare("UPDATE usuarios SET oauth_provider = 'google', firebase_uid = :uid WHERE id = :id");
            $upd->execute(['uid' => $firebaseUid, 'id' => $usuario['id']]);
        } catch (\Exception $ignored) {}

        salvarSessaoUsuario($usuario['id'], $usuario['nome']);
        responderJson(['sucesso' => true, 'novo' => false]);
    }

    // 4. Usuário novo: cria senha aleatória segura para respeitar NOT NULL caso o banco ainda exija
    $senhaHashAleatoria = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    try {
        // Tenta inserir incluindo foto_perfil, oauth_provider e firebase_uid
        $insert = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha, foto_perfil, oauth_provider, firebase_uid) 
             VALUES (:nome, :email, :senha, :foto, 'google', :uid) RETURNING id"
        );
        $insert->execute([
            'nome'  => $nome,
            'email' => $email,
            'senha' => $senhaHashAleatoria,
            'foto'  => $foto,
            'uid'   => $firebaseUid
        ]);
    } catch (PDOException $ex) {
        // Fallback 1: tenta com foto_perfil caso as colunas de provider ainda não existam
        try {
            $insert = $pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha, foto_perfil) 
                 VALUES (:nome, :email, :senha, :foto) RETURNING id"
            );
            $insert->execute([
                'nome'  => $nome,
                'email' => $email,
                'senha' => $senhaHashAleatoria,
                'foto'  => $foto
            ]);
        } catch (PDOException $ex2) {
            // Fallback 2: colunas básicas
            $insert = $pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha) 
                 VALUES (:nome, :email, :senha) RETURNING id"
            );
            $insert->execute([
                'nome'  => $nome,
                'email' => $email,
                'senha' => $senhaHashAleatoria
            ]);
        }
    }

    $novoUsuario = $insert->fetch();
    $novoId = $novoUsuario['id'];

    salvarSessaoUsuario($novoId, $nome);

    responderJson(['sucesso' => true, 'novo' => true]);
} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
}
?>
