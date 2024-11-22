-- Criar coluna temporária para o ID do médico
ALTER TABLE pacientes ADD COLUMN medico_id INT NULL;

-- Atualizar IDs dos médicos baseado nos nomes
UPDATE pacientes p
JOIN usuarios u ON p.medico = u.nome AND u.tipo_usuario = 'medico'
SET p.medico_id = u.id;

-- Remover a coluna antiga
ALTER TABLE pacientes DROP COLUMN medico;

-- Renomear a nova coluna
ALTER TABLE pacientes CHANGE medico_id medico INT NOT NULL;

-- Adicionar a chave estrangeira
ALTER TABLE pacientes
ADD CONSTRAINT fk_paciente_medico
FOREIGN KEY (medico) REFERENCES usuarios(id);
