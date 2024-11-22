<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

$pdo = getConnection();

// Incluir a classe helper de reabilitação
require_once __DIR__ . '/../../database/reabilitacao_helper.php';
$reabHelper = new ReabilitacaoHelper($pdo);

// Buscar informações do paciente
$stmt = $pdo->prepare("
    SELECT p.*, u.nome as nome_medico, u.id as medico_id
    FROM pacientes p
    JOIN usuarios u ON p.medico = u.id
    WHERE p.id_usuario = ?
");
$stmt->execute([$_SESSION['user_id']]);
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    die('Paciente não encontrado');
}

// Determinar etapa atual de reabilitação
$etapa_atual = $reabHelper->determinarEtapaReabilitacao($paciente['data_cirurgia']);

// Buscar orientações específicas para a etapa atual
$orientacoes = $reabHelper->buscarOrientacoes($etapa_atual['momento'], $paciente['medico_id']);

// Se não houver orientações específicas do médico, buscar orientações padrão
if (empty($orientacoes)) {
    $orientacoes = $reabHelper->buscarOrientacoes($etapa_atual['momento'], null);
}

// Preparar dados para exibição
$dias_pos_cirurgia = $etapa_atual['dias_pos_cirurgia'];
$momento_descricao = $etapa_atual['descricao'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reabilitação - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .icon-large {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .card-dashboard {
            transition: transform 0.2s;
            cursor: pointer;
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
        .navbar-custom {
            background-color: #231F5D !important;
        }
        .orientacao-card {
            border-left: 4px solid #231F5D;
            margin-bottom: 1rem;
        }
        .momento-badge {
            background-color: #231F5D;
            color: white;
        }
        .tipo-badge {
            background-color: #0d6efd;
            color: white;
        }
        .dias-badge {
            background-color: #198754;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">                    
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
                            <a href="index.php?page=paciente/painel" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Voltar ao Painel
                            </a>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            <i class="bi bi-calendar-check"></i> 
                            Seu Programa de Reabilitação
                        </h4>
                        <div class="alert alert-info">
                            <strong>Etapa Atual:</strong> <?php echo htmlspecialchars($momento_descricao); ?>
                            <?php if ($dias_pos_cirurgia !== null): ?>
                                <span class="badge dias-badge ms-2">
                                    <?php echo $dias_pos_cirurgia; ?> dias após a cirurgia
                                </span>
                            <?php endif; ?>
                            <br>
                            <strong>Data da Cirurgia:</strong> <?php echo date('d/m/Y', strtotime($paciente['data_cirurgia'])); ?><br>
                            <strong>Médico:</strong> <?php echo htmlspecialchars($paciente['nome_medico']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h5 class="mb-3">Orientações para esta etapa:</h5>
                <?php if (empty($orientacoes)): ?>
                    <div class="alert alert-warning">
                        Nenhuma orientação disponível para esta etapa.
                    </div>
                <?php else: ?>
                    <?php foreach ($orientacoes as $orientacao): ?>
                        <div class="orientacao-item mb-4">
                            <h5><?php echo htmlspecialchars($orientacao['titulo']); ?></h5>
                            <p class="text-muted">
                                <small>
                                    <i class="bi bi-calendar-event"></i> 
                                    <?php echo htmlspecialchars($orientacao['momento']); ?>
                                </small>
                            </p>
                            <div class="orientacao-texto">
                                <?php echo nl2br(htmlspecialchars($orientacao['texto'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
