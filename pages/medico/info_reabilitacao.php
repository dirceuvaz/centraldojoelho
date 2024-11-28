<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: /centraldojoelho/index.php?page=login');
    exit;
}

// Verifica se foi fornecido um ID de paciente
if (!isset($_GET['id'])) {
    header('Location: /centraldojoelho/index.php?page=medico/pacientes');
    exit;
}

$conn = getConnection();
$id_paciente = $_GET['id'];

// Buscar informações do paciente
$sql = "
    SELECT 
        u.nome as nome_paciente,
        p.data_cirurgia,
        p.problema,
        p.fisioterapeuta,
        DATEDIFF(CURRENT_DATE, p.data_cirurgia) as dias_pos_cirurgia
    FROM usuarios u
    JOIN pacientes p ON u.id = p.id_usuario
    WHERE u.id = ? AND u.tipo_usuario = 'paciente'
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_paciente]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    $_SESSION['error'] = "Paciente não encontrado.";
    header('Location: /centraldojoelho/index.php?page=medico/pacientes');
    exit;
}

// Calcular a fase atual da reabilitação
$dias_pos_cirurgia = $info['dias_pos_cirurgia'];
$fase = '';
$progresso = 0;

if ($dias_pos_cirurgia <= 30) {
    $fase = 'Fase 1 - Pós-operatório Imediato';
    $progresso = ($dias_pos_cirurgia / 30) * 100;
} elseif ($dias_pos_cirurgia <= 90) {
    $fase = 'Fase 2 - Fortalecimento Inicial';
    $progresso = ((($dias_pos_cirurgia - 30) / 60) * 100);
} elseif ($dias_pos_cirurgia <= 180) {
    $fase = 'Fase 3 - Fortalecimento Avançado';
    $progresso = ((($dias_pos_cirurgia - 90) / 90) * 100);
} else {
    $fase = 'Fase 4 - Retorno às Atividades';
    $progresso = 100;
}

$progresso = min(100, max(0, $progresso));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informações da Reabilitação - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .progress {
            height: 25px;
        }
        .fase-card {
            border-left: 5px solid #0d6efd;
        }
        .info-label {
            font-weight: bold;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../common/header_medico.php'; ?>

    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Informações da Reabilitação</h2>
                    <a href="/centraldojoelho/index.php?page=medico/pacientes" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informações do Paciente</h5>
                    </div>
                    <div class="card-body">
                        <p><span class="info-label">Nome:</span> <?php echo htmlspecialchars($info['nome_paciente']); ?></p>
                        <p><span class="info-label">Data da Cirurgia:</span> <?php echo date('d/m/Y', strtotime($info['data_cirurgia'])); ?></p>
                        <p><span class="info-label">Tipo de Cirurgia:</span> <?php echo htmlspecialchars($info['problema']); ?></p>
                        <p><span class="info-label">Fisioterapeuta:</span> <?php echo htmlspecialchars($info['fisioterapeuta']); ?></p>
                        <p><span class="info-label">Dias Pós-Cirurgia:</span> <?php echo $dias_pos_cirurgia; ?> dias</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card fase-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Fase Atual da Reabilitação</h5>
                    </div>
                    <div class="card-body">
                        <h4 class="text-primary mb-3"><?php echo $fase; ?></h4>
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: <?php echo $progresso; ?>%"
                                 aria-valuenow="<?php echo $progresso; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo round($progresso); ?>%
                            </div>
                        </div>
                        <p class="mb-0"><small class="text-muted">O progresso é calculado com base no tempo desde a cirurgia</small></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detalhes da Fase Atual</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($dias_pos_cirurgia <= 30): ?>
                            <h6>Objetivos da Fase 1:</h6>
                            <ul>
                                <li>Controle da dor e edema</li>
                                <li>Restauração da amplitude de movimento</li>
                                <li>Ativação muscular inicial</li>
                                <li>Treino de marcha com auxílio</li>
                            </ul>
                        <?php elseif ($dias_pos_cirurgia <= 90): ?>
                            <h6>Objetivos da Fase 2:</h6>
                            <ul>
                                <li>Fortalecimento muscular progressivo</li>
                                <li>Melhora do equilíbrio</li>
                                <li>Treino de marcha sem auxílio</li>
                                <li>Exercícios em cadeia cinética fechada</li>
                            </ul>
                        <?php elseif ($dias_pos_cirurgia <= 180): ?>
                            <h6>Objetivos da Fase 3:</h6>
                            <ul>
                                <li>Fortalecimento muscular avançado</li>
                                <li>Treino de agilidade</li>
                                <li>Exercícios pliométricos</li>
                                <li>Preparação para retorno às atividades</li>
                            </ul>
                        <?php else: ?>
                            <h6>Objetivos da Fase 4:</h6>
                            <ul>
                                <li>Retorno gradual às atividades esportivas</li>
                                <li>Treino específico do esporte</li>
                                <li>Prevenção de novas lesões</li>
                                <li>Manutenção do condicionamento</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
