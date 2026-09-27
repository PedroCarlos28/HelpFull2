-- =======================================================
-- MIGRAÇÃO: Verificação em Duas Etapas (2FA) por E-mail
-- Execute este script no SQL Editor do Supabase se necessário:
-- =======================================================

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS dois_fatores_ativo BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS codigo_2fa VARCHAR(10) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS codigo_2fa_expira TIMESTAMPTZ DEFAULT NULL;
