-- Update para autenticação passwordless de administradores com Telegram
-- Adiciona campo chat_id à tabela usuario para suporte à autenticação via Telegram

-- Adiciona coluna chat_id se não existir
ALTER TABLE usuario 
ADD COLUMN IF NOT EXISTS chat_id BIGINT NULL COMMENT 'Chat ID do Telegram para autenticação passwordless';

-- Atualiza usuários administradores existentes para terem permissão adequada
UPDATE usuario 
SET permissao = 'Admin' 
WHERE permissao IS NULL OR permissao = '';

-- Garante que o campo email seja único para administradores
ALTER TABLE usuario 
ADD UNIQUE KEY IF NOT EXISTS idx_email_unique (email);

-- Comentário sobre a nova funcionalidade
ALTER TABLE usuario COMMENT = 'Tabela de usuários com suporte a autenticação passwordless via Telegram';
