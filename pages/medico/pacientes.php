<?php
// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getConnection();

// Configuração da paginação
$limite_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// Busca os dados do médico
$stmt = $pdo->prepare("
    SELECT m.*, u.nome, u.id as usuario_id, u.status as usuario_status
    FROM medicos m
    JOIN usuarios u ON u.id = m.id_usuario
    WHERE m.id_usuario = ?
");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();

// Verifica se o médico está liberado
$medico_liberado = $medico['usuario_status'] === 'ativo';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'autorizar' && $medico_liberado) {
            $id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_VALIDATE_INT);
            if (!$id_paciente) {
                throw new Exception('ID do paciente inválido');
            }

            $stmt = $pdo->prepare("
                UPDATE usuarios u
                JOIN pacientes p ON p.id_usuario = u.id
                SET u.status = 'ativo', p.status = 'ativo'
                WHERE p.id = ? AND p.medico = ?
            ");
            $stmt->execute([$id_paciente, $medico['nome']]);
            
            header('Location: index.php?page=medico/pacientes&sucesso=Paciente autorizado com sucesso');
            exit;
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Contar total de pacientes
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM usuarios u
    JOIN pacientes p ON p.id_usuario = u.id
    WHERE p.medico = ?
");
$stmt->execute([$medico['nome']]);
$total_pacientes = $stmt->fetch()['total'];
$total_paginas = ceil($total_pacientes / $limite_por_pagina);

// Buscar pacientes do médico com paginação
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.nome,
        u.email,
        u.status as usuario_status,
        p.data_cirurgia,
        p.problema,
        p.status as paciente_status,
        p.data_cadastro,
        p.id as paciente_id
    FROM usuarios u
    JOIN pacientes p ON p.id_usuario = u.id
    WHERE p.medico = ?
    ORDER BY p.data_cadastro DESC
    LIMIT ?, ?
");
$stmt->bindValue(1, $medico['nome'], PDO::PARAM_STR);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->bindValue(3, $limite_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$pacientes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pacientes - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=medico/painel">Central do Joelho</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#perfilModal">
                            <i class="bi bi-person-circle"></i> Perfil
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
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=medico/painel" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Voltar ao Painel
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Gerenciar Pacientes</li>
                    </ol>
                </nav>
                <h2>Gerenciar Pacientes</h2>
                <p class="text-muted">Gerencie seus pacientes e autorize novos acessos</p>
            </div>
        </div>

        <?php if (!$medico_liberado): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">
                <i class="bi bi-exclamation-triangle"></i> Acesso Limitado
            </h4>
            <p class="mb-0">
                Seu cadastro está pendente de liberação. Algumas funcionalidades estarão indisponíveis até que seu acesso seja autorizado pela administração.
            </p>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['sucesso']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($erro); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Data da Cirurgia</th>
                                <th>Problema</th>
                                <th>Status</th>
                                <th>Data de Cadastro</th>
                                <th class="table-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pacientes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-info-circle text-muted"></i>
                                    Nenhum paciente encontrado
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($pacientes as $paciente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($paciente['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($paciente['email']); ?></td>
                                    <td><?php echo $paciente['data_cirurgia'] ? date('d/m/Y', strtotime($paciente['data_cirurgia'])) : 'Não definida'; ?></td>
                                    <td><?php echo htmlspecialchars($paciente['problema']); ?></td>
                                    <td>
                                        <?php if ($paciente['usuario_status'] === 'pendente'): ?>
                                            <span class="badge bg-warning status-badge">Pendente</span>
                                        <?php elseif ($paciente['usuario_status'] === 'ativo'): ?>
                                            <span class="badge bg-success status-badge">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger status-badge">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($paciente['data_cadastro'])); ?></td>
                                    <td>
                                        <?php if ($paciente['usuario_status'] === 'pendente' && $medico_liberado): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="autorizar">
                                                <input type="hidden" name="id_paciente" value="<?php echo $paciente['paciente_id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check-circle"></i> Liberar Acesso
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="index.php?page=medico/paciente_acompanhamento&id=<?php echo $paciente['paciente_id']; ?>" 
                                           class="btn btn-primary btn-sm <?php echo ($paciente['usuario_status'] === 'pendente' ? 'ms-2' : ''); ?>" 
                                           title="Gerenciar Paciente">
                                            <i class="bi bi-clipboard-pulse"></i> Gerenciar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Navegação de páginas">
                        <ul class="pagination">
                            <?php if ($pagina_atual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=medico/pacientes&pagina=<?php echo $pagina_atual - 1; ?>">Anterior</a>
                            </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo $i === $pagina_atual ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=medico/pacientes&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($pagina_atual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=medico/pacientes&pagina=<?php echo $pagina_atual + 1; ?>">Próxima</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Perfil -->
    <div class="modal fade" id="perfilModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Perfil do Médico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($medico['nome']); ?></p>
                    <p><strong>CRM:</strong> <?php echo htmlspecialchars($medico['crm']); ?></p>
                    <p><strong>Especialidade:</strong> <?php echo htmlspecialchars($medico['especialidade']); ?></p>
                    <p>
                        <strong>Status:</strong>
                        <?php if ($medico['usuario_status'] === 'pendente'): ?>
                            <span class="badge bg-warning">Pendente</span>
                        <?php elseif ($medico['usuario_status'] === 'ativo'): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativo</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
