<?php
require_once __DIR__ . '/../../config/database.php';
require_once(__DIR__ . '/../../components/filtro_form.php');

$conn = getConnection();

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// Processa exclusão se solicitado
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'inativo' WHERE id = ?");
        if ($stmt->execute([$_POST['delete_id']])) {
            header('Location: index.php?page=admin/usuarios&msg=Usuario_excluido_com_sucesso');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erro ao excluir: " . $e->getMessage();
    }
}

// Configuração do filtro
$filtro_config = [
    'page' => 'admin/usuarios',
    'action' => '',
    'filters' => [
        [
            'type' => 'text',
            'name' => 'search',
            'label' => 'Buscar',
            'placeholder' => 'Buscar por nome ou email',
            'col' => '6'
        ],
        [
            'type' => 'select',
            'name' => 'tipo',
            'label' => 'Tipo de Usuário',
            'options' => [
                'admin' => 'Administrador',
                'medico' => 'Médico',
                'paciente' => 'Paciente'
            ]
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'label' => 'Status',
            'options' => [
                'ativo' => 'Ativo',
                'inativo' => 'Inativo'
            ]
        ]
    ]
];

// Construir a query baseada nos filtros
$where_conditions = [];
$params = [];

if (!empty($_GET['search'])) {
    $where_conditions[] = "(nome LIKE ? OR email LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($_GET['tipo'])) {
    $where_conditions[] = "tipo_usuario = ?";
    $params[] = $_GET['tipo'];
}

if (!empty($_GET['status'])) {
    $where_conditions[] = "status = ?";
    $params[] = $_GET['status'];
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Contar total de registros
$count_query = "SELECT COUNT(*) FROM usuarios $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Configuração da paginação
$items_per_page = 10;
$total_records = (int)$total_records;
$total_pages = (int)ceil($total_records / $items_per_page);
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $items_per_page;

// Query principal com filtros
$query = "
    SELECT id, nome, email, tipo_usuario, status
    FROM usuarios
    $where_clause
    ORDER BY id DESC
    LIMIT $items_per_page OFFSET $offset
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
?>

<?php
$page = 'admin/usuarios'; // Define a página atual para o sidebar
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
         .sidebar {
            min-height: 100vh;
            background-color: #231F5D;
            padding: 20px 0;
            color: white;
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
        .sidebar .accordion-button {
            background-color: transparent;
            color: rgba(255, 255, 255, 0.85);
            padding: 10px 20px;
            border: none;
        }
        .sidebar .accordion-button:not(.collapsed) {
            background-color: transparent;
            color: white;
        }
        .sidebar .accordion-button::after {
            filter: brightness(0) invert(1);
        }
        .sidebar .accordion-body {
            background-color: transparent;
            padding: 0;
        }
        .sidebar .accordion-item {
            background-color: transparent;
            border: none;
        }
        .sidebar h5 {
            color: white;
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
                                        <i class="bi bi-life-preserver"></i>&nbsp; Atendimentos
                                </button>
                            </h2>
                            <div id="atendimentoCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">                                        
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/reabilitacao">
                                                <i class="bi bi-check-square"></i>&nbsp;  Reabilitação
                                            </a>
                                        </li>                                       
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/perguntas">
                                                <i class="bi bi-question-circle"></i>&nbsp; Perguntas
                                            </a>
                                        </li>                                       
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/cirurgias">
                                                <i class="bi bi-bandaid"></i>&nbsp; Cirurgias
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
                                    <i class="bi bi-collection-play me-2"></i>&nbsp;Mídias
                                </button>
                            </h2>
                            <div id="midiasCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/videos">
                                                <i class="bi bi-play-circle"></i>&nbsp; Vídeos
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
                                                <i class="bi bi-bell"></i>&nbsp; Notificações
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/usuarios">
                                                <i class="bi bi-people"></i>&nbsp; Usuários
                                            </a>
                                        </li>                                       
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/config-gerais">
                                                <i class="bi bi-gear"></i>&nbsp; Configurações Gerais
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
                        <nav aria-label="breadcrumb">                            
                            <ol class="breadcrumb mb-0">
                                <span class="me-3">
                                    <i class="bi bi-person"></i> 
                                    Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
                                </span>                                
                                 <a class="btn btn-outline-danger btn-sm" href="index.php?page=login_process&logout=1">
                                 <i class="bi bi-box-arrow-right"></i> Sair
                                 </a>                    
                            </ol>
                        </nav>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="main-content">
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            switch ($_GET['msg']) {
                                case 'Usuario_criado':
                                    echo 'Usuário criado com sucesso!';
                                    break;
                                case 'Usuario_atualizado':
                                    echo 'Usuário atualizado com sucesso!';
                                    break;
                                case 'Usuario_excluido_com_sucesso':
                                    echo 'Usuário excluído com sucesso!';
                                    break;
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php renderFiltroForm($filtro_config); ?>

                    <div class="mb-4">
                        <a href="index.php?page=admin/usuarios_form" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Novo Usuário
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($usuarios)): ?>
                                            <?php foreach ($usuarios as $usuario): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                    <td>
                                                        <?php 
                                                        switch ($usuario['tipo_usuario']) {
                                                            case 'admin':
                                                                echo 'Administrador';
                                                                break;
                                                            case 'medico':
                                                                echo 'Médico';
                                                                break;
                                                            case 'paciente':
                                                                echo 'Paciente';
                                                                break;
                                                            default:
                                                                echo htmlspecialchars($usuario['tipo_usuario']);
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $usuario['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                                                            <?php echo $usuario['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group">
                                                            <a href="index.php?page=admin/usuarios_form&id=<?php echo $usuario['id']; ?>" 
                                                               class="btn btn-sm btn-primary" title="Editar">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $usuario['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir"
                                                                            onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Nenhum usuário encontrado</td>
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
                                                    <a class="page-link" href="?page=admin/usuarios&p=<?php echo ((int)$page - 1); ?>">Anterior</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo (int)$i === (int)$page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=admin/usuarios&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ((int)$page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=admin/usuarios&p=<?php echo ((int)$page + 1); ?>">Próximo</a>
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
            </div>
        </div>
    </div>      
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
