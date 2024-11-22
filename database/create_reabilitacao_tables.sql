-- Tabela para armazenar os momentos de reabilitação (pré-operatório, pós-operatório, etc)
CREATE TABLE IF NOT EXISTS momentos_reabilitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela para armazenar os tipos de reabilitação (exercício, orientação, etc)
CREATE TABLE IF NOT EXISTS tipos_reabilitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela principal de reabilitação
CREATE TABLE IF NOT EXISTS reabilitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_medico INT NOT NULL,
    momento INT,
    tipo INT,
    orientacao TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_medico) REFERENCES medicos(id),
    FOREIGN KEY (momento) REFERENCES momentos_reabilitacao(id),
    FOREIGN KEY (tipo) REFERENCES tipos_reabilitacao(id)
);

-- Tabela de relacionamento entre pacientes e reabilitações
CREATE TABLE IF NOT EXISTS pacientes_reabilitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_reabilitacao INT NOT NULL,
    data_atribuicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id),
    FOREIGN KEY (id_reabilitacao) REFERENCES reabilitacao(id)
);

-- Inserindo alguns momentos de reabilitação padrão
INSERT IGNORE INTO momentos_reabilitacao (descricao) VALUES 
('Pré-operatório'),
('Pós-operatório imediato'),
('Pós-operatório 1-2 semanas'),
('Pós-operatório 2-4 semanas'),
('Pós-operatório 1-2 meses'),
('Pós-operatório 2-3 meses'),
('Pós-operatório 3-6 meses');

-- Inserindo alguns tipos de reabilitação padrão
INSERT IGNORE INTO tipos_reabilitacao (descricao) VALUES 
('Exercício'),
('Orientação Geral'),
('Cuidados com a Ferida'),
('Medicação'),
('Restrições de Movimento'),
('Retorno às Atividades');
