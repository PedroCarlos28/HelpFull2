<?php
// Exemplo de configuração de chaves para deploy ou novos ambientes.
// Em produção, defina as variáveis de ambiente no painel de hospedagem.
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'SUA_CHAVE_GEMINI_AQUI');
}
?>
