<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$pdo = getConnection();

// Buscar todas as orientações de reabilitação do médico
$stmt = $pdo->prepare("
    SELECT * FROM reabilitacao 
    WHERE id_medico = ? 
    ORDER BY data_criacao DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orientacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar informações do médico
$stmt = $pdo->prepare("SELECT * FROM medicos WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Reabilitação - Central do Joelho</title>
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
                        <li class="breadcrumb-item active" aria-current="page">Gerenciamento de Reabilitação</li>
                    </ol>
                </nav>
                <h2>Gerenciamento de Reabilitação</h2>
                <p class="text-muted">Gerencie as orientações e protocolos de reabilitação</p>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOrientacao">
                    <i class="bi bi-plus-lg"></i> Nova Orientação
                </button>
            </div>
        </div>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['sucesso']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['erro']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($orientacoes as $orientacao): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($orientacao['titulo']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                Momento: <?php echo htmlspecialchars($orientacao['momento']); ?>
                            </h6>
                            <p class="card-text">
                                <strong>Tipo:</strong> <?php echo htmlspecialchars($orientacao['tipo']); ?><br>
                                <?php echo nl2br(htmlspecialchars($orientacao['texto'])); ?>
                            </p>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-outline-primary editar-orientacao" 
                                        data-id="<?php echo $orientacao['id']; ?>"
                                        data-titulo="<?php echo htmlspecialchars($orientacao['titulo']); ?>"
                                        data-momento="<?php echo htmlspecialchars($orientacao['momento']); ?>"
                                        data-tipo="<?php echo htmlspecialchars($orientacao['tipo']); ?>"
                                        data-texto="<?php echo htmlspecialchars($orientacao['texto']); ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalOrientacao">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmarExclusao(<?php echo $orientacao['id']; ?>)">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            Atualizado em: <?php echo date('d/m/Y H:i', strtotime($orientacao['data_atualizacao'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para Criar/Editar Orientação -->
    <div class="modal fade" id="modalOrientacao" tabindex="-1" aria-labelledby="modalOrientacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOrientacaoLabel">Nova Orientação de Reabilitação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formOrientacao" action="index.php?page=medico/reabilitacao_process" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="orientacao_id">
                        <input type="hidden" name="action" id="form_action" value="criar">
                        
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="momento" class="form-label">Momento</label>
                            <select class="form-select" id="momento" name="momento" required>
                                <option value="">Selecione...</option>
                                <option value="Pre-operatorio">Pré-operatório</option>
                                <option value="Pos-operatorio">Pós-operatório</option>
                                <option value="Reabilitacao">Reabilitação</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Selecione...</option>
                                <option value="Exercicio">Exercício</option>
                                <option value="Cuidado">Cuidado</option>
                                <option value="Orientacao">Orientação Geral</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="texto" class="form-label">Texto</label>
                            <textarea class="form-control" id="texto" name="texto" rows="5" required></textarea>
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
        // Função para confirmar exclusão
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir esta orientação?')) {
                window.location.href = `index.php?page=medico/reabilitacao_process&action=excluir&id=${id}`;
            }
        }

        // Configurar modal para edição
        document.querySelectorAll('.editar-orientacao').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const titulo = this.dataset.titulo;
                const momento = this.dataset.momento;
                const tipo = this.dataset.tipo;
                const texto = this.dataset.texto;

                document.getElementById('modalOrientacaoLabel').textContent = 'Editar Orientação de Reabilitação';
                document.getElementById('orientacao_id').value = id;
                document.getElementById('form_action').value = 'editar';
                document.getElementById('titulo').value = titulo;
                document.getElementById('momento').value = momento;
                document.getElementById('tipo').value = tipo;
                document.getElementById('texto').value = texto;
            });
        });

        // Resetar formulário ao abrir modal para nova orientação
        document.getElementById('modalOrientacao').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formOrientacao').reset();
            document.getElementById('modalOrientacaoLabel').textContent = 'Nova Orientação de Reabilitação';
            document.getElementById('orientacao_id').value = '';
            document.getElementById('form_action').value = 'criar';
        });
    </script>
</body>
</html>
