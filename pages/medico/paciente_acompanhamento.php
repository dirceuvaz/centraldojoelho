<?php
// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getConnection();

// Verificar se o ID do paciente foi fornecido
$id_paciente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_paciente) {
    header('Location: index.php?page=medico/pacientes&erro=Paciente não encontrado');
    exit;
}

// Buscar dados do médico
$stmt = $pdo->prepare("
    SELECT m.*, u.nome, u.id as usuario_id
    FROM medicos m
    JOIN usuarios u ON u.id = m.id_usuario
    WHERE m.id_usuario = ?
");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();

// Buscar dados do paciente
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        u.nome,
        u.email,
        u.status as usuario_status,
        u.id as usuario_id
    FROM pacientes p
    JOIN usuarios u ON u.id = p.id_usuario
    WHERE p.id = ? AND p.medico = ?
");
$stmt->execute([$id_paciente, $medico['nome']]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: index.php?page=medico/pacientes&erro=Paciente não encontrado');
    exit;
}

// Buscar histórico de evolução
$stmt = $pdo->prepare("
    SELECT *
    FROM evolucao_paciente
    WHERE id_paciente = ?
    ORDER BY data_registro DESC
");
$stmt->execute([$id_paciente]);
$evolucoes = $stmt->fetchAll();

// Processar nova evolução
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'nova_evolucao') {
            $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
            if (empty($descricao)) {
                throw new Exception('A descrição é obrigatória');
            }

            $stmt = $pdo->prepare("
                INSERT INTO evolucao_paciente (
                    id_paciente,
                    id_medico,
                    descricao,
                    data_registro
                ) VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$id_paciente, $_SESSION['user_id'], $descricao]);
            
            header("Location: index.php?page=medico/paciente_acompanhamento&id={$id_paciente}&sucesso=Evolução registrada com sucesso");
            exit;
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento do Paciente - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #231F5D;
        }
        .navbar {
            background-color: var(--primary-color) !important;
        }
        .icon-large {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        .card-dashboard {
            transition: transform 0.2s;
            cursor: pointer;
            border: none;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-text {
            color: #6c757d;
        }
        .timeline {
            position: relative;
            padding-left: 3rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            height: 100%;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.35rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid #fff;
        }
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#perfilModal">
                            <i class="bi bi-person-circle"></i> Perfil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=medico/painel" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Voltar ao Painel
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="index.php?page=medico/pacientes" class="text-decoration-none">
                                Pacientes
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Acompanhamento - <?php echo htmlspecialchars($paciente['nome']); ?>
                        </li>
                    </ol>
                </nav>
                <h2>Acompanhamento do Paciente</h2>
                <p class="text-muted">Gerencie o progresso e evolução do paciente</p>
            </div>
        </div>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['sucesso']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($erro); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Informações do Paciente -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-person-badge"></i> Informações do Paciente
                        </h5>
                        <hr>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Nome:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($paciente['nome']); ?></dd>
                            
                            <dt class="col-sm-4">Email:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($paciente['email']); ?></dd>
                            
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-<?php echo $paciente['usuario_status'] === 'ativo' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($paciente['usuario_status']); ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-4">Cirurgia:</dt>
                            <dd class="col-sm-8">
                                <?php echo $paciente['data_cirurgia'] ? date('d/m/Y', strtotime($paciente['data_cirurgia'])) : 'Não agendada'; ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Histórico de Evolução -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-clock-history"></i> Histórico de Evolução
                            </h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEvolucao">
                                <i class="bi bi-plus-lg"></i> Nova Evolução
                            </button>
                        </div>
                        
                        <div class="timeline">
                            <?php if (empty($evolucoes)): ?>
                            <p class="text-muted text-center py-4">
                                <i class="bi bi-info-circle"></i>
                                Nenhuma evolução registrada ainda
                            </p>
                            <?php else: ?>
                            <?php foreach ($evolucoes as $evolucao): ?>
                            <div class="timeline-item">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($evolucao['data_registro'])); ?>
                                        </h6>
                                        <p class="card-text">
                                            <?php echo nl2br(htmlspecialchars($evolucao['descricao'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!-- Modal Evolução -->
        <div class="modal fade" id="modalEvolucao" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="action" value="nova_evolucao">
                        <div class="modal-header">
                            <h5 class="modal-title">Nova Evolução</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição da Evolução</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="5" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Registrar Evolução
                            </button>
                        </div>
                    </form>
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
</body>
</html>
