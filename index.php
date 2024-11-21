<?php
session_start();

// Obtém a página da URL ou usa 'login' como padrão
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Se for a página de processamento de login
if ($page === 'login_process') {
    require 'pages/login_process.php';
    exit;
}

// Se for página administrativa, verifica se é admin
if (strpos($page, 'admin/') === 0) {
    if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
        header('Location: index.php?page=login');
        exit;
    }
}

// Se não for login ou cadastro, verifica se está logado
if (!in_array($page, ['login', 'cadastro']) && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Carrega a página apropriada
$file = __DIR__ . '/pages/' . $page . '.php';
if (file_exists($file)) {
    require $file;
} else {
    echo "Página não encontrada";
}
