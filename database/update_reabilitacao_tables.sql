-- Removendo a tabela de relacionamento que não será mais necessária
DROP TABLE IF EXISTS pacientes_reabilitacao;

-- Atualizando a tabela de reabilitação (caso necessário)
ALTER TABLE reabilitacao MODIFY COLUMN orientacao TEXT NOT NULL;
