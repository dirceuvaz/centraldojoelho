-- Criar tabela de orientações de reabilitação
CREATE TABLE IF NOT EXISTS orientacoes_reabilitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    momento VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    texto TEXT NOT NULL,
    data_criacao DATETIME NOT NULL,
    data_atualizacao DATETIME NOT NULL,
    id_medico INT,
    FOREIGN KEY (id_medico) REFERENCES medicos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
