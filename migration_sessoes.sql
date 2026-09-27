-- =======================================================
-- MIGRAÇÃO: Gerenciamento de Dispositivos e Sessões
-- =======================================================

CREATE TABLE IF NOT EXISTS sessoes_usuario (
    id SERIAL PRIMARY KEY,
    usuario_id UUID NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_sessao VARCHAR(128) NOT NULL UNIQUE,
    dispositivo VARCHAR(150) NOT NULL,
    navegador VARCHAR(100) NOT NULL,
    sistema_operacional VARCHAR(100) NOT NULL,
    tipo_dispositivo VARCHAR(50) DEFAULT 'desktop',
    ip_origem VARCHAR(64) DEFAULT NULL,
    criado_em TIMESTAMPTZ DEFAULT NOW(),
    ultimo_acesso TIMESTAMPTZ DEFAULT NOW(),
    ativo BOOLEAN DEFAULT TRUE
);

CREATE INDEX IF NOT EXISTS idx_sessoes_usuario_id ON sessoes_usuario(usuario_id);
CREATE INDEX IF NOT EXISTS idx_sessoes_token ON sessoes_usuario(token_sessao);
