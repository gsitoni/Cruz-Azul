-- Migração para recuperação de senha admin com perguntas de segurança
-- Execute este script uma única vez no banco `cruzazul`.

ALTER TABLE usuario
    ADD COLUMN IF NOT EXISTS pergunta_seguranca VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS resposta_seguranca_hash VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS tentativas_recuperacao INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS bloqueado_ate DATETIME DEFAULT NULL;
