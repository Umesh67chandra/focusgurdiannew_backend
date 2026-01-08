<?php
// ---------------- HEADERS ----------------
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// ---------------- METHOD CHECK ----------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(["error" => "POST method required"]);
    exit;
}

// ---------------- READ JSON ----------------
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['role'])) {
    http_response_code(400);
    echo json_encode(["error" => "Role is required"]);
    exit;
}

$role = strtolower(trim($data['role']));

// ---------------- VALIDATE ROLE ----------------
$validRoles = ["child", "adult", "parent"];

if (!in_array($role, $validRoles)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid role selected"]);
    exit;
}

// ---------------- ROLE LOGIC ----------------
$response = [
    "status" => "success",
    "selected_role" => $role
];

switch ($role) {
    case "child":
        $response["next_screen"] = "parent_verification";
        $response["message"] = "Child account requires parent approval";
        break;

    case "adult":
        $response["next_screen"] = "adult_registration";
        $response["message"] = "Proceed with adult registration";
        break;

    case "parent":
        $response["next_screen"] = "parent_registration";
        $response["message"] = "Proceed with parent registration";
        break;
}

echo json_encode($response);
