<?php
require_once 'conexao.php';
limparSessaoUsuario();

// Redireciona com parâmetro de logout explícito para limpar sessões de clientes e Firebase no mobile
header("Location: Comeco.php?logout=1");
exit;
?>
