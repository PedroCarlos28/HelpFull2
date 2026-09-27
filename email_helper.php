<?php
/**
 * Helper de envio de e-mails para o HelpFull
 * Suporta envio por SMTP direto via sockets e mail() nativo do PHP.
 * Inclui notificações de:
 * 1. Verificação em Duas Etapas (2FA)
 * 2. Alerta de Novo Login na conta
 * 3. Boas-vindas / Criação de nova conta
 */

if (file_exists(__DIR__ . '/config_keys.php')) {
    require_once __DIR__ . '/config_keys.php';
}

/**
 * Mascara o e-mail para exibição segura (ex: pe***o@gmail.com)
 */
function mascararEmail($email) {
    if (!$email || strpos($email, '@') === false) return $email;
    list($user, $domain) = explode('@', $email, 2);
    $len = strlen($user);
    if ($len <= 2) {
        $maskedUser = $user[0] . '*';
    } else {
        $maskedUser = substr($user, 0, 2) . str_repeat('*', max(1, $len - 3)) . substr($user, -1);
    }
    return $maskedUser . '@' . $domain;
}

/**
 * Obtém o IP do cliente considerando proxies (Cloudflare, Vercel, etc.)
 */
function obterIpCliente() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $partes = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($partes[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Identifica o Sistema Operacional e Navegador a partir do User-Agent
 */
function detectarDispositivoNavegador($userAgent = null) {
    $ua = $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!$ua) {
        return [
            'dispositivo' => 'Dispositivo não identificado',
            'navegador'   => 'Navegador desconhecido',
            'resumo'      => 'Dispositivo não identificado'
        ];
    }

    // Identificação de Sistema Operacional / Dispositivo
    $so = 'Dispositivo';
    if (preg_match('/windows nt 10/i', $ua))      $so = 'Windows 10/11';
    elseif (preg_match('/windows nt 6\.3/i', $ua)) $so = 'Windows 8.1';
    elseif (preg_match('/windows nt 6\.1/i', $ua)) $so = 'Windows 7';
    elseif (preg_match('/windows/i', $ua))         $so = 'Windows';
    elseif (preg_match('/iphone/i', $ua))          $so = 'iPhone (iOS)';
    elseif (preg_match('/ipad/i', $ua))            $so = 'iPad (iPadOS)';
    elseif (preg_match('/android/i', $ua))         $so = 'Android';
    elseif (preg_match('/macintosh|mac os x/i', $ua)) $so = 'macOS (Mac)';
    elseif (preg_match('/linux/i', $ua))           $so = 'Linux';

    // Identificação de Navegador
    $nav = 'Navegador';
    if (preg_match('/edg/i', $ua))                 $nav = 'Microsoft Edge';
    elseif (preg_match('/opr|opera/i', $ua))       $nav = 'Opera';
    elseif (preg_match('/chrome|crios/i', $ua))    $nav = 'Google Chrome';
    elseif (preg_match('/firefox|fxios/i', $ua))   $nav = 'Mozilla Firefox';
    elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) $nav = 'Safari';

    return [
        'dispositivo' => $so,
        'navegador'   => $nav,
        'resumo'      => "{$nav} no {$so}"
    ];
}

/**
 * Obtém a URL base da aplicação para links nos e-mails
 */
function obterUrlBaseApp() {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                 (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $scriptDir = rtrim($scriptDir, '/');

    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') {
        $scriptDir = '';
    }

    return $protocolo . $host . $scriptDir;
}

/**
 * Template de e-mail para código 2FA
 */
function montarHtmlEmail2FA($nome, $codigo) {
    $nomeEsc = htmlspecialchars($nome ?: 'Usuário');
    $codigoEsc = htmlspecialchars($codigo);

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Código de Verificação HelpFull</title>
    <style>
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
        body { margin: 0; padding: 0; background-color: #0b1114; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; padding: 36px 14px; box-sizing: border-box; background-color: #0b1114; }
        .card { max-width: 520px; margin: 0 auto; background-color: #152026; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid #233742; }
        .header { background: linear-gradient(135deg, #13323a 0%, #206170 100%); background-color: #1a4955; padding: 35px 24px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 26px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; opacity: 0.95; }
        .content { padding: 35px 28px; color: #ffffff !important; line-height: 1.6; text-align: center; background-color: #152026; }
        .saudacao { font-size: 17px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 16px; font-weight: 700; }
        .codigo-box { margin: 26px auto; padding: 20px 28px; background-color: #0d2830; border: 2px dashed #22d3ee; border-radius: 16px; display: inline-block; }
        .codigo-numero { font-size: 36px; font-weight: 800; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; letter-spacing: 8px; font-family: 'Courier New', Courier, monospace; margin: 0; }
        .aviso { font-size: 14px; color: #e2e8f0 !important; -webkit-text-fill-color: #e2e8f0 !important; margin-top: 16px; }
        .destaque { color: #f87171 !important; -webkit-text-fill-color: #f87171 !important; font-weight: 700; }
        .footer { background-color: #0f171c; padding: 22px 24px; text-align: center; border-top: 1px solid #233742; font-size: 12px; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important; }
        .footer a { color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important; text-decoration: underline; font-weight: 600; }
        u + #body a { color: #ffffff !important; }
    </style>
</head>
<body id="body" style="margin: 0; padding: 0; background-color: #0b1114;">
    <div class="wrapper" style="width: 100%; padding: 36px 14px; box-sizing: border-box; background-color: #0b1114;">
        <div class="card" style="max-width: 520px; margin: 0 auto; background-color: #152026; border-radius: 20px; overflow: hidden; border: 1px solid #233742;">
            <div class="header" style="background: linear-gradient(135deg, #13323a 0%, #206170 100%); background-color: #1a4955; padding: 35px 24px; text-align: center;">
                <span style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 12px; font-weight: 700; padding: 5px 14px; border-radius: 20px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">🔐 Segurança HelpFull</span>
                <h1 style="margin: 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 26px; font-weight: 800; letter-spacing: 0.5px;">HelpFull</h1>
                <p style="margin: 8px 0 0 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 500;">Verificação de Segurança em Duas Etapas</p>
            </div>
            <div class="content" style="padding: 35px 28px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; line-height: 1.6; text-align: center; background-color: #152026;">
                <div class="saudacao" style="font-size: 17px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 16px; font-weight: 700;">Olá, {$nomeEsc}!</div>
                <p style="margin: 0; font-size: 15px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; line-height: 1.6;">Detectamos uma tentativa de login na sua conta. Use o código de 6 dígitos abaixo para concluir o acesso com segurança:</p>
                
                <div class="codigo-box" style="margin: 26px auto; padding: 20px 28px; background-color: #0d2830; border: 2px dashed #22d3ee; border-radius: 16px; display: inline-block;">
                    <div class="codigo-numero" style="font-size: 36px; font-weight: 800; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; letter-spacing: 8px; font-family: 'Courier New', Courier, monospace; margin: 0;">{$codigoEsc}</div>
                </div>

                <p class="aviso" style="font-size: 14px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-top: 16px;">⏱️ Este código é válido por <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">10 minutos</strong>.</p>
                <p class="aviso" style="font-size: 14px; color: #e2e8f0 !important; -webkit-text-fill-color: #e2e8f0 !important; margin-top: 8px;">Se você não solicitou este login, <span class="destaque" style="color: #f87171 !important; -webkit-text-fill-color: #f87171 !important; font-weight: 700;">não compartilhe este código</span> e recomendamos alterar sua senha imediatamente.</p>
            </div>
            <div class="footer" style="background-color: #0f171c; padding: 22px 24px; text-align: center; border-top: 1px solid #233742; font-size: 12px; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">
                <p style="margin: 0 0 6px 0; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">HelpFull — Promovendo bem-estar e conexões reais.</p>
                <p style="margin: 0; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">Dúvidas ou suporte? Escreva para <a href="mailto:contatohelpfull@gmail.com" style="color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important; text-decoration: underline; font-weight: 600;"><span style="color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important;">contatohelpfull@gmail.com</span></a></p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Template de e-mail para Notificação de Novo Login
 */
function montarHtmlEmailNovoLogin($nome, $dados = []) {
    $nomeEsc        = htmlspecialchars($nome ?: 'Usuário');
    $dataHora       = htmlspecialchars($dados['data_hora'] ?? date('d/m/Y \à\s H:i:s'));
    $dispositivo    = htmlspecialchars($dados['dispositivo'] ?? 'Dispositivo não identificado');
    $ip             = htmlspecialchars($dados['ip'] ?? '127.0.0.1');
    $metodo         = htmlspecialchars($dados['metodo'] ?? 'Senha e E-mail');
    $urlBase        = obterUrlBaseApp();
    $linkPerfil     = $urlBase ? ($urlBase . '/Perfil.php') : '#';

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Novo Acesso à sua Conta HelpFull</title>
    <style>
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
        body { margin: 0; padding: 0; background-color: #0b1114; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; padding: 36px 14px; box-sizing: border-box; background-color: #0b1114; }
        .card { max-width: 540px; margin: 0 auto; background-color: #152026; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid #233742; }
        .header { background: linear-gradient(135deg, #13323a 0%, #206170 100%); background-color: #1a4955; padding: 32px 24px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 26px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; opacity: 0.95; }
        .badge-alerta { display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 12px; font-weight: 700; padding: 5px 14px; border-radius: 20px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 32px 28px; color: #ffffff !important; line-height: 1.6; background-color: #152026; }
        .saudacao { font-size: 18px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 12px; font-weight: 700; }
        .descricao { font-size: 15px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin: 0 0 22px 0; line-height: 1.6; }
        .detalhes-tabela { width: 100%; background-color: #0f181d; border: 1px solid #223742; border-radius: 14px; padding: 18px 20px; box-sizing: border-box; margin-bottom: 22px; }
        .box-info { background-color: #0e2b34; border-left: 4px solid #22d3ee; padding: 14px 16px; border-radius: 8px; font-size: 13px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 16px; line-height: 1.5; }
        .box-aviso { background-color: #3b1419; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 8px; font-size: 13px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 24px; line-height: 1.5; }
        .botao-wrap { text-align: center; margin: 26px 0 10px 0; }
        .botao-cta { display: inline-block; background-color: #2b7a8c; background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 30px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35); }
        .footer { background-color: #0f171c; padding: 22px 24px; text-align: center; border-top: 1px solid #233742; font-size: 12px; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important; }
        .footer a { color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important; text-decoration: underline; font-weight: 600; }
        u + #body a { color: #ffffff !important; }
    </style>
</head>
<body id="body" style="margin: 0; padding: 0; background-color: #0b1114;">
    <div class="wrapper" style="width: 100%; padding: 36px 14px; box-sizing: border-box; background-color: #0b1114;">
        <div class="card" style="max-width: 540px; margin: 0 auto; background-color: #152026; border-radius: 20px; overflow: hidden; border: 1px solid #233742;">
            <div class="header" style="background: linear-gradient(135deg, #13323a 0%, #206170 100%); background-color: #1a4955; padding: 32px 24px; text-align: center;">
                <span class="badge-alerta" style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 12px; font-weight: 700; padding: 5px 14px; border-radius: 20px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">🔔 Alerta de Segurança</span>
                <h1 style="margin: 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 26px; font-weight: 800; letter-spacing: 0.5px;">HelpFull</h1>
                <p style="margin: 8px 0 0 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 500;">Identificamos um novo login na sua conta</p>
            </div>
            <div class="content" style="padding: 32px 28px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; line-height: 1.6; background-color: #152026;">
                <div class="saudacao" style="font-size: 18px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 12px; font-weight: 700;">Olá, {$nomeEsc}!</div>
                <p class="descricao" style="font-size: 15px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin: 0 0 22px 0; line-height: 1.6;">Sua conta do HelpFull acabou de ser acessada. Acompanhe os detalhes da sessão abaixo:</p>

                <div class="detalhes-tabela" style="width: 100%; background-color: #0f181d; border: 1px solid #223742; border-radius: 14px; padding: 18px 20px; box-sizing: border-box; margin-bottom: 22px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="border-bottom: 1px dashed rgba(255, 255, 255, 0.15);">
                            <td style="padding: 10px 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 600;">🕒 Data e Horário:</td>
                            <td style="padding: 10px 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 700; text-align: right;">{$dataHora}</td>
                        </tr>
                        <tr style="border-bottom: 1px dashed rgba(255, 255, 255, 0.15);">
                            <td style="padding: 10px 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 600;">💻 Dispositivo:</td>
                            <td style="padding: 10px 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 700; text-align: right;">{$dispositivo}</td>
                        </tr>
                        <tr style="border-bottom: 1px dashed rgba(255, 255, 255, 0.15);">
                            <td style="padding: 10px 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 600;">📍 Endereço IP:</td>
                            <td style="padding: 10px 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 700; text-align: right;"><code style="background-color: rgba(255, 255, 255, 0.1); padding: 3px 8px; border-radius: 4px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 13px;">{$ip}</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 14px; font-weight: 600;">🔐 Método de Login:</td>
                            <td style="padding: 10px 0; color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important; font-size: 14px; font-weight: 700; text-align: right;">{$metodo}</td>
                        </tr>
                    </table>
                </div>

                <div class="box-info" style="background-color: #0e2b34; border-left: 4px solid #22d3ee; padding: 14px 16px; border-radius: 8px; font-size: 13px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 16px; line-height: 1.5;">
                    <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">✓ Foi você?</strong> <span style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">Se foi você quem acessou sua conta, nenhuma ação adicional é necessária. Fique tranquilo(a)!</span>
                </div>

                <div class="box-aviso" style="background-color: #3b1419; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 8px; font-size: 13px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 24px; line-height: 1.5;">
                    <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">⚠️ Não reconhece esta atividade?</strong> <span style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">Recomendamos acessar imediatamente as configurações do seu perfil para alterar sua senha e habilitar a <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">Verificação em Duas Etapas (2FA)</strong>.</span>
                </div>

                <div class="botao-wrap" style="text-align: center; margin: 26px 0 10px 0;">
                    <a href="{$linkPerfil}" class="botao-cta" target="_blank" style="display: inline-block; background-color: #2b7a8c; background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 30px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);">
                        <span style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; text-decoration: none; font-weight: 700;">Acessar Meu Perfil e Segurança</span>
                    </a>
                </div>
            </div>
            <div class="footer" style="background-color: #0f171c; padding: 22px 24px; text-align: center; border-top: 1px solid #233742; font-size: 12px; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">
                <p style="margin: 0 0 6px 0; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">HelpFull — Promovendo bem-estar, acolhimento e conexões reais.</p>
                <p style="margin: 0; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">Precisa de ajuda ou suporte? Escreva para <a href="mailto:contatohelpfull@gmail.com" style="color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important; text-decoration: underline; font-weight: 600;"><span style="color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important;">contatohelpfull@gmail.com</span></a></p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Template de e-mail para Boas-Vindas / Nova Conta Criada
 */
function montarHtmlEmailBoasVindas($nome, $emailDestino = '') {
    $nomeEsc        = htmlspecialchars($nome ?: 'Novo Usuário');
    $emailEsc       = htmlspecialchars($emailDestino ? mascararEmail($emailDestino) : '');
    $dataHora       = date('d/m/Y');
    $urlBase        = obterUrlBaseApp();
    $linkInicio     = $urlBase ? ($urlBase . '/inicio.php') : '#';

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Bem-vindo(a) ao HelpFull!</title>
    <style>
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
        body { margin: 0; padding: 0; background-color: #0b1114; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; padding: 36px 14px; box-sizing: border-box; background-color: #0b1114; }
        .card { max-width: 560px; margin: 0 auto; background-color: #152026; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); border: 1px solid #233742; }
        .header { background: linear-gradient(135deg, #13323a 0%, #206170 100%); background-color: #1a4955; padding: 36px 24px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 28px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 15px; opacity: 0.95; }
        .badge-welcome { display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 12px; font-weight: 700; padding: 5px 16px; border-radius: 20px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 35px 28px; color: #ffffff !important; line-height: 1.6; background-color: #152026; }
        .saudacao { font-size: 19px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 12px; font-weight: 700; }
        .descricao { font-size: 15px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin: 0 0 24px 0; line-height: 1.6; }
        .features-grid { margin: 20px 0 24px 0; }
        .feature-card { background-color: #0f181d; border: 1px solid #223742; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
        .feature-title { font-size: 15px; font-weight: 700; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
        .feature-desc { font-size: 13px; color: #e2e8f0 !important; -webkit-text-fill-color: #e2e8f0 !important; margin: 0; line-height: 1.5; }
        .dados-conta { background-color: #0e2b34; border: 1px solid #22d3ee; border-radius: 12px; padding: 15px 18px; font-size: 14px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin: 22px 0; }
        .botao-wrap { text-align: center; margin: 28px 0 10px 0; }
        .botao-cta { display: inline-block; background-color: #2b7a8c; background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; text-decoration: none; padding: 14px 34px; border-radius: 30px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35); }
        .footer { background-color: #0f171c; padding: 22px 24px; text-align: center; border-top: 1px solid #233742; font-size: 12px; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important; }
        .footer a { color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important; text-decoration: underline; font-weight: 600; }
        u + #body a { color: #ffffff !important; }
    </style>
</head>
<body id="body" style="margin: 0; padding: 0; background-color: #0b1114;">
    <div class="wrapper" style="width: 100%; padding: 36px 14px; box-sizing: border-box; background-color: #0b1114;">
        <div class="card" style="max-width: 560px; margin: 0 auto; background-color: #152026; border-radius: 20px; overflow: hidden; border: 1px solid #233742;">
            <div class="header" style="background: linear-gradient(135deg, #13323a 0%, #206170 100%); background-color: #1a4955; padding: 36px 24px; text-align: center;">
                <span class="badge-welcome" style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 12px; font-weight: 700; padding: 5px 16px; border-radius: 20px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">🌿 Boas-vindas</span>
                <h1 style="margin: 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 28px; font-weight: 800; letter-spacing: 0.5px;">HelpFull</h1>
                <p style="margin: 8px 0 0 0; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; font-size: 15px; font-weight: 500;">Sua conta foi criada com sucesso!</p>
            </div>
            <div class="content" style="padding: 35px 28px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; line-height: 1.6; background-color: #152026;">
                <div class="saudacao" style="font-size: 19px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 12px; font-weight: 700;">Olá, {$nomeEsc}!</div>
                <p class="descricao" style="font-size: 15px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin: 0 0 24px 0; line-height: 1.6;">
                    Estamos muito felizes em ter você aqui. O <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">HelpFull</strong> foi pensado para ser o seu espaço seguro de acolhimento emocional, reflexão e conexões saudáveis.
                </p>

                <div class="features-grid" style="margin: 20px 0 24px 0;">
                    <div class="feature-card" style="background-color: #0f181d; border: 1px solid #223742; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px;">
                        <div class="feature-title" style="font-size: 15px; font-weight: 700; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 6px;">📖 Diário Emocional</div>
                        <p class="feature-desc" style="font-size: 13px; color: #e2e8f0 !important; -webkit-text-fill-color: #e2e8f0 !important; margin: 0; line-height: 1.5;">Registre seus sentimentos, pensamentos e acompanhe sua evolução pessoal com privacidade total.</p>
                    </div>
                    <div class="feature-card" style="background-color: #0f181d; border: 1px solid #223742; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px;">
                        <div class="feature-title" style="font-size: 15px; font-weight: 700; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 6px;">💬 Chat & Apoio Acolhedor</div>
                        <p class="feature-desc" style="font-size: 13px; color: #e2e8f0 !important; -webkit-text-fill-color: #e2e8f0 !important; margin: 0; line-height: 1.5;">Converse, desabafe e encontre clareza com nossa inteligência acolhedora sempre disponível.</p>
                    </div>
                    <div class="feature-card" style="background-color: #0f181d; border: 1px solid #223742; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px;">
                        <div class="feature-title" style="font-size: 15px; font-weight: 700; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 6px;">👥 Comunidade Segura</div>
                        <p class="feature-desc" style="font-size: 13px; color: #e2e8f0 !important; -webkit-text-fill-color: #e2e8f0 !important; margin: 0; line-height: 1.5;">Compartilhe relatos, leia histórias inspiradoras e receba apoio de pessoas que entendem você.</p>
                    </div>
                    <div class="feature-card" style="background-color: #0f181d; border: 1px solid #223742; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px;">
                        <div class="feature-title" style="font-size: 15px; font-weight: 700; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin-bottom: 6px;">🛡️ Sua Segurança Sempre em 1º Lugar</div>
                        <p class="feature-desc" style="font-size: 13px; color: #e2e8f0 !important; -webkit-text-fill-color: #e2e8f0 !important; margin: 0; line-height: 1.5;">Você pode ativar a Verificação em Duas Etapas (2FA) e personalizar suas preferências no seu perfil.</p>
                    </div>
                </div>

                <div class="dados-conta" style="background-color: #0e2b34; border: 1px solid #22d3ee; border-radius: 12px; padding: 15px 18px; font-size: 14px; color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; margin: 22px 0;">
                    <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">Detalhes do seu cadastro:</strong><br>
                    <span style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">E-mail: <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">{$emailEsc}</strong> • Data de adesão: <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">{$dataHora}</strong></span>
                </div>

                <div class="botao-wrap" style="text-align: center; margin: 28px 0 10px 0;">
                    <a href="{$linkInicio}" class="botao-cta" target="_blank" style="display: inline-block; background-color: #2b7a8c; background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; text-decoration: none; padding: 14px 34px; border-radius: 30px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);">
                        <span style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important; text-decoration: none; font-weight: 700;">Começar a Explorar o HelpFull</span>
                    </a>
                </div>
            </div>
            <div class="footer" style="background-color: #0f171c; padding: 22px 24px; text-align: center; border-top: 1px solid #233742; font-size: 12px; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">
                <p style="margin: 0 0 6px 0; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">HelpFull — Promovendo bem-estar, acolhimento e conexões reais.</p>
                <p style="margin: 0; color: #cbd5e1 !important; -webkit-text-fill-color: #cbd5e1 !important;">Precisa de ajuda ou suporte? Escreva para <a href="mailto:contatohelpfull@gmail.com" style="color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important; text-decoration: underline; font-weight: 600;"><span style="color: #38bdf8 !important; -webkit-text-fill-color: #38bdf8 !important;">contatohelpfull@gmail.com</span></a></p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Função base de envio de e-mail do sistema.
 * Suporta SMTP Direto e mail() nativo, com fallback inteligente e logs em scratch.
 */
function enviarEmailBase($email, $nome, $assunto, $corpoHtml, $tipo = 'geral') {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'sucesso'      => false,
            'enviado_mail' => false,
            'erro'         => 'E-mail inválido ou não informado.'
        ];
    }

    $remetente = defined('SMTP_FROM') ? SMTP_FROM : 'contatohelpfull@gmail.com';
    $nomeRemetente = 'HelpFull';

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $nomeRemetente . ' <' . $remetente . '>',
        'Reply-To: contatohelpfull@gmail.com',
        'X-Mailer: PHP/' . phpversion()
    ];

    $enviado = false;
    $erro = null;

    // 1. Tenta envio por SMTP direto se configurado em config_keys.php
    if (defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS')) {
        try {
            $enviado = enviarViaSMTPDirect(
                SMTP_HOST,
                defined('SMTP_PORT') ? SMTP_PORT : 587,
                SMTP_USER,
                SMTP_PASS,
                $remetente,
                $email,
                $assunto,
                $corpoHtml
            );
        } catch (Exception $e) {
            $erro = 'SMTP: ' . $e->getMessage();
        }
    }

    // 2. Se não enviado por SMTP direto, tenta função mail() nativa
    if (!$enviado) {
        $tentarMail = true;
        // No Windows localhost, se a porta SMTP do php.ini não estiver escutando, evita timeout
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $smtpHost = ini_get('SMTP') ?: 'localhost';
            $smtpPort = (int)(ini_get('smtp_port') ?: 25);
            $testSock = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 0.2);
            if ($testSock) {
                fclose($testSock);
            } else {
                $tentarMail = false;
            }
        }

        if ($tentarMail) {
            try {
                $enviado = @mail($email, $assunto, $corpoHtml, implode("\r\n", $headers));
            } catch (Exception $e) {
                $erro = 'Mail: ' . $e->getMessage();
            }
        }
    }

    // 3. Registra log para auditoria e desenvolvimento local
    $logDir = __DIR__ . '/scratch';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }

    $statusEnvio = $enviado ? 'ENVIADO' : 'SIMULADO_LOCAL';
    $logMsg = date('Y-m-d H:i:s') . " | [{$tipo}] | [{$statusEnvio}] | Destino: {$email} ({$nome}) | Assunto: {$assunto}\n";
    @file_put_contents($logDir . '/emails_enviados.log', $logMsg, FILE_APPEND);

    return [
        'sucesso'      => true,
        'enviado_mail' => $enviado,
        'erro'         => $erro
    ];
}

/**
 * Envia o código de 2FA para o e-mail do usuário
 */
function enviarEmail2FA($email, $nome, $codigo) {
    $assunto = "Seu código de login HelpFull: " . $codigo;
    $corpoHtml = montarHtmlEmail2FA($nome, $codigo);

    $resultado = enviarEmailBase($email, $nome, $assunto, $corpoHtml, '2FA');

    $isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || 
                   strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                   php_sapi_name() === 'cli';

    if (!$resultado['enviado_mail']) {
        @file_put_contents(
            __DIR__ . '/scratch/last_2fa_code.log',
            date('Y-m-d H:i:s') . " | $email | $codigo\n",
            FILE_APPEND
        );
    }

    $resultado['debug_codigo'] = ($isLocalhost && !$resultado['enviado_mail']) ? $codigo : null;
    return $resultado;
}

/**
 * Envia notificação de Novo Login realizado na conta
 */
function enviarEmailNovoLogin($email, $nome, $detalhes = []) {
    try {
        $ip = $detalhes['ip'] ?? obterIpCliente();
        $dispInfo = detectarDispositivoNavegador($detalhes['user_agent'] ?? null);
        $dispositivo = $detalhes['dispositivo'] ?? $dispInfo['resumo'];
        $dataHora = $detalhes['data_hora'] ?? date('d/m/Y \à\s H:i:s');
        $metodo = $detalhes['metodo'] ?? 'Senha e E-mail';

        $dadosTemplate = [
            'ip'          => $ip,
            'dispositivo' => $dispositivo,
            'data_hora'   => $dataHora,
            'metodo'      => $metodo
        ];

        $assunto = "🔔 Novo acesso à sua conta HelpFull ({$dataHora})";
        $corpoHtml = montarHtmlEmailNovoLogin($nome, $dadosTemplate);

        return enviarEmailBase($email, $nome, $assunto, $corpoHtml, 'NOVO_LOGIN');
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}

/**
 * Envia e-mail de Boas-Vindas para nova conta criada
 */
function enviarEmailBoasVindas($email, $nome) {
    try {
        $assunto = "🌿 Bem-vindo(a) ao HelpFull! Sua conta foi criada com sucesso";
        $corpoHtml = montarHtmlEmailBoasVindas($nome, $email);

        return enviarEmailBase($email, $nome, $assunto, $corpoHtml, 'CADASTRO_NOVO');
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}

/**
 * Envio direto via socket SMTP com suporte a STARTTLS / SSL
 */
function enviarViaSMTPDirect($host, $port, $user, $pass, $from, $to, $subject, $html) {
    $timeout = 8;
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) return false;

    $ler = function() use ($socket) {
        $resp = '';
        while ($str = @fgets($socket, 515)) {
            $resp .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $resp;
    };

    $enviar = function($cmd) use ($socket) {
        @fputs($socket, $cmd . "\r\n");
    };

    $ler();
    $enviar("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    $ler();

    if ($port == 587) {
        $enviar("STARTTLS");
        $ler();
        if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        $enviar("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $ler();
    }

    $enviar("AUTH LOGIN");
    $ler();
    $enviar(base64_encode($user));
    $ler();
    $enviar(base64_encode($pass));
    $ler();

    $enviar("MAIL FROM: <$from>");
    $ler();
    $enviar("RCPT TO: <$to>");
    $ler();
    $enviar("DATA");
    $ler();

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: HelpFull <$from>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "\r\n";

    $enviar($headers . $html . "\r\n.");
    $resp = $ler();
    $enviar("QUIT");
    fclose($socket);

    return strpos($resp, '250') !== false;
}
