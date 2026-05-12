<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name  = trim($input['name'] ?? '');

if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
    echo json_encode(['success' => false, 'message' => 'ناوەکە پێویستە لانیکەم ٢ پیت بێت']);
    exit;
}

$_SESSION['voter_name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

echo json_encode(['success' => true]);
