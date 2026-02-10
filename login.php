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
    !isset($data['password'])
) {
    http_response_code(400);
    echo json_encode([
        "error" => "Email and password are required"
    ]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// -------------------- DB CONNECTION --------------------
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "focusguardiannew"   // ✅ CORRECT DATABASE
);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database connection failed"
    ]);
    exit;
}

// -------------------- FETCH USER --------------------
$stmt = $conn->prepare(
    "SELECT id, email, password, role 
     FROM users 
     WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode([
        "error" => "Invalid email or password"
    ]);
    exit;
}

$user = $result->fetch_assoc();

// -------------------- VERIFY PASSWORD --------------------
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode([
        "error" => "Invalid email or password"
    ]);
    exit;
}

// -------------------- SUCCESS RESPONSE --------------------
echo json_encode([
    "status" => "success",
    "user" => [
        "id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role']
    ]
]);

$stmt->close();
$conn->close();
