<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$pdo = getConnection();

$acao = $_POST['acao'] ?? '';

try {
    switch ($acao) {
        case 'novo':
            // Validar dados
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $tipo = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_STRING);
            $senha = $_POST['senha'] ?? '';
            $especialidade = filter_input(INPUT_POST, 'especialidade', FILTER_SANITIZE_STRING);
            $crm = filter_input(INPUT_POST, 'crm', FILTER_SANITIZE_STRING);

            if (!$nome || !$email || !$tipo || !$senha) {
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

            // Iniciar transação
            $pdo->beginTransaction();

            try {
                // Inserir novo usuário
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, tipo_usuario, senha, status)
                    VALUES (?, ?, ?, ?, 'ativo')
                ");
                $stmt->execute([
                    $nome,
                    $email,
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

                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário criado com sucesso'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'editar':
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $tipo = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_STRING);
            $senha = $_POST['senha'] ?? '';
            $especialidade = filter_input(INPUT_POST, 'especialidade', FILTER_SANITIZE_STRING);
            $crm = filter_input(INPUT_POST, 'crm', FILTER_SANITIZE_STRING);

            if (!$id || !$nome || !$email || !$tipo) {
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
                throw new Exception('Este email já está em uso por outro usuário');
            }

            // Iniciar transação
            $pdo->beginTransaction();

            try {
                // Atualizar usuário
                if ($senha) {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET nome = ?, email = ?, tipo_usuario = ?, senha = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $nome,
                        $email,
                        $tipo,
                        password_hash($senha, PASSWORD_DEFAULT),
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET nome = ?, email = ?, tipo_usuario = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $nome,
                        $email,
                        $tipo,
                        $id
                    ]);
                }

                // Verificar se já existe registro na tabela medicos
                $stmt = $pdo->prepare("SELECT id FROM medicos WHERE id_usuario = ?");
                $stmt->execute([$id]);
                $medico_exists = $stmt->fetch();

                if ($tipo === 'medico') {
                    if ($medico_exists) {
                        // Atualizar dados do médico
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
                        // Inserir novo registro de médico
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

                // Commit da transação
                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário atualizado com sucesso'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'status':
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

            if (!$id || !in_array($status, ['pendente', 'ativo', 'inativo'])) {
                throw new Exception('Dados inválidos');
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            echo json_encode([
                'success' => true,
                'message' => 'Status atualizado com sucesso'
            ]);
            break;

        default:
            throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
