<?php
header('Content-Type: application/json');

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set',
    'files' => $_FILES,
    'post' => $_POST,
    'raw_input_length' => strlen(file_get_contents('php://input')),
    'headers' => getallheaders()
]);
?>