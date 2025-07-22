<?php
session_start();
require_once '../configuracion/conexion.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión para agregar al carrito']);
    exit;
}

// Obtener datos del POST
$producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
$cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

// Validar datos
if ($producto_id <= 0 || $cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
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

// Verificar si el producto existe y obtener su precio actual
$query_producto = "SELECT Precio FROM Productos WHERE ProductoID = ? AND EstaActivo = 1";
$stmt_producto = $conn->prepare($query_producto);
$stmt_producto->bind_param("i", $producto_id);
$stmt_producto->execute();
$result_producto = $stmt_producto->get_result();

if ($result_producto->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no disponible']);
    exit;
}

$producto = $result_producto->fetch_assoc();
$precio_unitario = $producto['Precio'];

// Verificar si el producto ya está en el carrito del usuario
$query_carrito = "SELECT CarritoID, Cantidad FROM Carrito WHERE UsuarioID = ? AND ProductoID = ?";
$stmt_carrito = $conn->prepare($query_carrito);
$stmt_carrito->bind_param("ii", $usuario_id, $producto_id);
$stmt_carrito->execute();
$result_carrito = $stmt_carrito->get_result();

if ($result_carrito->num_rows > 0) {
    // Actualizar cantidad si el producto ya está en el carrito
    $item_carrito = $result_carrito->fetch_assoc();
    $nueva_cantidad = $item_carrito['Cantidad'] + $cantidad;
    
    $query_actualizar = "UPDATE Carrito SET Cantidad = ?, PrecioUnitario = ?, FechaActualizacion = NOW() WHERE CarritoID = ?";
    $stmt_actualizar = $conn->prepare($query_actualizar);
    $stmt_actualizar->bind_param("idi", $nueva_cantidad, $precio_unitario, $item_carrito['CarritoID']);
    
    if ($stmt_actualizar->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cantidad actualizada en el carrito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el carrito']);
    }
} else {
    // Insertar nuevo item en el carrito
    $query_insertar = "INSERT INTO Carrito (UsuarioID, ProductoID, Cantidad, PrecioUnitario) VALUES (?, ?, ?, ?)";
    $stmt_insertar = $conn->prepare($query_insertar);
    $stmt_insertar->bind_param("iiid", $usuario_id, $producto_id, $cantidad, $precio_unitario);
    
    if ($stmt_insertar->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar al carrito']);
    }
}

$conn->close();
?>