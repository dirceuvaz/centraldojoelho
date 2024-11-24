<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// Processa exclusão antes de qualquer output
if (isset($_POST['delete_id'])) {
    $conn = getConnection();
    $id = $_POST['delete_id'];
    
    // Query simples para atualizar o status para "Cancelada"
    $stmt = $conn->prepare("UPDATE pacientes SET status = 'Cancelada' WHERE id = ?");
    if ($stmt->execute([$id])) {
        header('Location: index.php?page=admin/cirurgias&msg=Cirurgia_cancelada_com_sucesso');
        exit;
    }
}

// Função auxiliar para tratar valores nulos
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Define a ordem e os nomes amigáveis das colunas
$column_order = ['id', 'nome_paciente', 'nome_medico', 'data_cirurgia', 'tipo_cirurgia', 'fisioterapeuta', 'status'];
$friendly_names = [
    'id' => 'ID',
    'nome_paciente' => 'Nome do Paciente',
    'nome_medico' => 'Nome do Médico',
    'data_cirurgia' => 'Data da Cirurgia',
    'tipo_cirurgia' => 'Tipo de Cirurgia',
    'fisioterapeuta' => 'Fisioterapeuta',
    'status' => 'Status'
];

$conn = getConnection();

// Configuração da paginação
$items_per_page = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

// Busca o total de registros
$stmt = $conn->query("SELECT COUNT(*) FROM pacientes WHERE data_cirurgia IS NOT NULL AND status != 'Cancelada'");
$total_items = $stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// Query para buscar pacientes com cirurgias e seus respectivos médicos
$sql = "SELECT p.id,
               u.nome as nome_paciente,
               m.nome as nome_medico,
               p.data_cirurgia,
               p.problema as tipo_cirurgia,
               p.fisioterapeuta,
               p.status as status
        FROM pacientes p
        LEFT JOIN usuarios u ON p.id_usuario = u.id
        LEFT JOIN usuarios m ON p.medico = m.id
        WHERE p.data_cirurgia IS NOT NULL
        AND p.status != 'Cancelada'
        ORDER BY p.id DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cirurgias = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <h4 class="mb-0">Gerenciamento de Cirurgias</h4>
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person"></i> 
                            Olá, <?php 
                            echo h($_SESSION['user_nome']); ?>
                        </span>
                        <a class="btn btn-outline-danger btn-sm" href="index.php?page=login_process&logout=1">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="main-content">
                


                  

                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Gerenciamento de Cirurgias</h1>
                        
                        <?php if (isset($_GET['msg'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php 
                                $msg = str_replace('_', ' ', $_GET['msg']);
                                echo h($msg);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <a href="index.php?page=admin/cirurgia_form" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Nova Cirurgia
                            </a>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr class="table-light">
                                                <?php if (!empty($cirurgias)): ?>
                                                    <?php foreach ($column_order as $coluna): ?>
                                                        <?php if (isset($cirurgias[0][$coluna]) || in_array($coluna, ['nome_paciente', 'nome_medico'])): ?>
                                                            <th class="align-middle">
                                                                <?php echo h($friendly_names[$coluna]); ?>
                                                            </th>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <th class="align-middle text-center">Ações</th>
                                                <?php else: ?>
                                                    <th>ID</th>
                                                    <th>Informações da Cirurgia</th>
                                                    <th>Ações</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($cirurgias)): ?>
                                                <?php foreach ($cirurgias as $cirurgia): ?>
                                                    <tr>
                                                        <?php foreach ($column_order as $coluna): ?>
                                                            <?php if (isset($cirurgia[$coluna]) || in_array($coluna, ['nome_paciente', 'nome_medico'])): ?>
                                                                <td>
                                                                    <?php
                                                                    switch ($coluna) {
                                                                        case 'data_cirurgia':
                                                                            echo date('d/m/Y', strtotime($cirurgia[$coluna]));
                                                                            break;
                                                                        default:
                                                                            echo h($cirurgia[$coluna] ?? '');
                                                                    }
                                                                    ?>
                                                                </td>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                        <td class="text-center">
                                                            <div class="btn-group">
                                                                <a href="index.php?page=admin/cirurgia_form&id=<?php echo $cirurgia['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" title="Editar">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $cirurgia['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" title="Remover" 
                                                                            onclick="return confirm('Tem certeza que deseja remover esta cirurgia?')">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">Nenhuma cirurgia encontrada</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                    <!-- Paginação -->
                                    <?php if ($total_pages > 1): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div>
                                            Mostrando página <?php echo $page; ?> de <?php echo $total_pages; ?>
                                        </div>
                                        <nav aria-label="Navegação de páginas">
                                            <ul class="pagination mb-0">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=admin/cirurgias&p=<?php echo ($page - 1); ?>">Anterior</a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?page=admin/cirurgias&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=admin/cirurgias&p=<?php echo ($page + 1); ?>">Próxima</a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
