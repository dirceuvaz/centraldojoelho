<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();

// Buscar problemas únicos da tabela pacientes
$stmt = $conn->prepare("
    SELECT DISTINCT problema 
    FROM pacientes 
    WHERE problema IS NOT NULL 
    AND problema != '' 
    ORDER BY problema
");
$stmt->execute();
$problemas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar fisioterapeutas únicos da tabela pacientes
$stmt = $conn->prepare("
    SELECT DISTINCT fisioterapeuta 
    FROM pacientes 
    WHERE fisioterapeuta IS NOT NULL 
    AND fisioterapeuta != '' 
    ORDER BY fisioterapeuta
");
$stmt->execute();
$fisioterapeutas = $stmt->fetchAll(PDO::FETCH_COLUMN);

$lista_especialidades = [
    'Ortopedia',
    'Traumatologia',
    'Medicina Esportiva',
    'Cirurgia do Joelho'
];

$lista_status = [
    'ativo' => 'Ativo',
    'inativo' => 'Inativo',
    'pendente' => 'Pendente'
];

// Buscar todos os médicos cadastrados
$stmt = $conn->prepare("
    SELECT u.id, u.nome, m.especialidade, m.crm 
    FROM usuarios u 
    INNER JOIN medicos m ON u.id = m.id_usuario 
    WHERE u.tipo_usuario = 'medico' 
    AND u.status = 'ativo' 
    ORDER BY u.nome
");
$stmt->execute();
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$usuario = [
    'id' => '',
    'nome' => '',
    'email' => '',
    'cpf' => '',
    'tipo_usuario' => '',
    'status' => 'pendente',
    'id_medico' => '',
    'data_cirurgia' => '',
    'fisioterapeuta' => '',
    'problema' => '',
    'status_paciente' => '',
    'crm' => '',
    'especialidade' => ''
];

// Se for edição, busca os dados do usuário
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("
        SELECT u.*, p.medico as id_medico, p.data_cirurgia, p.fisioterapeuta, p.problema, p.status as status_paciente,
               m.crm, m.especialidade
        FROM usuarios u 
        LEFT JOIN pacientes p ON u.id = p.id_usuario 
        LEFT JOIN medicos m ON u.id = m.id_usuario
        WHERE u.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header('Location: index.php?page=admin/usuarios');
        exit;
    }
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Dados básicos do usuário
        $dados_usuario = [
            'nome' => $_POST['nome'],
            'email' => $_POST['email'],
            'cpf' => $_POST['cpf'],
            'tipo_usuario' => $_POST['tipo_usuario'],
            'status' => $_POST['status']
        ];

        // Se for edição
        if (isset($_POST['id'])) {
            $id = $_POST['id'];
            
            // Só inclui a senha se uma nova foi fornecida
            if (!empty($_POST['senha'])) {
                $dados_usuario['senha'] = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            }
            
            // Atualiza usuário existente
            $sql_update = '';
            $params = [];
            foreach ($dados_usuario as $campo => $valor) {
                if (!empty($sql_update)) {
                    $sql_update .= ', ';
                }
                $sql_update .= "$campo = ?";
                $params[] = $valor;
            }
            $params[] = $id;
            
            $stmt = $conn->prepare("UPDATE usuarios SET $sql_update WHERE id = ?");
            $stmt->execute($params);
        } else {
            // Inserção
            $sql_usuario = "INSERT INTO usuarios (nome, email, cpf, senha, tipo_usuario, status) 
                           VALUES (:nome, :email, :cpf, :senha, :tipo_usuario, :status)";
            $dados_usuario['senha'] = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $conn->prepare($sql_usuario);
            $stmt->execute($dados_usuario);
        }

        $id_usuario = isset($_POST['id']) ? $_POST['id'] : $conn->lastInsertId();

        // Se for paciente
        if ($_POST['tipo_usuario'] === 'paciente') {
            $dados_paciente = [
                'id_usuario' => $id_usuario,
                'medico' => $_POST['id_medico'],
                'data_cirurgia' => $_POST['data_cirurgia'],
                'fisioterapeuta' => $_POST['fisioterapeuta'],
                'problema' => $_POST['problema'],
                'status' => $_POST['status_paciente']
            ];

            // Verifica se já existe
            $stmt = $conn->prepare("SELECT id FROM pacientes WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            
            if ($stmt->fetch()) {
                // Update
                $sql_paciente = "UPDATE pacientes SET medico = :medico, data_cirurgia = :data_cirurgia, 
                                fisioterapeuta = :fisioterapeuta, problema = :problema, status = :status 
                                WHERE id_usuario = :id_usuario";
            } else {
                // Insert
                $sql_paciente = "INSERT INTO pacientes (id_usuario, medico, data_cirurgia, fisioterapeuta, 
                                problema, status) VALUES (:id_usuario, :medico, :data_cirurgia, :fisioterapeuta, 
                                :problema, :status)";
            }

            $stmt = $conn->prepare($sql_paciente);
            $stmt->execute($dados_paciente);
        }

        // Se for médico
        if ($_POST['tipo_usuario'] === 'medico') {
            $dados_medico = [
                'id_usuario' => $id_usuario,
                'crm' => $_POST['crm'],
                'especialidade' => $_POST['especialidade'],
                'status' => $_POST['status']
            ];

            // Verifica se já existe
            $stmt = $conn->prepare("SELECT id FROM medicos WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            
            if ($stmt->fetch()) {
                // Update
                $sql_medico = "UPDATE medicos SET crm = :crm, especialidade = :especialidade, 
                              status = :status WHERE id_usuario = :id_usuario";
            } else {
                // Insert
                $sql_medico = "INSERT INTO medicos (id_usuario, crm, especialidade, status) 
                              VALUES (:id_usuario, :crm, :especialidade, :status)";
            }

            $stmt = $conn->prepare($sql_medico);
            $stmt->execute($dados_medico);
        }

        $conn->commit();
        header('Location: index.php?page=admin/usuarios&msg=sucesso');
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $erro = "Erro ao salvar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['id']) ? 'Editar' : 'Novo'; ?> Usuário - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #231F5D;
        }
        body {
            background-color: #f8f9fa;
        }
        .main-content {
            padding: 20px;
        }
        .top-navbar {
            background-color: var(--primary-color);
            padding: 15px 20px;
            color: white;
            border-bottom: none;
        }
        .top-navbar h4 {
            color: white;
            margin: 0;
        }
        .top-navbar .btn-outline-secondary {
            color: white;
            border-color: white;
        }
        .top-navbar .btn-outline-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .top-navbar .btn-outline-danger {
            color: white;
            border-color: white;
        }
        .top-navbar .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #1a1747;
            border-color: #1a1747;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <a href="index.php?page=admin/usuarios" class="btn btn-outline-secondary me-3">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <h4 class="mb-0"><?php echo isset($_GET['id']) ? 'Editar' : 'Novo'; ?> Usuário</h4>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person"></i> 
                            Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
                        </span>
                        <a class="btn btn-outline-danger btn-sm" href="index.php?page=login_process&logout=1">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="main-content">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($erro)): ?>
                                <div class="alert alert-danger">
                                    <?php echo $erro; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <?php if (isset($_GET['id'])): ?>
                                    <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="cpf" class="form-label">CPF</label>
                                            <input type="text" class="form-control" id="cpf" name="cpf" 
                                                   value="<?php echo htmlspecialchars($usuario['cpf']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="senha" class="form-label">
                                                <?php echo isset($_GET['id']) ? 'Nova Senha (deixe em branco para manter a atual)' : 'Senha'; ?>
                                            </label>
                                            <input type="password" class="form-control" id="senha" name="senha" 
                                                   <?php echo !isset($_GET['id']) ? 'required' : ''; ?>>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <?php foreach ($lista_status as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" 
                                                            <?php echo $usuario['status'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                                            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                                <option value="">Selecione...</option>
                                                <option value="admin" <?php echo $usuario['tipo_usuario'] === 'admin' ? 'selected' : ''; ?>>
                                                    Administrador
                                                </option>
                                                <option value="medico" <?php echo $usuario['tipo_usuario'] === 'medico' ? 'selected' : ''; ?>>
                                                    Médico
                                                </option>
                                                <option value="paciente" <?php echo $usuario['tipo_usuario'] === 'paciente' ? 'selected' : ''; ?>>
                                                    Paciente
                                                </option>
                                                <option value="fisioterapeuta" <?php echo $usuario['tipo_usuario'] === 'fisioterapeuta' ? 'selected' : ''; ?>>
                                                    Fisioterapeuta
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Campos específicos para pacientes -->
                                        <div class="campos-paciente" style="display: none;">
                                            <div class="mb-3">
                                                <label for="id_medico" class="form-label">Médico Responsável</label>
                                                <select class="form-select" id="id_medico" name="id_medico" required>
                                                    <option value="">Selecione o médico...</option>
                                                    <?php foreach ($medicos as $med): ?>
                                                        <option value="<?php echo $med['id']; ?>" 
                                                                <?php echo (isset($usuario['id_medico']) && $usuario['id_medico'] == $med['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($med['nome'] . ' - CRM: ' . $med['crm']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="data_cirurgia" class="form-label">Data da Cirurgia</label>
                                                <input type="date" class="form-control" id="data_cirurgia" name="data_cirurgia" 
                                                       value="<?php echo htmlspecialchars($usuario['data_cirurgia'] ?? ''); ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="fisioterapeuta" class="form-label">Fisioterapeuta</label>
                                                <select class="form-select" id="fisioterapeuta" name="fisioterapeuta">
                                                    <option value="">Selecione o fisioterapeuta...</option>
                                                    <?php if (!empty($fisioterapeutas)): ?>
                                                        <?php foreach ($fisioterapeutas as $fisio): ?>
                                                            <option value="<?php echo $fisio; ?>" 
                                                                    <?php echo (isset($usuario['fisioterapeuta']) && $usuario['fisioterapeuta'] == $fisio) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($fisio); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="problema" class="form-label">Problema</label>
                                                <select class="form-select" id="problema" name="problema" required>
                                                    <option value="">Selecione o problema...</option>
                                                    <?php if (!empty($problemas)): ?>
                                                        <?php foreach ($problemas as $prob): ?>
                                                            <option value="<?php echo $prob; ?>" 
                                                                    <?php echo (isset($usuario['problema']) && $usuario['problema'] == $prob) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($prob); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="status_paciente" class="form-label">Status do Paciente</label>
                                                <select class="form-select" id="status_paciente" name="status_paciente" >
                                                    <?php foreach ($lista_status as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" 
                                                                <?php echo (isset($usuario['status_paciente']) && $usuario['status_paciente'] == $value) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Campos específicos para médicos -->
                                        <div class="campos-medico" style="display: none;">
                                            <div class="mb-3">
                                                <label for="crm" class="form-label">CRM</label>
                                                <input type="text" class="form-control" id="crm" name="crm" 
                                                       value="<?php echo htmlspecialchars($usuario['crm'] ?? ''); ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="especialidade" class="form-label">Especialidade</label>
                                                <select class="form-select" id="especialidade" name="especialidade">
                                                    <option value="">Selecione a especialidade...</option>
                                                    <?php foreach ($lista_especialidades as $esp): ?>
                                                        <option value="<?php echo $esp; ?>" 
                                                                <?php echo (isset($usuario['especialidade']) && $usuario['especialidade'] == $esp) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($esp); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const tipoUsuarioSelect = document.getElementById('tipo_usuario');
                                                const camposPaciente = document.querySelector('.campos-paciente');
                                                const camposMedico = document.querySelector('.campos-medico');

                                                function toggleCampos() {
                                                    const tipoSelecionado = tipoUsuarioSelect.value;
                                                    camposPaciente.style.display = tipoSelecionado === 'paciente' ? 'block' : 'none';
                                                    camposMedico.style.display = tipoSelecionado === 'medico' ? 'block' : 'none';
                                                }

                                                tipoUsuarioSelect.addEventListener('change', toggleCampos);
                                                toggleCampos(); // Executa ao carregar a página
                                            });
                                        </script>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Salvar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#cpf').mask('000.000.000-00', {reverse: true});
        });
    </script>
</body>
</html>
