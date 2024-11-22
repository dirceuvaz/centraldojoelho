<?php
session_start();

// Páginas que não precisam de login
$public_pages = ['login', 'logout', 'login_process'];

// Obtém a página da URL ou usa 'login' como padrão
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Se não for página pública, verifica se está logado
if (!in_array($page, $public_pages) && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Se for página administrativa, verifica se é admin
if (strpos($page, 'admin/') === 0) {
    if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
        header('Location: index.php?page=login');
        exit;
    }
}

// Se for página de médico, verifica se é médico
if (strpos($page, 'medico/') === 0) {
    if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'medico') {
        header('Location: index.php?page=login');
        exit;
    }
}

// Se for página de paciente, verifica se é paciente
if (strpos($page, 'paciente/') === 0) {
    if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
        header('Location: index.php?page=login');
        exit;
    }
}

// Carrega a página apropriada
$file = __DIR__ . '/pages/' . $page . '.php';
if (file_exists($file)) {
    require $file;
} else {
    require __DIR__ . '/pages/errors/404.php';
}
