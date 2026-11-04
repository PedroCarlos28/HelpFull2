<?php
require_once __DIR__ . '/conexao.php';

// Se já estiver logado com sessão válida e ativa, vai direto para a página inicial do sistema
$usuarioLogado = obterUsuarioLogado($pdo);
if ($usuarioLogado) {
    header("Location: inicio.php");
    exit();
}

// Caso contrário, vai para a tela de login / cadastro
header("Location: Comeco.php");
exit();
?>
