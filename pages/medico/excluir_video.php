<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é médico
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $video_id = $_POST['videoId'] ?? null;
    $medico_id = $_SESSION['user_id'];

    if (!$video_id) {
        $_SESSION['error'] = "ID do vídeo não fornecido.";
        header('Location: index.php?page=medico/videos');
        exit;
    }

    try {
        $conn = getConnection();
        
        // Verifica se o vídeo pertence a este médico
        $stmt = $conn->prepare("SELECT id FROM videos WHERE id = ? AND id_medico = ?");
        $stmt->execute([$video_id, $medico_id]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "Vídeo não encontrado.";
            header('Location: index.php?page=medico/videos');
            exit;
        }

        // Exclui o vídeo
        $stmt = $conn->prepare("DELETE FROM videos WHERE id = ? AND id_medico = ?");
        $stmt->execute([$video_id, $medico_id]);

        $_SESSION['success'] = "Vídeo excluído com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao excluir vídeo: " . $e->getMessage();
    }
}

header('Location: index.php?page=medico/videos');
exit;
