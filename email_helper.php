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
    <title>Código de Verificação HelpFull</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f8fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { width: 100%; padding: 40px 15px; box-sizing: border-box; }
        .card { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(43, 122, 140, 0.1); border: 1px solid #e1edf2; }
        .header { background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); padding: 35px 30px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 26px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0 0; color: #c4e9f2; font-size: 14px; }
        .content { padding: 35px 30px; color: #2d3748; line-height: 1.6; text-align: center; }
        .saudacao { font-size: 16px; color: #4a5568; margin-bottom: 20px; }
        .codigo-box { margin: 25px auto; padding: 18px 24px; background: #edf7fa; border: 2px dashed #92d0de; border-radius: 14px; display: inline-block; }
        .codigo-numero { font-size: 34px; font-weight: 800; color: #1b3d45; letter-spacing: 8px; font-family: 'Courier New', Courier, monospace; margin: 0; }
        .aviso { font-size: 13px; color: #718096; margin-top: 15px; }
        .destaque { color: #e53e3e; font-weight: 600; }
        .footer { background: #f8fafc; padding: 22px 30px; text-align: center; border-top: 1px solid #edf2f7; font-size: 12px; color: #a0aec0; }
        .footer a { color: #2b7a8c; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>HelpFull</h1>
                <p>Verificação de Segurança em Duas Etapas</p>
            </div>
            <div class="content">
                <div class="saudacao">Olá, <strong>{$nomeEsc}</strong>!</div>
                <p style="margin: 0; font-size: 15px;">Detectamos uma tentativa de login na sua conta. Use o código de 6 dígitos abaixo para concluir o acesso com segurança:</p>
                
                <div class="codigo-box">
                    <div class="codigo-numero">{$codigoEsc}</div>
                </div>

                <p class="aviso">⏱️ Este código é válido por <strong>10 minutos</strong>.</p>
                <p class="aviso" style="margin-top: 5px;">Se você não solicitou este login, <span class="destaque">não compartilhe este código</span> e recomendamos alterar sua senha imediatamente.</p>
            </div>
            <div class="footer">
                <p style="margin: 0 0 6px 0;">HelpFull — Promovendo bem-estar e conexões reais.</p>
                <p style="margin: 0;">Dúvidas ou suporte? Escreva para <a href="mailto:contatohelpfull@gmail.com">contatohelpfull@gmail.com</a></p>
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
    <title>Novo Acesso à sua Conta HelpFull</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f8fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { width: 100%; padding: 40px 15px; box-sizing: border-box; }
        .card { max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(43, 122, 140, 0.1); border: 1px solid #e1edf2; }
        .header { background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); padding: 32px 30px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 26px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0 0; color: #c4e9f2; font-size: 14px; }
        .badge-alerta { display: inline-block; background: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 12px; font-weight: 700; padding: 4px 14px; border-radius: 20px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 32px 30px; color: #2d3748; line-height: 1.6; }
        .saudacao { font-size: 17px; color: #2d3748; margin-bottom: 12px; }
        .descricao { font-size: 15px; color: #4a5568; margin: 0 0 22px 0; }
        .detalhes-tabela { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; box-sizing: border-box; margin-bottom: 24px; }
        .item-linha { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; font-size: 14px; }
        .item-linha:last-child { border-bottom: none; }
        .item-label { color: #718096; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .item-valor { color: #1a202c; font-weight: 700; text-align: right; }
        .box-info { background: #edf7fa; border-left: 4px solid #2b7a8c; padding: 14px 16px; border-radius: 8px; font-size: 13px; color: #2c5282; margin-bottom: 16px; line-height: 1.5; }
        .box-aviso { background: #fff5f5; border-left: 4px solid #e53e3e; padding: 14px 16px; border-radius: 8px; font-size: 13px; color: #9b2c2c; margin-bottom: 24px; line-height: 1.5; }
        .botao-wrap { text-align: center; margin: 25px 0 10px 0; }
        .botao-cta { display: inline-block; background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); color: #ffffff !important; text-decoration: none; padding: 13px 30px; border-radius: 30px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(43, 122, 140, 0.25); }
        .footer { background: #f8fafc; padding: 22px 30px; text-align: center; border-top: 1px solid #edf2f7; font-size: 12px; color: #a0aec0; }
        .footer a { color: #2b7a8c; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <span class="badge-alerta">🔔 Alerta de Segurança</span>
                <h1>HelpFull</h1>
                <p>Identificamos um novo login na sua conta</p>
            </div>
            <div class="content">
                <div class="saudacao">Olá, <strong>{$nomeEsc}</strong>!</div>
                <p class="descricao">Sua conta do HelpFull acabou de ser acessada. Acompanhe os detalhes da sessão abaixo:</p>

                <div class="detalhes-tabela">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="border-bottom: 1px dashed #e2e8f0;">
                            <td style="padding: 9px 0; color: #718096; font-size: 14px; font-weight: 600;">🕒 Data e Horário:</td>
                            <td style="padding: 9px 0; color: #1a202c; font-size: 14px; font-weight: 700; text-align: right;">{$dataHora}</td>
                        </tr>
                        <tr style="border-bottom: 1px dashed #e2e8f0;">
                            <td style="padding: 9px 0; color: #718096; font-size: 14px; font-weight: 600;">💻 Dispositivo:</td>
                            <td style="padding: 9px 0; color: #1a202c; font-size: 14px; font-weight: 700; text-align: right;">{$dispositivo}</td>
                        </tr>
                        <tr style="border-bottom: 1px dashed #e2e8f0;">
                            <td style="padding: 9px 0; color: #718096; font-size: 14px; font-weight: 600;">📍 Endereço IP:</td>
                            <td style="padding: 9px 0; color: #1a202c; font-size: 14px; font-weight: 700; text-align: right;"><code>{$ip}</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 9px 0; color: #718096; font-size: 14px; font-weight: 600;">🔐 Método de Login:</td>
                            <td style="padding: 9px 0; color: #2b7a8c; font-size: 14px; font-weight: 700; text-align: right;">{$metodo}</td>
                        </tr>
                    </table>
                </div>

                <div class="box-info">
                    <strong>✓ Foi você?</strong> Se foi você quem acessou sua conta, nenhuma ação adicional é necessária. Fique tranquilo(a)!
                </div>

                <div class="box-aviso">
                    <strong>⚠️ Não reconhece esta atividade?</strong> Recomendamos acessar imediatamente as configurações do seu perfil para alterar sua senha e habilitar a <strong>Verificação em Duas Etapas (2FA)</strong>.
                </div>

                <div class="botao-wrap">
                    <a href="{$linkPerfil}" class="botao-cta" target="_blank">Acessar Meu Perfil e Segurança</a>
                </div>
            </div>
            <div class="footer">
                <p style="margin: 0 0 6px 0;">HelpFull — Promovendo bem-estar, acolhimento e conexões reais.</p>
                <p style="margin: 0;">Precisa de ajuda ou suporte? Escreva para <a href="mailto:contatohelpfull@gmail.com">contatohelpfull@gmail.com</a></p>
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
    <title>Bem-vindo(a) ao HelpFull!</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f8fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { width: 100%; padding: 40px 15px; box-sizing: border-box; }
        .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(43, 122, 140, 0.1); border: 1px solid #e1edf2; }
        .header { background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); padding: 36px 30px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 28px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0 0; color: #c4e9f2; font-size: 15px; }
        .badge-welcome { display: inline-block; background: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 12px; font-weight: 700; padding: 5px 16px; border-radius: 20px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 35px 30px; color: #2d3748; line-height: 1.6; }
        .saudacao { font-size: 18px; color: #1b3d45; margin-bottom: 12px; font-weight: 700; }
        .descricao { font-size: 15px; color: #4a5568; margin: 0 0 24px 0; }
        .features-grid { margin: 20px 0 25px 0; }
        .feature-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
        .feature-title { font-size: 14px; font-weight: 700; color: #1b3d45; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .feature-desc { font-size: 13px; color: #718096; margin: 0; }
        .dados-conta { background: #edf7fa; border-radius: 12px; padding: 15px 18px; font-size: 13px; color: #2c5282; margin: 20px 0; }
        .botao-wrap { text-align: center; margin: 28px 0 10px 0; }
        .botao-cta { display: inline-block; background: linear-gradient(135deg, #1b3d45 0%, #2b7a8c 100%); color: #ffffff !important; text-decoration: none; padding: 14px 34px; border-radius: 30px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(43, 122, 140, 0.3); }
        .footer { background: #f8fafc; padding: 22px 30px; text-align: center; border-top: 1px solid #edf2f7; font-size: 12px; color: #a0aec0; }
        .footer a { color: #2b7a8c; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <span class="badge-welcome">🌿 Boas-vindas</span>
                <h1>HelpFull</h1>
                <p>Sua conta foi criada com sucesso!</p>
            </div>
            <div class="content">
                <div class="saudacao">Olá, {$nomeEsc}!</div>
                <p class="descricao">
                    Estamos muito felizes em ter você aqui. O <strong>HelpFull</strong> foi pensado para ser o seu espaço seguro de acolhimento emocional, reflexão e conexões saudáveis.
                </p>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-title">📖 Diário Emocional</div>
                        <p class="feature-desc">Registre seus sentimentos, pensamentos e acompanhe sua evolução pessoal com privacidade total.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-title">💬 Chat & Apoio Acolhedor</div>
                        <p class="feature-desc">Converse, desabafe e encontre clareza com nossa inteligência acolhedora sempre disponível.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-title">👥 Comunidade Segura</div>
                        <p class="feature-desc">Compartilhe relatos, leia histórias inspiradoras e receba apoio de pessoas que entendem você.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-title">🛡️ Sua Segurança Sempre em 1º Lugar</div>
                        <p class="feature-desc">Você pode ativar a Verificação em Duas Etapas (2FA) e personalizar suas preferências no seu perfil.</p>
                    </div>
                </div>

                <div class="dados-conta">
                    <strong>Detalhes do seu cadastro:</strong><br>
                    E-mail: <strong>{$emailEsc}</strong> • Data de adesão: <strong>{$dataHora}</strong>
                </div>

                <div class="botao-wrap">
                    <a href="{$linkInicio}" class="botao-cta" target="_blank">Começar a Explorar o HelpFull</a>
                </div>
            </div>
            <div class="footer">
                <p style="margin: 0 0 6px 0;">HelpFull — Promovendo bem-estar, acolhimento e conexões reais.</p>
                <p style="margin: 0;">Precisa de ajuda ou suporte? Escreva para <a href="mailto:contatohelpfull@gmail.com">contatohelpfull@gmail.com</a></p>
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
