<?php
// includes/csrf.php

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// Para formularios POST clásicos
function csrf_verify(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = trim($_POST['csrf_token'] ?? '');
    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        die("Acción no autorizada. Por favor, recarga la página e intenta de nuevo.");
    }
}

// Para endpoints JSON llamados con Fetch API (verifica el header X-CSRF-Token)
function csrf_verify_json(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Token de seguridad inválido. Recarga la página.']);
        exit;
    }
}
