<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Buscar vídeos de todas as reabilitações
$query = "
    SELECT v.*, r.titulo as reabilitacao_titulo
    FROM videos v
    LEFT JOIN reabilitacao r ON v.id_reabilitacao = r.id
    WHERE v.status = 'ativo'
    ORDER BY r.titulo, v.titulo
";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar vídeos: " . $e->getMessage();
    $videos = [];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vídeos - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .video-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .video-card:hover {
            transform: translateY(-5px);
        }
        .video-thumbnail {
            position: relative;
            overflow: hidden;
            border-radius: 15px 15px 0 0;
        }
        .video-thumbnail img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 3rem;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .video-thumbnail:hover .play-button {
            opacity: 1;
        }
        .video-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .reabilitacao-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #231F5D;">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=paciente/perfil">
                            <i class="bi bi-person-circle"></i> Meu Perfil
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Vídeos</h2>
                <p class="text-muted">Assista aos vídeos disponíveis para sua reabilitação</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php?page=paciente/painel">Painel</a></li>
                    <li class="breadcrumb-item active">Vídeos</li>
                </ol>
            </nav>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach ($videos as $video): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="video-card card h-100">
                        <div class="video-thumbnail">
                            <img src="<?php echo htmlspecialchars($video['thumbnail_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($video['titulo']); ?>"
                                 onerror="this.src='assets/img/video-placeholder.jpg'">
                            <div class="play-button">
                                <i class="bi bi-play-circle-fill"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="video-title"><?php echo htmlspecialchars($video['titulo']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($video['descricao']); ?></p>
                            <span class="badge bg-primary reabilitacao-badge">
                                <?php echo htmlspecialchars($video['reabilitacao_titulo']); ?>
                            </span>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <a href="<?php echo htmlspecialchars($video['url']); ?>" 
                               class="btn btn-primary w-100" 
                               target="_blank">
                                <i class="bi bi-play-fill"></i> Assistir Vídeo
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($videos)): ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        Nenhum vídeo disponível no momento.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
