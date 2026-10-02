-- =======================================================
-- MIGRAÇÃO: Termos de Consentimento e Privacidade
-- HelpFull 2026
-- Execute este script no SQL Editor do Supabase se necessário:
-- =======================================================

-- 1. Adicionar colunas de controle de aceite dos termos na tabela usuarios
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS termos_aceitos BOOLEAN DEFAULT TRUE;

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS termos_aceitos_em TIMESTAMP WITH TIME ZONE DEFAULT NOW();

-- 2. Garantir que usuários existentes tenham termos marcados como aceitos
UPDATE usuarios
SET termos_aceitos = TRUE,
    termos_aceitos_em = COALESCE(termos_aceitos_em, NOW())
WHERE termos_aceitos IS NULL;

-- =======================================================
-- Verificação das colunas
-- =======================================================
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'usuarios' AND column_name LIKE 'termos%'
ORDER BY ordinal_position;
