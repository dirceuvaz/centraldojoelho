<?php
require_once 'config/database.php';

// Verifica se a sessão já não está ativa antes de iniciá-la
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$pdo = getConnection();

// Buscar estatísticas
$stats = [];

// Total de usuários
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE status = 'ativo'");
$stats['total_usuarios'] = $stmt->fetchColumn();

// Total por tipo de usuário
$stmt = $pdo->query("SELECT tipo_usuario, COUNT(*) as total FROM usuarios WHERE status = 'ativo' GROUP BY tipo_usuario");
$stats['usuarios_por_tipo'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Total de reabilitações
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reabilitacao WHERE status = 'ativo'");
$stats['total_reabilitacoes'] = $stmt->fetchColumn();

// Total de perguntas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM perguntas");
$stats['total_perguntas'] = $stmt->fetchColumn();

// Total de cirurgias
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pacientes WHERE status = 'ativo'");
$stats['total_cirurgias'] = $stmt->fetchColumn();

// Tipos de problemas
$stmt = $pdo->query("SELECT tr.descricao, COUNT(r.id) as total 
                     FROM tipos_reabilitacao tr 
                     LEFT JOIN reabilitacao r ON r.tipo_problema = tr.id 
                     GROUP BY tr.id, tr.descricao 
                     ORDER BY total DESC");
$stats['tipos_problemas'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #231F5D;
        }
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
        .main-content {
            padding: 20px;
        }
        .top-navbar {
            background-color: #ffffff;
            padding: 15px 20px;
            color: #333333;
            border-bottom: 1px solid #dee2e6;
        }
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        .card-body {
            border-left: 4px solid transparent;
        }
        .card:nth-child(1) .card-body {
            border-left-color: #0d6efd;
        }
        .card:nth-child(2) .card-body {
            border-left-color: #198754;
        }
        .card:nth-child(3) .card-body {
            border-left-color: #dc3545;
        }
        .card:nth-child(4) .card-body {
            border-left-color: #ffc107;
        }
        .nav-link {
            font-size: 14px;
        }
        .accordion-button {
            font-size: 14px;
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

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h4 class="mb-0">Painel de Controle</h4>
                    </div>
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
                    <div class="row g-4">
                        <!-- Card Usuários -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0">Usuários</h6>
                                        <i class="bi bi-people fs-4 text-primary"></i>
                                    </div>
                                    <h2 class="mb-2"><?php echo $stats['total_usuarios']; ?></h2>
                                    <div class="small text-muted">
                                        <?php foreach ($stats['usuarios_por_tipo'] as $tipo => $total): ?>
                                            <div><?php echo ucfirst($tipo) . 's: ' . $total; ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Reabilitações -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0">Reabilitações</h6>
                                        <i class="bi bi-activity fs-4 text-success"></i>
                                    </div>
                                    <h2 class="mb-2"><?php echo $stats['total_reabilitacoes']; ?></h2>
                                    <div class="small text-muted">
                                        Total de reabilitações ativas
                                        <div class="mt-2">
                                            Perguntas cadastradas: <?php echo $stats['total_perguntas']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Cirurgias -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0">Cirurgias</h6>
                                        <i class="bi bi-bandaid"></i>
                                    </div>
                                    <h2 class="mb-2"><?php echo $stats['total_cirurgias']; ?></h2>
                                    <div class="small text-muted">Total de cirurgias registradas</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Tipos de Problemas -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0">Tipos de Problemas</h6>
                                        <i class="bi bi-clipboard-data fs-4 text-warning"></i>
                                    </div>
                                    <div class="small">
                                        <?php foreach ($stats['tipos_problemas'] as $tipo => $total): ?>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><?php echo $tipo; ?></span>
                                                <span class="badge bg-primary"><?php echo $total; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
