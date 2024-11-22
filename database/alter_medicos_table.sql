-- Primeiro, vamos adicionar a coluna id_usuario na tabela medicos
ALTER TABLE medicos ADD COLUMN id_usuario INT;

-- Adicionar a chave estrangeira
ALTER TABLE medicos
ADD CONSTRAINT fk_medico_usuario
FOREIGN KEY (id_usuario) REFERENCES usuarios(id);

-- Atualizar os registros existentes
UPDATE medicos m
INNER JOIN usuarios u ON u.email = m.email
SET m.id_usuario = u.id
WHERE u.tipo_usuario = 'medico';

-- Tornar a coluna id_usuario NOT NULL após a atualização
ALTER TABLE medicos MODIFY COLUMN id_usuario INT NOT NULL;

-- Adicionar um índice para melhor performance
CREATE INDEX idx_medico_usuario ON medicos(id_usuario);

-- Remover colunas redundantes que já existem na tabela usuarios
ALTER TABLE medicos
DROP COLUMN email,
DROP COLUMN senha;
