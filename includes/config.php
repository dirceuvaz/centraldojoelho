<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'centraldojoelho');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuração do fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Conexão com o banco de dados usando PDO
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        )
    );
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Configurações gerais do site
define('SITE_NAME', 'Central do Joelho');
define('SITE_URL', 'http://localhost/centraldojoelho');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();
