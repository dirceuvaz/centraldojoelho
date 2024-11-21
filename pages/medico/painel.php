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
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .icon-large {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .consulta-item {
            border-left: 4px solid #0d6efd;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
        }
        .consulta-item:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=medico/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=medico/painel">Painel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=medico/perguntas">
                            <i class="bi bi-chat-dots"></i> Perguntas
                            <?php if ($total_perguntas > 0): ?>
                                <span class="badge bg-danger"><?php echo $total_perguntas; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3 text-white">
                        <i class="bi bi-person"></i> 
                        Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
                    </span>
                    <a class="btn btn-light btn-sm" href="index.php?page=login_process&logout=1">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Meu Painel</h2>
                <p class="text-muted">Bem-vindo ao seu painel de controle</p>
            </div>
        </div>

        <div class="row">
            <!-- Cards de Estatísticas -->
            <div class="col-md-3 mb-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/pacientes'">
                    <div class="card-body text-center">
                        <i class="bi bi-people icon-large text-primary"></i>
                        <h5 class="card-title">Pacientes</h5>
                        <p class="card-text">Gerencie seus pacientes</p>
                        <span class="badge bg-primary"><?php echo $total_pacientes; ?> pacientes ativos</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/consultas'">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar2-week icon-large text-success"></i>
                        <h5 class="card-title">Consultas</h5>
                        <p class="card-text">Gerencie sua agenda</p>
                        <span class="badge bg-success"><?php echo $total_consultas; ?> consultas agendadas</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/cirurgias'">
                    <div class="card-body text-center">                    
                    <i class="bi bi-bandaid icon-large text-danger"></i>
                        <h5 class="card-title">Cirurgias</h5>
                        <p class="card-text">Acompanhe cirurgias</p>
                        <span class="badge bg-danger"><?php echo $total_cirurgias; ?> cirurgias agendadas</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/exames'">
                    <div class="card-body text-center">
                        <i class="bi bi-file-medical icon-large text-info"></i>
                        <h5 class="card-title">Exames</h5>
                        <p class="card-text">Gerencie exames</p>
                        <span class="badge bg-info"><?php echo $total_exames; ?> exames pendentes</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/exercicios'">
                    <div class="card-body text-center">
                        <i class="bi bi-clock-fill icon-large text-warning"></i>                        
                        <h5 class="card-title">Exercícios</h5>
                        <p class="card-text">Prescreva exercícios</p>
                        <span class="badge bg-warning"><?php echo $total_exercicios; ?> prescrições ativas</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card card-dashboard h-100" onclick="window.location='index.php?page=medico/perguntas'">
                    <div class="card-body text-center">
                        <i class="bi bi-chat-dots icon-large text-info"></i>
                        <h5 class="card-title">Perguntas</h5>
                        <p class="card-text">Responda dúvidas</p>
                        <?php if ($total_perguntas > 0): ?>
                            <span class="badge bg-danger"><?php echo $total_perguntas; ?> não respondidas</span>
                        <?php else: ?>
                            <span class="badge bg-success">Nenhuma pendente</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximas Consultas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar2-check"></i> 
                            Próximas Consultas do Dia
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($proximas_consultas)): ?>
                            <p class="text-muted text-center mb-0">Nenhuma consulta agendada para hoje</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Horário</th>
                                            <th>Paciente</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($proximas_consultas as $consulta): ?>
                                            <tr>
                                                <td><?php echo date('H:i', strtotime($consulta['data_consulta'])); ?></td>
                                                <td><?php echo htmlspecialchars($consulta['nome_paciente']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $consulta['tipo_consulta'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $consulta['status'] === 'agendada' ? 'warning' : 'success'; ?>">
                                                        <?php echo ucfirst($consulta['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="verDetalhesConsulta(<?php echo $consulta['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Modal Perfil -->
    <div class="modal fade" id="perfilModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="index.php?page=medico/perfil_process" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Meu Perfil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($medico['nome']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($medico['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CRM</label>
                            <input type="text" class="form-control" name="crm" value="<?php echo htmlspecialchars($medico['crm']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especialidade</label>
                            <input type="text" class="form-control" name="especialidade" value="<?php echo htmlspecialchars($medico['especialidade']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" name="senha" placeholder="Deixe em branco para manter a atual">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function responderPergunta(id) {
            window.location.href = `index.php?page=medico/perguntas#pergunta-${id}`;
        }

        // Atualiza o badge de perguntas a cada 5 minutos
        setInterval(function() {
            fetch('index.php?page=medico/get_perguntas_pendentes')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('#perguntas-badge');
                    if (badge) {
                        if (data.total > 0) {
                            badge.textContent = data.total;
                            badge.classList.remove('bg-success');
                            badge.classList.add('bg-danger');
                        } else {
                            badge.textContent = 'Nenhuma';
                            badge.classList.remove('bg-danger');
                            badge.classList.add('bg-success');
                        }
                    }
                });
        }, 300000); // 5 minutos
    </script>
</body>
</html>
