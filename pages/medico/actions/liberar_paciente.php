<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'medico') {
    header('Location: ../../../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID do paciente não fornecido.';
    header('Location: ../../../index.php?page=medico/pacientes');
    exit;
}

$conn = getConnection();
$medico_id = $_SESSION['user_id'];
$paciente_id = $_GET['id'];

// Verifica se o paciente pertence a este médico
$sql = "SELECT COUNT(*) as total FROM pacientes WHERE medico = ? AND id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$medico_id, $paciente_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['total'] == 0) {
    $_SESSION['error'] = 'Você não tem permissão para liberar este paciente.';
    header('Location: ../../../index.php?page=medico/pacientes');
    exit;
}

// Libera o paciente
$sql = "UPDATE usuarios SET status = 1 WHERE id = ?";
$stmt = $conn->prepare($sql);

try {
    $stmt->execute([$paciente_id]);
    $_SESSION['success'] = 'Paciente liberado com sucesso.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erro ao liberar paciente: ' . $e->getMessage();
}

header('Location: ../../../index.php?page=medico/pacientes');
exit;
