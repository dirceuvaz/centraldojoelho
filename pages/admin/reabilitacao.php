<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();

// Processar exclusão se solicitado
if (isset($_POST['delete_id'])) {
    try {
        // Primeiro verifica se existem perguntas associadas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM perguntas WHERE id_reabilitacao = ?");
        $stmt->execute([$_POST['delete_id']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            header('Location: index.php?page=admin/reabilitacao&error=Não é possível excluir. Existem perguntas associadas a esta reabilitação.');
            exit;
        }
        
        // Se não houver perguntas, pode excluir
        $stmt = $conn->prepare("UPDATE reabilitacao SET status = 'inativo' WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        header('Location: index.php?page=admin/reabilitacao&msg=Reabilitacao_deletada');
        exit;
    } catch (PDOException $e) {
        $error = "Erro ao excluir: " . $e->getMessage();
    }
}

// Configuração da paginação
$items_per_page = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

// Buscar total de registros
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM reabilitacao 
    WHERE status = 'ativo'
");
$stmt->execute();
$total_items = $stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// Buscar todas as reabilitações ativas com paginação e contagem de perguntas
$stmt = $conn->prepare("
    SELECT r.*, m.nome as nome_medico, 
           SUBSTRING(r.texto, 1, 100) as texto_preview,
           tr.descricao as nome_tipo_problema,
           (SELECT COUNT(*) FROM perguntas p WHERE p.id_reabilitacao = r.id) as total_perguntas,
           r.duracao_dias as duracao,
           u.nome as nome_paciente
    FROM reabilitacao r
    LEFT JOIN usuarios m ON r.id_medico = m.id
    LEFT JOIN usuarios u ON r.id_paciente = u.id
    LEFT JOIN tipos_reabilitacao tr ON r.tipo_problema = tr.id
    WHERE r.status = 'ativo'
    ORDER BY r.data_criacao DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reabilitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Paciente - Central do Joelho</title>
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
                    <h4 class="mb-0">Gerenciamento de Reabilitação</h4>
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
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            $msg = $_GET['msg'];
                            switch($msg) {
                                case 'Reabilitacao_deletada':
                                    echo 'Reabilitação excluída com sucesso!';
                                    break;
                                case 'Reabilitacao_criada':
                                    echo 'Reabilitação criada com sucesso!';
                                    break;
                                case 'Reabilitacao_atualizada':
                                    echo 'Reabilitação atualizada com sucesso!';
                                    break;
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_GET['error']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Botão Nova Reabilitação -->
                    <div class="mb-4">
                        <a href="index.php?page=admin/reabilitacao_form" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Reabilitação
                        </a>
                    </div>

                    <!-- Tabela de Reabilitações -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <th>Médico</th>
                                            <th>Paciente</th>
                                            <th>Duração (dias)</th>
                                            <th>Perguntas</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reabilitacoes as $reabilitacao): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reabilitacao['titulo'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($reabilitacao['nome_tipo_problema'] ?? 'Não definido'); ?></td>
                                                <td><?php echo htmlspecialchars($reabilitacao['nome_medico'] ?? 'Não atribuído'); ?></td>
                                                <td><?php echo htmlspecialchars($reabilitacao['nome_paciente'] ?? 'Não atribuído'); ?></td>
                                                <td><?php echo $reabilitacao['duracao'] ?? 'Não definido'; ?></td>
                                                <td><?php echo $reabilitacao['total_perguntas'] ?? '0'; ?></td>
                                                <td><?php echo htmlspecialchars($reabilitacao['status'] ?? ''); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="index.php?page=admin/reabilitacao_form&id=<?php echo $reabilitacao['id']; ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="index.php?page=admin/perguntas&reabilitacao_id=<?php echo $reabilitacao['id']; ?>" 
                                                           class="btn btn-sm btn-info">
                                                            <i class="bi bi-list-check"></i> Perguntas
                                                        </a>
                                                        <?php if ($reabilitacao['total_perguntas'] == 0): ?>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Tem certeza que deseja excluir esta reabilitação?');">
                                                            <input type="hidden" name="delete_id" value="<?php echo $reabilitacao['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Navegação de página">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=admin/reabilitacao&p=<?php echo ($page - 1); ?>" aria-label="Anterior">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=admin/reabilitacao&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=admin/reabilitacao&p=<?php echo ($page + 1); ?>" aria-label="Próximo">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>      
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
