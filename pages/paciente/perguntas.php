<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Buscar informações do paciente e sua reabilitação atual
$stmt = $conn->prepare("
    SELECT p.*, r.id as id_reabilitacao, r.titulo as reabilitacao_titulo,
           DATEDIFF(CURRENT_DATE, p.data_cirurgia) as dias_pos_cirurgia
    FROM pacientes p
    LEFT JOIN reabilitacao r ON r.id = p.id_reabilitacao
    WHERE p.id_usuario = ?
");
$stmt->execute([$user_id]);
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não encontrar paciente, criar um registro básico
if (!$paciente) {
    $paciente = [
        'id_reabilitacao' => null,
        'reabilitacao_titulo' => 'Não definida',
        'dias_pos_cirurgia' => 0
    ];
}

// Determinar o momento atual baseado nos dias após a cirurgia
$momento_atual = 'Pre_Operatorio';
if ($paciente['dias_pos_cirurgia'] > 0) {
    if ($paciente['dias_pos_cirurgia'] <= 7) {
        $momento_atual = 'Primeira_Semana';
    } elseif ($paciente['dias_pos_cirurgia'] <= 14) {
        $momento_atual = 'Segunda_Semana';
    } elseif ($paciente['dias_pos_cirurgia'] <= 21) {
        $momento_atual = 'Terceira_Semana';
    } elseif ($paciente['dias_pos_cirurgia'] <= 28) {
        $momento_atual = 'Quarta_Semana';
    } elseif ($paciente['dias_pos_cirurgia'] <= 35) {
        $momento_atual = 'Quinta_Semana';
    } elseif ($paciente['dias_pos_cirurgia'] <= 70) {
        $momento_atual = 'Sexta_a_Decima_Semana';
    } elseif ($paciente['dias_pos_cirurgia'] <= 140) {
        $momento_atual = 'Decima_Primeira_a_Vigesima_Semana';
    } else {
        $momento_atual = 'Sexto_Mes';
    }
}

// Buscar perguntas da reabilitação atual do paciente
$stmt = $conn->prepare("
    SELECT p.*, 
           COALESCE(r.resposta, '') as resposta,
           COALESCE(r.data_resposta, '') as data_resposta,
           p.comentario_afirmativo,
           p.comentario_negativo
    FROM perguntas p
    LEFT JOIN respostas r ON p.id = r.id_pergunta AND r.id_paciente = ?
    WHERE (p.id_reabilitacao = ? OR p.id_reabilitacao IS NULL)
    AND (p.id_paciente IS NULL OR p.id_paciente = ?)
    AND (p.momento IS NULL OR p.momento = ?)
    ORDER BY p.sequencia ASC
");
$stmt->execute([$user_id, $paciente['id_reabilitacao'], $paciente['id'] ?? null, $momento_atual]);
$perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar o envio de respostas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;
    $respostas_validas = true;
    $total_perguntas = count($perguntas);
    $respostas_respondidas = 0;

    // Validar respostas
    foreach ($perguntas as $pergunta) {
        if (isset($_POST['respostas'][$pergunta['id']])) {
            $resposta = trim($_POST['respostas'][$pergunta['id']]);
            if ($resposta === 'sim' || $resposta === 'nao') {
                $respostas_respondidas++;
            }
        }
    }

    // Verificar se todas as perguntas foram respondidas
    if ($respostas_respondidas < $total_perguntas) {
        $error = "Por favor, responda todas as perguntas antes de enviar.";
        $respostas_validas = false;
    }

    if ($respostas_validas) {
        try {
            $conn->beginTransaction();
            
            foreach ($_POST['respostas'] as $pergunta_id => $resposta) {
                // Validar se a pergunta existe e pertence ao paciente
                $stmt = $conn->prepare("
                    SELECT id FROM perguntas 
                    WHERE id = ? 
                    AND id_reabilitacao = ? 
                    AND (id_paciente IS NULL OR id_paciente = ?)
                ");
                $stmt->execute([$pergunta_id, $paciente['id_reabilitacao'], $paciente['id'] ?? null]);
                
                if ($stmt->rowCount() > 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO respostas (id_pergunta, id_paciente, resposta, data_resposta)
                        VALUES (?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE resposta = ?, data_resposta = NOW()
                    ");
                    $stmt->execute([$pergunta_id, $user_id, $resposta, $resposta]);
                }
            }
            
            $conn->commit();

            // Registrar atividade do paciente
            $stmt = $conn->prepare("
                INSERT INTO atividades_paciente (id_paciente, tipo_atividade, descricao, data_atividade)
                VALUES (?, 'resposta_perguntas', 'Respondeu às perguntas da reabilitação', NOW())
            ");
            $stmt->execute([$user_id]);

            header('Location: index.php?page=paciente/perguntas&msg=respostas_salvas');
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Erro ao salvar respostas: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perguntas da Reabilitação - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .pergunta-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .pergunta-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .pergunta-numero {
            width: 32px;
            height: 32px;
            background-color: #231F5D;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .resposta-anterior {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin: 15px 0;
            border-left: 4px solid #231F5D;
        }
        .descricao-pergunta {
            color: #666;
            margin: 15px 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .form-check-input:checked {
            background-color: #231F5D;
            border-color: #231F5D;
        }
        .form-check-input:focus {
            border-color: #231F5D;
            box-shadow: 0 0 0 0.25rem rgba(35, 31, 93, 0.25);
        }
        .comentarios .alert {
            border: none;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid;
        }
        .comentarios .alert-success {
            background-color: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }
        .comentarios .alert-warning {
            background-color: #fff3e0;
            border-left-color: #ff9800;
            color: #ef6c00;
        }
        .btn-primary {
            background-color: #231F5D;
            border-color: #231F5D;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #1a174a;
            border-color: #1a174a;
            transform: translateY(-2px);
        }
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 20px;
        }
        .badge.bg-success {
            background-color: #4caf50 !important;
        }
        .badge.bg-danger {
            background-color: #f44336 !important;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #231F5D;">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=paciente/perfil">
                            <i class="bi bi-person-circle"></i> Meu Perfil
                        </a>
                    </li>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Perguntas da Reabilitação</h2>
                <p class="text-muted">
                    Reabilitação: <?php echo htmlspecialchars($paciente['reabilitacao_titulo'] ?? 'Não definida'); ?><br>
                    Momento: <?php echo $momento_atual; ?>
                </p>
            </div>
            <nav aria-label="breadcrumb">
            <div class="d-flex align-items-center">
                <a href="index.php?page=paciente/painel" class="btn btn-warning me-2">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
                </a>
            </div>              
            </nav>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'respostas_salvas'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Suas respostas foram salvas com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($perguntas)): ?>
            <div class="alert alert-info" role="alert">
                Não há perguntas disponíveis para responder neste momento.
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="row g-4">
                    <?php foreach ($perguntas as $index => $pergunta): ?>
                        <div class="col-12">
                            <div class="pergunta-card card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="pergunta-numero">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <h5 class="card-title mb-0 ms-3">
                                            <?php echo strip_tags($pergunta['titulo']); ?>
                                        </h5>
                                    </div>
                                    <p class="card-text mb-4">
                                        <?php echo strip_tags($pergunta['descricao']); ?>
                                    </p>

                                    <?php if (!empty($pergunta['resposta'])): ?>
                                        <div class="resposta-anterior mb-3">
                                            <div class="d-flex align-items-center">
                                                <span class="badge <?php echo $pergunta['resposta'] === 'sim' ? 'bg-success' : 'bg-danger'; ?> me-2">
                                                    <?php echo $pergunta['resposta'] === 'sim' ? 'Sim' : 'Não'; ?>
                                                </span>
                                                <small class="text-muted">
                                                    Respondido em <?php echo date('d/m/Y \à\s H:i', strtotime($pergunta['data_resposta'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" 
                                                   name="respostas[<?php echo $pergunta['id']; ?>]" 
                                                   id="resposta_sim_<?php echo $pergunta['id']; ?>" 
                                                   value="sim"
                                                   <?php echo ($pergunta['resposta'] === 'sim') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="resposta_sim_<?php echo $pergunta['id']; ?>">
                                                Sim
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" 
                                                   name="respostas[<?php echo $pergunta['id']; ?>]" 
                                                   id="resposta_nao_<?php echo $pergunta['id']; ?>" 
                                                   value="nao"
                                                   <?php echo ($pergunta['resposta'] === 'nao') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="resposta_nao_<?php echo $pergunta['id']; ?>">
                                                Não
                                            </label>
                                        </div>
                                    </div>

                                    <?php if (!empty($pergunta['comentario_afirmativo']) || !empty($pergunta['comentario_negativo'])): ?>
                                        <div class="comentarios mt-3" id="comentarios_<?php echo $pergunta['id']; ?>">
                                            <?php if (!empty($pergunta['comentario_afirmativo'])): ?>
                                                <div class="alert alert-success comentario-afirmativo" style="display: none;">
                                                    <i class="bi bi-check-circle-fill me-2"></i>
                                                    <?php echo strip_tags($pergunta['comentario_afirmativo']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($pergunta['comentario_negativo'])): ?>
                                                <div class="alert alert-danger comentario-negativo" style="display: none;">
                                                    <i class="bi bi-x-circle-fill me-2"></i>
                                                    <?php echo strip_tags($pergunta['comentario_negativo']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-grid gap-2 col-md-6 mx-auto mt-4 mb-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle"></i> Salvar Respostas
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para mostrar/ocultar comentários com base na resposta
        function toggleComentarios(perguntaId, resposta) {
            const comentarios = document.getElementById('comentarios_' + perguntaId);
            if (comentarios) {
                const comentarioAfirmativo = comentarios.querySelector('.comentario-afirmativo');
                const comentarioNegativo = comentarios.querySelector('.comentario-negativo');
                
                if (comentarioAfirmativo) {
                    comentarioAfirmativo.style.display = resposta === 'sim' ? 'block' : 'none';
                }
                if (comentarioNegativo) {
                    comentarioNegativo.style.display = resposta === 'nao' ? 'block' : 'none';
                }
            }
        }

        // Adiciona listeners para os radio buttons
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const perguntaId = this.id.split('_').pop();
                toggleComentarios(perguntaId, this.value);
            });

            // Mostra comentários para respostas já selecionadas
            if (radio.checked) {
                const perguntaId = radio.id.split('_').pop();
                toggleComentarios(perguntaId, radio.value);
            }
        });

        // Mostra mensagem de sucesso por 3 segundos
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.classList.remove('show');
                setTimeout(() => successAlert.remove(), 150);
            }, 3000);
        }
    </script>
</body>
</html>
