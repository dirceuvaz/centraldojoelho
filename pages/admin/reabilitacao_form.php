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
    'id_paciente' => '', // Adicionando campo id_paciente
    'id_pergunta' => '' // Adicionando campo id_pergunta
];

// Busca médicos para o select
$stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico' ORDER BY nome");
$stmt->execute();
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca pacientes para o select
$stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'paciente' ORDER BY nome");
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca perguntas disponíveis
$stmt = $conn->prepare("SELECT id, titulo, descricao FROM perguntas ORDER BY titulo");
$stmt->execute();
$perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca pergunta vinculada se for edição
$pergunta_selecionada = null;
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT id_pergunta FROM reabilitacao WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $pergunta_selecionada = $stmt->fetchColumn();
}

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
            'id_paciente' => !empty($_POST['id_paciente']) ? $_POST['id_paciente'] : null,
            'id_pergunta' => !empty($_POST['id_pergunta']) ? $_POST['id_pergunta'] : null
        ];

        // Validação
        if (empty($reabilitacao['titulo']) || empty($reabilitacao['texto']) || 
            empty($reabilitacao['tipo_problema']) || empty($reabilitacao['id_medico']) || 
            empty($reabilitacao['duracao_dias'])) {
            throw new Exception('Todos os campos são obrigatórios');
        }

        if ($reabilitacao['id']) {
            // Atualização
            $stmt = $conn->prepare("
                UPDATE reabilitacao 
                SET titulo = ?, texto = ?, momento = ?, tipo_problema = ?, 
                    id_medico = ?, duracao_dias = ?, status = ?, id_paciente = ?, id_pergunta = ?
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
                $reabilitacao['id_pergunta'],
                $reabilitacao['id']
            ];
            $stmt->execute($params);

            // Se uma pergunta foi selecionada, atualiza o momento dela
            if ($reabilitacao['id_pergunta']) {
                $stmt = $conn->prepare("UPDATE perguntas SET momento = ? WHERE id = ?");
                $stmt->execute([$reabilitacao['momento'], $reabilitacao['id_pergunta']]);
            }

            $success = 'Reabilitação atualizada com sucesso!';
        } else {
            // Inserção
            $stmt = $conn->prepare("
                INSERT INTO reabilitacao (titulo, texto, momento, tipo_problema, 
                                        id_medico, duracao_dias, status, id_paciente, id_pergunta)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $reabilitacao['id_pergunta']
            ];
            $stmt->execute($params);

            // Se uma pergunta foi selecionada, atualiza o momento dela
            if ($reabilitacao['id_pergunta']) {
                $stmt = $conn->prepare("UPDATE perguntas SET momento = ? WHERE id = ?");
                $stmt->execute([$reabilitacao['momento'], $reabilitacao['id_pergunta']]);
            }

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
    <title><?php echo $reabilitacao['id'] ? 'Editar' : 'Nova' ?> Reabilitação - Central do Joelho</title>
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
                        <a href="index.php?page=admin/reabilitacao" class="btn btn-outline-secondary me-3">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <h4 class="mb-0"><?php echo $reabilitacao['id'] ? 'Editar' : 'Nova' ?> Reabilitação</h4>
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
                            <form method="POST" action="index.php?page=admin/reabilitacao_form<?php echo !empty($reabilitacao['id']) ? '&id=' . $reabilitacao['id'] : ''; ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($reabilitacao['id'] ?? ''); ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="titulo" class="form-label">Título</label>
                                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                                   value="<?php echo htmlspecialchars($reabilitacao['titulo']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="tipo_problema" class="form-label">Tipo de Problema</label>
                                            <select class="form-select" id="tipo_problema" name="tipo_problema" required>
                                                <option value="">Selecione o tipo do problema</option>
                                                <?php foreach ($tipos_reabilitacao as $tipo): ?>
                                                    <option value="<?php echo h($tipo['id']); ?>" <?php echo $tipo['id'] == $reabilitacao['tipo_problema'] ? 'selected' : ''; ?>>
                                                        <?php echo h($tipo['nome']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="ativo" <?php echo ($reabilitacao['status'] == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                                                <option value="inativo" <?php echo ($reabilitacao['status'] == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="texto" class="form-label">Texto</label>
                                    <textarea class="form-control" id="texto" name="texto" rows="5" required>
                                        <?php echo htmlspecialchars($reabilitacao['texto']); ?>
                                    </textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="id_pergunta" class="form-label">Pergunta Vinculada (Opcional)</label>
                                    <select class="form-select" id="id_pergunta" name="id_pergunta">
                                        <option value="">Selecione uma pergunta</option>
                                        <?php foreach ($perguntas as $pergunta): ?>
                                            <option value="<?php echo h($pergunta['id']); ?>" 
                                                <?php echo $pergunta['id'] == $reabilitacao['id_pergunta'] ? 'selected' : ''; ?>>
                                                <?php echo h($pergunta['titulo']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="momento" class="form-label">Momento da Pergunta (Opcional)</label>
                                    <select class="form-select" id="momento" name="momento">
                                        <option value="">Selecione o momento</option>
                                        <option value="Pre_Operatorio" <?php echo $reabilitacao['momento'] == 'Pre_Operatorio' ? 'selected' : ''; ?>>Pré Operatório</option>
                                        <option value="Primeira_Semana" <?php echo $reabilitacao['momento'] == 'Primeira_Semana' ? 'selected' : ''; ?>>Primeira Semana</option>
                                        <option value="Segunda_Semana" <?php echo $reabilitacao['momento'] == 'Segunda_Semana' ? 'selected' : ''; ?>>Segunda Semana</option>
                                        <option value="Terceira_Semana" <?php echo $reabilitacao['momento'] == 'Terceira_Semana' ? 'selected' : ''; ?>>Terceira Semana</option>
                                        <option value="Quarta_Semana" <?php echo $reabilitacao['momento'] == 'Quarta_Semana' ? 'selected' : ''; ?>>Quarta Semana</option>
                                        <option value="Quinta_Semana" <?php echo $reabilitacao['momento'] == 'Quinta_Semana' ? 'selected' : ''; ?>>Quinta Semana</option>
                                        <option value="Sexta_a_Decima_Semana" <?php echo $reabilitacao['momento'] == 'Sexta_a_Decima_Semana' ? 'selected' : ''; ?>>Sexta a Décima Semana</option>
                                        <option value="Decima_Primeira_a_Vigesima_Semana" <?php echo $reabilitacao['momento'] == 'Decima_Primeira_a_Vigesima_Semana' ? 'selected' : ''; ?>>Décima Primeira a Vigésima Semana</option>
                                        <option value="Sexto_Mes" <?php echo $reabilitacao['momento'] == 'Sexto_Mes' ? 'selected' : ''; ?>>Sexto Mês</option>
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
                                            <option value="<?php echo $paciente['id']; ?>" 
                                                <?php echo ($paciente['id'] == $reabilitacao['id_paciente']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($paciente['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Deixe em branco se não quiser atribuir a um paciente específico.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="duracao_dias" class="form-label">Duração (em dias)</label>
                                    <input type="number" class="form-control" id="duracao_dias" name="duracao_dias" 
                                           value="<?php echo htmlspecialchars($reabilitacao['duracao_dias']); ?>" required>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <a href="index.php?page=admin/reabilitacao" class="btn btn-outline-secondary me-3">
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
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#texto'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
                language: 'pt-br'
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>
