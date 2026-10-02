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

// Validação dos termos de consentimento
if (empty($input['termos'])) {
    responderJson(['sucesso' => false, 'mensagem' => 'Você precisa ler e aceitar os Termos de Uso e Política de Privacidade.']);
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
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, termos_aceitos, termos_aceitos_em) VALUES (:nome, :email, :senha, true, NOW()) RETURNING id");
        $stmt->execute([
            'nome'  => $nome,
            'email' => $email,
            'senha' => $senha
        ]);
    } catch (PDOException $eCol) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha) RETURNING id");
        $stmt->execute([
            'nome'  => $nome,
            'email' => $email,
            'senha' => $senha
        ]);
    }

    $usuario = $stmt->fetch();
    $novoId  = $usuario['id'];
    
    salvarSessaoUsuario($novoId, $nome);

    // Envia e-mail de boas-vindas e confirmação de criação de conta
    require_once __DIR__ . '/email_helper.php';
    enviarEmailBoasVindas($email, $nome);

    // Registra também na central de notificações do site
    require_once __DIR__ . '/notificacao_helper.php';
    registrarNotificacaoBoasVindas($pdo, $novoId);
    registrarNotificacaoNovoLogin($pdo, $novoId);

    responderJson(['sucesso' => true, 'mensagem' => 'Cadastro realizado com sucesso!']);
} catch (PDOException $e) {
    responderJson(['sucesso' => false, 'mensagem' => 'Erro ao cadastrar: ' . $e->getMessage()]);
}
?>
