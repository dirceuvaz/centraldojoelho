<?php
$tipo_usuario = isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : '';
$pagina_retorno = 'login';

if ($tipo_usuario === 'medico') {
    $pagina_retorno = 'medico/painel';
} elseif ($tipo_usuario === 'paciente') {
    $pagina_retorno = 'paciente/painel';
} elseif ($tipo_usuario === 'admin') {
    $pagina_retorno = 'admin/painel';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Não Encontrada - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .error-icon {
            font-size: 5rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .error-card {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-card">
            <i class="bi bi-exclamation-triangle error-icon"></i>
            <h1 class="display-4 mb-4">Página Não Encontrada</h1>
            <p class="lead mb-4">Desculpe, a página que você está procurando não existe ou foi movida.</p>
            <a href="index.php?page=<?php echo htmlspecialchars($pagina_retorno); ?>" class="btn btn-primary btn-lg">
                <i class="bi bi-house-door"></i> Voltar ao Painel
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
