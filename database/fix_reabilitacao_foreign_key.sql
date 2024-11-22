-- Remover a foreign key antiga
ALTER TABLE reabilitacao DROP FOREIGN KEY reabilitacao_ibfk_1;

-- Alterar a referência da coluna id_medico para a tabela correta
ALTER TABLE reabilitacao MODIFY id_medico INT,
ADD CONSTRAINT fk_reabilitacao_medico FOREIGN KEY (id_medico) REFERENCES medicos(id);

-- Atualizar os registros existentes para associar ao primeiro médico encontrado
UPDATE reabilitacao r
SET r.id_medico = (
    SELECT m.id 
    FROM medicos m 
    INNER JOIN usuarios u ON m.id_usuario = u.id 
    WHERE u.tipo_usuario = 'medico' 
    LIMIT 1
)
WHERE r.id_medico IS NULL;
