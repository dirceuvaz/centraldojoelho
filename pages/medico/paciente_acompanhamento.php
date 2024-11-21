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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: -20px;
            width: 2px;
            background-color: #0d6efd;
        }
        .timeline-item::after {
            content: "";
            position: absolute;
            left: -4px;
            top: 8px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #0d6efd;
        }
        .timeline-item:last-child::before {
            bottom: 0;
        }
        .status-badge {
            width: 100px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=medico/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=medico/pacientes">
                            <i class="bi bi-arrow-left-circle"></i> Voltar para Lista de Pacientes
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['sucesso']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($erro); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Informações do Paciente -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="card-title">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($paciente['nome']); ?>
                        </h4>
                        <p class="text-muted mb-0">
                            <i class="bi bi-envelope"></i> 
                            <?php echo htmlspecialchars($paciente['email']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <?php
                        $statusClass = [
                            'pendente' => 'warning',
                            'ativo' => 'success',
                            'inativo' => 'danger'
                        ][$paciente['usuario_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                            <?php echo htmlspecialchars($paciente['usuario_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalhes do Tratamento -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-clipboard-pulse"></i> 
                            Detalhes do Tratamento
                        </h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Problema:</strong><br>
                                <?php echo htmlspecialchars($paciente['problema']); ?>
                            </li>
                            <li class="mb-2">
                                <strong>Data da Cirurgia:</strong><br>
                                <?php echo $paciente['data_cirurgia'] ? date('d/m/Y', strtotime($paciente['data_cirurgia'])) : 'Não definida'; ?>
                            </li>
                            <li>
                                <strong>Início do Tratamento:</strong><br>
                                <?php echo date('d/m/Y', strtotime($paciente['data_cadastro'])); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-journal-plus"></i> 
                            Nova Evolução
                        </h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="nova_evolucao">
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> 
                                Registrar Evolução
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linha do Tempo de Evolução -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="bi bi-clock-history"></i> 
                    Histórico de Evolução
                </h5>
                
                <?php if (empty($evolucoes)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-info-circle"></i>
                    Nenhuma evolução registrada ainda
                </p>
                <?php else: ?>
                <div class="timeline">
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
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
