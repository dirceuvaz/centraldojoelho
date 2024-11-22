-- Adicionar coluna orientacao_tipo na tabela reabilitacao
ALTER TABLE reabilitacao ADD COLUMN orientacao_tipo INT DEFAULT NULL;

-- Adicionar índice para melhor performance
CREATE INDEX idx_orientacao_tipo ON reabilitacao(orientacao_tipo);

-- Atualizar registros existentes
UPDATE reabilitacao r
JOIN momentos_reabilitacao m ON r.momento = m.id
SET r.orientacao_tipo = CASE 
    WHEN m.descricao LIKE '%Pré-operatório%' THEN 0
    WHEN m.descricao LIKE '%1-2 semanas%' THEN 7
    WHEN m.descricao LIKE '%2-4 semanas%' THEN 14
    WHEN m.descricao LIKE '%1-2 meses%' THEN 30
    WHEN m.descricao LIKE '%2-3 meses%' THEN 60
    WHEN m.descricao LIKE '%3-6 meses%' THEN 90
    ELSE 180
END;
