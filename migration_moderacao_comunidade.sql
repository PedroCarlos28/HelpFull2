-- ============================================================
-- Migration: Sistema de Moderação da Comunidade HelpFull
-- Data: 2026-11-12
-- NOTA: usuarios.id é UUID
-- ============================================================

-- Tabela de avisos/punições por usuário (UPSERT — 1 linha por user)
CREATE TABLE IF NOT EXISTS moderacao_comunidade (
    id              SERIAL PRIMARY KEY,
    usuario_id      UUID NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    nivel           INTEGER NOT NULL DEFAULT 1 CHECK (nivel IN (1, 2, 3)),
    motivo          TEXT NOT NULL,
    admin_id        UUID REFERENCES usuarios(id) ON DELETE SET NULL,
    post_id         UUID,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    expira_avisos_em TIMESTAMPTZ,
    UNIQUE(usuario_id)
);
CREATE INDEX IF NOT EXISTS idx_moderacao_usuario ON moderacao_comunidade(usuario_id);
CREATE INDEX IF NOT EXISTS idx_moderacao_nivel ON moderacao_comunidade(nivel);

-- Histórico de ações de moderação (log imutável)
CREATE TABLE IF NOT EXISTS moderacao_historico (
    id              SERIAL PRIMARY KEY,
    usuario_id      UUID NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    admin_id        UUID REFERENCES usuarios(id) ON DELETE SET NULL,
    acao            VARCHAR(50) NOT NULL,
    nivel_anterior  INTEGER,
    nivel_novo      INTEGER,
    motivo          TEXT,
    post_id         UUID,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_mod_hist_usuario ON moderacao_historico(usuario_id);
CREATE INDEX IF NOT EXISTS idx_mod_hist_criado ON moderacao_historico(criado_em);

-- Controle de regras aceitas pelo usuário
CREATE TABLE IF NOT EXISTS comunidade_regras_aceitas (
    usuario_id      UUID PRIMARY KEY REFERENCES usuarios(id) ON DELETE CASCADE,
    aceito_em       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Mensagens prontas para admin escolher ao punir
CREATE TABLE IF NOT EXISTS moderacao_mensagens_prontas (
    id          SERIAL PRIMARY KEY,
    titulo      VARCHAR(100) NOT NULL,
    mensagem    TEXT NOT NULL,
    ativo       BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO moderacao_mensagens_prontas (titulo, mensagem) VALUES
('Conteúdo ofensivo', 'Seu post foi removido por conter conteúdo ofensivo, agressivo ou que viola o respeito mútuo da nossa comunidade.'),
('Spam ou publicidade', 'Seu post foi removido por ser identificado como spam ou conteúdo publicitário não autorizado.'),
('Desinformação', 'Seu post foi removido por conter informações falsas ou enganosas que podem prejudicar outros membros.'),
('Conteúdo inadequado', 'Seu post foi removido por conter conteúdo inadequado para o ambiente da nossa comunidade de saúde mental.'),
('Assédio a membros', 'Seu post foi removido por conter assédio, intimidação ou comportamento inadequado direcionado a outros membros.'),
('Fora do escopo', 'Seu post foi removido por estar fora do tema principal da comunidade HelpFull.')
ON CONFLICT DO NOTHING;
