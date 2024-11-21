<?php
// Verifica se o usuário está logado e é um paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getConnection();

// Busca os exercícios do paciente
try {
    $stmt = $pdo->prepare("
        SELECT e.*, f.nome as nome_fisioterapeuta
        FROM exercicios e
        LEFT JOIN usuarios f ON e.id_fisioterapeuta = f.id
        WHERE e.id_paciente = ?
        ORDER BY e.data_prescricao DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exercicios = $stmt->fetchAll();

    // Agrupa exercícios por data
    $exercicios_por_data = [];
    foreach ($exercicios as $exercicio) {
        $data = date('Y-m-d', strtotime($exercicio['data_prescricao']));
        if (!isset($exercicios_por_data[$data])) {
            $exercicios_por_data[$data] = [];
        }
        $exercicios_por_data[$data][] = $exercicio;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar exercícios: " . $e->getMessage());
    $exercicios = [];
    $exercicios_por_data = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Exercícios - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .exercicio-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
        }
        .exercicio-video {
            aspect-ratio: 16/9;
            width: 100%;
            max-width: 400px;
            margin: 1rem 0;
        }
        .exercicio-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=paciente/painel">Painel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=paciente/exercicios">Exercícios</a>
                    </li>
                </ul>
                <div class="d-flex">
                <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person"></i> 
                            Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
                        </span>
                        <a class="btn btn-danger btn-sm" href="index.php?page=login_process&logout=1">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>                 
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Meus Exercícios</h2>
                <p class="text-muted">Acompanhe seus exercícios prescritos</p>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($exercicios_por_data)): ?>
        <div class="alert alert-info">
            Você ainda não tem exercícios prescritos.
        </div>
        <?php else: ?>
            <?php foreach ($exercicios_por_data as $data => $exercicios_dia): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo date('d/m/Y', strtotime($data)); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($exercicios_dia as $exercicio): ?>
                    <div class="exercicio-card p-3 position-relative">
                        <div class="exercicio-status">
                            <?php if ($exercicio['status'] === 'completo'): ?>
                            <span class="badge bg-success">Concluído</span>
                            <?php else: ?>
                            <span class="badge bg-warning">Pendente</span>
                            <?php endif; ?>
                        </div>
                        <h5><?php echo htmlspecialchars($exercicio['titulo']); ?></h5>
                        <p class="text-muted mb-2">
                            Prescrito por: <?php echo htmlspecialchars($exercicio['nome_fisioterapeuta']); ?>
                        </p>
                        <p><?php echo nl2br(htmlspecialchars($exercicio['descricao'])); ?></p>
                        <?php if ($exercicio['video_url']): ?>
                        <div class="exercicio-video">
                            <iframe 
                                width="100%" 
                                height="100%" 
                                src="<?php echo htmlspecialchars($exercicio['video_url']); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <strong>Duração:</strong> <?php echo htmlspecialchars($exercicio['duracao']); ?> minutos<br>
                            <strong>Repetições:</strong> <?php echo htmlspecialchars($exercicio['repeticoes']); ?>
                        </div>
                        <?php if ($exercicio['status'] !== 'completo'): ?>
                        <div class="mt-3">
                            <form action="index.php?page=paciente/exercicios_process" method="POST">
                                <input type="hidden" name="exercicio_id" value="<?php echo $exercicio['id']; ?>">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Marcar como Concluído
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
