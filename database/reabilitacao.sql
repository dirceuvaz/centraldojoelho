-- Criação da tabela reabilitacao
CREATE TABLE IF NOT EXISTS reabilitacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    texto TEXT NOT NULL,
    momento VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_medico INT,
    FOREIGN KEY (id_medico) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir dados iniciais
INSERT INTO reabilitacao (titulo, texto, momento, tipo) VALUES
('Primeira Semana', 'Pós-Operatório PRIMEIRA SEMANA Vejo que você se encontra na primeira semana...', 'Primeira Semana', 'Joelho'),
('Segunda Semana', 'Olá, você passou pela fase mais difícil da reabilitação. Nessa semana...', 'Segunda Semana', 'Joelho'),
('Terceira Semana', 'Olá, você se encontra na terceira semana de operado. Esses são seus objetivos:...', 'Terceira Semana', 'Joelho'),
('Quarta Semana', 'Quarta Semana - Olá, você está na quarta semana de operado. Seus objetivos...', 'Quarta Semana', 'Joelho'),
('Quinta e Sexta Semana', 'Quinta e Sexta Semana Você passou do primeiro mês de operado. Nessa etapa...', 'Quinta e Sexta Semana', 'Joelho'),
('SEXTA A DÉCIMA SEMANA', 'SEXTA A DÉCIMA SEMANA Você já tem mais de um mês e meio de operado...', 'Sexta a Décima Semana', 'Joelho'),
('Décima Primeira a Vigésima Semana', 'Olá, você já está passado de 3 meses de operado. Iniciaremos agora uma...', 'Décima primeira a Vigésima Semana', 'Joelho'),
('Sexto Mês', 'Olá, já temos mais de 6 meses de operado. E hora de seguir a vida normal e preparar...', 'Sexto Mês', 'Joelho'),
('Pré Operatório', 'Sua cirurgia ainda não aconteceu. Até lá, preparamos um programa de exercícios...', 'A Cirurgia', 'Joelho');
