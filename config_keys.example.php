<?php
// Exemplo de configuração de chaves para deploy ou novos ambientes.
// Em produção, defina as variáveis de ambiente no painel de hospedagem.
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'SUA_CHAVE_GEMINI_AQUI');
}

// Configurações opcionais de SMTP para envio real de e-mails (Gmail, SendGrid, Brevo, etc.)
// if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
// if (!defined('SMTP_PORT')) define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
// if (!defined('SMTP_USER')) define('SMTP_USER', getenv('SMTP_USER') ?: 'contatohelpfull@gmail.com');
// if (!defined('SMTP_PASS')) define('SMTP_PASS', getenv('SMTP_PASS') ?: 'sua_senha_de_app_aqui');
// if (!defined('SMTP_FROM')) define('SMTP_FROM', getenv('SMTP_FROM') ?: 'contatohelpfull@gmail.com');
?>
