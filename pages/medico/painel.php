<?php
// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getConnection();

// Busca os dados do médico
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.nome, u.email
        FROM usuarios u 
        LEFT JOIN medicos m ON m.id_usuario = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $medico = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medico) {
        // Dados padrão se não encontrar o médico
        $medico = [
            'id_usuario' => $_SESSION['user_id'],
            'nome' => $_SESSION['user_nome'],
            'email' => '',
            'especialidade' => '',
            'crm' => '',
            'status' => 'ativo'
        ];
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do médico: " . $e->getMessage());
    $medico = [
        'id_usuario' => $_SESSION['user_id'],
        'nome' => $_SESSION['user_nome'],
        'email' => '',
        'especialidade' => '',
        'crm' => '',
        'status' => 'ativo'
    ];
}

// Busca o total de pacientes ativos do médico
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id) as total_pacientes
        FROM pacientes p
        JOIN consultas c ON c.id_paciente = p.id_usuario
        WHERE c.id_medico = ? AND p.status = 'ativo'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_pacientes = $stmt->fetch(PDO::FETCH_ASSOC)['total_pacientes'] ?? 0;
} catch (PDOException $e) {
    error_log("Erro ao buscar total de pacientes: " . $e->getMessage());
    $total_pacientes = 0;
}

// Busca o total de consultas agendadas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_consultas
        FROM consultas
        WHERE id_medico = ? AND data_consulta >= CURRENT_DATE AND status = 'agendada'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_consultas = $stmt->fetch(PDO::FETCH_ASSOC)['total_consultas'] ?? 0;
} catch (PDOException $e) {
    error_log("Erro ao buscar total de consultas: " . $e->getMessage());
    $total_consultas = 0;
}

// Busca perguntas pendentes
try {
    // Define o número de itens por página
    $itens_por_pagina = 5;
    $pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $offset = ($pagina_atual - 1) * $itens_por_pagina;

    // Busca o total de perguntas pendentes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM perguntas p
        LEFT JOIN usuarios u ON p.id_paciente = u.id
        WHERE p.status = 'pendente'
        AND (p.id_medico = ? OR p.id_medico IS NULL)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_perguntas = $stmt->fetch()['total'];
    $total_paginas = ceil($total_perguntas / $itens_por_pagina);

    // Busca as perguntas da página atual
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COALESCE(u.nome, 'Pergunta do Admin') as nome_paciente
        FROM perguntas p
        LEFT JOIN usuarios u ON p.id_paciente = u.id
        WHERE p.status = 'pendente'
        AND (p.id_medico = ? OR p.id_medico IS NULL)
        ORDER BY p.data_criacao DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $itens_por_pagina, $offset]);
    $perguntas_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar perguntas pendentes: " . $e->getMessage());
    $perguntas_pendentes = [];
    $total_paginas = 0;
}

// Busca o total de cirurgias agendadas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_cirurgias
        FROM cirurgias
        WHERE id_medico = ? AND data_cirurgia >= CURRENT_DATE AND status = 'agendada'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_cirurgias = $stmt->fetch(PDO::FETCH_ASSOC)['total_cirurgias'] ?? 0;
} catch (PDOException $e) {
    error_log("Erro ao buscar total de cirurgias: " . $e->getMessage());
    $total_cirurgias = 0;
}

// Busca o total de exames pendentes
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_exames
        FROM exames
        WHERE id_medico = ? AND status = 'pendente'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_exames = $stmt->fetch(PDO::FETCH_ASSOC)['total_exames'] ?? 0;
} catch (PDOException $e) {
    error_log("Erro ao buscar total de exames: " . $e->getMessage());
    $total_exames = 0;
}

// Busca o total de exercícios prescritos
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_exercicios
        FROM exercicios
        WHERE id_medico = ? AND status = 'ativo'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $total_exercicios = $stmt->fetch(PDO::FETCH_ASSOC)['total_exercicios'] ?? 0;
} catch (PDOException $e) {
    error_log("Erro ao buscar total de exercícios: " . $e->getMessage());
    $total_exercicios = 0;
}

// Busca as próximas consultas do dia
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nome as nome_paciente
        FROM consultas c
        JOIN usuarios u ON u.id = c.id_paciente
        WHERE c.id_medico = ? 
        AND DATE(c.data_consulta) = CURRENT_DATE
        AND c.status = 'agendada'
        ORDER BY c.data_consulta ASC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $proximas_consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar próximas consultas: " . $e->getMessage());
    $proximas_consultas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Médico - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .card-dashboard {
            transition: transform 0.2s;
            cursor: pointer;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .icon-large {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .bg-primary {
            background-color: #0d6efd !important;
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-text {
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once __DIR__ . '/../../common/header_medico.php'; ?>

    <div class="container my-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Painel do Médico</h2>
                <p class="text-muted">Bem-vindo, Dr(a). <?php echo htmlspecialchars($medico['nome']); ?></p>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#perfilModal">
                    <i class="bi bi-person-circle"></i> Ver Perfil
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Reabilitação -->
            <div class="col-md-4 col-lg-3">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/reabilitacao_medico'">
                    <div class="card-body text-center">
                    <i class="bi bi-check-square icon-large"></i>                                              
                        <h5 class="card-title">Reabilitação</h5>
                        <p class="card-text">Gerencie protocolos de reabilitação</p>
                    </div>
                </div>
            </div>

            <!-- Perguntas e respostas  -->
            <div class="col-md-4 col-lg-3">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/perguntas'">
                    <div class="card-body text-center">
                        <i class="bi bi-question-circle icon-large"></i>
                        <h5 class="card-title">Perguntas e Respostas</h5>
                        <p class="card-text">Gerenciar</p>
                    </div>
                </div>
            </div>

            <!-- Pacientes -->
            <div class="col-md-4 col-lg-3">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/pacientes'">
                    <div class="card-body text-center">
                        <i class="bi bi-people icon-large"></i>
                        <h5 class="card-title">Pacientes</h5>
                        <p class="card-text">Gerenciar pacientes</p>
                    </div>
                </div>
            </div>

            <!-- Vídeos -->
            <div class="col-md-4 col-lg-3">
                <div class="card card-dashboard h-100" onclick="window.location='#'">
                    <div class="card-body text-center">
                        <i class="bi bi-camera-video icon-large"></i>
                        <h5 class="card-title">Vídeos (Em Manutenção)</h5>
                        <p class="card-text">Gerenciar vídeos educativos</p>
                    </div>
                </div>
           </div>
    </div>

    <!-- Modal de Perfil -->
    <div class="modal fade" id="perfilModal" tabindex="-1" aria-labelledby="perfilModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="perfilModalLabel">Perfil do Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Informações Pessoais</h6>
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($medico['nome']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($medico['email']); ?></p>
                        <p><strong>CRM:</strong> <?php echo htmlspecialchars($medico['crm'] ?? 'Não informado'); ?></p>
                        <p><strong>Especialidade:</strong> <?php echo htmlspecialchars($medico['especialidade'] ?? 'Não informada'); ?></p>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Alterar Senha</h6>
                        <form id="alterarSenhaForm" action="index.php?page=medico/alterar_senha" method="POST">
                            <div class="mb-3">
                                <label for="senhaAtual" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="senhaAtual" name="senha_atual" required>
                            </div>
                            <div class="mb-3">
                                <label for="novaSenha" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="novaSenha" name="nova_senha" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmarSenha" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirmarSenha" name="confirmar_senha" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Alterar Senha</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>