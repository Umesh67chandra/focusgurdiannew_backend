<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "focusguardiannnew"; // ✅ CORRECT DB NAME

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        "error" => "Database connection failed"
    ]));
}
?>
