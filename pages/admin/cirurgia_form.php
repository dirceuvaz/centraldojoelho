<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// Função auxiliar para tratar valores nulos
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Inicializa as variáveis
$cirurgia = [];
$conn = getConnection();

// Se for edição, busca os dados da cirurgia
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT p.*, 
                                  u.nome as nome_paciente,
                                  m.nome as nome_medico,
                                  m.id as id_medico,
                                  f.nome as nome_fisioterapeuta,
                                  f.id as id_fisioterapeuta
                           FROM pacientes p
                           LEFT JOIN usuarios u ON p.id_usuario = u.id
                           LEFT JOIN usuarios m ON p.medico = m.id
                           LEFT JOIN usuarios f ON p.fisioterapeuta = f.id
                           WHERE p.id = ?");
    $stmt->execute([$_GET['id']]);
    $cirurgia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cirurgia) {
        header('Location: index.php?page=admin/cirurgias&msg=Cirurgia_nao_encontrada');
        exit;
    }
}

// Busca médicos ativos
$stmt = $conn->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico' AND status = 'ativo' ORDER BY nome");
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca pacientes ativos
$stmt = $conn->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'paciente' AND status = 'ativo' ORDER BY nome");
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca fisioterapeutas únicos da tabela pacientes
$stmt = $conn->query("SELECT DISTINCT fisioterapeuta FROM pacientes WHERE fisioterapeuta IS NOT NULL AND fisioterapeuta != '' ORDER BY fisioterapeuta");
$fisioterapeutas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Busca tipos de problemas únicos da tabela pacientes
$stmt = $conn->query("SELECT DISTINCT problema FROM pacientes WHERE problema IS NOT NULL AND problema != '' ORDER BY problema");
$problemas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['id']) && $_POST['id']) {
            // Atualizar
            $sql = "UPDATE pacientes SET 
                    medico = :medico,
                    id_usuario = :id_usuario,
                    data_cirurgia = :data_cirurgia,
                    problema = :problema,
                    fisioterapeuta = :fisioterapeuta,
                    status = :status
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'medico' => $_POST['medico'],
                'id_usuario' => $_POST['paciente'],
                'data_cirurgia' => $_POST['data_cirurgia'],
                'problema' => $_POST['problema'],
                'fisioterapeuta' => $_POST['fisioterapeuta'],
                'status' => $_POST['status'],
                'id' => $_POST['id']
            ]);
            
            header('Location: index.php?page=admin/cirurgias&msg=Cirurgia_atualizada_com_sucesso');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erro ao salvar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($cirurgia['id']) ? 'Editar' : 'Nova'; ?> Cirurgia - Central do Joelho</title>
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
                    <h5 class="text-white">Central do Joelho</h5>
                </div>
                <ul class="nav flex-column">             
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/painel">
                            <i class="bi bi-speedometer2"></i> Painel
                        </a>
                        <div class="nav-link">
                            <a href="index.php?page=admin/cirurgias" class="btn btn-warning w-100">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>                       
                    </li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-10 main-content">
                <div class="container px-4">
                    <h1 class="mt-4"><?php echo isset($cirurgia['id']) ? 'Editar' : 'Nova'; ?> Cirurgia</h1>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo h($error); ?></div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <?php if (isset($cirurgia['id'])): ?>
                                    <input type="hidden" name="id" value="<?php echo h($cirurgia['id']); ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="paciente" class="form-label">Paciente</label>
                                    <select name="paciente" id="paciente" class="form-select" required>
                                        <option value="">Selecione um paciente</option>
                                        <?php foreach ($pacientes as $paciente): ?>
                                            <option value="<?php echo h($paciente['id']); ?>" <?php echo ($cirurgia['id_usuario'] ?? '') == $paciente['id'] ? 'selected' : ''; ?>>
                                                <?php echo h($paciente['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="medico" class="form-label">Médico</label>
                                    <select name="medico" id="medico" class="form-select" required>
                                        <option value="">Selecione um médico</option>
                                        <?php foreach ($medicos as $medico): ?>
                                            <option value="<?php echo h($medico['id']); ?>" <?php echo ($cirurgia['id_medico'] ?? '') == $medico['id'] ? 'selected' : ''; ?>>
                                                <?php echo h($medico['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="data_cirurgia" class="form-label">Data da Cirurgia</label>
                                    <input type="date" class="form-control" id="data_cirurgia" name="data_cirurgia" 
                                           value="<?php echo h($cirurgia['data_cirurgia'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="problema" class="form-label">Tipo de Cirurgia</label>
                                    <select name="problema" id="problema" class="form-select" required>
                                        <option value="">Selecione o tipo de cirurgia</option>
                                        <?php foreach ($problemas as $problema): ?>
                                            <option value="<?php echo h($problema); ?>" <?php echo ($cirurgia['problema'] ?? '') === $problema ? 'selected' : ''; ?>>
                                                <?php echo h($problema); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="fisioterapeuta" class="form-label">Fisioterapeuta</label>
                                    <select name="fisioterapeuta" id="fisioterapeuta" class="form-select" required>
                                        <option value="">Selecione um fisioterapeuta</option>
                                        <?php foreach ($fisioterapeutas as $fisio): ?>
                                            <option value="<?php echo h($fisio); ?>" <?php echo ($cirurgia['fisioterapeuta'] ?? '') === $fisio ? 'selected' : ''; ?>>
                                                <?php echo h($fisio); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select" required>
                                        <option value="">Selecione o status</option>
                                        <option value="Agendada" <?php echo ($cirurgia['status'] ?? '') === 'Agendada' ? 'selected' : ''; ?>>Agendada</option>
                                        <option value="Realizada" <?php echo ($cirurgia['status'] ?? '') === 'Realizada' ? 'selected' : ''; ?>>Realizada</option>
                                        <option value="Cancelada" <?php echo ($cirurgia['status'] ?? '') === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                    </select>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php?page=admin/cirurgias" class="btn btn-secondary">Voltar</a>
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
