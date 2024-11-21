<?php
// Verifica se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // Busca todas as perguntas com informações do paciente
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u_criador.nome as criado_por_nome,
               u_paciente.nome as paciente_nome
        FROM perguntas p
        LEFT JOIN usuarios u_criador ON p.criado_por = u_criador.id
        LEFT JOIN usuarios u_paciente ON p.id_paciente = u_paciente.id
        ORDER BY p.data_criacao DESC
    ");
    $stmt->execute();
    $perguntas = $stmt->fetchAll();

    // Busca todos os pacientes ativos
    $stmt = $pdo->prepare("
        SELECT id, nome, email
        FROM usuarios
        WHERE tipo_usuario = 'paciente' AND status = 'ativo'
        ORDER BY nome ASC
    ");
    $stmt->execute();
    $pacientes = $stmt->fetchAll();

    // Busca todos os médicos para o formulário
    $stmt = $pdo->prepare("
        SELECT id, nome
        FROM usuarios
        WHERE tipo_usuario = 'medico' AND status = 'ativo'
        ORDER BY nome ASC
    ");
    $stmt->execute();
    $medicos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar perguntas: " . $e->getMessage());
    $perguntas = [];
    $pacientes = [];
    $medicos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Perguntas - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .pergunta-card {
            transition: all 0.3s ease;
        }
        .pergunta-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transform: translateY(-2px);
        }
        .status-pendente {
            color: #dc3545;
        }
        .status-respondida {
            color: #198754;
        }
        .pergunta-timestamp {
            font-size: 0.875rem;
            color: #6c757d;
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
                        <a class="nav-link" href="index.php?page=admin/exercicios">Exercícios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=admin/perguntas">Perguntas</a>
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
                <h2>Gerenciar Perguntas</h2>
                <p class="text-muted">Gerencie as perguntas do sistema</p>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaPerguntaModal">
                    <i class="bi bi-plus-circle"></i> Nova Pergunta
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

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Paciente</th>
                        <th>Criado por</th>
                        <th>Data Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perguntas as $pergunta): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pergunta['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($pergunta['paciente_nome']); ?></td>
                        <td><?php echo htmlspecialchars($pergunta['criado_por_nome']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="editarPergunta(<?php echo htmlspecialchars(json_encode($pergunta)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $pergunta['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nova Pergunta -->
    <div class="modal fade" id="novaPerguntaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Pergunta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?page=admin/perguntas_process" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="id_paciente" class="form-label">Paciente</label>
                            <select class="form-select" id="id_paciente" name="id_paciente" required>
                                <option value="">Selecione um paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>">
                                        <?php echo htmlspecialchars($paciente['nome']); ?> (<?php echo htmlspecialchars($paciente['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
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

    <!-- Modal Editar Pergunta -->
    <div class="modal fade" id="editarPerguntaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Pergunta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?page=admin/perguntas_process" method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_paciente" class="form-label">Paciente</label>
                            <select class="form-select" id="edit_id_paciente" name="id_paciente" required>
                                <option value="">Selecione um paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>">
                                        <?php echo htmlspecialchars($paciente['nome']); ?> (<?php echo htmlspecialchars($paciente['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarPergunta(pergunta) {
            document.getElementById('edit_id').value = pergunta.id;
            document.getElementById('edit_titulo').value = pergunta.titulo;
            document.getElementById('edit_descricao').value = pergunta.descricao;
            document.getElementById('edit_id_paciente').value = pergunta.id_paciente;
            
            new bootstrap.Modal(document.getElementById('editarPerguntaModal')).show();
        }

        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir esta pergunta?')) {
                window.location.href = 'index.php?page=admin/perguntas_process&action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>
