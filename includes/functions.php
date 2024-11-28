<?php
/**
 * Funções utilitárias para o sistema
 */

/**
 * Sanitiza uma string para evitar XSS
 */
function sanitize_string($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se o usuário está logado
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário é administrador
 */
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Redireciona para uma página
 */
function redirect($page) {
    header("Location: " . SITE_URL . $page);
    exit();
}

/**
 * Gera uma senha hash segura
 */
function generate_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifica se uma senha corresponde ao hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Formata uma data para o padrão brasileiro
 */
function format_date($date, $with_time = false) {
    if ($with_time) {
        return date('d/m/Y H:i', strtotime($date));
    }
    return date('d/m/Y', strtotime($date));
}

/**
 * Gera uma string aleatória
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Valida um email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Limpa uma string para uso em URLs
 */
function slugify($text) {
    // Remove acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    // Converte para minúsculas
    $text = strtolower($text);
    // Remove caracteres especiais
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    // Remove hífens duplicados
    $text = preg_replace('/-+/', '-', $text);
    // Remove hífens do início e fim
    $text = trim($text, '-');
    return $text;
}

/**
 * Formata um número para o padrão brasileiro
 */
function format_number($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Valida uma data no formato brasileiro
 */
function is_valid_date($date) {
    $pattern = '/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/';
    if (!preg_match($pattern, $date)) {
        return false;
    }
    
    list($day, $month, $year) = explode('/', $date);
    return checkdate($month, $day, $year);
}

/**
 * Converte uma data do formato brasileiro para o formato do banco de dados
 */
function date_to_database($date) {
    if (empty($date)) return null;
    return date('Y-m-d', strtotime(str_replace('/', '-', $date)));
}

/**
 * Converte uma data do banco de dados para o formato brasileiro
 */
function date_from_database($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

/**
 * Gera um token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica se um token CSRF é válido
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
