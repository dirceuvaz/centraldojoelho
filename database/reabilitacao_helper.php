<?php
/**
 * Classe auxiliar para manipulação de dados de reabilitação
 */
class ReabilitacaoHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Determina a etapa de reabilitação com base na data da cirurgia
     */
    public function determinarEtapaReabilitacao($data_cirurgia) {
        $hoje = new DateTime();
        $data_cirurgia = new DateTime($data_cirurgia);
        $diferenca = $hoje->diff($data_cirurgia);
        
        // Lógica para determinar a etapa baseada nos dias desde a cirurgia
        if ($diferenca->days <= 7) {
            return 1; // Primeira semana
        } elseif ($diferenca->days <= 30) {
            return 2; // Primeiro mês
        } elseif ($diferenca->days <= 90) {
            return 3; // Três meses
        } else {
            return 4; // Mais de três meses
        }
    }

    /**
     * Obtém os tipos de reabilitação disponíveis
     */
    public function getTiposReabilitacao() {
        $sql = "SELECT id, descricao FROM tipos_reabilitacao ORDER BY descricao";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém os momentos de reabilitação disponíveis
     */
    public function getMomentosReabilitacao() {
        $sql = "SELECT id, descricao FROM momentos_reabilitacao ORDER BY ordem";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo registro de reabilitação
     */
    public function criarReabilitacao($dados) {
        $sql = "INSERT INTO reabilitacao (id_paciente, tipo_problema, momento, titulo, texto, duracao_dias, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dados['id_paciente'],
            $dados['tipo_problema'],
            $dados['momento'],
            $dados['titulo'],
            $dados['texto'],
            $dados['duracao_dias'],
            $dados['status']
        ]);
    }

    /**
     * Atualiza um registro de reabilitação existente
     */
    public function atualizarReabilitacao($id, $dados) {
        $sql = "UPDATE reabilitacao 
                SET tipo_problema = ?, momento = ?, titulo = ?, texto = ?, 
                    duracao_dias = ?, status = ?, data_atualizacao = CURRENT_TIMESTAMP 
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dados['tipo_problema'],
            $dados['momento'],
            $dados['titulo'],
            $dados['texto'],
            $dados['duracao_dias'],
            $dados['status'],
            $id
        ]);
    }

    /**
     * Obtém os detalhes de uma reabilitação específica
     */
    public function getReabilitacao($id) {
        $sql = "SELECT r.*, tr.descricao as tipo_descricao, mr.descricao as momento_descricao 
                FROM reabilitacao r 
                LEFT JOIN tipos_reabilitacao tr ON r.tipo_problema = tr.id 
                LEFT JOIN momentos_reabilitacao mr ON r.momento = mr.id 
                WHERE r.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém a reabilitação atual de um paciente
     */
    public function getReabilitacaoAtualPaciente($id_paciente) {
        $sql = "SELECT r.*, tr.descricao as tipo_descricao, mr.descricao as momento_descricao 
                FROM reabilitacao r 
                LEFT JOIN tipos_reabilitacao tr ON r.tipo_problema = tr.id 
                LEFT JOIN momentos_reabilitacao mr ON r.momento = mr.id 
                WHERE r.id_paciente = ? AND r.status = 'ativo' 
                ORDER BY r.data_criacao DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_paciente]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista o histórico de reabilitação de um paciente
     */
    public function getHistoricoReabilitacao($id_paciente) {
        $sql = "SELECT r.*, tr.descricao as tipo_descricao, mr.descricao as momento_descricao 
                FROM reabilitacao r 
                LEFT JOIN tipos_reabilitacao tr ON r.tipo_problema = tr.id 
                LEFT JOIN momentos_reabilitacao mr ON r.momento = mr.id 
                WHERE r.id_paciente = ? 
                ORDER BY r.data_criacao DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_paciente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
