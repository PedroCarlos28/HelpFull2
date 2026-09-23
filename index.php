<?php
session_start();

// Se já estiver logado, vai direto para a página inicial do sistema
if (isset($_SESSION['usuario_id'])) {
    header("Location: inicio.php");
    exit();
}

// Caso contrário, vai para a tela de login / cadastro
header("Location: Comeco.php");
exit();
?>
