<?php
session_start();
session_destroy();

// Remove o cookie de sessão persistente
if (isset($_COOKIE['helpfull_session'])) {
    setcookie('helpfull_session', '', time() - 3600, '/');
}

header("Location: Comeco.php");
exit;
?>
