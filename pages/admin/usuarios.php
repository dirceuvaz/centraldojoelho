<?php
require_once 'config/database.php';
$pdo = getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_usuario = $_POST['id_usuario'] ?? '';

    if ($acao === 'aprovar' && !empty($id_usuario)) {
        $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
        $stmt->execute([$id_usuario]);
    } elseif ($acao === 'bloquear' && !empty($id_usuario)) {
        $stmt = $pdo->prepare("UPDATE usuarios SET status = 'inativo' WHERE id = ?");
        $stmt->execute([$id_usuario]);
    }
}

// Filtros
$status = $_GET['status'] ?? 'todos';
$tipo = $_GET['tipo'] ?? 'todos';

// Construir query
$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($status !== 'todos') {
    $sql .= " AND status = ?";
    $params[] = $status;
}
if ($tipo !== 'todos') {
    $sql .= " AND tipo_usuario = ?";
    $params[] = $tipo;
}

$sql .= " ORDER BY nome";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #231F5D;
        }
        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color);
            padding: 20px 0;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            padding-left: 25px;
        }
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .sidebar .text-muted {
            color: rgba(255, 255, 255, 0.5) !important;
        }
        .sidebar h5 {
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .top-navbar {
            background-color: white;
            border-bottom: 1px solid #dee2e6;
            padding: 10px 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center p-3">
                    <h5>Central do Joelho</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/painel">
                            <i class="bi bi-speedometer2"></i> Painel
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <small class="text-muted px-3">ATENDIMENTOS</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/consultas">
                            <i class="bi bi-calendar-check"></i> Consultas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/cirurgias">
                            <i class="bi bi-activity"></i> Cirurgias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/exercicios">
                            <i class="bi bi-clock-fill"></i> Exercícios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/exames">
                            <i class="bi bi-file-medical"></i> Exames
                        </a>
                    </li>

                    <li class="nav-item mt-3">
                        <small class="text-muted px-3">FEEDBACK</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/depoimentos">
                            <i class="bi bi-chat-quote"></i> Depoimentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/perguntas">
                            <i class="bi bi-question-circle"></i> Perguntas
                        </a>
                    </li>

                    <li class="nav-item mt-3">
                        <small class="text-muted px-3">SISTEMA</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/relatorios">
                            <i class="bi bi-graph-up"></i> Relatórios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/notificacoes">
                            <i class="bi bi-bell"></i> Notificar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=admin/usuarios">
                            <i class="bi bi-people"></i> Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/configuracoes">
                            <i class="bi bi-gear"></i> Configurações
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-end">
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person"></i> 
                            Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
                        </span>
                        <a class="btn btn-outline-danger btn-sm" href="index.php?page=login_process&logout=1">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Gerenciar Usuários</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoUsuarioModal">
                            <i class="bi bi-person-plus"></i> Novo Usuário
                        </button>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="page" value="admin/usuarios">
                                
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="todos" <?php echo $status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                        <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                                        <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                                        <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativos</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Tipo de Usuário</label>
                                    <select name="tipo" class="form-select">
                                        <option value="todos" <?php echo $tipo === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                        <option value="admin" <?php echo $tipo === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="medico" <?php echo $tipo === 'medico' ? 'selected' : ''; ?>>Médico</option>
                                        <option value="fisioterapeuta" <?php echo $tipo === 'fisioterapeuta' ? 'selected' : ''; ?>>Fisioterapeuta</option>
                                        <option value="secretaria" <?php echo $tipo === 'secretaria' ? 'selected' : ''; ?>>Secretária</option>
                                        <option value="paciente" <?php echo $tipo === 'paciente' ? 'selected' : ''; ?>>Paciente</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block">
                                        <i class="bi bi-search"></i> Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Usuários -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($usuario['tipo_usuario']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pendente' => 'warning',
                                                    'ativo' => 'success',
                                                    'inativo' => 'danger'
                                                ][$usuario['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($usuario['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($usuario['status'] === 'pendente'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                    <input type="hidden" name="acao" value="aprovar">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="bi bi-check-circle"></i> Liberar Acesso
                                                    </button>
                                                </form>
                                                <?php endif; ?>

                                                <?php if ($usuario['status'] === 'ativo'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                    <input type="hidden" name="acao" value="bloquear">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-lock-fill"></i> Bloquear
                                                    </button>
                                                </form>
                                                <?php endif; ?>

                                                <?php if ($usuario['status'] === 'inativo'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                    <input type="hidden" name="acao" value="aprovar">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="bi bi-unlock-fill"></i> Desbloquear
                                                    </button>
                                                </form>
                                                <?php endif; ?>

                                                <button class="btn btn-primary btn-sm" onclick="carregarUsuario(<?php echo $usuario['id']; ?>)">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Novo Usuário -->
    <div class="modal fade" id="novoUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNovoUsuario">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuário</label>
                            <select class="form-select" name="tipo_usuario" id="tipo_usuario" required onchange="toggleMedicoFields()">
                                <option value="medico">Médico</option>
                                <option value="fisioterapeuta">Fisioterapeuta</option>
                                <option value="secretaria">Secretária</option>
                                <option value="paciente">Paciente</option>
                            </select>
                        </div>
                        <div id="medico_fields">
                            <div class="mb-3">
                                <label class="form-label">CRM</label>
                                <input type="text" class="form-control" name="crm" id="crm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Especialidade</label>
                                <input type="text" class="form-control" name="especialidade" id="especialidade">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" class="form-control" name="senha" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarNovoUsuario()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarUsuario">
                    <div class="modal-body">
                        <input type="hidden" id="editId" name="id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" id="editNome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuário</label>
                            <select class="form-select" id="editTipo" name="tipo_usuario" required onchange="toggleEditMedicoFields()">
                                <option value="admin">Administrador</option>
                                <option value="medico">Médico</option>
                                <option value="fisioterapeuta">Fisioterapeuta</option>
                                <option value="secretaria">Secretária</option>
                                <option value="paciente">Paciente</option>
                            </select>
                        </div>
                        
                        <!-- Campos específicos para médico -->
                        <div id="edit_medico_fields">
                            <div class="mb-3">
                                <label class="form-label">CRM</label>
                                <input type="text" class="form-control" name="crm" id="editCrm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Especialidade</label>
                                <input type="text" class="form-control" name="especialidade" id="editEspecialidade">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" class="form-control" id="editSenha" name="senha">
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
        function toggleMedicoFields() {
            const tipoUsuario = document.getElementById('tipo_usuario').value;
            const medicoFields = document.getElementById('medico_fields');
            const crmInput = document.getElementById('crm');
            const especialidadeInput = document.getElementById('especialidade');
            
            if (tipoUsuario === 'medico') {
                medicoFields.style.display = 'block';
                crmInput.required = true;
                especialidadeInput.required = true;
            } else {
                medicoFields.style.display = 'none';
                crmInput.required = false;
                especialidadeInput.required = false;
            }
        }

        // Executar ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            toggleMedicoFields();
        });

        function salvarNovoUsuario() {
            const form = document.getElementById('formNovoUsuario');
            const formData = new FormData(form);
            formData.append('acao', 'novo');

            fetch('pages/admin/usuarios_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Usuário criado com sucesso!');
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao criar usuário');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar a requisição');
            });
        }

        // Função para carregar dados do usuário no modal de edição
        function carregarUsuario(id) {
            fetch(`pages/admin/get_usuario.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.message || 'Erro ao carregar dados do usuário');
                    }
                    
                    document.getElementById('editId').value = data.id;
                    document.getElementById('editNome').value = data.nome;
                    document.getElementById('editEmail').value = data.email;
                    document.getElementById('editTipo').value = data.tipo_usuario;
                    
                    // Carregar dados médicos se existirem
                    if (data.crm) {
                        document.getElementById('editCrm').value = data.crm;
                    }
                    if (data.especialidade) {
                        document.getElementById('editEspecialidade').value = data.especialidade;
                    }
                    
                    toggleEditMedicoFields();
                    
                    // Abrir o modal
                    new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados do usuário');
                });
        }

        function toggleEditMedicoFields() {
            const tipoUsuario = document.getElementById('editTipo').value;
            const medicoFields = document.getElementById('edit_medico_fields');
            const crmInput = document.getElementById('editCrm');
            const especialidadeInput = document.getElementById('editEspecialidade');
            
            if (tipoUsuario === 'medico') {
                medicoFields.style.display = 'block';
                crmInput.required = true;
                especialidadeInput.required = true;
            } else {
                medicoFields.style.display = 'none';
                crmInput.required = false;
                especialidadeInput.required = false;
            }
        }

        // Função para salvar edição do usuário
        document.getElementById('formEditarUsuario').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'editar');

            fetch('pages/admin/usuarios_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Usuário atualizado com sucesso!');
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao atualizar usuário');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar a requisição');
            });
        });
    </script>
</body>
</html>