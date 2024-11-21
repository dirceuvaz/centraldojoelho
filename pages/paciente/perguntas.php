<?php
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getConnection();
    
    // Conta perguntas por status
    $sql_count_status = "
        SELECT status, COUNT(*) as total 
        FROM perguntas 
        WHERE id_paciente = ? OR criado_por = ?
        GROUP BY status
    ";
    $stmt = $pdo->prepare($sql_count_status);
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $contagem_status = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contagem_status[$row['status']] = $row['total'];
    }

    // Filtro de status
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'todas';
    $where_clause = "WHERE (p.id_paciente = ? OR p.criado_por = ?)";
    $params = [$_SESSION['user_id'], $_SESSION['user_id']];

    if ($status_filter === 'pendentes') {
        $where_clause .= " AND p.status = 'pendente'";
    } elseif ($status_filter === 'respondidas') {
        $where_clause .= " AND p.status = 'respondida'";
    }
    
    // Busca todas as perguntas relacionadas ao paciente (enviadas por ele ou direcionadas a ele)
    $sql = "SELECT p.*, 
                   u_med.nome as nome_medico,
                   u_criador.nome as nome_criador,
                   u_criador.tipo_usuario as tipo_criador,
                   u_criador.email as email_criador
            FROM perguntas p
            LEFT JOIN usuarios u_med ON p.id_medico = u_med.id
            LEFT JOIN usuarios u_criador ON p.criado_por = u_criador.id
            $where_clause
            ORDER BY p.data_criacao DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca lista de médicos para o formulário de nova pergunta
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico' AND status = 'ativo' ORDER BY nome");
    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca os dados do usuário logado
    $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar perguntas: " . $e->getMessage());
    $error = "Erro ao carregar as perguntas. Por favor, tente novamente mais tarde.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central do Joelho - Perguntas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=paciente/painel">Painel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=paciente/perguntas">Perguntas</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3 text-white">
                        <i class="bi bi-person"></i> 
                        Olá, <?php echo htmlspecialchars($usuario['email']); ?>
                    </span>
                    <a class="btn btn-light btn-sm" href="index.php?page=login_process&logout=1">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?page=paciente/painel">Painel</a></li>
                        <li class="breadcrumb-item active">Perguntas</li>
                    </ol>
                </nav>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-chat-dots"></i> 
                                Minhas Perguntas
                                <span class="badge bg-warning ms-2">
                                    Pendentes: <?php echo isset($contagem_status['pendente']) ? $contagem_status['pendente'] : 0; ?>
                                </span>
                                <span class="badge bg-success ms-2">
                                    Respondidas: <?php echo isset($contagem_status['respondida']) ? $contagem_status['respondida'] : 0; ?>
                                </span>
                            </h5>
                            <div>
                                <div class="btn-group">
                                    <a href="?page=paciente/perguntas&status=todas" 
                                       class="btn btn-<?php echo $status_filter === 'todas' ? 'light' : 'outline-light'; ?> btn-sm">
                                        Todas
                                    </a>
                                    <a href="?page=paciente/perguntas&status=pendentes" 
                                       class="btn btn-<?php echo $status_filter === 'pendentes' ? 'light' : 'outline-light'; ?> btn-sm">
                                        Pendentes
                                    </a>
                                    <a href="?page=paciente/perguntas&status=respondidas" 
                                       class="btn btn-<?php echo $status_filter === 'respondidas' ? 'light' : 'outline-light'; ?> btn-sm">
                                        Respondidas
                                    </a>
                                </div>
                                <button class="btn btn-light btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#novaPerguntaModal">
                                    <i class="bi bi-plus-circle"></i> Nova Pergunta
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                    echo $_SESSION['error'];
                                    unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                    echo $_SESSION['success'];
                                    unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($perguntas)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">Nenhuma pergunta encontrada</h4>
                                <p class="text-muted">Clique em "Nova Pergunta" para fazer sua primeira pergunta.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($perguntas as $pergunta): ?>
                                    <div class="col-12 mb-3">
                                        <div class="card h-100 <?php echo $pergunta['status'] === 'pendente' ? 'border-warning' : 'border-success'; ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h5 class="card-title"><?php echo htmlspecialchars($pergunta['titulo']); ?></h5>
                                                        <p class="card-subtitle mb-2 text-muted">
                                                            <small>
                                                                <?php if (!empty($pergunta['nome_medico'])): ?>
                                                                    Médico: <?php echo htmlspecialchars($pergunta['nome_medico']); ?> |
                                                                <?php endif; ?>
                                                                Data: <?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?>
                                                                <?php if (!empty($pergunta['data_atualizacao'])): ?>
                                                                    | Atualizada em: <?php echo date('d/m/Y H:i', strtotime($pergunta['data_atualizacao'])); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </p>
                                                    </div>
                                                    <div class="btn-group">
                                                        <?php if ($pergunta['status'] === 'respondida'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success" 
                                                                    onclick="verResposta(<?php echo $pergunta['id']; ?>)">
                                                                <i class="bi bi-eye"></i> Ver Resposta
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary" 
                                                                    onclick="responderMedico(<?php echo $pergunta['id']; ?>)">
                                                                <i class="bi bi-reply"></i> Responder
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary" 
                                                                    onclick="editarPergunta(<?php echo $pergunta['id']; ?>)">
                                                                <i class="bi bi-pencil"></i> Editar
                                                            </button>
                                                            <form method="POST" action="pages/paciente/excluir_pergunta.php" style="display: inline;">
                                                                <input type="hidden" name="id" value="<?php echo $pergunta['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir esta pergunta?')">
                                                                    <i class="bi bi-trash"></i> Excluir
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <p class="card-text mt-2"><?php echo nl2br(htmlspecialchars($pergunta['descricao'])); ?></p>
                                                
                                                <?php if (!empty($pergunta['resposta'])): ?>
                                                    <div class="alert alert-info mt-3">
                                                        <h6 class="alert-heading">Resposta:</h6>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($pergunta['resposta'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Pergunta -->
    <div class="modal fade" id="novaPerguntaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nova Pergunta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formNovaPergunta" action="pages/paciente/salvar_pergunta.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required 
                                   placeholder="Digite o título da sua pergunta">
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="4" required
                                      placeholder="Descreva sua pergunta em detalhes"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="medico" class="form-label">Médico (opcional)</label>
                            <select class="form-select" id="medico" name="id_medico">
                                <option value="">Selecione um médico...</option>
                                <?php foreach ($medicos as $medico): ?>
                                    <option value="<?php echo $medico['id']; ?>">
                                        Dr(a). <?php echo htmlspecialchars($medico['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione um médico específico ou deixe em branco para todos.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Salvar Pergunta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Pergunta -->
    <div class="modal fade" id="editarPerguntaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Pergunta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="editarPerguntaForm" action="index.php?page=paciente/editar_pergunta_ajax" method="post">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required
                                   placeholder="Digite o título da sua pergunta">
                        </div>
                        <div class="mb-3">
                            <label for="edit_descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="edit_descricao" name="descricao" rows="4" required
                                      placeholder="Descreva sua pergunta em detalhes"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_medico" class="form-label">Médico (opcional)</label>
                            <select class="form-select" id="edit_medico" name="id_medico">
                                <option value="">Selecione um médico...</option>
                                <?php foreach ($medicos as $medico): ?>
                                    <option value="<?php echo $medico['id']; ?>">
                                        Dr(a). <?php echo htmlspecialchars($medico['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione um médico específico ou deixe em branco para todos.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Responder Pergunta -->
    <div class="modal fade" id="responderPerguntaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-reply"></i> Responder Pergunta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formResponderPergunta" method="POST" action="index.php?page=paciente/responder_pergunta">
                    <div class="modal-body">
                        <input type="hidden" id="resposta_id" name="id">
                        <div class="mb-3">
                            <label for="resposta" class="form-label">Sua Resposta</label>
                            <textarea class="form-control" id="resposta" name="resposta" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send"></i> Enviar Resposta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Resposta -->
    <div class="modal fade" id="verRespostaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Resposta do Médico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sua Pergunta:</label>
                        <p id="perguntaTexto"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Médico:</label>
                        <p id="medicoNome"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Resposta:</label>
                        <p id="respostaTexto"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Data da Resposta:</label>
                        <p id="dataResposta"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Responder Médico -->
    <div class="modal fade" id="responderMedicoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-reply"></i> Responder ao Médico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form action="index.php?page=paciente/responder_medico_process" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_pergunta" id="perguntaId">
                        <div class="mb-3">
                            <label class="form-label">Médico:</label>
                            <p id="medicoNomeResposta"></p>
                        </div>
                        <div class="mb-3">
                            <label for="mensagem" class="form-label">Sua Mensagem:</label>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Enviar Mensagem</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializa todos os modais
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                new bootstrap.Modal(modal);
            });

            // Limpa os formulários quando os modais são fechados
            modals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function() {
                    const form = this.querySelector('form');
                    if (form) {
                        form.reset();
                    }
                });
            });

            // Configura o formulário de nova pergunta
            document.getElementById('formNovaPergunta').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('pages/paciente/salvar_pergunta.php', {
                    method: 'POST',
                    body: formData
                })
                .then(async response => {
                    const text = await response.text();
                    try {
                        const data = JSON.parse(text);
                        if (!response.ok) {
                            throw new Error(data.message || 'Erro ao salvar pergunta');
                        }
                        return data;
                    } catch (e) {
                        console.error('Resposta do servidor:', text);
                        throw new Error('Erro na resposta do servidor: ' + e.message);
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Fecha o modal
                        bootstrap.Modal.getInstance(document.getElementById('novaPerguntaModal')).hide();
                        // Mostra mensagem de sucesso
                        alert(data.message);
                        // Recarrega a página para mostrar a nova pergunta
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Erro ao salvar pergunta');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar pergunta: ' + error.message);
                });
            });

            // Modal Ver Resposta
            const verRespostaModal = document.getElementById('verRespostaModal');
            if (verRespostaModal) {
                verRespostaModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const pergunta = button.getAttribute('data-pergunta');
                    const medico = button.getAttribute('data-medico');
                    
                    console.log('Buscando resposta para pergunta ID:', id); // Debug
                    
                    // Busca a resposta via AJAX
                    fetch(`pages/paciente/get_resposta.php?id=${id}`)
                        .then(async response => {
                            const text = await response.text();
                            try {
                                const data = JSON.parse(text);
                                if (!response.ok) {
                                    throw new Error(data.message || 'Erro ao carregar resposta');
                                }
                                return data;
                            } catch (e) {
                                console.error('Resposta do servidor:', text);
                                throw new Error('Erro na resposta do servidor: ' + e.message);
                            }
                        })
                        .then(data => {
                            if (data.success && data.data) {
                                // Preenche os dados no modal
                                document.getElementById('perguntaTexto').textContent = data.data.descricao;
                                document.getElementById('medicoNome').textContent = data.data.nome_medico;
                                document.getElementById('respostaTexto').textContent = data.data.resposta;
                                document.getElementById('dataResposta').textContent = data.data.data_resposta;
                                
                                // Abre o modal
                                new bootstrap.Modal(document.getElementById('verRespostaModal')).show();
                            } else {
                                throw new Error(data.message || 'Erro ao carregar resposta');
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            alert('Erro ao carregar a resposta: ' + error.message);
                        });
                });
            }

            // Modal Responder Médico
            const responderMedicoModal = document.getElementById('responderMedicoModal');
            if (responderMedicoModal) {
                responderMedicoModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const medico = button.getAttribute('data-medico');
                    
                    document.getElementById('perguntaId').value = id;
                    document.getElementById('medicoNomeResposta').textContent = medico;
                });
            }
        });

        // Função para editar pergunta
        function editarPergunta(id) {
            fetch(`index.php?page=paciente/carregar_pergunta&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.pergunta.id;
                        document.getElementById('edit_titulo').value = data.pergunta.titulo;
                        document.getElementById('edit_descricao').value = data.pergunta.descricao;
                        
                        // Preenche o select de médicos
                        const medicoSelect = document.getElementById('edit_medico');
                        medicoSelect.value = data.pergunta.id_medico || '';
                        
                        // Abre o modal
                        new bootstrap.Modal(document.getElementById('editarPerguntaModal')).show();
                    } else {
                        alert(data.message || 'Erro ao carregar dados da pergunta');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados da pergunta');
                });
        }

        // Função para salvar edição
        document.getElementById('editarPerguntaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('pages/paciente/editar_pergunta_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (!response.ok) {
                        throw new Error(data.message || 'Erro ao salvar alterações');
                    }
                    return data;
                } catch (e) {
                    console.error('Resposta do servidor:', text);
                    throw new Error('Erro no servidor: ' + e.message);
                }
            })
            .then(data => {
                if (data.success) {
                    // Fecha o modal
                    bootstrap.Modal.getInstance(document.getElementById('editarPerguntaModal')).hide();
                    // Mostra mensagem de sucesso
                    alert(data.message);
                    // Recarrega a página para mostrar as alterações
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao salvar alterações');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert(error.message || 'Erro ao salvar alterações');
            });
        });

        function responderPergunta(id) {
            document.getElementById('resposta_id').value = id;
            const modal = new bootstrap.Modal(document.getElementById('responderPerguntaModal'));
            modal.show();
        }

        function excluirPergunta(id) {
            if (!confirm('Tem certeza que deseja excluir esta pergunta?')) {
                return;
            }

            fetch('pages/paciente/excluir_pergunta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pergunta excluída com sucesso!');
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao excluir pergunta');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir pergunta: ' + error.message);
            });
        }

        function verResposta(id) {
            fetch(`pages/paciente/get_resposta.php?id=${id}`)
                .then(async response => {
                    const text = await response.text();
                    try {
                        const data = JSON.parse(text);
                        if (!response.ok) {
                            throw new Error(data.message || 'Erro ao carregar resposta');
                        }
                        return data;
                    } catch (e) {
                        console.error('Resposta do servidor:', text);
                        throw new Error('Erro na resposta do servidor: ' + e.message);
                    }
                })
                .then(data => {
                    if (data.success && data.data) {
                        // Preenche os dados no modal
                        document.getElementById('perguntaTexto').textContent = data.data.descricao;
                        document.getElementById('medicoNome').textContent = data.data.nome_medico || 'Não informado';
                        document.getElementById('respostaTexto').textContent = data.data.resposta;
                        document.getElementById('dataResposta').textContent = data.data.data_resposta || 'Não informada';
                        
                        // Abre o modal
                        new bootstrap.Modal(document.getElementById('verRespostaModal')).show();
                    } else {
                        throw new Error(data.message || 'Erro ao carregar resposta');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    if (error.message.includes('ainda não foi respondida')) {
                        alert('Esta pergunta ainda não foi respondida pelo médico.');
                    } else {
                        alert('Erro ao carregar a resposta: ' + error.message);
                    }
                });
        }
    </script>
</body>
</html>
