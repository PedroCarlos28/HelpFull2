<?php
// Novas configurações usando o Agrupador de Sessões (Session Pooler) para funcionar no IPv4
$host = 'aws-1-us-west-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.mxqhpyzdgthzrzchhjqb';

// ATENÇÃO: Substitua a linha abaixo pela sua senha real do banco de dados
$password = 'HelpFull-2026';

// Monta a string de conexão (DSN)
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    // Tenta fazer a conexão com o banco de dados
    $pdo = new PDO($dsn, $user, $password);

    // Configura o PDO para mostrar os erros caso algo dê errado
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Se quiser testar se funcionou, tire as duas barras (//) da linha abaixo:
    // echo "Conexão com o Supabase realizada com sucesso!";

} catch (PDOException $e) {
    // Se der erro, ele captura aqui e te avisa qual foi o problema
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>