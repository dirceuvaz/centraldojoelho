<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Buscar informações do paciente e data da cirurgia
$stmt = $conn->prepare("SELECT u.nome, u.email, p.data_cirurgia 
                       FROM usuarios u 
                       LEFT JOIN pacientes p ON u.id = p.id_usuario 
                       WHERE u.id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculando a semana de reabilitação
$semanas = "Pré-Operatório";
if (!empty($usuario['data_cirurgia'])) {
    $data_atual = time();
    $data_cirurgia = strtotime($usuario['data_cirurgia']);
    $resultados_dos_dias = ($data_atual - $data_cirurgia) / (60 * 60 * 24);
    $duracao_dias = (int) $resultados_dos_dias;

    if ($duracao_dias <= 7) {
        $semanas = "Primeira Semana";
    } elseif ($duracao_dias <= 14) {
        $semanas = "Segunda Semana";
    } elseif ($duracao_dias <= 21) {
        $semanas = "Terceira Semana";
    } elseif ($duracao_dias <= 28) {
        $semanas = "Quarta Semana";
    } elseif ($duracao_dias <= 35) {
        $semanas = "Quinta Semana";
    } elseif ($duracao_dias <= 70) {
        $semanas = "Sexta Semana a Décima Semana";
    } elseif ($duracao_dias <= 140) {
        $semanas = "Décima Primeira a Vigésima Semana";
    } elseif ($duracao_dias <= 180) {
        $semanas = "Sexto Mês";
    }
}

// Buscar perguntas associadas ao paciente
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_perguntas
    FROM perguntas
    WHERE id_paciente = ?
");
$stmt->execute([$user_id]);
$perguntas = $stmt->fetch();

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
        .card-dashboard {
            transition: transform 0.2s;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .icon-large {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .card-text {
            color: #6c757d;
        }

        @keyframes pulseGreen {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
                transform: scale(1);
            }
            70% {
                box-shadow: 0 0 20px 10px rgba(40, 167, 69, 0.3);
                transform: scale(1.02);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
                transform: scale(1);
            }
        }

        .card-reabilitacao .card-body.text-center {
            animation: pulseGreen 2s infinite;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .card-reabilitacao:hover .card-body.text-center {
            box-shadow: 0 0 25px 15px rgba(40, 167, 69, 0.4);
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #231F5D;">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=paciente/perfil">
                            <i class="bi bi-person-circle"></i> Meu Perfil
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
                <h2>Painel do Paciente</h2>
                <p class="text-muted">Bem-vindo(a), <?php echo htmlspecialchars($usuario['nome']); ?></p>
            </div>
        </div>

        <div class="p-5 mb-4 bg-light rounded-3">
            <div class="container-fluid py-2 text-center">
                <h1 class="display-5 fw-bold">Bem-vindo ao Central do Joelho!</h1>
                <p class="col-sd-8 fs-4 mx-auto">
                    Aqui você pode monitorar seu progresso, acessar exercícios em <a href="index.php?page=paciente/reabilitacao_paciente" class="fw-bold text-primary text-decoration-none">REABILITAÇÃO</a> recomendados e responder perguntas importantes sobre seu tratamento.
                </p>           
            </div>
        </div>

        <div class="row g-4">
            <!-- Responder -->
            <div class="col-md-3">
                <a href="index.php?page=paciente/perguntas" class="text-decoration-none">
                    <div class="card card-dashboard h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-question-circle icon-large text-primary"></i>
                            <h3 class="card-title">Perguntas</h3>
                            <p class="card-text">Responder perguntas</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Reabilitação -->
            <div class="col-md-3">
                <a href="index.php?page=paciente/reabilitacao_paciente" class="text-decoration-none">
                    <div class="card card-dashboard h-100 card-reabilitacao">
                        <div class="card-body text-center">                            
                            <i class="bi bi-calendar-check icon-large" style="color: #28a745;"></i>
                            <h3 class="card-title">Reabilitação</h3>
                            <p class="card-text">Minhas Orientações</p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?php echo htmlspecialchars($semanas); ?></span>
                            </div>                        
                        </div>
                    </div>
                </a>
            </div>

            <!-- Vídeos -->
            <div class="col-md-3">
                <a href="#" class="text-decoration-none">
                    <div class="card card-dashboard h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-play-circle icon-large text-warning"></i>
                            <h3 class="card-title">Vídeos (Em Manutenção)</h3>
                            <p class="card-text">Assista aos vídeos</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>