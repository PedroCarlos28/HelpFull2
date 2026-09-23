<?php
require_once 'conexao.php';
session_start();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['nome']) || !isset($input['email']) || !isset($input['senha'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit();
}

$nome = trim($input['nome']);
$email = trim($input['email']);
$senhaRaw = $input['senha'];

// Validação: mínimo 6 caracteres na senha
if (strlen($senhaRaw) < 6) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 6 caracteres.']);
    exit();
}

$senha = password_hash($senhaRaw, PASSWORD_DEFAULT);

try {
    // Verificar se o email já existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $check->execute(['email' => $email]);
    if ($check->fetch()) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Este email já está cadastrado.']);
        exit();
    }

    // Inserir novo usuário usando RETURNING id (padrão estável para PostgreSQL/Supabase)
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha) RETURNING id");
    $stmt->execute([
        'nome' => $nome,
        'email' => $email,
        'senha' => $senha
    ]);

    $usuario = $stmt->fetch();
    $novoId = $usuario['id'];
    
    $_SESSION['usuario_id'] = $novoId;
    $_SESSION['usuario_nome'] = $nome;

    echo json_encode(['sucesso' => true, 'mensagem' => 'Cadastro realizado com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cadastrar: ' . $e->getMessage()]);
}
?>
