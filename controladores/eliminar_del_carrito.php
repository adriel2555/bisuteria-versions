<?php
session_start();
require_once '../../configuracion/conexion.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['email'])) {
    die("0"); // No autorizado
}

$producto_id = (int)$_POST['producto_id'];
$usuario_id = (int)$_POST['usuario_id'];

// Verificar que el usuario que hace la petición es el mismo que el del carrito
$email = $_SESSION['email'];
$stmt_verificar = $conn->prepare("SELECT UsuarioID FROM Usuarios WHERE Email = ? AND UsuarioID = ?");
$stmt_verificar->bind_param("si", $email, $usuario_id);
$stmt_verificar->execute();
$result_verificar = $stmt_verificar->get_result();

if ($result_verificar->num_rows === 0) {
    die("0"); // No coincide el usuario
}

// Eliminar por ProductoID y UsuarioID
$sql = "DELETE FROM Carrito WHERE ProductoID = ? AND UsuarioID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $producto_id, $usuario_id);

if ($stmt->execute()) {
    // Verificar que realmente se eliminó
    if ($stmt->affected_rows > 0) {
        echo "1"; // Éxito
    } else {
        // Verificar si el producto existe en el carrito
        $check_sql = "SELECT * FROM Carrito WHERE ProductoID = ? AND UsuarioID = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $producto_id, $usuario_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo "2"; // El producto ya no está en el carrito
        } else {
            echo "0"; // No se pudo eliminar por otra razón
        }
    }
} else {
    error_log("Error al eliminar del carrito: " . $stmt->error);
    echo "0"; // Error en la ejecución
}

$conn->close();
?>