<?php
// Configurações do banco de dados
function getConnection() {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=centraldojoelho', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}
