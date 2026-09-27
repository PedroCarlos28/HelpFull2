<?php
// =======================================================
// HELPFULL - HELPER DE SESSÕES E DISPOSITIVOS CONECTADOS
// Design do sistema HelpFull - Sem emojis
// =======================================================

if (!defined('HELPFULL_SESSION_HELPER')) {
    define('HELPFULL_SESSION_HELPER', true);
}

if (!function_exists('obterIpCliente')) {
    /**
     * Obtém o IP real do cliente, considerando proxies e Cloudflare
     */
    function obterIpCliente() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

if (!function_exists('mascararIp')) {
    /**
     * Mascara o IP para privacidade (ex: 189.120.***.*** ou 2804:14d:***)
     */
    function mascararIp($ip) {
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return 'Rede local';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $partes = explode('.', $ip);
            if (count($partes) === 4) {
                return $partes[0] . '.' . $partes[1] . '.*.*';
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $partes = explode(':', $ip);
            if (count($partes) >= 2) {
                return $partes[0] . ':' . $partes[1] . ':****:****';
            }
        }

        return $ip;
    }
}

/**
 * Detecta Sistema Operacional, Navegador e Tipo de Dispositivo
 */
function detectarDispositivoInfo($userAgent = null) {
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    $so = 'Desconhecido';
    $navegador = 'Desconhecido';
    $tipo = 'desktop';

    // 1. Tipo de Dispositivo
    $uaLower = strtolower($userAgent);
    if (preg_match('/(ipad|tablet|(android(?!.*mobile)))/i', $userAgent)) {
        $tipo = 'tablet';
    } elseif (preg_match('/(mobile|iphone|ipod|android.*mobile|blackberry|bb10|windows phone|opera mini)/i', $userAgent)) {
        $tipo = 'mobile';
    } else {
        $tipo = 'desktop';
    }

    // 2. Sistema Operacional
    if (preg_match('/windows nt 10\.0/i', $userAgent)) {
        $so = 'Windows 11 / 10';
    } elseif (preg_match('/windows nt 6\.3/i', $userAgent)) {
        $so = 'Windows 8.1';
    } elseif (preg_match('/windows nt 6\.2/i', $userAgent)) {
        $so = 'Windows 8';
    } elseif (preg_match('/windows nt 6\.1/i', $userAgent)) {
        $so = 'Windows 7';
    } elseif (preg_match('/windows/i', $userAgent)) {
        $so = 'Windows';
    } elseif (preg_match('/iphone/i', $userAgent)) {
        $so = 'iOS (iPhone)';
    } elseif (preg_match('/ipad/i', $userAgent)) {
        $so = 'iPadOS';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $so = 'macOS';
    } elseif (preg_match('/android/i', $userAgent)) {
        $so = 'Android';
    } elseif (preg_match('/cros/i', $userAgent)) {
        $so = 'ChromeOS';
    } elseif (preg_match('/linux/i', $userAgent)) {
        $so = 'Linux';
    }

    // 3. Navegador
    if (preg_match('/edg\/([0-9\.]+)/i', $userAgent)) {
        $navegador = 'Microsoft Edge';
    } elseif (preg_match('/samsungbrowser\/([0-9\.]+)/i', $userAgent)) {
        $navegador = 'Samsung Internet';
    } elseif (preg_match('/opr\/([0-9\.]+)|opera\/([0-9\.]+)/i', $userAgent)) {
        $navegador = 'Opera';
    } elseif (preg_match('/brave/i', $userAgent)) {
        $navegador = 'Brave';
    } elseif (preg_match('/chrome\/([0-9\.]+)|crios\/([0-9\.]+)/i', $userAgent)) {
        $navegador = 'Google Chrome';
    } elseif (preg_match('/firefox\/([0-9\.]+)|fxios\/([0-9\.]+)/i', $userAgent)) {
        $navegador = 'Mozilla Firefox';
    } elseif (preg_match('/safari\/([0-9\.]+)/i', $userAgent) && !preg_match('/chrome|crios/i', $userAgent)) {
        $navegador = 'Safari';
    }

    $dispositivo = $so . ' · ' . $navegador;

    return [
        'dispositivo' => $dispositivo,
        'sistema_operacional' => $so,
        'navegador' => $navegador,
        'tipo_dispositivo' => $tipo
    ];
}

/**
 * Registra uma nova sessão de dispositivo para o usuário
 */
function registrarNovaSessao($pdo, $usuarioId) {
    if (!$pdo || empty($usuarioId)) {
        return null;
    }

    try {
        $info = detectarDispositivoInfo();
        $ip = obterIpCliente();
        $tokenSessao = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO sessoes_usuario (
                usuario_id,
                token_sessao,
                dispositivo,
                navegador,
                sistema_operacional,
                tipo_dispositivo,
                ip_origem,
                criado_em,
                ultimo_acesso,
                ativo
            ) VALUES (
                :usuario_id,
                :token_sessao,
                :dispositivo,
                :navegador,
                :sistema_operacional,
                :tipo_dispositivo,
                :ip_origem,
                NOW(),
                NOW(),
                TRUE
            )
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token_sessao' => $tokenSessao,
            ':dispositivo' => $info['dispositivo'],
            ':navegador' => $info['navegador'],
            ':sistema_operacional' => $info['sistema_operacional'],
            ':tipo_dispositivo' => $info['tipo_dispositivo'],
            ':ip_origem' => $ip
        ]);

        return $tokenSessao;
    } catch (Exception $e) {
        error_log("Erro ao registrar sessao: " . $e->getMessage());
        return null;
    }
}

/**
 * Valida se a sessão atual ainda está ativa no banco de dados.
 * Atualiza o último acesso periodicamente (throttled a cada 2 minutos).
 */
function validarSessaoAtiva($pdo, $usuarioId, $tokenSessao) {
    if (!$pdo || empty($usuarioId) || empty($tokenSessao)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, ativo, EXTRACT(EPOCH FROM (NOW() - ultimo_acesso)) as segundos_desde_acesso
            FROM sessoes_usuario
            WHERE usuario_id = :usuario_id AND token_sessao = :token_sessao
            LIMIT 1
        ");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token_sessao' => $tokenSessao
        ]);

        $sessao = $stmt->fetch();
        if (!$sessao || empty($sessao['ativo'])) {
            return false;
        }

        // Atualiza ultimo_acesso caso tenha passado mais de 120 segundos
        if (isset($sessao['segundos_desde_acesso']) && $sessao['segundos_desde_acesso'] > 120) {
            $stmtUp = $pdo->prepare("UPDATE sessoes_usuario SET ultimo_acesso = NOW() WHERE id = :id");
            $stmtUp->execute([':id' => $sessao['id']]);
        }

        return true;
    } catch (Exception $e) {
        error_log("Erro ao validar sessao: " . $e->getMessage());
        // Se houver falha transitória de conexão, não desloga imediatamente
        return true;
    }
}

/**
 * Lista todas as sessões ativas do usuário
 */
function listarSessoesUsuario($pdo, $usuarioId, $tokenAtual = null) {
    if (!$pdo || empty($usuarioId)) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                dispositivo,
                navegador,
                sistema_operacional,
                tipo_dispositivo,
                ip_origem,
                criado_em,
                ultimo_acesso,
                ativo,
                token_sessao
            FROM sessoes_usuario
            WHERE usuario_id = :usuario_id AND ativo = TRUE
            ORDER BY 
                CASE WHEN token_sessao = :token_atual THEN 0 ELSE 1 END,
                ultimo_acesso DESC
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token_atual' => $tokenAtual ?? ''
        ]);

        $sessoes = $stmt->fetchAll();
        $resultado = [];

        foreach ($sessoes as $s) {
            $isAtual = ($tokenAtual !== null && $s['token_sessao'] === $tokenAtual);
            $tempoFormatado = formatarTempoRelativo($s['ultimo_acesso']);
            $criadoFormatado = date('d/m/Y \à\s H:i', strtotime($s['criado_em']));

            $resultado[] = [
                'id' => (int)$s['id'],
                'dispositivo' => $s['dispositivo'],
                'navegador' => $s['navegador'],
                'sistema_operacional' => $s['sistema_operacional'],
                'tipo_dispositivo' => $s['tipo_dispositivo'],
                'ip_mascarado' => mascararIp($s['ip_origem']),
                'ip_real' => $s['ip_origem'],
                'criado_em' => $s['criado_em'],
                'criado_em_formatado' => $criadoFormatado,
                'ultimo_acesso' => $s['ultimo_acesso'],
                'ultimo_acesso_relativo' => $tempoFormatado,
                'is_atual' => $isAtual
            ];
        }

        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao listar sessoes: " . $e->getMessage());
        return [];
    }
}

/**
 * Desconecta uma sessão específica do usuário
 */
function desconectarSessao($pdo, $usuarioId, $identificadorSessao) {
    if (!$pdo || empty($usuarioId) || empty($identificadorSessao)) {
        return false;
    }

    try {
        if (is_numeric($identificadorSessao)) {
            $stmt = $pdo->prepare("
                UPDATE sessoes_usuario 
                SET ativo = FALSE 
                WHERE id = :id AND usuario_id = :usuario_id
            ");
            $stmt->execute([
                ':id' => (int)$identificadorSessao,
                ':usuario_id' => $usuarioId
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE sessoes_usuario 
                SET ativo = FALSE 
                WHERE token_sessao = :token AND usuario_id = :usuario_id
            ");
            $stmt->execute([
                ':token' => $identificadorSessao,
                ':usuario_id' => $usuarioId
            ]);
        }

        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Erro ao desconectar sessao: " . $e->getMessage());
        return false;
    }
}

/**
 * Desconecta todas as outras sessões ativas, exceto a atual
 */
function desconectarOutrasSessoes($pdo, $usuarioId, $tokenAtual) {
    if (!$pdo || empty($usuarioId) || empty($tokenAtual)) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE sessoes_usuario
            SET ativo = FALSE
            WHERE usuario_id = :usuario_id 
              AND token_sessao != :token_atual
              AND ativo = TRUE
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token_atual' => $tokenAtual
        ]);

        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Erro ao desconectar outras sessoes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Formata timestamp para tempo relativo amigável em português
 */
function formatarTempoRelativo($dataTimestamp) {
    if (empty($dataTimestamp)) {
        return 'Recentemente';
    }

    $timestamp = is_numeric($dataTimestamp) ? (int)$dataTimestamp : strtotime($dataTimestamp);
    $diferenca = time() - $timestamp;

    if ($diferenca < 60) {
        return 'Agora mesmo';
    }

    $minutos = round($diferenca / 60);
    if ($minutos < 60) {
        return $minutos === 1 ? 'Há 1 minuto' : "Há {$minutos} minutos";
    }

    $horas = round($diferenca / 3600);
    if ($horas < 24) {
        return $horas === 1 ? 'Há 1 hora' : "Há {$horas} horas";
    }

    $dias = round($diferenca / 86400);
    if ($dias < 7) {
        return $dias === 1 ? 'Ontem' : "Há {$dias} dias";
    }

    return date('d/m/Y \à\s H:i', $timestamp);
}
