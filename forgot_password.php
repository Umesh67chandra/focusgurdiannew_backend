<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(["error" => "POST method required"]);
    exit;
}

// Read JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Email and new_password are required"]);
    exit;
}

$email = trim($data['email']);
$newPassword = $data['new_password'];

// ✅ CORRECT DATABASE NAME
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "focusguardiannew"
);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Check user
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Email not registered"]);
    exit;
}

// Hash password
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// Update password
$update = $conn->prepare(
    "UPDATE users SET password = ? WHERE email = ?"
);
$update->bind_param("ss", $hashedPassword, $email);

if ($update->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Password reset successful"
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Password reset failed"]);
}

$update->close();
$conn->close();
