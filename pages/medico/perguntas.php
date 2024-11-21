<?php
// Removido session_start() pois já é iniciado no index.php

// Debug da sessão
error_log("Sessão atual: " . print_r($_SESSION, true));

// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    error_log("Acesso negado - user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'não definido'));
    error_log("Acesso negado - tipo_usuario: " . (isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : 'não definido'));
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    // Testa a conexão com o banco
    $pdo = getConnection();
    $pdo->query("SELECT 1");
    error_log("Conexão com banco de dados estabelecida com sucesso");
} catch (PDOException $e) {
    error_log("Erro na conexão com banco de dados: " . $e->getMessage());
    die("Erro na conexão com banco de dados. Por favor, contate o administrador.");
}

// Configuração da paginação
$perguntas_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $perguntas_por_pagina;

// Filtro de status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'todas';
$where_clause = "";
$params = [];

switch ($status_filter) {
    case 'pendentes':
        $where_clause = "WHERE p.status = 'pendente'";
        break;
    case 'respondidas':
        $where_clause = "WHERE p.status = 'respondida'";
        break;
    default:
        $where_clause = ""; // todas as perguntas
}

try {
    // Debug - Mostrar informações do usuário
    error_log("ID do usuário: " . $_SESSION['user_id']);
    error_log("Nome do usuário: " . $_SESSION['user_nome']);
    error_log("Tipo do usuário: " . $_SESSION['tipo_usuario']);

    // Verifica se o usuário existe e está ativo
    $stmt = $pdo->prepare("
        SELECT id, nome, tipo_usuario, status 
        FROM usuarios 
        WHERE id = ? AND tipo_usuario = 'medico' AND status = 'ativo'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        error_log("Usuário não encontrado ou inativo: ID " . $_SESSION['user_id']);
        throw new Exception("Usuário não encontrado ou sem permissão.");
    }

    // Conta total de perguntas
    $sql_count = "SELECT COUNT(*) FROM perguntas p " . $where_clause;
    error_log("SQL Count: " . $sql_count);
    $stmt = $pdo->query($sql_count);
    $total_perguntas = $stmt->fetchColumn();
    $total_paginas = ceil($total_perguntas / $perguntas_por_pagina);

    // Conta perguntas por status
    $sql_count_status = "
        SELECT status, COUNT(*) as total 
        FROM perguntas p 
        GROUP BY status
    ";
    $stmt = $pdo->query($sql_count_status);
    $contagem_status = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contagem_status[$row['status']] = $row['total'];
    }

    // Busca as perguntas com paginação
    $sql = "
        SELECT 
            p.*,
            COALESCE(u.nome, 'Pergunta do Admin') as nome_paciente,
            COALESCE(m.nome, '') as nome_medico
        FROM perguntas p
        LEFT JOIN usuarios u ON p.id_paciente = u.id
        LEFT JOIN usuarios m ON p.id_medico = m.id
        {$where_clause}
        ORDER BY p.data_criacao DESC
        LIMIT :limit OFFSET :offset
    ";
    
    error_log("SQL Perguntas: " . $sql);
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int)$perguntas_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar perguntas para o usuário {$_SESSION['user_nome']} (ID: {$_SESSION['user_id']}): " . $e->getMessage());
    error_log("Código do erro: " . $e->getCode());
    error_log("SQL State: " . $e->errorInfo[0]);
    $error = "Erro ao carregar as perguntas: " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perguntas - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=medico/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=medico/painel">Painel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=medico/perguntas">Perguntas</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3 text-white">
                        <i class="bi bi-person"></i> 
                        Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
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
                        <li class="breadcrumb-item"><a href="index.php?page=medico/painel">Painel</a></li>
                        <li class="breadcrumb-item active">Perguntas</li>
                    </ol>
                </nav>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

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

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-chat-dots"></i> 
                                Perguntas
                                <span class="badge bg-warning ms-2">
                                    Pendentes: <?php echo isset($contagem_status['pendente']) ? $contagem_status['pendente'] : 0; ?>
                                </span>
                                <span class="badge bg-success ms-2">
                                    Respondidas: <?php echo isset($contagem_status['respondida']) ? $contagem_status['respondida'] : 0; ?>
                                </span>
                            </h5>
                            <div>
                                <div class="btn-group">
                                    <a href="?page=medico/perguntas&status=todas" 
                                       class="btn btn-<?php echo $status_filter === 'todas' ? 'light' : 'outline-light'; ?> btn-sm">
                                        Todas
                                    </a>
                                    <a href="?page=medico/perguntas&status=pendentes" 
                                       class="btn btn-<?php echo $status_filter === 'pendentes' ? 'light' : 'outline-light'; ?> btn-sm">
                                        Pendentes
                                    </a>
                                    <a href="?page=medico/perguntas&status=respondidas" 
                                       class="btn btn-<?php echo $status_filter === 'respondidas' ? 'light' : 'outline-light'; ?> btn-sm">
                                        Respondidas
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($perguntas)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">Nenhuma pergunta encontrada</h4>
                                <p class="text-muted">
                                    Não há perguntas para exibir com os filtros selecionados.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Paciente</th>
                                            <th>Título</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                            <th>Respondido por</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($perguntas as $pergunta): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($pergunta['nome_paciente']); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($pergunta['titulo']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $pergunta['status'] === 'pendente' ? 'warning' : 'success'; ?>">
                                                        <?php echo ucfirst($pergunta['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($pergunta['status'] === 'respondida') {
                                                        echo htmlspecialchars($pergunta['nome_medico']);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($pergunta['status'] === 'pendente'): ?>
                                                        <a href="index.php?page=medico/responder_pergunta&id=<?php echo $pergunta['id']; ?>" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="bi bi-reply"></i> Responder
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="index.php?page=medico/visualizar_pergunta&id=<?php echo $pergunta['id']; ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="bi bi-eye"></i> Visualizar
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($total_paginas > 1): ?>
                                <nav aria-label="Navegação de páginas" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($pagina_atual > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=medico/perguntas&status=<?php echo $status_filter; ?>&pagina=<?php echo ($pagina_atual - 1); ?>">
                                                    <i class="bi bi-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                            <li class="page-item <?php echo $i === $pagina_atual ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=medico/perguntas&status=<?php echo $status_filter; ?>&pagina=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($pagina_atual < $total_paginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=medico/perguntas&status=<?php echo $status_filter; ?>&pagina=<?php echo ($pagina_atual + 1); ?>">
                                                    <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
