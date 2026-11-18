<?php
require 'conexao.php';
$stmt = $pdo->query("SELECT id, nome, LENGTH(foto_perfil) as len, LEFT(foto_perfil, 40) as pre FROM usuarios WHERE foto_perfil IS NOT NULL AND foto_perfil != ''");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | Nome: {$r['nome']} | Len: {$r['len']} | Pre: {$r['pre']}\n";
}
unlink(__FILE__);
