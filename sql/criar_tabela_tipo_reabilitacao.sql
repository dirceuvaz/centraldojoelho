-- Criação da tabela tipo_reabilitacao
CREATE TABLE IF NOT EXISTS tipo_reabilitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserção de tipos comuns de reabilitação
INSERT INTO tipo_reabilitacao (nome) VALUES
('Pós-operatório'),
('Lesão ligamentar'),
('Lesão meniscal'),
('Lesão muscular'),
('Artrose'),
('Tendinite'),
('Condromalácia patelar'),
('Instabilidade articular'),
('Fortalecimento muscular'),
('Reabilitação esportiva');
