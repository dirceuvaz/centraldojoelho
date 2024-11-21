-- Criação da tabela de evolução do paciente
CREATE TABLE IF NOT EXISTS evolucao_paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_medico INT NOT NULL,
    descricao TEXT NOT NULL,
    data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medico) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_paciente (id_paciente),
    INDEX idx_medico (id_medico),
    INDEX idx_data (data_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários da tabela e colunas para documentação
ALTER TABLE evolucao_paciente
COMMENT 'Registros de evolução e acompanhamento dos pacientes';

ALTER TABLE evolucao_paciente
    MODIFY COLUMN id INT AUTO_INCREMENT COMMENT 'Identificador único do registro de evolução',
    MODIFY COLUMN id_paciente INT NOT NULL COMMENT 'Referência ao ID do paciente na tabela pacientes',
    MODIFY COLUMN id_medico INT NOT NULL COMMENT 'Referência ao ID do médico na tabela usuarios',
    MODIFY COLUMN descricao TEXT NOT NULL COMMENT 'Descrição detalhada da evolução do paciente',
    MODIFY COLUMN data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora do registro da evolução';
