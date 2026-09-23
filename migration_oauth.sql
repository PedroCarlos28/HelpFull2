-- =======================================================
-- MIGRAÇÃO: Suporte a Login Social (Firebase OAuth)
-- Execute este script no SQL Editor do Supabase:
-- https://supabase.com/dashboard > SQL Editor
-- =======================================================

-- 1. Tornar a coluna 'senha' opcional (nullable)
--    Usuários OAuth não têm senha local.
ALTER TABLE usuarios
    ALTER COLUMN senha DROP NOT NULL;

-- 2. Adicionar coluna para identificar o provedor OAuth
--    Ex: 'google', 'github', 'facebook', 'apple', 'microsoft'
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS oauth_provider VARCHAR(50) DEFAULT NULL;

-- 3. Adicionar coluna para o UID do Firebase (identificador único do usuário no provedor)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS firebase_uid VARCHAR(255) DEFAULT NULL;

-- 4. (Opcional) Índice único para evitar duplicatas por firebase_uid
CREATE UNIQUE INDEX IF NOT EXISTS idx_usuarios_firebase_uid
    ON usuarios (firebase_uid)
    WHERE firebase_uid IS NOT NULL;

-- =======================================================
-- Verificação: confira as colunas da tabela após a migração
-- =======================================================
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'usuarios'
ORDER BY ordinal_position;
