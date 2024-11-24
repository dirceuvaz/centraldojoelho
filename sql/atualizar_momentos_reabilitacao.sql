-- Atualizar a coluna momento para incluir pré-operatório
ALTER TABLE reabilitacao MODIFY COLUMN momento ENUM(
    'pre-operatorio',
    'Primeira Semana',
    'Segunda Semana',
    'Terceira Semana',
    'Quarta Semana',
    'Quinta Semana',
    'Sexta Semana'
) NOT NULL;
