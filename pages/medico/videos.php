<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$medico_id = $_SESSION['user_id'];

// Buscar todos os vídeos cadastrados por este médico
$stmt = $conn->prepare("
    SELECT 
        v.*,
        m.nome as momento_nome
    FROM videos v
    LEFT JOIN momentos_reabilitacao m ON v.id_momento = m.id
    WHERE v.id_medico = ?
    ORDER BY v.data_criacao DESC
");
$stmt->execute([$medico_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar momentos para o formulário de novo vídeo
$stmt = $conn->prepare("SELECT id, nome FROM momentos_reabilitacao ORDER BY ordem");
$stmt->execute();
$momentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vídeos Educativos - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .card-header {
            background-color: #f8f9fa;
        }
        .video-card {
            transition: transform 0.2s;
        }
        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .video-thumbnail {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
        }
        .video-thumbnail iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once __DIR__ . '/../../common/header_medico.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="bi bi-camera-video"></i> Vídeos Educativos</h2>
                <p class="text-muted">Gerencie os vídeos educativos para seus pacientes</p>
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#videoModal">
                    <i class="bi bi-plus-circle"></i> Novo Vídeo
                </button>
            </div>
        </div>

        <!-- Lista de Vídeos -->
        <div class="row g-4">
            <?php if (empty($videos)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhum vídeo encontrado. Clique em "Novo Vídeo" para adicionar.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($videos as $video): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 video-card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo htmlspecialchars($video['titulo']); ?></h5>
                                <?php if ($video['momento_nome']): ?>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($video['momento_nome']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="video-thumbnail">
                                <?php
                                // Extrai o ID do vídeo do YouTube da URL
                                $video_id = '';
                                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video['url'], $match)) {
                                    $video_id = $match[1];
                                }
                                ?>
                                <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen>
                                </iframe>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($video['descricao'])); ?></p>
                                <small class="text-muted">
                                    Adicionado em <?php echo date('d/m/Y', strtotime($video['data_criacao'])); ?>
                                </small>
                            </div>
                            <div class="card-footer bg-transparent">
                                <button class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" data-bs-target="#videoModal" 
                                        data-video-id="<?php echo $video['id']; ?>" 
                                        data-titulo="<?php echo htmlspecialchars($video['titulo']); ?>" 
                                        data-url="<?php echo htmlspecialchars($video['url']); ?>" 
                                        data-descricao="<?php echo htmlspecialchars($video['descricao']); ?>" 
                                        data-momento="<?php echo $video['id_momento']; ?>">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                        data-video-id="<?php echo $video['id']; ?>">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Vídeo -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel">Adicionar/Editar Vídeo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="pages/medico/salvar_video.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="videoId" id="videoId">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="url" class="form-label">URL do YouTube</label>
                            <input type="url" class="form-control" id="url" name="url" required>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="momento" class="form-label">Momento da Reabilitação</label>
                            <select class="form-select" id="momento" name="momento">
                                <option value="">Selecione um momento</option>
                                <?php
                                $stmt = $conn->query("SELECT id, nome FROM momentos_reabilitacao ORDER BY ordem");
                                while ($momento = $stmt->fetch()) {
                                    echo "<option value='{$momento['id']}'>{$momento['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir este vídeo?
                </div>
                <form action="pages/medico/excluir_video.php" method="POST">
                    <input type="hidden" name="videoId" id="deleteVideoId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoModal = document.getElementById('videoModal');
            videoModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const isEdit = button.hasAttribute('data-video-id');
                
                document.getElementById('videoModalLabel').textContent = isEdit ? 'Editar Vídeo' : 'Adicionar Vídeo';
                document.getElementById('videoId').value = isEdit ? button.getAttribute('data-video-id') : '';
                document.getElementById('titulo').value = isEdit ? button.getAttribute('data-titulo') : '';
                document.getElementById('url').value = isEdit ? button.getAttribute('data-url') : '';
                document.getElementById('descricao').value = isEdit ? button.getAttribute('data-descricao') : '';
                document.getElementById('momento').value = isEdit ? button.getAttribute('data-momento') : '';
            });

            const deleteModal = document.getElementById('deleteModal');
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                document.getElementById('deleteVideoId').value = button.getAttribute('data-video-id');
            });
        });
    </script>
</body>
</html>
