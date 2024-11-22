<?php
require_once 'config/database.php';
$pdo = getConnection();

// Buscar médicos ativos
$stmt = $pdo->query("SELECT nome FROM usuarios WHERE tipo_usuario = 'medico' AND status = 'ativo' ORDER BY nome");
$medicos = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar fisioterapeutas ativos
$stmt = $pdo->query("SELECT nome FROM usuarios WHERE tipo_usuario = 'fisioterapeuta' AND status = 'ativo' ORDER BY nome");
$fisioterapeutas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Lista de problemas
$problemas = [
    'Ligamento Cruzado Anterior (LCA)',
    'Menisco',
    'Luxação de Patela',
    'Prótese de Joelho'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
        }
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .cadastro-container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
        }
        .logo-container h2 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .required::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .cadastro-container {
                margin: 10px;
                padding: 20px;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cadastro-container">
            <div class="logo-container">
                <h2>Central do Joelho</h2>
                <p class="text-muted">Cadastro de Novo Paciente</p>
            </div>

            <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <?php echo htmlspecialchars(urldecode($_GET['erro'])); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form id="formCadastro" method="POST" action="index.php?page=cadastro_process">
                <input type="hidden" name="tipo_usuario" value="paciente">
                
                <div class="row g-3">
                    <!-- Dados Pessoais -->
                    <div class="col-12">
                        <h5 class="mb-3"><i class="bi bi-person"></i> Dados Pessoais</h5>
                    </div>

                    <div class="col-12 col-md-12">
                        <label class="form-label required">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">CPF</label>
                        <input type="text" class="form-control" name="cpf" required maxlength="14">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">E-mail</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <!-- Dados Médicos -->
                    <div class="col-12 mt-4">
                        <h5 class="mb-3"><i class="bi bi-heart-pulse"></i> Dados do Tratamento</h5>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Data da Cirurgia</label>
                        <input type="date" class="form-control" name="data_cirurgia" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Médico</label>
                        <select class="form-select" name="medico" required>
                            <option value="">Selecione o médico</option>
                            <?php foreach ($medicos as $medico): ?>
                            <option value="<?php echo htmlspecialchars($medico); ?>">
                                <?php echo htmlspecialchars($medico); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Fisioterapeuta</label>
                        <select class="form-select" name="fisioterapeuta" required>
                            <option value="">Selecione o fisioterapeuta</option>
                            <?php foreach ($fisioterapeutas as $fisio): ?>
                            <option value="<?php echo htmlspecialchars($fisio); ?>">
                                <?php echo htmlspecialchars($fisio); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="Dra. Juliana">Dra. Juliana</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Problema</label>
                        <select class="form-select" name="problema" required>
                            <option value="">Selecione o problema</option>
                            <?php foreach ($problemas as $problema): ?>
                            <option value="<?php echo htmlspecialchars($problema); ?>">
                                <?php echo htmlspecialchars($problema); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Senha -->
                    <div class="col-12 mt-4">
                        <h5 class="mb-3"><i class="bi bi-lock"></i> Dados de Acesso</h5>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Senha</label>
                        <input type="password" class="form-control" name="senha" required minlength="8">
                        <div class="form-text">Mínimo de 8 caracteres</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Confirmar Senha</label>
                        <input type="password" class="form-control" name="confirma_senha" required minlength="8">
                    </div>

                    <div class="col-12 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="aceito_termos" id="termos" required>
                            <label class="form-check-label" for="termos">
                                Li e aceito os <a href="#" data-bs-toggle="modal" data-bs-target="#termosModal">Termos de Uso</a>
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-success w-100 mb-3">
                            <i class="bi bi-check-circle"></i> Confirmar Cadastro
                        </button>
                        <a href="index.php" class="btn btn-link w-100">
                            <i class="bi bi-arrow-left"></i> Voltar para o Login
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Termos -->
    <div class="modal fade" id="termosModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Termos de Uso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Conteúdo dos termos de uso -->
                    <p>Termos de uso do sistema...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara para CPF
        document.querySelector('input[name="cpf"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
