<?php
session_start();
require_once '../configuracion/conexion.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Obtener el ID del usuario
$email = $_SESSION['email'];
$query_usuario = "SELECT UsuarioID FROM Usuarios WHERE Email = ?";
$stmt_usuario = $conn->prepare($query_usuario);
$stmt_usuario->bind_param("s", $email);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$usuario = $result_usuario->fetch_assoc();
$usuario_id = $usuario['UsuarioID'];

// Contar items en el carrito
$query_contar = "SELECT SUM(Cantidad) as total FROM Carrito WHERE UsuarioID = ?";
$stmt_contar = $conn->prepare($query_contar);
$stmt_contar->bind_param("i", $usuario_id);
$stmt_contar->execute();
$result_contar = $stmt_contar->get_result();

$total = 0;
if ($result_contar->num_rows > 0) {
    $row = $result_contar->fetch_assoc();
    $total = $row['total'] ?? 0;
}

echo json_encode(['success' => true, 'count' => $total]);

$conn->close();
?>