-- Adicionar coluna sequencia na tabela reabilitacao
ALTER TABLE reabilitacao
ADD COLUMN sequencia INT DEFAULT 0 AFTER momento;
