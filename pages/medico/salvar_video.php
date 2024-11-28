<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $video_id = $_POST['videoId'] ?? null;
    $titulo = $_POST['titulo'] ?? null;
    $url = $_POST['url'] ?? null;
    $descricao = $_POST['descricao'] ?? null;
    $momento = $_POST['momento'] ?? null;
    $medico_id = $_SESSION['user_id'];

    if (!$titulo || !$url) {
        $_SESSION['error'] = "Título e URL são obrigatórios.";
        header('Location: index.php?page=medico/videos');
        exit;
    }

    try {
        $conn = getConnection();
        
        if ($video_id) {
            // Verifica se o vídeo pertence a este médico
            $stmt = $conn->prepare("SELECT id FROM videos WHERE id = ? AND id_medico = ?");
            $stmt->execute([$video_id, $medico_id]);
            if (!$stmt->fetch()) {
                $_SESSION['error'] = "Vídeo não encontrado.";
                header('Location: index.php?page=medico/videos');
                exit;
            }

            // Atualiza o vídeo
            $stmt = $conn->prepare("
                UPDATE videos 
                SET titulo = ?, url = ?, descricao = ?, id_momento = ?
                WHERE id = ? AND id_medico = ?
            ");
            $stmt->execute([$titulo, $url, $descricao, $momento ?: null, $video_id, $medico_id]);
        } else {
            // Insere novo vídeo
            $stmt = $conn->prepare("
                INSERT INTO videos (titulo, url, descricao, id_momento, id_medico, data_criacao)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$titulo, $url, $descricao, $momento ?: null, $medico_id]);
        }

        $_SESSION['success'] = "Vídeo " . ($video_id ? "atualizado" : "cadastrado") . " com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao salvar vídeo: " . $e->getMessage();
    }
}

header('Location: index.php?page=medico/videos');
exit;
