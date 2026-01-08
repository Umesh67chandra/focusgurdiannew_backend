<?php
// -------------------- HEADERS --------------------
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// -------------------- METHOD CHECK --------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode([
        "error" => "POST method required"
    ]);
    exit;
}

// -------------------- READ JSON BODY --------------------
$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['email']) ||
    !isset($data['password']) ||
    !isset($data['role'])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Email, password and role are required"
    ]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];
$role = trim($data['role']);

// -------------------- DB CONNECTION (✅ FIXED HERE) --------------------
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "focusguardiannew"   // ✅ CORRECT DATABASE NAME
);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed"
    ]);
    exit;
}

// -------------------- CHECK EMAIL EXISTS --------------------
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(409);
    echo json_encode([
        "error" => "Email already registered"
    ]);
    exit;
}

// -------------------- HASH PASSWORD --------------------
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// -------------------- INSERT USER --------------------
$stmt = $conn->prepare(
    "INSERT INTO users (email, password, role, created_at)
     VALUES (?, ?, ?, NOW())"
);

$stmt->bind_param("sss", $email, $hashedPassword, $role);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "error" => "Registration failed"
    ]);
}

// -------------------- CLOSE CONNECTION --------------------
$stmt->close();
$conn->close();
