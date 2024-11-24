<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $conn = getConnection();
        switch ($_POST['action']) {
            case 'liberar':
                if (isset($_POST['user_id'])) {
                    $stmt = $conn->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    header('Location: index.php?page=admin/usuarios&msg=Usuario_liberado');
                    exit;
                }
                break;
            case 'deletar':
                if (isset($_POST['user_id'])) {
                    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    header('Location: index.php?page=admin/usuarios&msg=Usuario_deletado');
                    exit;
                }
                break;
        }
    }
}

// Configuração da paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Buscar total de usuários
$conn = getConnection();
$stmt = $conn->query("SELECT COUNT(*) FROM usuarios");
$total_usuarios = $stmt->fetchColumn();
$total_paginas = ceil($total_usuarios / $itens_por_pagina);

// Buscar usuários da página atual
$query = "SELECT * FROM usuarios ORDER BY data_cadastro DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        .main-content {
            padding: 20px;
        }
        .top-navbar {
            background-color: #ffffff;
            padding: 15px 20px;
            color: #333333;
            border-bottom: 1px solid #dee2e6;
        }
        .top-navbar .breadcrumb-item,
        .top-navbar .breadcrumb-item.active {
            color: #333333;
        }
        .top-navbar .breadcrumb-item + .breadcrumb-item::before {
            color: #666666;
        }
        .top-navbar .btn-link {
            color: #333333 !important;
            text-decoration: none;
        }
        .top-navbar .btn-link:hover {
            color: #000000 !important;
        }
        .card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .icon-large {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        .sidebar h5 {
            color: white;
        }
        .accordion {
            background: none;
            border: none;
        }
        .accordion-item {
            background: none;
            border: none;
        }
        .accordion-button {
            background-color: transparent !important;
            color: rgba(255, 255, 255, 0.85) !important;
            padding: 10px 20px;
            box-shadow: none !important;
        }
        .accordion-button:not(.collapsed) {
            background-color: transparent;
            color: white !important;
        }
        .accordion-button::after {
            filter: invert(1);
        }
        .accordion-collapse {
            background: none;
        }
        .accordion-body {
            padding: 0;
        }
        .accordion-body .nav-link {
            padding-left: 40px !important;
        }
        .accordion-body .nav-link:hover {
            padding-left: 45px !important;
        }
        .table th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        .table td {
            vertical-align: middle;
        }
        .btn-group .btn {
            margin: 0 2px;
        }
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
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

                     <!-- Menu Atendimento -->
                      <div class="accordion accordion-flush" id="menuAtendimento">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#atendimentoCollapse" aria-expanded="true">
                                    <i class="bi bi-clipboard-pulse me-2"></i> Atendimento
                                </button>
                            </h2>
                            <div id="atendimentoCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">                                        
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/reabilitacao">
                                                <i class="bi bi-check-square"></i> Reabilitação
                                            </a>
                                        </li>                                       
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/perguntas">
                                                <i class="bi bi-question-circle"></i> Perguntas
                                            </a>
                                        </li>                                       
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/cirurgias">
                                                <i class="bi bi-bandaid"></i> Cirurgias
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu Mídias -->
                    <div class="accordion accordion-flush" id="menuMidias">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#midiasCollapse" aria-expanded="true">
                                    <i class="bi bi-collection-play me-2"></i> Mídias
                                </button>
                            </h2>
                            <div id="midiasCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/videos">
                                                <i class="bi bi-play-circle"></i> Vídeos
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu Configurações -->
                    <div class="accordion accordion-flush" id="menuConfiguracoes">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#configuracoesCollapse" aria-expanded="true">
                                    <i class="bi bi-gear me-2"></i> Configurações
                                </button>
                            </h2>
                            <div id="configuracoesCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/notificacoes">
                                                <i class="bi bi-bell"></i> Notificações
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/usuarios">
                                                <i class="bi bi-people"></i> Usuários
                                            </a>
                                        </li>                                       
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/config-gerais">
                                                <i class="bi bi-gear"></i> Configurações Gerais
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                

                </ul>
            </div>

            <div class="col-md-9 col-lg-10 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Gerenciamento de Usuários</h4>
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
                    <!-- Botão Novo Usuário -->
                    <div class="mb-4">
                        <a href="index.php?page=admin/usuarios_form" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Novo Usuário
                        </a>
                    </div>

                    <!-- Tabela de Usuários -->
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
                                            <th>Data da Cirurgia</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): 
                                            // Buscar data de cirurgia se for paciente
                                            $data_cirurgia = '';
                                            if ($usuario['tipo_usuario'] === 'paciente') {
                                                $stmt = $conn->prepare("SELECT data_cirurgia FROM pacientes WHERE id_usuario = ?");
                                                $stmt->execute([$usuario['id']]);
                                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                $data_cirurgia = $result ? date('d/m/Y', strtotime($result['data_cirurgia'])) : 'Não definida';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $usuario['status'] === 'ativo' ? 'bg-success' : 
                                                            ($usuario['status'] === 'pendente' ? 'bg-warning' : 'bg-danger'); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($usuario['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $usuario['tipo_usuario'] === 'paciente' ? $data_cirurgia : '-'; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($usuario['status'] === 'pendente'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="liberar">
                                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm" title="Liberar Acesso">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                        
                                                        <a href="index.php?page=admin/usuarios_form&id=<?php echo $usuario['id']; ?>" 
                                                           class="btn btn-primary btn-sm" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        
                                                        <button type="button" class="btn btn-warning btn-sm" title="Alterar Senha"
                                                                onclick="abrirModalSenha(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')">
                                                            <i class="bi bi-key"></i>
                                                        </button>

                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                                            <input type="hidden" name="action" value="deletar">
                                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <!-- Paginação -->
                                <?php if ($total_paginas > 1): ?>
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div>
                                        <?php if ($pagina_atual > 1): ?>
                                            <a href="index.php?page=admin/usuarios&pagina=<?php echo ($pagina_atual - 1); ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-chevron-left"></i> Anterior
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-center">
                                        <span class="text-muted">
                                            Página <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?>
                                        </span>
                                    </div>

                                    <div>
                                        <?php if ($pagina_atual < $total_paginas): ?>
                                            <a href="index.php?page=admin/usuarios&pagina=<?php echo ($pagina_atual + 1); ?>" 
                                               class="btn btn-outline-primary">
                                                Próxima <i class="bi bi-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div class="modal fade" id="modalAlterarSenha" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Alterar Senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Alterando senha para: <strong id="nomeUsuario"></strong></p>
                    <div class="mb-3">
                        <label for="novaSenha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="novaSenha" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmarSenha" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirmarSenha" required>
                    </div>
                    <div id="senhaFeedback" class="invalid-feedback" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="alterarSenha()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let usuarioIdAtual = null;
        const modalAlterarSenha = new bootstrap.Modal(document.getElementById('modalAlterarSenha'));

        function abrirModalSenha(userId, nome) {
            usuarioIdAtual = userId;
            document.getElementById('nomeUsuario').textContent = nome;
            document.getElementById('novaSenha').value = '';
            document.getElementById('confirmarSenha').value = '';
            document.getElementById('senhaFeedback').style.display = 'none';
            modalAlterarSenha.show();
        }

        function alterarSenha() {
            const novaSenha = document.getElementById('novaSenha').value;
            const confirmarSenha = document.getElementById('confirmarSenha').value;
            const feedback = document.getElementById('senhaFeedback');

            if (novaSenha !== confirmarSenha) {
                feedback.textContent = 'As senhas não coincidem!';
                feedback.style.display = 'block';
                return;
            }

            if (novaSenha.length < 6) {
                feedback.textContent = 'A senha deve ter pelo menos 6 caracteres!';
                feedback.style.display = 'block';
                return;
            }

            // Enviar requisição para alterar a senha
            fetch('index.php?page=admin/alterar_senha', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${usuarioIdAtual}&nova_senha=${encodeURIComponent(novaSenha)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalAlterarSenha.hide();
                    alert('Senha alterada com sucesso!');
                } else {
                    feedback.textContent = data.message;
                    feedback.style.display = 'block';
                }
            })
            .catch(error => {
                feedback.textContent = 'Erro ao alterar a senha. Tente novamente.';
                feedback.style.display = 'block';
            });
        }
    </script>
</body>
</html>
