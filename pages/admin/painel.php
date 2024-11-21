<?php
require_once 'config/database.php';
$pdo = getConnection();
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
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            padding: 20px 0;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 10px 20px;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
        }
        .sidebar .nav-link.active {
            background-color: #0d6efd;
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
                        <a class="nav-link active" href="index.php?page=admin/painel">
                            <i class="bi bi-speedometer2"></i> Painel
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <small class="text-muted px-3">Reabilitação</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/consultas">
                            <i class="bi bi-calendar-check"></i> Consultas
                        </a>
                    </li>                   
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/exercicios">
                            <i class="bi bi-clock-fill"></i> Reabilita
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
                        <a class="nav-link" href="index.php?page=admin/usuarios">
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
                    <h2>Painel Administrativo</h2>
                    <hr>

                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-people-fill"></i> Usuários
                                    </h5>
                                    <p class="card-text">Gerenciar usuários do sistema</p>
                                    <a href="index.php?page=admin/usuarios" class="btn btn-light">Acessar</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-clock-fill"></i> Exercícios
                                    </h5>
                                    <p class="card-text">Gerenciar Exercícios criados </p>
                                    <a href="index.php?page=admin/exercicios" class="btn btn-light">Acessar</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-question-circle"></i> Perguntas
                                    </h5>
                                    <p class="card-text">Gerenciar perguntas criadas</p>
                                    <a href="index.php?page=admin/configuracoes" class="btn btn-light">Acessar</a>
                                </div>
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
