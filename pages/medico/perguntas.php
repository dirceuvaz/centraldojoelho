<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$medico_id = $_SESSION['user_id'];

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                // Excluir pergunta
                if (isset($_POST['perguntaId'])) {
                    $stmt = $conn->prepare("DELETE FROM perguntas WHERE id = ? AND id_medico = ?");
                    if ($stmt->execute([$_POST['perguntaId'], $_SESSION['user_id']])) {
                        $_SESSION['success'] = "Pergunta excluída com sucesso!";
                    } else {
                        $_SESSION['error'] = "Erro ao excluir a pergunta.";
                    }
                }
                break;

            case 'save':
                // Salvar ou atualizar pergunta
                $perguntaId = $_POST['perguntaId'] ?? null;
                $titulo = $_POST['titulo'] ?? '';
                $descricao = $_POST['descricao'] ?? '';
                $id_reabilitacao = !empty($_POST['id_reabilitacao']) ? $_POST['id_reabilitacao'] : null;
                $momento = !empty($_POST['momento']) ? $_POST['momento'] : null;
                $comentario_afirmativo = $_POST['comentario_afirmativo'] ?? '';
                $comentario_negativo = $_POST['comentario_negativo'] ?? '';
                $sequencia = $_POST['sequencia'] ?? 1;

                if ($perguntaId) {
                    // Atualizar pergunta existente
                    $sql = "UPDATE perguntas SET 
                            titulo = ?, 
                            descricao = ?, 
                            id_reabilitacao = ?, 
                            id_momento = ?,
                            comentario_afirmativo = ?,
                            comentario_negativo = ?,
                            sequencia = ?
                            WHERE id = ? AND id_medico = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt->execute([$titulo, $descricao, $id_reabilitacao, $momento, $comentario_afirmativo, $comentario_negativo, $sequencia, $perguntaId, $_SESSION['user_id']])) {
                        $_SESSION['success'] = "Pergunta atualizada com sucesso!";
                    } else {
                        $_SESSION['error'] = "Erro ao atualizar a pergunta.";
                    }
                } else {
                    // Inserir nova pergunta
                    $sql = "INSERT INTO perguntas (titulo, descricao, id_reabilitacao, id_momento, comentario_afirmativo, comentario_negativo, sequencia, id_medico) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt->execute([$titulo, $descricao, $id_reabilitacao, $momento, $comentario_afirmativo, $comentario_negativo, $sequencia, $_SESSION['user_id']])) {
                        $_SESSION['success'] = "Pergunta criada com sucesso!";
                    } else {
                        $_SESSION['error'] = "Erro ao criar a pergunta.";
                    }
                }
                break;
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header("Location: http://localhost/centraldojoelho/index.php?page=medico/perguntas");
    exit;
}

// Buscar todas as perguntas
$itemsPerPage = 10;
$currentPage = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Consulta para contar o total de registros
$countSql = "SELECT COUNT(*) as total FROM perguntas WHERE id_medico = ?";
$countStmt = $conn->prepare($countSql);
$countStmt->execute([$_SESSION['user_id']]);
$totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $itemsPerPage);

// Consulta paginada
$sql = "SELECT p.*, r.titulo as nome_reabilitacao, m.nome as nome_momento 
        FROM perguntas p 
        LEFT JOIN reabilitacao r ON p.id_reabilitacao = r.id 
        LEFT JOIN momentos_reabilitacao m ON p.id_momento = m.id 
        WHERE p.id_medico = ? 
        ORDER BY p.id DESC 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->bindValue(3, $itemsPerPage, PDO::PARAM_INT);
$stmt->execute();
$perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar momentos de reabilitação
$stmt = $conn->query("SELECT id, nome FROM momentos_reabilitacao ORDER BY ordem");
$momentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar lista de reabilitações
$stmt = $conn->query("SELECT id, titulo FROM reabilitacao WHERE status = 'ativo' ORDER BY titulo");
$reabilitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Perguntas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <style>
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .card {
            margin-bottom: 20px;
            position: relative;
        }
        .card-text {
            white-space: pre-line;
        }
        /* Ocultar todas as notificações do CKEditor */
        .cke_notification_warning,
        .cke_notification_info,
        .cke_notification_success,
        .cke_notification {
            display: none !important;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../common/header_medico.php'; ?>
     
    <div class="container my-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="row mb-4">
                <div class="col">
                    <h2><i class="bi bi-question-circle"></i> Perguntas para Pacientes</h2>
                    <p class="text-muted">Gerencie as perguntas para seus pacientes</p>
                </div>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#perguntaModal">
                    <i class="bi bi-plus-circle"></i> Nova Pergunta
                </button>
            </div>        
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="index.php?page=medico/painel" class="btn btn-warning">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if (empty($perguntas)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhuma pergunta encontrada.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($perguntas as $pergunta): ?>
                    <div class="col-md-6">
                        <div class="card h-100" 
                             data-pergunta-id="<?php echo $pergunta['id']; ?>"
                             data-titulo="<?php echo htmlspecialchars($pergunta['titulo'] ?? ''); ?>"
                             data-descricao="<?php echo htmlspecialchars($pergunta['descricao'] ?? ''); ?>"
                             data-id_reabilitacao="<?php echo $pergunta['id_reabilitacao'] ?? ''; ?>"
                             data-momento="<?php echo $pergunta['id_momento'] ?? ''; ?>"
                             data-comentario_afirmativo="<?php echo htmlspecialchars($pergunta['comentario_afirmativo'] ?? ''); ?>"
                             data-comentario_negativo="<?php echo htmlspecialchars($pergunta['comentario_negativo'] ?? ''); ?>"
                             data-sequencia="<?php echo $pergunta['sequencia'] ?? ''; ?>">
                            
                            <?php if (isset($pergunta['resposta_paciente']) && $pergunta['resposta_paciente']): ?>
                                <div class="status-badge">
                                    <span class="badge bg-success">Respondida</span>
                                </div>
                            <?php else: ?>
                                <div class="status-badge">
                                    <span class="badge bg-warning text-dark">Pendente</span>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($pergunta['titulo'] ?? ''); ?>
                                </h5>

                                <?php if (isset($pergunta['nome_paciente']) && $pergunta['nome_paciente']): ?>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        Paciente: <?php echo htmlspecialchars($pergunta['nome_paciente']); ?>
                                    </h6>
                                <?php endif; ?>

                                <?php if (isset($pergunta['reabilitacao_titulo']) && $pergunta['reabilitacao_titulo']): ?>
                                    <div class="mb-2 text-muted">
                                        Reabilitação: <?php echo htmlspecialchars($pergunta['reabilitacao_titulo']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="card-text mt-3">
                                    <strong>Descrição:</strong>
                                    <?php echo $pergunta['descricao'] ?? ''; ?>
                                </div>

                                <?php if (!empty($pergunta['comentario_afirmativo'])): ?>
                                    <div class="mt-2">
                                        <strong>Comentário para Resposta Positiva:</strong>
                                        <p class="text-success"><?php echo $pergunta['comentario_afirmativo']; ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($pergunta['comentario_negativo'])): ?>
                                    <div class="mt-2">
                                        <strong>Comentário para Resposta Negativa:</strong>
                                        <p class="text-danger"><?php echo $pergunta['comentario_negativo']; ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($pergunta['resposta_paciente']) && $pergunta['resposta_paciente']): ?>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <strong>Resposta do Paciente:</strong>
                                        <?php echo $pergunta['resposta_paciente']; ?>
                                        <small class="text-muted d-block mt-2">
                                            Respondida em <?php echo isset($pergunta['data_resposta']) ? date('d/m/Y', strtotime($pergunta['data_resposta'])) : ''; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer bg-transparent">
                                <button class="btn btn-primary btn-sm edit-button" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#perguntaModal">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <button class="btn btn-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal"
                                        data-pergunta-id="<?php echo $pergunta['id']; ?>">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <br>

        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="http://localhost/centraldojoelho/index.php?page=medico/perguntas&page_num=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="http://localhost/centraldojoelho/index.php?page=medico/perguntas&page_num=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="http://localhost/centraldojoelho/index.php?page=medico/perguntas&page_num=<?php echo $currentPage + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Modal de Pergunta -->
    <div class="modal fade" id="perguntaModal" tabindex="-1" aria-labelledby="perguntaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="perguntaModalLabel">Nova Pergunta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="perguntaForm" action="http://localhost/centraldojoelho/index.php?page=medico/perguntas" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="perguntaId" id="perguntaId">
                        <input type="hidden" name="action" value="save">
                        
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título da Pergunta</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control editor" id="descricao" name="descricao" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_reabilitacao" class="form-label">Reabilitação</label>
                                <select class="form-select" id="id_reabilitacao" name="id_reabilitacao" required>
                                    <option value="">Selecione uma reabilitação</option>
                                    <?php foreach ($reabilitacoes as $reab): ?>
                                        <option value="<?php echo $reab['id']; ?>">
                                            <?php echo htmlspecialchars($reab['titulo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="momento" class="form-label">Momento da Reabilitação</label>
                                <select class="form-select" id="momento" name="momento">
                                    <option value="">Selecione um momento</option>
                                    <?php foreach ($momentos as $momento): ?>
                                        <option value="<?php echo $momento['id']; ?>">
                                            <?php echo htmlspecialchars($momento['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comentario_afirmativo" class="form-label">Comentário para Resposta Positiva</label>
                            <textarea class="form-control editor" id="comentario_afirmativo" name="comentario_afirmativo" rows="2"></textarea>
                            <div class="form-text">Este comentário será exibido quando o paciente responder positivamente.</div>
                        </div>

                        <div class="mb-3">
                            <label for="comentario_negativo" class="form-label">Comentário para Resposta Negativa</label>
                            <textarea class="form-control editor" id="comentario_negativo" name="comentario_negativo" rows="2"></textarea>
                            <div class="form-text">Este comentário será exibido quando o paciente responder negativamente.</div>
                        </div>

                        <div class="mb-3">
                            <label for="sequencia" class="form-label">Sequência</label>
                            <input type="number" class="form-control" id="sequencia" name="sequencia" min="1">
                            <div class="form-text">Ordem em que a pergunta aparecerá para o paciente.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="deleteForm" action="http://localhost/centraldojoelho/index.php?page=medico/perguntas" method="POST">
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir esta pergunta?</p>
                        <input type="hidden" name="perguntaId" id="deletePerguntaId">
                        <input type="hidden" name="action" value="delete">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar CKEditor em todos os campos com a classe 'editor'
            document.querySelectorAll('.editor').forEach(function(element) {
                CKEDITOR.replace(element, {
                    language: 'pt-br',
                    height: 200,
                    removeButtons: 'Save,NewPage,Preview,Print,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Find,Replace,SelectAll,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Strike,Subscript,Superscript,CopyFormatting,RemoveFormat,NumberedList,BulletedList,Outdent,Indent,Blockquote,CreateDiv,JustifyLeft,JustifyCenter,JustifyRight,JustifyBlock,BidiLtr,BidiRtl,Language,Link,Unlink,Anchor,Image,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Styles,Format,Font,FontSize,TextColor,BGColor,Maximize,ShowBlocks,About'
                });
            });

            // Modal de pergunta
            const perguntaModal = document.getElementById('perguntaModal');
            perguntaModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const isEdit = button.hasAttribute('data-pergunta-id');
                
                // Atualiza o título do modal
                document.getElementById('perguntaModalLabel').textContent = isEdit ? 'Editar Pergunta' : 'Nova Pergunta';
                
                // Limpa os campos do formulário
                if (!isEdit) {
                    document.getElementById('perguntaId').value = '';
                    document.getElementById('titulo').value = '';
                    for (let instance in CKEDITOR.instances) {
                        CKEDITOR.instances[instance].setData('');
                    }
                    document.getElementById('id_reabilitacao').value = '';
                    document.getElementById('momento').value = '';
                    document.getElementById('sequencia').value = '';
                    return;
                }
                
                // Preenche os campos do formulário para edição
                document.getElementById('perguntaId').value = button.getAttribute('data-pergunta-id');
                document.getElementById('titulo').value = button.getAttribute('data-titulo');
                CKEDITOR.instances.descricao.setData(button.getAttribute('data-descricao'));
                CKEDITOR.instances.comentario_afirmativo.setData(button.getAttribute('data-comentario_afirmativo'));
                CKEDITOR.instances.comentario_negativo.setData(button.getAttribute('data-comentario_negativo'));
                document.getElementById('id_reabilitacao').value = button.getAttribute('data-id_reabilitacao');
                document.getElementById('momento').value = button.getAttribute('data-momento');
                document.getElementById('sequencia').value = button.getAttribute('data-sequencia');
            });

            // Adiciona os data attributes ao botão de editar
            document.querySelectorAll('.edit-button').forEach(button => {
                const card = button.closest('.card');
                button.setAttribute('data-pergunta-id', card.getAttribute('data-pergunta-id'));
                button.setAttribute('data-titulo', card.getAttribute('data-titulo'));
                button.setAttribute('data-descricao', card.getAttribute('data-descricao'));
                button.setAttribute('data-id_reabilitacao', card.getAttribute('data-id_reabilitacao'));
                button.setAttribute('data-momento', card.getAttribute('data-momento'));
                button.setAttribute('data-comentario_afirmativo', card.getAttribute('data-comentario_afirmativo'));
                button.setAttribute('data-comentario_negativo', card.getAttribute('data-comentario_negativo'));
                button.setAttribute('data-sequencia', card.getAttribute('data-sequencia'));
            });

            // Modal de exclusão
            const deleteModal = document.getElementById('deleteModal');
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                document.getElementById('deletePerguntaId').value = button.getAttribute('data-pergunta-id');
            });

            // Função para remover notificações do CKEditor
            function removeCKEditorNotifications() {
                const notifications = document.querySelectorAll('.cke_notification_warning, .cke_notification_info, .cke_notification_success, .cke_notification');
                notifications.forEach(notification => {
                    notification.remove();
                });
            }

            // Observer para remover notificações assim que aparecerem
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.addedNodes.length) {
                        removeCKEditorNotifications();
                    }
                });
            });

            // Configuração do observer
            const observerConfig = {
                childList: true,
                subtree: true
            };

            // Iniciar observação quando o documento estiver pronto
            document.addEventListener('DOMContentLoaded', function() {
                observer.observe(document.body, observerConfig);
                // Remover notificações existentes
                removeCKEditorNotifications();
            });
        });
    </script>
</body>
</html>
