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
                                        <i class="bi bi-life-preserver"></i>&nbsp; Atendiamentos
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
                    <h4 class="mb-0">Gerenciamento de Perguntas</h4>
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
                            switch ($_GET['msg']) {
                                case 'Pergunta_criada':
                                    echo 'Pergunta criada com sucesso!';
                                    break;
                                case 'Pergunta_atualizada':
                                    echo 'Pergunta atualizada com sucesso!';
                                    break;
                                case 'Pergunta_excluida':
                                    echo 'Pergunta excluída com sucesso!';
                                    break;
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php
                    require_once __DIR__ . '/../../config/database.php';
                    require_once(__DIR__ . '/../../components/filtro_form.php');

                    $conn = getConnection();

                    // Buscar médicos para o filtro
                    $stmt = $conn->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico' ORDER BY nome");
                    $medicos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    // Buscar reabilitações para o filtro
                    $stmt = $conn->query("SELECT id, titulo FROM reabilitacao ORDER BY titulo");
                    $reabilitacoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    // Configuração do filtro
                    $filtro_config = [
                        'page' => 'admin/perguntas',
                        'action' => '',
                        'filters' => [
                            [
                                'type' => 'text',
                                'name' => 'search',
                                'label' => 'Buscar',
                                'placeholder' => 'Buscar por título ou descrição',
                                'col' => '6'
                            ],
                            [
                                'type' => 'select',
                                'name' => 'medico',
                                'label' => 'Médico',
                                'options' => $medicos
                            ],
                            [
                                'type' => 'select',
                                'name' => 'reabilitacao',
                                'label' => 'Reabilitação',
                                'options' => $reabilitacoes
                            ]
                        ]
                    ];

                    // Construir a query baseada nos filtros
                    $where_conditions = [];
                    $params = [];

                    if (!empty($_GET['search'])) {
                        $where_conditions[] = "(p.titulo LIKE ? OR p.descricao LIKE ?)";
                        $search_term = "%" . $_GET['search'] . "%";
                        $params[] = $search_term;
                        $params[] = $search_term;
                    }

                    if (!empty($_GET['medico'])) {
                        $where_conditions[] = "p.id_medico = ?";
                        $params[] = $_GET['medico'];
                    }

                    if (!empty($_GET['reabilitacao'])) {
                        $where_conditions[] = "p.id_reabilitacao = ?";
                        $params[] = $_GET['reabilitacao'];
                    }

                    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

                    // Contar total de registros
                    $count_query = "SELECT COUNT(*) FROM perguntas p $where_clause";
                    $stmt = $conn->prepare($count_query);
                    $stmt->execute($params);
                    $total_records = $stmt->fetchColumn();

                    // Configuração da paginação
                    $items_per_page = 10;
                    $total_pages = ceil($total_records / $items_per_page);
                    $page = isset($_GET['p']) ? max(1, min((int)$_GET['p'], $total_pages)) : 1;
                    $offset = ($page - 1) * $items_per_page;

                    // Query principal com filtros
                    $query = "
                        SELECT p.*, u.nome as medico_nome, r.titulo as reabilitacao_titulo
                        FROM perguntas p
                        LEFT JOIN usuarios u ON p.id_medico = u.id
                        LEFT JOIN reabilitacao r ON p.id_reabilitacao = r.id
                        $where_clause
                        ORDER BY p.data_criacao DESC
                        LIMIT $items_per_page OFFSET $offset
                    ";

                    $stmt = $conn->prepare($query);
                    $stmt->execute($params);
                    $perguntas = $stmt->fetchAll();
                    ?>

                    <?php renderFiltroForm($filtro_config); ?>

                    <div class="mb-4">
                        <a href="index.php?page=admin/perguntas_form" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Pergunta
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sequência</th>
                                            <th>Título</th>
                                            <th>Reabilitação</th>
                                            <th>Médico</th>
                                            <th>Data de Criação</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($perguntas as $pergunta): ?>
                                            <tr>
                                                <td><?php echo $pergunta['sequencia']; ?></td>
                                                <td><?php echo $pergunta['titulo']; ?></td>
                                                <td><?php echo $pergunta['reabilitacao_titulo']; ?></td>
                                                <td><?php echo $pergunta['medico_nome']; ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?></td>
                                                <td>
                                                    <a href="index.php?page=admin/perguntas_form&id=<?php echo $pergunta['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="confirmarExclusao(<?php echo $pergunta['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($perguntas)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhuma pergunta encontrada.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Navegação de página">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=admin/perguntas&p=<?php echo ($page - 1); ?>" aria-label="Anterior">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=admin/perguntas&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=admin/perguntas&p=<?php echo ($page + 1); ?>" aria-label="Próximo">
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
    <script>
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir esta pergunta?')) {
                window.location.href = `index.php?page=admin/perguntas_process&action=excluir&id=${id}`;
            }
        }
    </script>
</body>
</html>
