<?php

class ReabilitacaoHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Determina a etapa de reabilitação do paciente baseado na data da cirurgia
     * @param string $data_cirurgia Data da cirurgia no formato Y-m-d
     * @return array Informações sobre a etapa atual de reabilitação
     */
    public function determinarEtapaReabilitacao($data_cirurgia) {
        $hoje = new DateTime();
        $data_cirurgia = new DateTime($data_cirurgia);
        
        // Se a data da cirurgia ainda não chegou, retorna orientações pré-operatórias
        if ($data_cirurgia > $hoje) {
            return [
                'momento' => 'A Cirurgia',
                'descricao' => 'Pré Operatório',
                'dias_pos_cirurgia' => null
            ];
        }

        // Calcula a diferença em dias
        $diff = $hoje->diff($data_cirurgia);
        $dias = $diff->days;

        // Define o momento baseado no número de dias após a cirurgia
        if ($dias <= 7) {
            return [
                'momento' => 'Primeira Semana',
                'descricao' => 'Primeira Semana Pós-operatória',
                'dias_pos_cirurgia' => $dias
            ];
        } elseif ($dias <= 14) {
            return [
                'momento' => 'Segunda Semana',
                'descricao' => 'Segunda Semana Pós-operatória',
                'dias_pos_cirurgia' => $dias
            ];
        } elseif ($dias <= 21) {
            return [
                'momento' => 'Terceira Semana',
                'descricao' => 'Terceira Semana Pós-operatória',
                'dias_pos_cirurgia' => $dias
            ];
        } elseif ($dias <= 28) {
            return [
                'momento' => 'Quarta Semana',
                'descricao' => 'Quarta Semana Pós-operatória',
                'dias_pos_cirurgia' => $dias
            ];
        } elseif ($dias <= 42) {
            return [
                'momento' => 'Quinta e Sexta Semana',
                'descricao' => 'Quinta e Sexta Semana Pós-operatória',
                'dias_pos_cirurgia' => $dias
            ];
        } elseif ($dias <= 70) {
            return [
                'momento' => 'Sexta a Décima Semana',
                'descricao' => 'Sexta a Décima Semana Pós-operatória',
                'dias_pos_cirurgia' => $dias
            ];
        } elseif ($dias <= 140) {
            return [
                'momento' => 'Décima primeira a Vigésima Semana',
                'descricao' => 'Décima primeira a Vigésima Semana Pós-operatória',
                'dias_pos_cirurgia' => $dias
            ];
        } else {
            return [
                'momento' => 'Sexto Mês',
                'descricao' => 'Sexto Mês em diante',
                'dias_pos_cirurgia' => $dias
            ];
        }
    }

    /**
     * Busca as orientações de reabilitação para um determinado momento
     * @param string $momento Momento da reabilitação
     * @param int $id_medico ID do médico
     * @return array Lista de orientações
     */
    public function buscarOrientacoes($momento, $id_medico) {
        // Primeiro tenta buscar orientações específicas do médico
        $stmt = $this->pdo->prepare("
            SELECT r.*
            FROM reabilitacao r
            WHERE r.id_medico = ? AND r.momento = ?
            ORDER BY r.data_criacao DESC
        ");
        
        $stmt->execute([$id_medico, $momento]);
        $orientacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Se não encontrar orientações específicas do médico, busca as orientações padrão
        if (empty($orientacoes)) {
            $stmt = $this->pdo->prepare("
                SELECT r.*
                FROM reabilitacao r
                WHERE r.momento = ? AND (r.id_medico IS NULL OR r.id_medico = 0)
                ORDER BY r.data_criacao DESC
            ");
            $stmt->execute([$momento]);
            $orientacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $orientacoes;
    }

    /**
     * Atualiza o progresso da reabilitação do paciente
     * @param int $id_paciente ID do paciente
     * @param int $id_reabilitacao ID da reabilitação
     * @param string $resposta Resposta do paciente
     * @return bool
     */
    public function atualizarProgressoReabilitacao($id_paciente, $id_reabilitacao, $resposta) {
        $stmt = $this->pdo->prepare("
            INSERT INTO pacientes_reabilitacao 
            (id_paciente, id_reabilitacao, resposta, data_resposta)
            VALUES (?, ?, ?, NOW())
        ");
        
        return $stmt->execute([$id_paciente, $id_reabilitacao, $resposta]);
    }
}
