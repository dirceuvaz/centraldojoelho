-- Remover a foreign key antiga
ALTER TABLE reabilitacao DROP FOREIGN KEY reabilitacao_ibfk_1;

-- Alterar a referência da coluna id_medico
ALTER TABLE reabilitacao MODIFY id_medico INT,
ADD CONSTRAINT fk_reabilitacao_medico FOREIGN KEY (id_medico) REFERENCES medicos(id);

-- Atualizar os registros existentes para associar a um médico
UPDATE reabilitacao r
JOIN medicos m ON m.id = (SELECT id FROM medicos LIMIT 1)
SET r.id_medico = m.id
WHERE r.id_medico IS NULL;
