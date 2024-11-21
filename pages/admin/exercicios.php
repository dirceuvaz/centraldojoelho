<?php
// Verifica se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // Busca todos os exercícios com informações do paciente
    $stmt = $pdo->prepare("
        SELECT e.*, u.nome as nome_paciente
        FROM exercicios e
        LEFT JOIN usuarios u ON e.id_paciente = u.id
        ORDER BY e.data_criacao DESC
    ");
    $stmt->execute();
    $exercicios = $stmt->fetchAll();

    // Busca todos os pacientes para o formulário de novo exercício
    $stmt = $pdo->prepare("
        SELECT id, nome
        FROM usuarios
        WHERE tipo_usuario = 'paciente'
        ORDER BY nome ASC
    ");
    $stmt->execute();
    $pacientes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar exercícios: " . $e->getMessage());
    $exercicios = [];
    $pacientes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Exercícios - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .exercicio-card {
            transition: all 0.3s ease;
        }
        .exercicio-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=admin/painel">Central do Joelho - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/painel">Painel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=admin/exercicios">Exercícios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/perguntas">Perguntas</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="index.php?page=login_process&logout=1" class="btn btn-light btn-sm">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Gerenciar Exercícios</h2>
                <p class="text-muted">Gerencie os exercícios dos pacientes</p>
            </div>
            <div class="col-md-4 text-md-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoExercicioModal">
                    <i class="bi bi-plus-circle"></i> Novo Exercício
                </button>
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

        <?php if (empty($exercicios)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Nenhum exercício cadastrado.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Data Criação</th>
                            <th>Data Conclusão</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exercicios as $exercicio): ?>
                            <tr class="exercicio-card">
                                <td><?php echo htmlspecialchars($exercicio['nome_paciente']); ?></td>
                                <td><?php echo htmlspecialchars($exercicio['titulo']); ?></td>
                                <td>
                                    <span class="badge <?php echo $exercicio['status'] === 'completo' ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo ucfirst($exercicio['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($exercicio['data_criacao'])); ?></td>
                                <td>
                                    <?php 
                                    echo $exercicio['data_conclusao'] 
                                        ? date('d/m/Y H:i', strtotime($exercicio['data_conclusao']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            onclick="editarExercicio(<?php echo htmlspecialchars(json_encode($exercicio)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmarExclusao(<?php echo $exercicio['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Novo Exercício -->
    <div class="modal fade" id="novoExercicioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="index.php?page=admin/exercicios_process" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Exercício</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Paciente</label>
                            <select name="id_paciente" class="form-select" required>
                                <option value="">Selecione um paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>">
                                        <?php echo htmlspecialchars($paciente['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Título do Exercício</label>
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link do Vídeo (opcional)</label>
                            <input type="url" name="video_url" class="form-control" 
                                   placeholder="https://www.youtube.com/watch?v=...">
                            <div class="form-text">
                                Cole aqui o link do vídeo do YouTube que demonstra o exercício.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Exercício</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Exercício -->
    <div class="modal fade" id="editarExercicioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="index.php?page=admin/exercicios_process" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Exercício</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Paciente</label>
                            <select name="id_paciente" id="edit_id_paciente" class="form-select" required>
                                <option value="">Selecione um paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>">
                                        <?php echo htmlspecialchars($paciente['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Título do Exercício</label>
                            <input type="text" name="titulo" id="edit_titulo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" id="edit_descricao" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link do Vídeo (opcional)</label>
                            <input type="url" name="video_url" id="edit_video_url" class="form-control" 
                                   placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="pendente">Pendente</option>
                                <option value="completo">Completo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarExercicio(exercicio) {
            document.getElementById('edit_id').value = exercicio.id;
            document.getElementById('edit_id_paciente').value = exercicio.id_paciente;
            document.getElementById('edit_titulo').value = exercicio.titulo;
            document.getElementById('edit_descricao').value = exercicio.descricao;
            document.getElementById('edit_video_url').value = exercicio.video_url || '';
            document.getElementById('edit_status').value = exercicio.status;
            
            new bootstrap.Modal(document.getElementById('editarExercicioModal')).show();
        }

        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir este exercício?')) {
                window.location.href = `index.php?page=admin/exercicios_process&action=delete&id=${id}`;
            }
        }
    </script>
</body>
</html>
