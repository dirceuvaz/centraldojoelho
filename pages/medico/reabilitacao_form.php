<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$error = null;
$success = null;

// Inicializa variáveis
$reabilitacao = [
    'id' => '',
    'titulo' => '',
    'texto' => '',
    'momento' => '',
    'tipo_problema' => '',
    'id_medico' => $_SESSION['user_id'], // Já define o médico atual
    'duracao_dias' => '',
    'status' => 'ativo'
];

// Busca tipos de reabilitação para o select
$stmt = $conn->prepare("SELECT id, descricao as nome FROM tipos_reabilitacao ORDER BY descricao");
$stmt->execute();
$tipos_reabilitacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca momentos de reabilitação para o select
$stmt = $conn->prepare("SELECT id, nome FROM momentos_reabilitacao ORDER BY ordem");
$stmt->execute();
$momentos_reabilitacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se for edição, carrega os dados da reabilitação
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM reabilitacao WHERE id = ? AND id_medico = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $reabilitacao_db = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reabilitacao_db) {
        $reabilitacao = array_merge($reabilitacao, $reabilitacao_db);
    } else {
        // Se não encontrou a reabilitação ou não pertence ao médico
        header('Location: index.php?page=medico/reabilitacao_medico');
        exit;
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
            'id_medico' => $_SESSION['user_id'],
            'duracao_dias' => $_POST['duracao_dias'] ?? '',
            'status' => $_POST['status'] ?? 'ativo'
        ];

        // Validação
        if (empty($reabilitacao['titulo']) || empty($reabilitacao['texto']) || 
            empty($reabilitacao['tipo_problema']) || empty($reabilitacao['duracao_dias'])) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
        }

        if ($reabilitacao['id']) {
            // Verifica se a reabilitação pertence ao médico
            $stmt = $conn->prepare("SELECT id FROM reabilitacao WHERE id = ? AND id_medico = ?");
            $stmt->execute([$reabilitacao['id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Você não tem permissão para editar esta reabilitação');
            }

            // Atualização
            $stmt = $conn->prepare("
                UPDATE reabilitacao 
                SET titulo = ?, texto = ?, momento = ?, tipo_problema = ?, 
                    duracao_dias = ?, status = ?
                WHERE id = ? AND id_medico = ?
            ");
            $params = [
                $reabilitacao['titulo'],
                $reabilitacao['texto'],
                $reabilitacao['momento'],
                $reabilitacao['tipo_problema'],
                $reabilitacao['duracao_dias'],
                $reabilitacao['status'],
                $reabilitacao['id'],
                $_SESSION['user_id']
            ];
            $stmt->execute($params);
            $success = 'Reabilitação atualizada com sucesso!';
        } else {
            // Inserção
            $stmt = $conn->prepare("
                INSERT INTO reabilitacao (titulo, texto, momento, tipo_problema, 
                                        id_medico, duracao_dias, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $params = [
                $reabilitacao['titulo'],
                $reabilitacao['texto'],
                $reabilitacao['momento'],
                $reabilitacao['tipo_problema'],
                $_SESSION['user_id'],
                $reabilitacao['duracao_dias'],
                $reabilitacao['status']
            ];
            $stmt->execute($params);
            $success = 'Reabilitação criada com sucesso!';
        }

        header('Location: index.php?page=medico/reabilitacao_medico&msg=' . ($reabilitacao['id'] ? 'Reabilitacao_atualizada' : 'Reabilitacao_criada'));
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
</head>
<body>
    <?php include_once __DIR__ . '/../../common/header_medico.php'; ?>

    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $reabilitacao['id'] ? 'Editar' : 'Nova' ?> Reabilitação</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="index.php?page=medico/reabilitacao_form<?php echo !empty($reabilitacao['id']) ? '&id=' . $reabilitacao['id'] : ''; ?>">
                            <input type="hidden" name="id" value="<?php echo h($reabilitacao['id']); ?>">
                            
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo h($reabilitacao['titulo']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="texto" class="form-label">Texto *</label>
                                <textarea class="form-control" id="texto" name="texto" rows="10" required><?php echo h($reabilitacao['texto']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tipo_problema" class="form-label">Tipo de Problema *</label>
                                        <select class="form-select" id="tipo_problema" name="tipo_problema" required>
                                            <option value="">Selecione um tipo</option>
                                            <?php foreach ($tipos_reabilitacao as $tipo): ?>
                                                <option value="<?php echo h($tipo['id']); ?>" 
                                                    <?php echo $reabilitacao['tipo_problema'] == $tipo['id'] ? 'selected' : ''; ?>>
                                                    <?php echo h($tipo['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duracao_dias" class="form-label">Duração (em dias) *</label>
                                        <input type="number" class="form-control" id="duracao_dias" name="duracao_dias" 
                                               value="<?php echo h($reabilitacao['duracao_dias']); ?>" required min="1">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="momento" class="form-label">Momento da Reabilitação</label>
                                <select class="form-select" id="momento" name="momento">
                                    <option value="">Selecione o momento</option>
                                    <?php foreach ($momentos_reabilitacao as $momento): ?>
                                        <option value="<?php echo h($momento['id']); ?>" 
                                            <?php echo $reabilitacao['momento'] == $momento['id'] ? 'selected' : ''; ?>>
                                            <?php echo h($momento['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativo" <?php echo $reabilitacao['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inativo" <?php echo $reabilitacao['status'] == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="index.php?page=medico/reabilitacao_medico" class="btn btn-outline-secondary me-3">
                                    <i class="bi bi-arrow-left"></i> Voltar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> <?php echo $reabilitacao['id'] ? 'Atualizar' : 'Criar' ?> Reabilitação
                                </button>
                            </div>
                        </form>
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
