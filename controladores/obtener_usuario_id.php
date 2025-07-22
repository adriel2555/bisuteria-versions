<?php
session_start();
require_once '../configuracion/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT UsuarioID FROM Usuarios WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$usuario = $result->fetch_assoc();
echo json_encode(['success' => true, 'usuario_id' => $usuario['UsuarioID']]);

$conn->close();
?>