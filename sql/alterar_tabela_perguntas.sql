ALTER TABLE perguntas
ADD COLUMN sequencia VARCHAR(75) NULL AFTER id,
ADD COLUMN id_reabilitacao INT NULL AFTER sequencia,
ADD COLUMN comentario_afirmativo VARCHAR(500) NULL AFTER resposta_paciente,
ADD COLUMN comentario_negativo VARCHAR(500) NULL AFTER comentario_afirmativo,
ADD FOREIGN KEY (id_reabilitacao) REFERENCES reabilitacao(id);
