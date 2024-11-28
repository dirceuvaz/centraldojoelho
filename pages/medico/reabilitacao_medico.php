<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();

// Configuração da paginação
$itemsPerPage = 9; // 9 cards por página (3x3 grid)
$currentPage = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Consulta para contar o total de registros
$countSql = "SELECT COUNT(*) as total FROM reabilitacao WHERE status = 'ativo'";
$countStmt = $conn->prepare($countSql);
$countStmt->execute();
$totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $itemsPerPage);

// Buscar todas as reabilitações com paginação
$sql = "
    SELECT r.*, m.nome as nome_medico,
           GROUP_CONCAT(DISTINCT tr.descricao ORDER BY tr.descricao) as lista_tipos
    FROM reabilitacao r
    LEFT JOIN medicos med ON r.id_medico = med.id_usuario
    LEFT JOIN usuarios m ON med.id_usuario = m.id
    LEFT JOIN tipos_reabilitacao tr ON r.tipo_problema = tr.id
    WHERE r.status = 'ativo'
    GROUP BY r.id, r.titulo, r.data_criacao, r.status, r.id_medico, m.nome
    ORDER BY r.data_criacao DESC
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(1, $offset, PDO::PARAM_INT);
$stmt->bindValue(2, $itemsPerPage, PDO::PARAM_INT);
$stmt->execute();
$reabilitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Reabilitações - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .badge-tipo {
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            background-color: #6f42c1;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../common/header_medico.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gerenciamento de Reabilitações</h2>           
            <a href="index.php?page=medico/reabilitacao_form" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nova Reabilitação
            </a>
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

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($reabilitacoes as $reabilitacao): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($reabilitacao['titulo']); ?></h5>
                            <p class="card-text">
                                <strong>Criado por:</strong> <?php echo htmlspecialchars($reabilitacao['nome_medico'] ?? 'Sistema'); ?>
                            </p>
                            <p class="card-text">
                                <strong>Data de Criação:</strong> <?php echo date('d/m/Y', strtotime($reabilitacao['data_criacao'])); ?>
                            </p>
                            
                            <?php if (!empty($reabilitacao['lista_tipos'])): ?>
                                <div class="mb-2">
                                    <strong>Tipos:</strong><br>
                                    <?php foreach (explode(',', $reabilitacao['lista_tipos']) as $tipo): ?>
                                        <span class="badge badge-tipo"><?php echo htmlspecialchars($tipo); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <a href="index.php?page=medico/reabilitacao_detalhes&id=<?php echo $reabilitacao['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye-fill"></i> Detalhes
                                </a>
                                <a href="index.php?page=medico/reabilitacao_form&id=<?php echo $reabilitacao['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-fill"></i> Editar
                                </a>
                                <button onclick="confirmarExclusao(<?php echo $reabilitacao['id']; ?>)" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash-fill"></i> Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Navegação de páginas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?page=medico/reabilitacao_medico&page_num=<?php echo ($currentPage - 1); ?>">
                                Anterior
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?page=medico/reabilitacao_medico&page_num=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?page=medico/reabilitacao_medico&page_num=<?php echo ($currentPage + 1); ?>">
                                Próximo
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir esta reabilitação?')) {
                window.location.href = `index.php?page=medico/excluir_reabilitacao&id=${id}`;
            }
        }
    </script>
</body>
</html>
