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
    SELECT m.*, u.nome, u.id as usuario_id
    FROM medicos m
    JOIN usuarios u ON u.id = m.id_usuario
    WHERE m.id_usuario = ?
");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'autorizar') {
            $stmt = $pdo->prepare("
                UPDATE usuarios u
                JOIN pacientes p ON p.id_usuario = u.id
                SET u.status = 'ativo', p.status = 'ativo'
                WHERE u.id = ? AND p.medico = ?
            ");
            $stmt->execute([$_POST['id_paciente'], $medico['nome']]);
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
    WHERE p.medico = 'Lucas'
");
$stmt->execute();
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
        p.data_cadastro
    FROM usuarios u
    JOIN pacientes p ON p.id_usuario = u.id
    WHERE p.medico = 'Lucas'
    ORDER BY p.data_cadastro DESC
    LIMIT ?, ?
");
$stmt->bindValue(1, $offset, PDO::PARAM_INT);
$stmt->bindValue(2, $limite_por_pagina, PDO::PARAM_INT);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .card-dashboard {
            transition: transform 0.2s;
            cursor: pointer;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .icon-large {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .bg-primary {
            background-color: #0d6efd !important;
        }
        .table-actions {
            width: 100px;
        }
        .status-badge {
            width: 100px;
            text-align: center;
        }
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
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
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=medico/painel">
                            <i class="bi bi-arrow-left-circle"></i> Voltar ao Painel
                        </a>
                    </li>
                </ul>
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

    <div class="container my-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Gerenciar Pacientes</h2>
                <p class="text-muted">Gerencie seus pacientes e autorize novos acessos</p>
            </div>
        </div>

        <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['sucesso']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($erro); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
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
                                        <div class="btn-group">
                                            <?php if ($paciente['usuario_status'] === 'pendente'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="autorizar">
                                                    <input type="hidden" name="id_paciente" value="<?php echo $paciente['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Autorizar acesso">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="window.location='index.php?page=medico/paciente_detalhes&id=<?php echo $paciente['id']; ?>'"
                                                    title="Ver detalhes">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Navegação de páginas">
                        <ul class="pagination">
                            <?php if ($pagina_atual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=medico/pacientes&pagina=<?php echo $pagina_atual - 1; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo $i === $pagina_atual ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=medico/pacientes&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($pagina_atual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=medico/pacientes&pagina=<?php echo $pagina_atual + 1; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
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
                    <h5 class="modal-title">Meu Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($medico['nome']); ?></p>
                    <p><strong>CRM:</strong> <?php echo htmlspecialchars($medico['crm']); ?></p>
                    <p><strong>Especialidade:</strong> <?php echo htmlspecialchars($medico['especialidade']); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="window.location='index.php?page=medico/editar_perfil'">
                        Editar Perfil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
