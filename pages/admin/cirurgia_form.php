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
                                  f.id as id_fisioterapeuta,
                                  r.titulo as titulo_reabilitacao
                           FROM pacientes p
                           LEFT JOIN usuarios u ON p.id_usuario = u.id
                           LEFT JOIN usuarios m ON p.medico = m.id
                           LEFT JOIN usuarios f ON p.fisioterapeuta = f.id
                           LEFT JOIN reabilitacao r ON p.id_reabilitacao = r.id
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

// Busca reabilitações ativas
$stmt = $conn->query("SELECT id, titulo FROM reabilitacao WHERE status = 'ativo' ORDER BY titulo");
$reabilitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca tipos de problemas únicos da tabela pacientes
$stmt = $conn->query("SELECT DISTINCT problema FROM pacientes WHERE problema IS NOT NULL AND problema != '' ORDER BY problema");
$problemas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos obrigatórios
    if (empty($_POST['id_reabilitacao'])) {
        $error = "Por favor, selecione uma reabilitação para o paciente.";
    } else {
        try {
            if (isset($_POST['id']) && $_POST['id']) {
                // Atualizar
                $sql = "UPDATE pacientes SET 
                        medico = :medico,
                        id_usuario = :id_usuario,
                        data_cirurgia = :data_cirurgia,
                        problema = :problema,
                        fisioterapeuta = :fisioterapeuta,
                        status = :status,
                        id_reabilitacao = :id_reabilitacao
                        WHERE id = :id";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'medico' => $_POST['medico'],
                    'id_usuario' => $_POST['id_paciente'],
                    'data_cirurgia' => $_POST['data_cirurgia'],
                    'problema' => $_POST['problema'],
                    'fisioterapeuta' => $_POST['fisioterapeuta'],
                    'status' => $_POST['status'],
                    'id_reabilitacao' => $_POST['id_reabilitacao'],
                    'id' => $_POST['id']
                ]);
                header('Location: index.php?page=admin/cirurgias&msg=Cirurgia_atualizada');
                exit;
            } else {
                // Inserir nova cirurgia
                $sql = "INSERT INTO pacientes (medico, id_usuario, data_cirurgia, problema, fisioterapeuta, status, id_reabilitacao) 
                        VALUES (:medico, :id_usuario, :data_cirurgia, :problema, :fisioterapeuta, :status, :id_reabilitacao)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'medico' => $_POST['medico'],
                    'id_usuario' => $_POST['id_paciente'],
                    'data_cirurgia' => $_POST['data_cirurgia'],
                    'problema' => $_POST['problema'],
                    'fisioterapeuta' => $_POST['fisioterapeuta'],
                    'status' => $_POST['status'],
                    'id_reabilitacao' => $_POST['id_reabilitacao']
                ]);
                header('Location: index.php?page=admin/cirurgias&msg=Cirurgia_criada');
                exit;
            }
            
            // Redireciona para a página de cirurgias após salvar
        } catch (PDOException $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
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
        .top-navbar {
            background-color: #231F5D;
            color: white;
            padding: 1rem;
        }
        .top-navbar a {
            color: white;
        }
        .top-navbar .btn-outline-secondary {
            color: white;
            border-color: white;
        }
        .top-navbar .btn-outline-secondary:hover {
            background-color: white;
            color: #231F5D;
        }
        .main-content {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <a href="index.php?page=admin/cirurgias" class="btn btn-outline-secondary me-3">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <h4 class="mb-0"><?php echo isset($cirurgia['id']) ? 'Editar' : 'Nova'; ?> Cirurgia</h4>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="main-content">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <?php if (isset($cirurgia['id'])): ?>
                                    <input type="hidden" name="id" value="<?php echo h($cirurgia['id']); ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="id_paciente" class="form-label">Paciente</label>
                                            <select name="id_paciente" id="id_paciente" class="form-select" required>
                                                <option value="">Selecione o paciente</option>
                                                <?php foreach ($pacientes as $paciente): ?>
                                                    <option value="<?php echo h($paciente['id']); ?>" 
                                                            <?php echo ($cirurgia['id_usuario'] ?? '') == $paciente['id'] ? 'selected' : ''; ?>>
                                                        <?php echo h($paciente['nome']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="medico" class="form-label">Médico</label>
                                            <select name="medico" id="medico" class="form-select" required>
                                                <option value="">Selecione o médico</option>
                                                <?php foreach ($medicos as $medico): ?>
                                                    <option value="<?php echo h($medico['id']); ?>" 
                                                            <?php echo ($cirurgia['medico'] ?? '') == $medico['id'] ? 'selected' : ''; ?>>
                                                        <?php echo h($medico['nome']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="data_cirurgia" class="form-label">Data da Cirurgia</label>
                                            <input type="date" name="data_cirurgia" id="data_cirurgia" class="form-control" 
                                                   value="<?php echo h($cirurgia['data_cirurgia'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="problema" class="form-label">Problema</label>
                                            <input type="text" name="problema" id="problema" class="form-control" 
                                                   value="<?php echo h($cirurgia['problema'] ?? ''); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="id_reabilitacao" class="form-label">Reabilitação *</label>
                                            <select name="id_reabilitacao" id="id_reabilitacao" class="form-select" required>
                                                <option value="">Selecione uma reabilitação</option>
                                                <?php foreach ($reabilitacoes as $reabilitacao): ?>
                                                    <option value="<?php echo h($reabilitacao['id']); ?>" 
                                                        <?php echo (isset($cirurgia['id_reabilitacao']) && $cirurgia['id_reabilitacao'] == $reabilitacao['id']) ? 'selected' : ''; ?>>
                                                        <?php echo h($reabilitacao['titulo']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Por favor, selecione uma reabilitação.
                                            </div>
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

                                        <div class="mb-3">
                                            <label for="fisioterapeuta" class="form-label">Fisioterapeuta</label>
                                            <select name="fisioterapeuta" id="fisioterapeuta" class="form-select">
                                                <option value="">Selecione o fisioterapeuta</option>
                                                <?php foreach ($fisioterapeutas as $fisio): ?>
                                                    <option value="<?php echo h($fisio); ?>" 
                                                            <?php echo ($cirurgia['fisioterapeuta'] ?? '') === $fisio ? 'selected' : ''; ?>>
                                                        <?php echo h($fisio); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <a href="index.php?page=admin/cirurgias" class="btn btn-outline-secondary me-3">
                                        <i class="bi bi-arrow-left"></i> Voltar
                                    </a>                                
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Salvar
                                    </button>
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
