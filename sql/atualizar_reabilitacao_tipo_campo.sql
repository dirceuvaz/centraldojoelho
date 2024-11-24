-- Adicionar coluna tipo_campo se não existir
ALTER TABLE reabilitacao 
ADD COLUMN IF NOT EXISTS tipo_campo INT NOT NULL DEFAULT 1 AFTER momento;

-- Atualizar os valores possíveis da coluna momento
ALTER TABLE reabilitacao MODIFY COLUMN momento ENUM(
    'Pré Operatório',
    'Primeira Semana',
    'Segunda Semana',
    'Terceira Semana',
    'Quarta Semana',
    'Quinta Semana',
    'Sexta Semana'
) NOT NULL;
