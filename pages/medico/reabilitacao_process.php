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
    
    return $erros;
}

// Processar as ações
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'criar':
            $dados = [
                'titulo' => $_POST['titulo'] ?? '',
                'texto' => $_POST['texto'] ?? ''
            ];
            
            $erros = validarDados($dados);
            
            if (empty($erros)) {
                $stmt = $pdo->prepare("
                    INSERT INTO reabilitacao (titulo, texto, id_medico, data_criacao, data_atualizacao)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $dados['titulo'],
                    $dados['texto'],
                    $_SESSION['user_id']
                ]);
                
                header('Location: index.php?page=medico/reabilitacao&sucesso=Orientação criada com sucesso!');
                exit;
            }
            break;

        case 'editar':
            $id = $_POST['id'] ?? 0;
            $dados = [
                'titulo' => $_POST['titulo'] ?? '',
                'texto' => $_POST['texto'] ?? ''
            ];
            
            $erros = validarDados($dados);
            
            if (empty($erros)) {
                $stmt = $pdo->prepare("
                    UPDATE reabilitacao 
                    SET titulo = ?, texto = ?, data_atualizacao = NOW()
                    WHERE id = ? AND id_medico = ?
                ");
                
                $stmt->execute([
                    $dados['titulo'],
                    $dados['texto'],
                    $id,
                    $_SESSION['user_id']
                ]);
                
                header('Location: index.php?page=medico/reabilitacao&sucesso=Orientação atualizada com sucesso!');
                exit;
            }
            break;

        case 'excluir':
            $id = $_GET['id'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM reabilitacao WHERE id = ? AND id_medico = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            
            header('Location: index.php?page=medico/reabilitacao&sucesso=Orientação excluída com sucesso!');
            exit;
            break;

        case 'buscar':
            $id = $_GET['id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT * FROM reabilitacao 
                WHERE id = ? AND id_medico = ?
            ");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $orientacao = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($orientacao) {
                header('Content-Type: application/json');
                echo json_encode($orientacao);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Orientação não encontrada']);
            }
            exit;
            break;
    }
} catch (Exception $e) {
    $erro = "Erro ao processar a solicitação: " . $e->getMessage();
    header('Location: index.php?page=medico/reabilitacao&erro=' . urlencode($erro));
    exit;
}
