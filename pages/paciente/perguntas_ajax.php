<?php
// Verifica se é uma requisição AJAX
if (!isset($_GET['ajax']) || $_GET['ajax'] != 1) {
    exit('Acesso não permitido');
}

// Verifica se o usuário está logado e é um paciente
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    http_response_code(403);
    exit('Acesso não permitido');
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    $perguntas_por_pagina = 5;
    $pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $offset = ($pagina_atual - 1) * $perguntas_por_pagina;
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todas';

    // Monta a condição WHERE baseada no tipo de pergunta
    $where_conditions = [];
    $params = [];

    if ($tipo === 'enviada') {
        $where_conditions[] = "p.criado_por = ?";
        $params[] = $_SESSION['user_id'];
    } elseif ($tipo === 'recebida') {
        $where_conditions[] = "p.id_paciente = ? AND p.criado_por != ?";
        $params[] = $_SESSION['user_id'];
        $params[] = $_SESSION['user_id'];
    } elseif ($tipo === 'admin') {
        $where_conditions[] = "u_criador.tipo_usuario = 'admin' AND p.id_paciente = ?";
        $params[] = $_SESSION['user_id'];
    } else { // todas
        $where_conditions[] = "(p.id_paciente = ? OR p.criado_por = ?)";
        $params[] = $_SESSION['user_id'];
        $params[] = $_SESSION['user_id'];
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Busca o total de perguntas do paciente
    $sql = "SELECT COUNT(*) as total FROM perguntas p 
            LEFT JOIN usuarios u_criador ON p.criado_por = u_criador.id 
            WHERE " . $where_clause;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_perguntas = $stmt->fetch()['total'];

    // Adiciona os parâmetros de LIMIT e OFFSET
    $params[] = $perguntas_por_pagina;
    $params[] = $offset;

    // Busca as perguntas do paciente com limite
    $sql = "SELECT p.*, 
                   u_med.nome as nome_medico,
                   u_criador.nome as criado_por_nome,
                   u_criador.tipo_usuario as tipo_criador,
                   CASE 
                       WHEN p.criado_por = ? THEN 'enviada'
                       ELSE 'recebida'
                   END as tipo_pergunta
            FROM perguntas p
            LEFT JOIN usuarios u_med ON p.id_medico = u_med.id
            LEFT JOIN usuarios u_criador ON p.criado_por = u_criador.id
            WHERE " . $where_clause . "
            ORDER BY p.data_criacao DESC
            LIMIT ? OFFSET ?";

    array_unshift($params, $_SESSION['user_id']); // Adiciona user_id para o CASE
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $perguntas = $stmt->fetchAll();

    // Renderiza apenas os cards das perguntas
    foreach ($perguntas as $pergunta) {
        $cardClass = 'pergunta-card';
        if ($pergunta['tipo_criador'] === 'admin') {
            $cardClass .= ' pergunta-admin';
        } elseif ($pergunta['tipo_pergunta'] === 'recebida') {
            $cardClass .= ' pergunta-recebida';
        }
        ?>
        <div class="card <?php echo $cardClass; ?> mb-4" 
             data-tipo="<?php echo $pergunta['tipo_criador'] === 'admin' ? 'admin' : $pergunta['tipo_pergunta']; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($pergunta['titulo']); ?></h5>
                    <div>
                        <?php if ($pergunta['tipo_criador'] === 'admin'): ?>
                            <span class="badge bg-danger">Pergunta do Administrador</span>
                        <?php elseif ($pergunta['tipo_pergunta'] === 'recebida'): ?>
                            <span class="badge bg-purple">Pergunta Recebida</span>
                        <?php endif; ?>
                        
                        <?php if ($pergunta['criado_por'] === $_SESSION['user_id'] && empty($pergunta['resposta'])): ?>
                            <button class="btn btn-sm btn-outline-primary ms-2" 
                                    onclick="editarPergunta(<?php echo $pergunta['id']; ?>, 
                                                          '<?php echo addslashes($pergunta['titulo']); ?>', 
                                                          '<?php echo addslashes($pergunta['descricao']); ?>', 
                                                          <?php echo $pergunta['id_medico'] ?? 'null'; ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <p class="card-text"><?php echo nl2br(htmlspecialchars($pergunta['descricao'])); ?></p>
                
                <div class="pergunta-timestamp">
                    <i class="bi bi-clock"></i> 
                    <?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?>
                    <?php if ($pergunta['id_medico']): ?>
                        <span class="ms-2">
                            <i class="bi bi-person-badge"></i> 
                            Para: <?php echo htmlspecialchars($pergunta['nome_medico']); ?>
                        </span>
                    <?php endif; ?>
                    <span class="ms-2">
                        <i class="bi bi-person"></i> 
                        <?php if ($pergunta['tipo_criador'] === 'admin'): ?>
                            De: Administrador
                        <?php else: ?>
                            De: <?php echo htmlspecialchars($pergunta['criado_por_nome']); ?>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($pergunta['tipo_criador'] !== $_SESSION['user_id'] && empty($pergunta['resposta_paciente'])): ?>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#responderModal" 
                                onclick="prepararResposta(<?php echo $pergunta['id']; ?>)">
                            <i class="bi bi-reply"></i> Responder Pergunta
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pergunta['resposta'])): ?>
                    <div class="card resposta-card mt-3">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-success">
                                <i class="bi bi-chat-right-text"></i> Resposta do Médico
                            </h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($pergunta['resposta'])); ?></p>
                            <small class="text-muted d-block mb-3">
                                <i class="bi bi-clock"></i> 
                                Respondido em: <?php echo date('d/m/Y H:i', strtotime($pergunta['data_resposta'])); ?>
                            </small>

                            <?php if (empty($pergunta['resposta_paciente'])): ?>
                                <button class="btn btn-sm btn-outline-success" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#responderModal" 
                                        onclick="prepararResposta(<?php echo $pergunta['id']; ?>)">
                                    <i class="bi bi-reply"></i> Responder ao Médico
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pergunta['resposta_paciente'])): ?>
                    <div class="card resposta-card mt-3">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-primary">
                                <i class="bi bi-chat-right-text"></i> Sua Resposta
                            </h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($pergunta['resposta_paciente'])); ?></p>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> 
                                Respondido em: <?php echo date('d/m/Y H:i', strtotime($pergunta['data_resposta_paciente'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // Se houver mais perguntas, renderiza o botão de carregar mais
    $tem_mais_perguntas = $total_perguntas > ($offset + $perguntas_por_pagina);
    if ($tem_mais_perguntas) {
        ?>
        <div class="d-flex justify-content-center">
            <a href="index.php?page=paciente/perguntas&pagina=<?php echo $pagina_atual + 1; ?>&tipo=<?php echo $tipo; ?>" class="btn btn-primary">
                <i class="bi bi-arrow-down-circle"></i> Ver mais perguntas
            </a>
        </div>
        <?php
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar perguntas via AJAX: " . $e->getMessage());
    http_response_code(500);
    exit('Erro ao carregar mais perguntas');
}
?>
