<?php
require_once __DIR__ . '/../../config/database.php';
$pdo = getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        switch ($acao) {
            case 'editar':
                $id = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
                $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
                $tipo = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_STRING);
                $senha = $_POST['senha'] ?? '';
                $especialidade = filter_input(INPUT_POST, 'especialidade', FILTER_SANITIZE_STRING);
                $crm = filter_input(INPUT_POST, 'crm', FILTER_SANITIZE_STRING);
                $id_medico = filter_input(INPUT_POST, 'id_medico', FILTER_VALIDATE_INT);

                if (!$id || !$nome || !$email || !$tipo || !$cpf) {
                    throw new Exception('Dados inválidos');
                }

                // Validações específicas para médico
                if ($tipo === 'medico' && (!$especialidade || !$crm)) {
                    throw new Exception('Para médicos, CRM e Especialidade são obrigatórios');
                }

                // Verificar se email já existe (exceto para o próprio usuário)
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    throw new Exception('Este email já está cadastrado para outro usuário');
                }

                // Verificar se CPF já existe (exceto para o próprio usuário)
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cpf = ? AND id != ?");
                $stmt->execute([$cpf, $id]);
                if ($stmt->fetch()) {
                    throw new Exception('Este CPF já está cadastrado para outro usuário');
                }

                // Iniciar transação
                $pdo->beginTransaction();

                try {
                    // Atualizar usuário
                    if ($senha) {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET nome = ?, email = ?, cpf = ?, tipo_usuario = ?, senha = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $nome,
                            $email,
                            $cpf,
                            $tipo,
                            password_hash($senha, PASSWORD_DEFAULT),
                            $id
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET nome = ?, email = ?, cpf = ?, tipo_usuario = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $nome,
                            $email,
                            $cpf,
                            $tipo,
                            $id
                        ]);
                    }

                    // Verificar se já existe registro na tabela médicos
                    $stmt = $pdo->prepare("SELECT id FROM medicos WHERE id_usuario = ?");
                    $stmt->execute([$id]);
                    $medico_exists = $stmt->fetch();

                    if ($tipo === 'medico') {
                        if ($medico_exists) {
                            // Atualizar registro existente
                            $stmt = $pdo->prepare("
                                UPDATE medicos 
                                SET crm = ?, especialidade = ?
                                WHERE id_usuario = ?
                            ");
                            $stmt->execute([
                                $crm,
                                $especialidade,
                                $id
                            ]);
                        } else {
                            // Criar novo registro
                            $stmt = $pdo->prepare("
                                INSERT INTO medicos (id_usuario, crm, especialidade, status)
                                VALUES (?, ?, ?, 'ativo')
                            ");
                            $stmt->execute([
                                $id,
                                $crm,
                                $especialidade
                            ]);
                        }
                    } else if ($medico_exists) {
                        // Se não é mais médico, remover registro da tabela medicos
                        $stmt = $pdo->prepare("DELETE FROM medicos WHERE id_usuario = ?");
                        $stmt->execute([$id]);
                    }

                    // Se for paciente, atualizar o médico responsável
                    if ($tipo === 'paciente' && $id_medico) {
                        // Verificar se já existe registro na tabela pacientes
                        $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ?");
                        $stmt->execute([$id]);
                        $paciente = $stmt->fetch();

                        if ($paciente) {
                            // Atualizar o médico do paciente
                            $stmt = $pdo->prepare("
                                UPDATE pacientes 
                                SET medico = (SELECT id FROM medicos WHERE id = ?)
                                WHERE id_usuario = ?
                            ");
                            $stmt->execute([$id_medico, $id]);
                        } else {
                            // Se não existe registro do paciente, criar um novo
                            $stmt = $pdo->prepare("
                                INSERT INTO pacientes (id_usuario, medico, data_cirurgia, fisioterapeuta, problema, status)
                                VALUES (?, (SELECT id FROM medicos WHERE id = ?), CURRENT_DATE, '', '', 'pendente')
                            ");
                            $stmt->execute([$id, $id_medico]);
                        }
                    }

                    // Commit da transação
                    $pdo->commit();
                    $_SESSION['success'] = 'Usuário atualizado com sucesso!';
                    header('Location: index.php?page=admin/usuarios');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
                break;

            case 'novo':
                // Validar dados
                $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $tipo = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_STRING);
                $senha = $_POST['senha'] ?? '';
                $especialidade = filter_input(INPUT_POST, 'especialidade', FILTER_SANITIZE_STRING);
                $crm = filter_input(INPUT_POST, 'crm', FILTER_SANITIZE_STRING);
                $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);

                if (!$nome || !$email || !$tipo || !$senha || !$cpf) {
                    throw new Exception('Todos os campos são obrigatórios');
                }

                // Validações específicas para médico
                if ($tipo === 'medico' && (!$especialidade || !$crm)) {
                    throw new Exception('Para médicos, CRM e Especialidade são obrigatórios');
                }

                // Verificar se email já existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception('Este email já está cadastrado');
                }

                // Verificar se CPF já existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cpf = ?");
                $stmt->execute([$cpf]);
                if ($stmt->fetch()) {
                    throw new Exception('Este CPF já está cadastrado');
                }

                // Iniciar transação
                $pdo->beginTransaction();

                try {
                    // Inserir novo usuário
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios (nome, email, cpf, tipo_usuario, senha, status)
                        VALUES (?, ?, ?, ?, ?, 'ativo')
                    ");
                    $stmt->execute([
                        $nome,
                        $email,
                        $cpf,
                        $tipo,
                        password_hash($senha, PASSWORD_DEFAULT)
                    ]);

                    $id_usuario = $pdo->lastInsertId();

                    // Se for médico, inserir na tabela médicos
                    if ($tipo === 'medico') {
                        $stmt = $pdo->prepare("
                            INSERT INTO medicos (id_usuario, crm, especialidade, status)
                            VALUES (?, ?, ?, 'ativo')
                        ");
                        $stmt->execute([
                            $id_usuario,
                            $crm,
                            $especialidade
                        ]);
                    }

                    // Commit da transação
                    $pdo->commit();
                    $_SESSION['success'] = 'Usuário criado com sucesso!';
                    header('Location: index.php?page=admin/usuarios');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
                break;

            case 'aprovar':
                $id = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
                if (!$id) {
                    throw new Exception('ID do usuário inválido');
                }
                
                $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['success'] = 'Usuário aprovado com sucesso!';
                header('Location: index.php?page=admin/usuarios');
                exit;
                break;

            case 'bloquear':
                $id = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
                if (!$id) {
                    throw new Exception('ID do usuário inválido');
                }
                
                $stmt = $pdo->prepare("UPDATE usuarios SET status = 'inativo' WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['success'] = 'Usuário bloqueado com sucesso!';
                header('Location: index.php?page=admin/usuarios');
                exit;
                break;

            case 'desbloquear':
                $id = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
                if (!$id) {
                    throw new Exception('ID do usuário inválido');
                }
                
                $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['success'] = 'Usuário desbloqueado com sucesso!';
                header('Location: index.php?page=admin/usuarios');
                exit;
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php?page=admin/usuarios');
        exit;
    }
}

// Parâmetros de paginação e busca
$busca = $_GET['busca'] ?? '';
$status = $_GET['status'] ?? 'todos';
$tipo = $_GET['tipo'] ?? 'todos';
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Buscar total de registros
$sql = "
    SELECT COUNT(DISTINCT u.id) as total
    FROM usuarios u 
    LEFT JOIN medicos m ON u.id = m.id_usuario
    WHERE 1=1
";

if ($busca) {
    $sql .= " AND (u.nome LIKE :busca OR u.email LIKE :busca OR u.cpf LIKE :busca)";
}

if ($status !== 'todos') {
    $sql .= " AND u.status = :status";
}

$stmt = $pdo->prepare($sql);

if ($busca) {
    $busca_param = "%{$busca}%";
    $stmt->bindParam(':busca', $busca_param);
}

if ($status !== 'todos') {
    $stmt->bindParam(':status', $status);
}

$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calcular páginas
$por_pagina = 10;
$total_paginas = ceil($total / $por_pagina);
$pagina = isset($_GET['pagina']) ? max(1, min($total_paginas, intval($_GET['pagina']))) : 1;
$offset = ($pagina - 1) * $por_pagina;

// Buscar usuários
$sql = "
    SELECT DISTINCT 
        u.*, 
        m.crm,
        m.especialidade,
        p.medico as id_medico
    FROM usuarios u 
    LEFT JOIN medicos m ON u.id = m.id_usuario
    LEFT JOIN pacientes p ON u.id = p.id_usuario
    WHERE 1=1
";

if ($busca) {
    $sql .= " AND (u.nome LIKE :busca OR u.email LIKE :busca OR u.cpf LIKE :busca)";
}

if ($status !== 'todos') {
    $sql .= " AND u.status = :status";
}

$sql .= " ORDER BY u.nome LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

if ($busca) {
    $busca_param = "%{$busca}%";
    $stmt->bindParam(':busca', $busca_param);
}

if ($status !== 'todos') {
    $stmt->bindParam(':status', $status);
}

$stmt->bindParam(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #231F5D;
        }
        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color);
            padding: 20px 0;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            padding-left: 25px;
        }
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .sidebar .text-muted {
            color: rgba(255, 255, 255, 0.5) !important;
        }
        .sidebar h5 {
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .top-navbar {
            border-bottom: 1px solid #dee2e6;
            background-color: white;
            padding: 10px 20px;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        .table-responsive {
            margin-bottom: 0;
        }
        .accordion {
            background: none;
            border: none;
        }
        .accordion-item {
            background: none;
            border: none;
        }
        .accordion-button {
            background-color: transparent !important;
            color: rgba(255, 255, 255, 0.85) !important;
            padding: 10px 20px;
            box-shadow: none !important;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }
        .accordion-button::after {
            filter: brightness(0) invert(1);
        }
        .accordion-collapse {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .accordion-body {
            padding: 0;
        }
        .accordion-body .nav-link {
            padding-left: 40px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center p-3">
                    <h5>Central do Joelho</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=admin/painel">
                            <i class="bi bi-speedometer2"></i> Painel
                        </a>
                    </li>

                    <!-- Menu Atendimento -->
                    <div class="accordion accordion-flush" id="menuAtendimento">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#atendimentoCollapse" aria-expanded="true">
                                    <i class="bi bi-clipboard-pulse me-2"></i> Atendimento
                                </button>
                            </h2>
                            <div id="atendimentoCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/exercicios">
                                                <i class="bi bi-activity"></i> Exercícios
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/reabilitacao">
                                                <i class="bi bi-check-square"></i> Reabilitação
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/tratamentos">
                                                <i class="bi bi-journal-medical"></i> Tratamentos
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/perguntas">
                                                <i class="bi bi-question-circle"></i> Perguntas
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/pacientes">
                                                <i class="bi bi-people"></i> Pacientes
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/agenda">
                                                <i class="bi bi-calendar-check"></i> Agenda
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/consultas">
                                                <i class="bi bi-calendar-plus"></i> Consultas
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/cirurgias">
                                                <i class="bi bi-bandaid"></i> Cirurgias
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu Mídias -->
                    <div class="accordion accordion-flush" id="menuMidias">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#midiasCollapse" aria-expanded="true">
                                    <i class="bi bi-collection-play me-2"></i> Mídias
                                </button>
                            </h2>
                            <div id="midiasCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/videos">
                                                <i class="bi bi-play-circle"></i> Vídeos
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/arquivos">
                                                <i class="bi bi-file-earmark"></i> Arquivos
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu Configurações -->
                    <div class="accordion accordion-flush" id="menuConfiguracoes">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#configuracoesCollapse" aria-expanded="true">
                                    <i class="bi bi-gear me-2"></i> Configurações
                                </button>
                            </h2>
                            <div id="configuracoesCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/notificacoes">
                                                <i class="bi bi-bell"></i> Notificações
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/usuarios">
                                                <i class="bi bi-people"></i> Usuários
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/relatorios">
                                                <i class="bi bi-file-earmark-spreadsheet"></i> Relatórios
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php?page=admin/config-gerais">
                                                <i class="bi bi-gear"></i> Configurações Gerais
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <!-- Top Navbar -->
                <div class="top-navbar d-flex justify-content-end">
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person"></i> 
                            Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>
                        </span>
                        <a class="btn btn-link" href="index.php?page=login_process&logout=1">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Gerenciar Usuários</h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php?page=admin/painel">Painel</a></li>
                                    <li class="breadcrumb-item active">Usuários</li>
                                </ol>
                            </nav>
                        </div>                      
                    </div>

                    <!-- Lista de Usuários -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Lista de Usuários</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <form class="row g-3" method="GET">
                                        <input type="hidden" name="page" value="admin/usuarios">
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="busca" placeholder="Buscar por nome ou email" value="<?php echo htmlspecialchars($busca); ?>">
                                                <button class="btn btn-outline-secondary" type="submit">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="limite" onchange="this.form.submit()">
                                                <option value="5" <?php echo $limite == 5 ? 'selected' : ''; ?>>5 por página</option>
                                                <option value="10" <?php echo $limite == 10 ? 'selected' : ''; ?>>10 por página</option>
                                                <option value="15" <?php echo $limite == 15 ? 'selected' : ''; ?>>15 por página</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="status" onchange="this.form.submit()">
                                                <option value="todos" <?php echo $status === 'todos' ? 'selected' : ''; ?>>Todos os status</option>
                                                <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                                                <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativos</option>
                                                <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select class="form-select" name="tipo" onchange="this.form.submit()">
                                                <option value="todos" <?php echo $tipo === 'todos' ? 'selected' : ''; ?>>Todos os tipos</option>
                                                <option value="admin" <?php echo $tipo === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                                <option value="medico" <?php echo $tipo === 'medico' ? 'selected' : ''; ?>>Médico</option>
                                                <option value="paciente" <?php echo $tipo === 'paciente' ? 'selected' : ''; ?>>Paciente</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoUsuarioModal">
                                        <i class="bi bi-person-plus"></i> Novo Usuário
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>E-mail</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td>
                                                <?php 
                                                    $tipoIcon = '';
                                                    $tipoNome = '';
                                                    
                                                    switch($usuario['tipo_usuario']) {
                                                        case 'admin':
                                                            $tipoIcon = 'bi-shield-lock';
                                                            $tipoNome = 'Administrador';
                                                            break;
                                                        case 'medico':
                                                            $tipoIcon = 'bi-person';
                                                            $tipoNome = 'Médico';
                                                            break;
                                                        case 'paciente':
                                                            $tipoIcon = 'bi-person';
                                                            $tipoNome = 'Paciente';
                                                            break;
                                                        default:
                                                            $tipoIcon = 'bi-person';
                                                            $tipoNome = 'Desconhecido';
                                                    }
                                                ?>
                                                <i class="bi <?php echo $tipoIcon; ?>"></i>
                                                <?php echo $tipoNome; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $usuario['status']; ?>">
                                                    <?php echo ucfirst($usuario['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-primary btn-icon" title="Editar"
                                                            data-bs-toggle="modal" data-bs-target="#editarUsuarioModal"
                                                            onclick="editarUsuario(
                                                                <?php echo $usuario['id']; ?>,
                                                                '<?php echo htmlspecialchars($usuario['nome']); ?>',
                                                                '<?php echo htmlspecialchars($usuario['email']); ?>',
                                                                '<?php echo htmlspecialchars($usuario['tipo_usuario']); ?>',
                                                                '<?php echo isset($usuario['crm']) ? htmlspecialchars($usuario['crm']) : ''; ?>',
                                                                '<?php echo isset($usuario['especialidade']) ? htmlspecialchars($usuario['especialidade']) : ''; ?>',
                                                                '<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>',
                                                                '<?php echo isset($usuario['id_medico']) ? $usuario['id_medico'] : ''; ?>'
                                                            )">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($usuario['status'] === 'pendente'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="acao" value="aprovar">
                                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                        <button type="submit" class="btn btn-warning btn-icon" title="Aprovar"
                                                                onclick="return confirm('Tem certeza que deseja aprovar este usuário?')">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <?php if ($usuario['status'] === 'ativo'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="acao" value="bloquear">
                                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-icon" title="Bloquear"
                                                                onclick="return confirm('Tem certeza que deseja bloquear este usuário?')">
                                                            <i class="bi bi-lock"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <?php if ($usuario['status'] === 'inativo'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="acao" value="desbloquear">
                                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-icon" title="Desbloquear"
                                                                onclick="return confirm('Tem certeza que deseja desbloquear este usuário?')">
                                                            <i class="bi bi-unlock"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagina > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=admin/usuarios&pagina=<?php echo $pagina - 1; ?>&limite=<?php echo $limite; ?>&status=<?php echo $status; ?>&tipo=<?php echo $tipo; ?>&busca=<?php echo $busca; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=admin/usuarios&pagina=<?php echo $i; ?>&limite=<?php echo $limite; ?>&status=<?php echo $status; ?>&tipo=<?php echo $tipo; ?>&busca=<?php echo $busca; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    <?php if ($pagina < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=admin/usuarios&pagina=<?php echo $pagina + 1; ?>&limite=<?php echo $limite; ?>&status=<?php echo $status; ?>&tipo=<?php echo $tipo; ?>&busca=<?php echo $busca; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Novo Usuário -->
    <div class="modal fade" id="novoUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="novo">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CPF</label>
                            <input type="text" class="form-control" name="cpf" required maxlength="14">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuário</label>
                            <select class="form-select" name="tipo_usuario" required onchange="toggleUserTypeFields(this)">
                                <option value="">Selecione...</option>
                                <option value="admin">Administrador</option>
                                <option value="medico">Médico</option>
                                <option value="paciente">Paciente</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" class="form-control" name="senha" required>
                        </div>
                        <div class="medico-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">CRM</label>
                                <input type="text" class="form-control" name="crm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Especialidade</label>
                                <input type="text" class="form-control" name="especialidade">
                            </div>
                        </div>
                        <div class="paciente-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Médico Responsável</label>
                                <select class="form-select" name="id_medico">
                                    <option value="">Selecione o médico...</option>
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT m.id, u.nome, m.crm 
                                        FROM medicos m 
                                        JOIN usuarios u ON m.id_usuario = u.id 
                                        WHERE m.status = 'ativo'
                                        ORDER BY u.nome
                                    ");
                                    $stmt->execute();
                                    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($medicos as $medico) {
                                        echo '<option value="' . $medico['id'] . '">' . 
                                             htmlspecialchars($medico['nome']) . ' (CRM: ' . htmlspecialchars($medico['crm']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id_usuario" id="edit_id_usuario">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" id="edit_nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CPF</label>
                            <input type="text" class="form-control" name="cpf" id="edit_cpf" required maxlength="14">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuário</label>
                            <select class="form-select" name="tipo_usuario" id="edit_tipo_usuario" required onchange="toggleUserTypeFields(this)">
                                <option value="">Selecione...</option>
                                <option value="admin">Administrador</option>
                                <option value="medico">Médico</option>
                                <option value="paciente">Paciente</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" class="form-control" name="senha">
                        </div>
                        <div class="medico-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">CRM</label>
                                <input type="text" class="form-control" name="crm" id="edit_crm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Especialidade</label>
                                <input type="text" class="form-control" name="especialidade" id="edit_especialidade">
                            </div>
                        </div>
                        <div class="paciente-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Médico Responsável</label>
                                <select class="form-select" name="id_medico" id="edit_medico">
                                    <option value="">Selecione o médico...</option>
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT m.id, u.nome, m.crm 
                                        FROM medicos m 
                                        JOIN usuarios u ON m.id_usuario = u.id 
                                        WHERE m.status = 'ativo'
                                        ORDER BY u.nome
                                    ");
                                    $stmt->execute();
                                    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($medicos as $medico) {
                                        echo '<option value="' . $medico['id'] . '">' . 
                                             htmlspecialchars($medico['nome']) . ' (CRM: ' . htmlspecialchars($medico['crm']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para mostrar/esconder campos específicos de médico
        function toggleUserTypeFields(select) {
            const medicoFields = select.closest('.modal-content').querySelector('.medico-fields');
            const pacienteFields = select.closest('.modal-content').querySelector('.paciente-fields');
            if (medicoFields) {
                if (select.value === 'medico') {
                    medicoFields.style.display = 'block';
                    medicoFields.querySelectorAll('input').forEach(input => input.required = true);
                    pacienteFields.style.display = 'none';
                    pacienteFields.querySelectorAll('input').forEach(input => input.required = false);
                } else if (select.value === 'paciente') {
                    pacienteFields.style.display = 'block';
                    pacienteFields.querySelectorAll('input').forEach(input => input.required = true);
                    medicoFields.style.display = 'none';
                    medicoFields.querySelectorAll('input').forEach(input => input.required = false);
                } else {
                    medicoFields.style.display = 'none';
                    medicoFields.querySelectorAll('input').forEach(input => input.required = false);
                    pacienteFields.style.display = 'none';
                    pacienteFields.querySelectorAll('input').forEach(input => input.required = false);
                }
            }
        }

        // Carregar dados do usuário para edição
        function editarUsuario(id, nome, email, tipo, crm, especialidade, cpf, id_medico) {
            document.getElementById('edit_id_usuario').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_cpf').value = cpf || '';
            document.getElementById('edit_tipo_usuario').value = tipo;
            
            const select = document.getElementById('edit_tipo_usuario');
            toggleUserTypeFields(select);
            
            if (tipo === 'medico') {
                document.getElementById('edit_crm').value = crm || '';
                document.getElementById('edit_especialidade').value = especialidade || '';
            } else if (tipo === 'paciente' && id_medico) {
                document.getElementById('edit_medico').value = id_medico;
            }
        }
    </script>
</body>
</html>