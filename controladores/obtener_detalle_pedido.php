<?php
require_once 'conexion.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID de pedido no válido']);
    exit;
}

$pedidoId = (int)$_GET['id'];

// Obtener información básica del pedido
$sqlPedido = "SELECT p.*, u.Nombre, u.Apellido 
              FROM Pedidos p
              INNER JOIN Usuarios u ON p.UsuarioID = u.UsuarioID
              WHERE p.PedidoID = ?";
$stmtPedido = $conn->prepare($sqlPedido);
$stmtPedido->bind_param("i", $pedidoId);
$stmtPedido->execute();
$resultPedido = $stmtPedido->get_result();

if ($resultPedido->num_rows === 0) {
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

$pedido = $resultPedido->fetch_assoc();

// Obtener productos del pedido
$sqlProductos = "SELECT pr.NombreProducto, ap.Cantidad, ap.PrecioUnitario, ap.Subtotal 
                 FROM ArticulosPedido ap
                 INNER JOIN Productos pr ON ap.ProductoID = pr.ProductoID
                 WHERE ap.PedidoID = ?";
$stmtProductos = $conn->prepare($sqlProductos);
$stmtProductos->bind_param("i", $pedidoId);
$stmtProductos->execute();
$resultProductos = $stmtProductos->get_result();

$productos = [];
while ($producto = $resultProductos->fetch_assoc()) {
    $productos[] = $producto;
}

// Devolver datos en formato JSON
echo json_encode([
    'pedido' => $pedido,
    'productos' => $productos
]);