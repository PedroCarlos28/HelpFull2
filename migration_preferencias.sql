-- ============================================================
-- Migration: Vincular Preferências e Cor do Tema à Conta do Usuário
-- Data: 2026-11-17
-- ============================================================

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS preferencias JSONB DEFAULT '{}'::jsonb;
