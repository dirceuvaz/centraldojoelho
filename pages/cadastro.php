<?php
require_once 'config/database.php';
$pdo = getConnection();

// Buscar médicos e fisioterapeutas
$stmt = $pdo->query("SELECT nome FROM usuarios WHERE tipo_usuario = 'medico' AND status = 'ativo' ORDER BY nome");
$medicos = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT nome FROM usuarios WHERE tipo_usuario = 'fisioterapeuta' AND status = 'ativo' ORDER BY nome");
$fisioterapeutas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Lista de problemas - Array
$problemas = [
    'Ligamento Cruzado Anterior (LCA)',
     //'Menisco',
    // 'Luxação de Patela',
   // 'Prótese de Joelho'
];

// Listar os médicos padrão CASO O ARRAY ESTEJA VAZIO
// $medicos_default = [
//     'Dr. Cláudio Karan',
//     'Dr. João Bosco Sales Nogueira',
//     'Dr. Marcelo Cortez',
//     'Dr. Cláudio Gimenes',
//     'Dr. Leonardo Heráclio',
//     'Dr. Pedro Ricardo de Mesquita Coutinho',
//     'Dr. Carlos Alberto Viana Filho',
//     'Dr. Kristopherson Lustosa'
// ];
// //Caso a variavel estiver vazia, usar um médico padrão
// if (empty($medicos)) {
//     $medicos = $medicos_default;
// }
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
        body {
            background-color: #f8f9fa;
        }
        .cadastro-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container img {
            max-width: 200px;
        }
        .form-label {
            font-weight: 500;
        }
        .required::after {
            content: "*";
            color: red;
            margin-left: 4px;
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
            <div class="alert alert-danger mb-4">
                <?php echo htmlspecialchars(urldecode($_GET['erro'])); ?>
            </div>
            <?php endif; ?>

            <form id="formCadastro" method="POST" action="pages/cadastro_process.php">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label required">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">CPF</label>
                        <input type="text" class="form-control" name="cpf" required maxlength="14">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">E-mail</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">Data da Cirurgia</label>
                        <input type="date" class="form-control" name="data_cirurgia" required>
                    </div>

                    <div class="col-md-6">
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

                    <div class="col-md-6">
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

                    <div class="col-md-6">
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

                    <div class="col-md-6">
                        <label class="form-label required">Senha</label>
                        <input type="password" class="form-control" name="senha" required minlength="8">
                        <div class="form-text">Mínimo de 8 caracteres</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">Confirmar Senha</label>
                        <input type="password" class="form-control" name="confirma_senha" required minlength="6">
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="aceito_termos" id="termos" required>
                            <label class="form-check-label" for="termos">
                                Li e aceito os <a href="#" data-bs-toggle="modal" data-bs-target="#termosModal">Termos de Uso</a>
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Confirmar Cadastro para Acesso
                        </button>
                        <a href="index.php" class="btn btn-link w-100 mt-2">Já tem Acesso? Faça login</a>
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
                    <h6>1. Aceitação dos Termos</h6>
                    <p>Ao se cadastrar na Central do Joelho, você concorda com todos os termos e condições aqui estabelecidos.</p>

                    <h6>2. Uso do Sistema</h6>
                    <p>O sistema é destinado exclusivamente para acompanhamento de tratamentos e exercícios prescritos pelos profissionais da Central do Joelho.</p>

                    <h6>3. Privacidade</h6>
                    <p>Seus dados pessoais serão tratados conforme nossa política de privacidade e utilizados apenas para fins de tratamento.</p>

                    <h6>4. Responsabilidades</h6>
                    <p>É sua responsabilidade manter seus dados atualizados e seguir corretamente as orientações dos profissionais.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-mask-plugin@1.14.16/dist/jquery.mask.min.js"></script>

    <!-- javascript para Validação dos dados inseridos pelo usuário -->
    <script>
        $(document).ready(function() {
            // Máscara para CPF
            $('input[name="cpf"]').mask('000.000.000-00');

            // Validação do formulário
            $('#formCadastro').on('submit', function(e) {
                const senha = $('input[name="senha"]').val();
                const confirma = $('input[name="confirma_senha"]').val();

                if (senha !== confirma) {
                    e.preventDefault();
                    alert('As senhas não conferem!');
                    return false;
                }

                if (!$('#termos').is(':checked')) {
                    e.preventDefault();
                    alert('Você precisa aceitar os Termos de Uso!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
