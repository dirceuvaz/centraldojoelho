<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$error = null;
$success = null;
$page = 'admin/reabilitacao_form'; // Adicionando variável page para o sidebar

// Inicializa variáveis
$reabilitacao = [
    'id' => '',
    'titulo' => '',
    'texto' => '',
    'momento' => '',
    'tipo_problema' => '',
    'id_medico' => '',
    'duracao_dias' => '',
    'status' => 'ativo',
    'id_paciente' => '' // Adicionando campo id_paciente
];

// Busca médicos para o select
$stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico' ORDER BY nome");
$stmt->execute();
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca pacientes para o select
$stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'paciente' ORDER BY nome");
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca tipos de reabilitação para o select
$stmt = $conn->prepare("SELECT id, descricao as nome FROM tipos_reabilitacao ORDER BY descricao");
$stmt->execute();
$tipos_reabilitacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variável para controlar se existem perguntas associadas
$tem_perguntas = false;

// Se for edição, carrega os dados da reabilitação
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM reabilitacao WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $reabilitacao_db = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reabilitacao_db) {
        $reabilitacao = array_merge($reabilitacao, $reabilitacao_db);
        
        // Verifica se existem perguntas associadas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM perguntas WHERE id_reabilitacao = ?");
        $stmt->execute([$_GET['id']]);
        $tem_perguntas = $stmt->fetchColumn() > 0;
    }
}

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $reabilitacao = [
            'id' => $_POST['id'] ?? '',
            'titulo' => $_POST['titulo'] ?? '',
            'texto' => $_POST['texto'] ?? '',
            'momento' => $_POST['momento'] ?? '',
            'tipo_problema' => $_POST['tipo_problema'] ?? '',
            'id_medico' => $_POST['id_medico'] ?? '',
            'duracao_dias' => $_POST['duracao_dias'] ?? '',
            'status' => $_POST['status'] ?? 'ativo',
            'id_paciente' => !empty($_POST['id_paciente']) ? $_POST['id_paciente'] : null
        ];

        // Validação
        if (empty($reabilitacao['titulo']) || empty($reabilitacao['texto']) || 
            empty($reabilitacao['momento']) || empty($reabilitacao['tipo_problema']) || 
            empty($reabilitacao['id_medico']) || empty($reabilitacao['duracao_dias'])) {
            throw new Exception('Todos os campos são obrigatórios');
        }

        if ($reabilitacao['id']) {
            // Atualização
            $stmt = $conn->prepare("
                UPDATE reabilitacao 
                SET titulo = ?, texto = ?, momento = ?, tipo_problema = ?, 
                    id_medico = ?, duracao_dias = ?, status = ?, id_paciente = ?
                WHERE id = ?
            ");
            $params = [
                $reabilitacao['titulo'],
                $reabilitacao['texto'],
                $reabilitacao['momento'],
                $reabilitacao['tipo_problema'],
                $reabilitacao['id_medico'],
                $reabilitacao['duracao_dias'],
                $reabilitacao['status'],
                $reabilitacao['id_paciente'],
                $reabilitacao['id']
            ];
            $stmt->execute($params);
            $success = 'Reabilitação atualizada com sucesso!';
        } else {
            // Inserção
            $stmt = $conn->prepare("
                INSERT INTO reabilitacao (titulo, texto, momento, tipo_problema, 
                                        id_medico, duracao_dias, status, id_paciente)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $params = [
                $reabilitacao['titulo'],
                $reabilitacao['texto'],
                $reabilitacao['momento'],
                $reabilitacao['tipo_problema'],
                $reabilitacao['id_medico'],
                $reabilitacao['duracao_dias'],
                $reabilitacao['status'],
                $reabilitacao['id_paciente']
            ];
            $stmt->execute($params);
            $success = 'Reabilitação criada com sucesso!';
        }

        header('Location: index.php?page=admin/reabilitacao&msg=' . ($reabilitacao['id'] ? 'Reabilitacao_atualizada' : 'Reabilitacao_criada'));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Reabilitação - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
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
                            <a href="index.php?page=admin/reabilitacao" class="btn btn-warning w-100">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>                       
                    </li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-10 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Formulário de Reabilitação</h4>
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person"></i> 
                            Olá, <?php echo h($_SESSION['user_nome']); ?>
                        </span>
                        <a class="btn btn-outline-danger btn-sm" href="index.php?page=login_process&logout=1">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="main-content">
                    <div class="container-fluid px-4">
                        <h1 class="mt-4"><?php echo empty($reabilitacao['id']) ? 'Nova Reabilitação' : 'Editar Reabilitação'; ?></h1>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo h($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo h($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($tem_perguntas): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-exclamation-triangle"></i>
                                Esta reabilitação possui perguntas associadas. Algumas alterações podem afetar as perguntas existentes.
                            </div>
                        <?php endif; ?>

                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="POST" action="index.php?page=admin/reabilitacao_form<?php echo !empty($reabilitacao['id']) ? '&id=' . $reabilitacao['id'] : ''; ?>">
                                    <input type="hidden" name="id" value="<?php echo h($reabilitacao['id']); ?>">
                                    
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label">Título</label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo h($reabilitacao['titulo']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="texto" class="form-label">Texto</label>
                                        <textarea class="form-control" id="texto" name="texto" rows="5"><?php echo h($reabilitacao['texto']); ?></textarea>
                                        <div class="form-text">Use o editor acima para formatar o texto da reabilitação.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="momento" class="form-label">Momento</label>
                                        <input type="text" class="form-control" id="momento" name="momento" value="<?php echo h($reabilitacao['momento']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tipo_problema" class="form-label">Tipo do Problema</label>
                                        <select class="form-select" id="tipo_problema" name="tipo_problema" required>
                                            <option value="">Selecione o tipo do problema</option>
                                            <?php foreach ($tipos_reabilitacao as $tipo): ?>
                                                <option value="<?php echo h($tipo['id']); ?>" <?php echo $tipo['id'] == $reabilitacao['tipo_problema'] ? 'selected' : ''; ?>>
                                                    <?php echo h($tipo['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="id_medico" class="form-label">Médico Responsável</label>
                                        <select class="form-select" id="id_medico" name="id_medico" required>
                                            <option value="">Selecione um médico</option>
                                            <?php foreach ($medicos as $medico): ?>
                                                <option value="<?php echo h($medico['id']); ?>" <?php echo $medico['id'] == $reabilitacao['id_medico'] ? 'selected' : ''; ?>>
                                                    <?php echo h($medico['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="id_paciente" class="form-label">Paciente (Opcional)</label>
                                        <select class="form-select" id="id_paciente" name="id_paciente">
                                            <option value="">Selecione um paciente</option>
                                            <?php foreach ($pacientes as $paciente): ?>
                                                <option value="<?php echo h($paciente['id']); ?>" <?php echo $paciente['id'] == $reabilitacao['id_paciente'] ? 'selected' : ''; ?>>
                                                    <?php echo h($paciente['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Deixe em branco se não quiser atribuir a um paciente específico.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="duracao_dias" class="form-label">Duração da Reabilitação (em dias)</label>
                                        <input type="number" class="form-control" id="duracao_dias" name="duracao_dias" value="<?php echo h($reabilitacao['duracao_dias']); ?>" required min="1">
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="index.php?page=admin/reabilitacao" class="btn btn-secondary me-md-2">Voltar</a>
                                        <button type="submit" class="btn btn-primary">Salvar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Inicialização do CKEditor -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let editor;
            
            ClassicEditor
                .create(document.querySelector('#texto'), {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
                    language: 'pt-br'
                })
                .then(newEditor => {
                    editor = newEditor;
                })
                .catch(error => {
                    console.error('Erro ao inicializar o editor:', error);
                });

            // Validação do formulário
            document.querySelector('form').addEventListener('submit', function(e) {
                const textoConteudo = editor.getData().trim();
                if (!textoConteudo) {
                    e.preventDefault();
                    alert('O campo texto é obrigatório.');
                }
            });
        });
    </script>
</body>
</html>
