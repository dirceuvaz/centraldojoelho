<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();

// Configuração da paginação
$itemsPerPage = 10;
$currentPage = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Busca o total de pacientes do médico
$countSql = "
    SELECT COUNT(*) as total 
    FROM pacientes p
    JOIN usuarios u ON p.id_usuario = u.id
    WHERE p.medico = ? AND u.status IN ('ativo', 'bloqueado')
";
$countStmt = $conn->prepare($countSql);
$countStmt->execute([$_SESSION['user_id']]);
$totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $itemsPerPage);

// Busca os pacientes do médico com paginação
$sql = "
    SELECT 
        p.id as id_paciente,
        p.id_usuario,
        p.data_cirurgia,
        p.problema,
        u.nome, 
        u.email,
        u.status as usuario_status
    FROM pacientes p
    JOIN usuarios u ON p.id_usuario = u.id
    WHERE p.medico = ? AND u.status IN ('ativo', 'bloqueado')
    ORDER BY u.nome
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->bindValue(3, $itemsPerPage, PDO::PARAM_INT);
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para formatar a data
function formatarData($data) {
    return $data ? date('d/m/Y', strtotime($data)) : '-';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pacientes - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .status-badge {
            width: 100px;
            text-align: center;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .btn-block-red {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-block-red:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
            color: white;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../common/header_medico.php'; ?>

    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <h2>Meus Pacientes</h2>                        
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
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
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
                                        <th>Tipo de Cirurgia</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pacientes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Nenhum paciente encontrado.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pacientes as $paciente): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($paciente['nome']); ?></td>
                                                <td><?php echo htmlspecialchars($paciente['email']); ?></td>
                                                <td><?php echo formatarData($paciente['data_cirurgia']); ?></td>
                                                <td><?php echo htmlspecialchars($paciente['problema']); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill status-badge <?php echo $paciente['usuario_status'] == 'ativo' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ucfirst($paciente['usuario_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <?php if ($paciente['usuario_status'] == 'bloqueado'): ?>
                                                        <a href="index.php?page=medico/liberar_paciente&id=<?php echo $paciente['id_usuario']; ?>" 
                                                           class="btn btn-success btn-sm" title="Liberar Paciente">
                                                            <i class="bi bi-unlock"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="index.php?page=medico/bloquear_paciente&id=<?php echo $paciente['id_usuario']; ?>" 
                                                           class="btn btn-block-red btn-sm" title="Bloquear Paciente">
                                                            <i class="bi bi-lock-fill"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="index.php?page=medico/perguntas&id_paciente=<?php echo $paciente['id_usuario']; ?>" 
                                                       class="btn btn-primary btn-sm" title="Perguntas">
                                                        <i class="bi bi-question-circle"></i>
                                                    </a>
                                                    
                                                    <a href="index.php?page=medico/info_reabilitacao&id=<?php echo $paciente['id_usuario']; ?>" 
                                                       class="btn btn-warning btn-sm" title="Reabilitação">
                                                        <i class="bi bi-activity"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Navegação de páginas" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($currentPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="index.php?page=medico/pacientes&page_num=<?php echo ($currentPage - 1); ?>">
                                                Anterior
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="index.php?page=medico/pacientes&page_num=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($currentPage < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="index.php?page=medico/pacientes&page_num=<?php echo ($currentPage + 1); ?>">
                                                Próximo
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
