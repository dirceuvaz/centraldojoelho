<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

$pdo = getConnection();

// Buscar todas as orientações de reabilitação
$stmt = $pdo->prepare("
    SELECT r.*, m.descricao as momento_desc, t.descricao as tipo_desc, 
           u.nome as nome_medico, med.crm
    FROM reabilitacao r
    LEFT JOIN momentos_reabilitacao m ON r.momento = m.id
    LEFT JOIN tipos_reabilitacao t ON r.tipo = t.id
    LEFT JOIN medicos med ON r.id_medico = med.id
    LEFT JOIN usuarios u ON med.id_usuario = u.id
    ORDER BY r.data_criacao DESC
");

$stmt->execute();
$orientacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <li class="breadcrumb-item active" aria-current="page">Reabilitação</li>
                    </ol>
                </nav>                
            </div>
        </div>

        <div class="row mb-4">
            <div class="col">
                <h2 class="mb-4">
                    <i class="bi bi-clipboard2-pulse text-primary"></i>
                    Orientações de Reabilitação
                </h2>

                <?php if (empty($orientacoes)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Ainda não há orientações de reabilitação cadastradas.
                    </div>
                <?php else: ?>
                    <?php foreach ($orientacoes as $orientacao): ?>
                        <div class="card orientacao-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge momento-badge me-2">
                                            <?php echo htmlspecialchars($orientacao['momento_desc']); ?>
                                        </span>
                                        <span class="badge tipo-badge">
                                            <?php echo htmlspecialchars($orientacao['tipo_desc']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($orientacao['data_criacao'])); ?>
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <h5><?php echo htmlspecialchars($orientacao['titulo']); ?></h5>
                                    <?php echo $orientacao['texto']; ?>
                                </div>

                                <div class="text-muted">
                                    <small>
                                        <i class="bi bi-person-badge"></i>
                                        Dr(a). <?php echo htmlspecialchars($orientacao['nome_medico']); ?>
                                        <?php if (!empty($orientacao['crm'])): ?>
                                            - CRM: <?php echo htmlspecialchars($orientacao['crm']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
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
