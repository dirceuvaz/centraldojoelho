<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$pdo = getConnection();

// Buscar todas as orientações de reabilitação do médico
$stmt = $pdo->prepare("
    SELECT r.*, m.descricao as momento_desc, t.descricao as tipo_desc
    FROM reabilitacao r
    LEFT JOIN momentos_reabilitacao m ON r.momento = m.id
    LEFT JOIN tipos_reabilitacao t ON r.tipo = t.id
    WHERE r.id_medico = ? 
    ORDER BY r.data_criacao DESC
");

$stmt->execute([$_SESSION['user_id']]);
$orientacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar momentos de reabilitação
$stmt = $pdo->prepare("SELECT * FROM momentos_reabilitacao ORDER BY id");
$stmt->execute();
$momentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar tipos de reabilitação
$stmt = $pdo->prepare("SELECT * FROM tipos_reabilitacao ORDER BY id");
$stmt->execute();
$tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar informações do médico
$stmt = $pdo->prepare("SELECT * FROM medicos WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar se há uma orientação para editar
if (isset($_GET['editar']) && isset($_SESSION['temp_orientacao'])) {
    $orientacao_edicao = $_SESSION['temp_orientacao'];
    unset($_SESSION['temp_orientacao']); // Limpar os dados temporários
}

// Exibir mensagens de sucesso ou erro
if (isset($_GET['sucesso'])) {
    $mensagem = htmlspecialchars($_GET['sucesso']);
    echo "<script>window.addEventListener('DOMContentLoaded', () => { mostrarMensagem('$mensagem'); });</script>";
}
if (isset($_GET['erro'])) {
    $mensagem = htmlspecialchars($_GET['erro']);
    echo "<script>window.addEventListener('DOMContentLoaded', () => { mostrarMensagem('$mensagem'); });</script>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Reabilitação - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.2/classic/ckeditor.js"></script>
    <style>
        .icon-large {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .card-dashboard {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-text {
            color: #6c757d;
        }
        .navbar-custom {
            background-color: #231F5D !important;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=medico/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">                    
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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=medico/painel" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Voltar ao Painel
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Gerenciamento de Reabilitação</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Gerenciamento de Reabilitação</h2>
                        <p class="text-muted mb-0">Gerencie as orientações e protocolos de reabilitação</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOrientacao" id="btnNovaOrientacao">
                        <i class="bi bi-plus-lg"></i> Nova Orientação
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Momento</th>
                                <th>Tipo</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orientacoes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="icon-large">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <p class="text-muted">Nenhuma orientação cadastrada</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orientacoes as $orientacao): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($orientacao['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($orientacao['momento_desc']); ?></td>
                                        <td><?php echo htmlspecialchars($orientacao['tipo_desc']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($orientacao['data_criacao'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary editar-orientacao" 
                                                    data-id="<?php echo $orientacao['id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger excluir-orientacao" 
                                                    data-id="<?php echo $orientacao['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Criar/Editar Orientação -->
    <div class="modal fade" id="modalOrientacao" tabindex="-1" aria-labelledby="modalOrientacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOrientacaoLabel">Nova Orientação de Reabilitação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formOrientacao" method="POST" action="index.php?page=medico/reabilitacao_process">
                    <div class="modal-body">
                        <input type="hidden" name="orientacao_id" id="orientacao_id">
                        <input type="hidden" name="action" id="form_action" value="criar">
                        
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>

                        <div class="mb-3">
                            <label for="momento" class="form-label">Momento</label>
                            <select class="form-select" id="momento" name="momento" required>
                                <option value="">Selecione o momento</option>
                                <?php foreach ($momentos as $momento): ?>
                                    <option value="<?php echo $momento['id']; ?>"><?php echo htmlspecialchars($momento['descricao']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Selecione o tipo</option>
                                <?php foreach ($tipos as $tipo): ?>
                                    <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['descricao']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editor" class="form-label">Texto</label>
                            <textarea id="editor" name="texto"></textarea>
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

    <!-- Modal de Mensagens -->
    <div id="modalMensagem" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aviso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p id="mensagemTexto"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function mostrarMensagem(mensagem) {
            document.getElementById('mensagemTexto').textContent = mensagem;
            const modalMensagem = new bootstrap.Modal(document.getElementById('modalMensagem'));
            modalMensagem.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            let editor;
            const formOrientacao = document.getElementById('formOrientacao');
            const modalOrientacao = new bootstrap.Modal(document.getElementById('modalOrientacao'));

            // Inicializar o CKEditor
            ClassicEditor
                .create(document.querySelector('#editor'), {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote'],
                    language: 'pt-br',
                    placeholder: 'Digite o texto da orientação aqui...'
                })
                .then(newEditor => {
                    editor = newEditor;
                    
                    // Se houver dados para edição, preencher o formulário
                    <?php if (isset($orientacao_edicao)): ?>
                    document.getElementById('orientacao_id').value = '<?php echo $orientacao_edicao['id']; ?>';
                    document.getElementById('titulo').value = '<?php echo addslashes($orientacao_edicao['titulo']); ?>';
                    document.getElementById('momento').value = '<?php echo $orientacao_edicao['momento']; ?>';
                    document.getElementById('tipo').value = '<?php echo $orientacao_edicao['tipo']; ?>';
                    editor.setData(<?php echo json_encode($orientacao_edicao['texto']); ?>);
                    document.getElementById('form_action').value = 'editar';
                    document.getElementById('modalOrientacaoLabel').textContent = 'Editar Orientação de Reabilitação';
                    modalOrientacao.show();
                    <?php endif; ?>
                })
                .catch(error => {
                    console.error('Erro ao inicializar o editor:', error);
                });

            // Função para limpar o formulário
            function limparFormulario() {
                formOrientacao.reset();
                document.getElementById('orientacao_id').value = '';
                document.getElementById('form_action').value = 'criar';
                if (editor) {
                    editor.setData('');
                }
            }

            // Manipulador para o botão "Nova Orientação"
            document.getElementById('btnNovaOrientacao').addEventListener('click', function() {
                limparFormulario();
                document.getElementById('modalOrientacaoLabel').textContent = 'Nova Orientação de Reabilitação';
                modalOrientacao.show();
            });

            // Manipulador para o formulário
            formOrientacao.addEventListener('submit', function(e) {
                const titulo = document.getElementById('titulo').value.trim();
                const momento = document.getElementById('momento').value;
                const tipo = document.getElementById('tipo').value;
                const texto = editor.getData().trim();

                if (!titulo || !momento || !tipo || !texto) {
                    e.preventDefault();
                    mostrarMensagem('Por favor, preencha todos os campos obrigatórios.');
                    return false;
                }

                // Atualizar o campo de texto com o conteúdo do editor
                const textareaElement = document.querySelector('textarea[name="texto"]');
                textareaElement.value = texto;
            });

            // Manipulador para os botões de editar
            document.querySelectorAll('.editar-orientacao').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const orientacaoId = this.getAttribute('data-id');
                    window.location.href = 'index.php?page=medico/reabilitacao_process&action=buscar&id=' + orientacaoId;
                });
            });

            // Manipulador para os botões de excluir
            document.querySelectorAll('.excluir-orientacao').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    mostrarMensagem('Tem certeza que deseja excluir esta orientação?');
                    document.getElementById('modalMensagem').addEventListener('hidden.bs.modal', function () {
                        if (document.getElementById('mensagemTexto').textContent === 'Tem certeza que deseja excluir esta orientação?') {
                            window.location.href = 'index.php?page=medico/reabilitacao_process&action=excluir&orientacao_id=' + id;
                        }
                    }, { once: true });
                });
            });

            // Fechar o modal de orientação após mostrar mensagem de sucesso
            document.getElementById('modalMensagem').addEventListener('hidden.bs.modal', function () {
                const mensagem = document.getElementById('mensagemTexto').textContent;
                if (mensagem.includes('sucesso')) {
                    modalOrientacao.hide();
                }
            });
        });
    </script>
</body>
</html>