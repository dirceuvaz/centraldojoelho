<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pergunta = [
    'titulo' => '', 
    'descricao' => '', 
    'id_paciente' => null, 
    'id_medico' => $_SESSION['user_id'],
    'criado_por' => $_SESSION['user_id'], 
    'sequencia' => '',
    'id_reabilitacao' => '',
    'comentario_afirmativo' => '',
    'comentario_negativo' => ''
];

// Buscar lista de pacientes
$stmt = $conn->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'paciente' ORDER BY nome");
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar lista de médicos
$stmt = $conn->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico' ORDER BY nome");
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar lista de reabilitações
$stmt = $conn->query("SELECT id, titulo FROM reabilitacao WHERE status = 'ativo' ORDER BY titulo");
$reabilitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM perguntas WHERE id = ?");
    $stmt->execute([$id]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $id_paciente = !empty($_POST['id_paciente']) ? $_POST['id_paciente'] : null;
    $id_medico = $_POST['id_medico'];
    $sequencia = trim($_POST['sequencia']);
    $id_reabilitacao = $_POST['id_reabilitacao'];
    $comentario_afirmativo = trim($_POST['comentario_afirmativo']);
    $comentario_negativo = trim($_POST['comentario_negativo']);
    
    if (empty($titulo) || empty($descricao) || empty($id_medico) || empty($id_reabilitacao)) {
        $error = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            if ($id > 0) {
                // Atualização
                $stmt = $conn->prepare("
                    UPDATE perguntas 
                    SET titulo = ?, descricao = ?, id_paciente = ?, id_medico = ?, 
                        sequencia = ?, id_reabilitacao = ?, comentario_afirmativo = ?,
                        comentario_negativo = ?, data_atualizacao = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $titulo, $descricao, $id_paciente, $id_medico, 
                    $sequencia, $id_reabilitacao, $comentario_afirmativo,
                    $comentario_negativo, $id
                ]);
                header('Location: index.php?page=admin/perguntas&msg=Pergunta_atualizada');
            } else {
                // Inserção
                $stmt = $conn->prepare("
                    INSERT INTO perguntas (titulo, descricao, id_paciente, id_medico, 
                                         sequencia, id_reabilitacao, comentario_afirmativo,
                                         comentario_negativo, data_criacao, data_atualizacao,
                                         criado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ?)
                ");
                $stmt->execute([
                    $titulo, $descricao, $id_paciente, $id_medico,
                    $sequencia, $id_reabilitacao, $comentario_afirmativo,
                    $comentario_negativo, $_SESSION['user_id']
                ]);
                header('Location: index.php?page=admin/perguntas&msg=Pergunta_criada');
            }
            exit;
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
    <title>Formulário de Pergunta - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- CKEditor -->
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
        .main-content {
            padding: 20px;
        }
        .top-navbar {
            background-color: #ffffff;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .ck-editor__editable {
            min-height: 200px;
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
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=admin/perguntas">
                            <i class="bi bi-arrow-left"></i> Voltar para Perguntas
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-10 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo $id ? 'Editar' : 'Nova' ?> Pergunta</h4>
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
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($pergunta['id'] ?? ''); ?>">
                                
                                <div class="mb-3">
                                    <label for="id_reabilitacao" class="form-label">Reabilitação</label>
                                    <select class="form-select" id="id_reabilitacao" name="id_reabilitacao" required>
                                        <option value="">Selecione uma reabilitação</option>
                                        <?php foreach ($reabilitacoes as $reabilitacao): ?>
                                            <option value="<?php echo $reabilitacao['id']; ?>" 
                                                <?php echo ($pergunta['id_reabilitacao'] == $reabilitacao['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($reabilitacao['titulo']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="sequencia" class="form-label">Sequência</label>
                                    <input type="text" class="form-control" id="sequencia" name="sequencia" 
                                           value="<?php echo htmlspecialchars($pergunta['sequencia']); ?>"
                                           maxlength="75">
                                </div>

                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" 
                                           value="<?php echo htmlspecialchars($pergunta['titulo']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="id_paciente" class="form-label">Paciente (Opcional)</label>
                                    <select class="form-select" id="id_paciente" name="id_paciente">
                                        <option value="">Selecione um paciente</option>
                                        <?php foreach ($pacientes as $paciente): ?>
                                            <option value="<?php echo $paciente['id']; ?>" 
                                                <?php echo ($pergunta['id_paciente'] == $paciente['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($paciente['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Este campo é opcional. Deixe em branco se a pergunta não for específica para um paciente.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="id_medico" class="form-label">Médico</label>
                                    <select class="form-select" id="id_medico" name="id_medico" required>
                                        <option value="">Selecione um médico</option>
                                        <?php foreach ($medicos as $medico): ?>
                                            <option value="<?php echo $medico['id']; ?>" 
                                                <?php echo ($pergunta['id_medico'] == $medico['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($medico['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição da Pergunta</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="5" required>
                                        <?php echo htmlspecialchars($pergunta['descricao']); ?>
                                    </textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="comentario_afirmativo" class="form-label">Comentário Afirmativo</label>
                                    <textarea class="form-control" id="comentario_afirmativo" name="comentario_afirmativo" 
                                              rows="3" maxlength="500"><?php echo htmlspecialchars($pergunta['comentario_afirmativo']); ?></textarea>
                                    <div class="form-text">Máximo de 500 caracteres</div>
                                </div>

                                <div class="mb-3">
                                    <label for="comentario_negativo" class="form-label">Comentário Negativo</label>
                                    <textarea class="form-control" id="comentario_negativo" name="comentario_negativo" 
                                              rows="3" maxlength="500"><?php echo htmlspecialchars($pergunta['comentario_negativo']); ?></textarea>
                                    <div class="form-text">Máximo de 500 caracteres</div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php?page=admin/perguntas" class="btn btn-secondary">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#descricao'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
                language: 'pt-br'
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>
