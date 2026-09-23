<?php
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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['nome']) || !isset($input['email']) || !isset($input['senha'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
}

$nome     = trim($input['nome']);
$email    = trim($input['email']);
$senhaRaw = $input['senha'];

// Validação: mínimo 6 caracteres na senha
if (strlen($senhaRaw) < 6) {
    responderJson(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 6 caracteres.']);
}

$senha = password_hash($senhaRaw, PASSWORD_DEFAULT);

try {
    // Verificar se o email já existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $check->execute(['email' => $email]);
    if ($check->fetch()) {
        responderJson(['sucesso' => false, 'mensagem' => 'Este email já está cadastrado.']);
    }

    // Inserir novo usuário usando RETURNING id (padrão estável para PostgreSQL/Supabase)
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha) RETURNING id");
    $stmt->execute([
        'nome'  => $nome,
        'email' => $email,
        'senha' => $senha
    ]);

    $usuario = $stmt->fetch();
    $novoId  = $usuario['id'];
    
    salvarSessaoUsuario($novoId, $nome);

    responderJson(['sucesso' => true, 'mensagem' => 'Cadastro realizado com sucesso!']);
} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao cadastrar: ' . $e->getMessage()]);
}
?>
