<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é um médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

$pdo = getConnection();

// Função para validar e sanitizar os dados
function validarDados($dados) {
    $erros = [];
    
    if (empty($dados['titulo'])) {
        $erros[] = "O título é obrigatório";
    }
    
    if (empty($dados['texto'])) {
        $erros[] = "O texto é obrigatório";
    }

    if (empty($dados['momento'])) {
        $erros[] = "O momento é obrigatório";
    }

    if (empty($dados['tipo'])) {
        $erros[] = "O tipo é obrigatório";
    }
    
    return $erros;
}

// Processar as ações
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'criar':
            $dados = [
                'titulo' => $_POST['titulo'] ?? '',
                'texto' => $_POST['texto'] ?? '',
                'momento' => $_POST['momento'] ?? '',
                'tipo' => $_POST['tipo'] ?? ''
            ];
            
            $erros = validarDados($dados);
            
            if (empty($erros)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO reabilitacao (titulo, texto, momento, tipo, id_medico, data_criacao, data_atualizacao)
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $dados['titulo'],
                        $dados['texto'],
                        $dados['momento'],
                        $dados['tipo'],
                        $_SESSION['user_id']
                    ]);
                    
                    header("Location: index.php?page=medico/reabilitacao&sucesso=Orientação criada com sucesso!");
                    exit;
                } catch (PDOException $e) {
                    header("Location: index.php?page=medico/reabilitacao&erro=Erro ao criar orientação: " . $e->getMessage());
                    exit;
                }
            } else {
                header('Location: index.php?page=medico/reabilitacao&erro=' . urlencode(implode(", ", $erros)));
            }
            exit;

        case 'editar':
            if (empty($_POST['orientacao_id'])) {
                header('Location: index.php?page=medico/reabilitacao&erro=ID da orientação não fornecido');
                exit;
            }

            $dados = [
                'id' => $_POST['orientacao_id'],
                'titulo' => $_POST['titulo'] ?? '',
                'texto' => $_POST['texto'] ?? '',
                'momento' => $_POST['momento'] ?? '',
                'tipo' => $_POST['tipo'] ?? ''
            ];
            
            $erros = validarDados($dados);
            
            if (empty($erros)) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE reabilitacao 
                        SET titulo = ?, 
                            texto = ?,
                            momento = ?,
                            tipo = ?,
                            data_atualizacao = NOW()
                        WHERE id = ? AND id_medico = ?
                    ");
                    
                    $stmt->execute([
                        $dados['titulo'],
                        $dados['texto'],
                        $dados['momento'],
                        $dados['tipo'],
                        $dados['id'],
                        $_SESSION['user_id']
                    ]);
                    
                    header("Location: index.php?page=medico/reabilitacao&sucesso=Orientação atualizada com sucesso!");
                    exit;
                } catch (PDOException $e) {
                    header("Location: index.php?page=medico/reabilitacao&erro=Erro ao atualizar orientação: " . $e->getMessage());
                    exit;
                }
            } else {
                header('Location: index.php?page=medico/reabilitacao&erro=' . urlencode(implode(", ", $erros)));
            }
            exit;

        case 'excluir':
            if (empty($_GET['orientacao_id'])) {
                header('Location: index.php?page=medico/reabilitacao&erro=ID da orientação não fornecido');
                exit;
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM reabilitacao WHERE id = ? AND id_medico = ?");
                $stmt->execute([$_GET['orientacao_id'], $_SESSION['user_id']]);
                header("Location: index.php?page=medico/reabilitacao&sucesso=Orientação excluída com sucesso!");
                exit;
            } catch (PDOException $e) {
                header("Location: index.php?page=medico/reabilitacao&erro=Erro ao excluir orientação: " . $e->getMessage());
                exit;
            }

        case 'buscar':
            if (empty($_GET['id'])) {
                header('Location: index.php?page=medico/reabilitacao&erro=ID da orientação não fornecido');
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT r.*, m.descricao as momento_desc, t.descricao as tipo_desc
                FROM reabilitacao r
                LEFT JOIN momentos_reabilitacao m ON r.momento = m.id
                LEFT JOIN tipos_reabilitacao t ON r.tipo = t.id
                WHERE r.id = ? AND r.id_medico = ?
            ");
            
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            $orientacao = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($orientacao) {
                // Preencher os dados no formulário e mostrar o modal
                $_SESSION['temp_orientacao'] = $orientacao;
                header('Location: index.php?page=medico/reabilitacao&editar=' . $orientacao['id']);
            } else {
                header('Location: index.php?page=medico/reabilitacao&erro=Orientação não encontrada');
            }
            exit;

        default:
            header('Location: index.php?page=medico/reabilitacao&erro=Ação inválida');
            exit;
    }
} catch (Exception $e) {
    header('Location: index.php?page=medico/reabilitacao&erro=' . urlencode($e->getMessage()));
    exit;
}