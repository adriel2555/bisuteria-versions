<?php
session_start();
require_once '../../configuracion/conexion.php';

header('Content-Type: application/json');

// Función para registrar errores
function logError($message) {
    file_put_contents('reserva_errors.log', date('Y-m-d H:i:s')." - ".$message.PHP_EOL, FILE_APPEND);
}

try {
    // Verificar sesión
    if (!isset($_SESSION['email'])) {
        logError('Usuario no logueado');
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar una reserva']);
        exit;
    }

    // Obtener datos del POST
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError('Error al decodificar JSON: '.json_last_error_msg().' - Input: '.$jsonInput);
        throw new Exception('Formato de datos incorrecto');
    }
    
    // Validar datos requeridos
    if (empty($data['items']) || !isset($data['subtotal']) || !isset($data['total'])) {
        logError('Datos incompletos: '.print_r($data, true));
        throw new Exception('Datos de reserva incompletos');
    }
    
    $items = $data['items'];
    $subtotal = $data['subtotal'];
    $envio = $data['envio'] ?? 0;
    $total = $data['total'];
    
    // Obtener ID del usuario logueado
    $stmt = $conn->prepare("SELECT UsuarioID, Direccion, Ciudad, Departamento, CodigoPostal FROM Usuarios WHERE Email = ?");
    $stmt->bind_param("s", $_SESSION['email']);
    if (!$stmt->execute()) {
        logError('Error al buscar usuario: '.$stmt->error);
        throw new Exception('Error al verificar usuario');
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        logError('Usuario no encontrado: '.$_SESSION['email']);
        throw new Exception('Usuario no encontrado en la base de datos');
    }
    
    $usuario = $result->fetch_assoc();
    $usuarioId = $usuario['UsuarioID'];
    logError('Usuario ID obtenido: '.$usuarioId);
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Crear el pedido
        $stmt = $conn->prepare("
            INSERT INTO Pedidos (
                UsuarioID, MontoTotal, EstadoPedido, 
                DireccionEnvio, CiudadEnvio, DepartamentoEnvio, CodigoPostalEnvio
            ) VALUES (?, ?, 'Procesando', ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            logError('Error al preparar inserción de pedido: '.$conn->error);
            throw new Exception('Error al preparar consulta de pedido');
        }
        
        $stmt->bind_param(
            "idssss", 
            $usuarioId, $total,
            $usuario['Direccion'], $usuario['Ciudad'], $usuario['Departamento'], $usuario['CodigoPostal']
        );
        
        if (!$stmt->execute()) {
            logError('Error al insertar pedido: '.$stmt->error);
            throw new Exception('Error al crear el pedido');
        }
        
        $pedidoId = $conn->insert_id;
        logError('Pedido creado ID: '.$pedidoId);
        
        // 2. Procesar cada item del carrito
        foreach ($items as $index => $item) {
            if (empty($item['productoId']) || empty($item['cantidad']) || empty($item['precio'])) {
                logError('Item incompleto en índice '.$index.': '.print_r($item, true));
                throw new Exception('Datos de producto incompletos');
            }
            
            // Insertar en ArticulosPedido
            $stmt = $conn->prepare("
                INSERT INTO ArticulosPedido (
                    PedidoID, ProductoID, Cantidad, PrecioUnitario, Subtotal
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                logError('Error al preparar inserción de artículo: '.$conn->error);
                throw new Exception('Error al preparar artículo de pedido');
            }
            
            $subtotalItem = $item['precio'] * $item['cantidad'];
            $stmt->bind_param(
                "iiidd", 
                $pedidoId, $item['productoId'], $item['cantidad'], $item['precio'], $subtotalItem
            );
            
            if (!$stmt->execute()) {
                logError('Error al insertar artículo: '.$stmt->error);
                throw new Exception('Error al agregar producto al pedido');
            }
            
            // Registrar salida de inventario
            $stmt = $conn->prepare("
                INSERT INTO SalidasInventario (
                    ProductoID, Cantidad, TipoSalida, PedidoID, UsuarioResponsable
                ) VALUES (?, ?, 'Venta', ?, ?)
            ");
            
            if (!$stmt) {
                logError('Error al preparar salida de inventario: '.$conn->error);
                throw new Exception('Error al registrar salida de inventario');
            }
            
            $usuarioEmail = $_SESSION['email'];
            $stmt->bind_param(
                "iiis", 
                $item['productoId'], $item['cantidad'], $pedidoId, $usuarioEmail
            );
            
            if (!$stmt->execute()) {
                logError('Error al insertar salida de inventario: '.$stmt->error);
                throw new Exception('Error al registrar movimiento de inventario');
            }
            
            // Actualizar stock
            $stmt = $conn->prepare("
                UPDATE Productos 
                SET CantidadStock = CantidadStock - ? 
                WHERE ProductoID = ? AND CantidadStock >= ?
            ");
            
            if (!$stmt) {
                logError('Error al preparar actualización de stock: '.$conn->error);
                throw new Exception('Error al actualizar inventario');
            }
            
            $stmt->bind_param("iii", $item['cantidad'], $item['productoId'], $item['cantidad']);
            
            if (!$stmt->execute()) {
                logError('Error al actualizar stock: '.$stmt->error);
                throw new Exception('Error al actualizar cantidad en stock');
            }
            
            if ($stmt->affected_rows === 0) {
                logError('Stock insuficiente para producto: '.$item['productoId']);
                throw new Exception("No hay suficiente stock para el producto ID: ".$item['productoId']);
            }
        }
        
        // 3. Vaciar el carrito del usuario
        $stmt = $conn->prepare("DELETE FROM Carrito WHERE UsuarioID = ?");
        
        if (!$stmt) {
            logError('Error al preparar limpieza de carrito: '.$conn->error);
            throw new Exception('Error al vaciar carrito');
        }
        
        $stmt->bind_param("i", $usuarioId);
        
        if (!$stmt->execute()) {
            logError('Error al vaciar carrito: '.$stmt->error);
            throw new Exception('Error al limpiar el carrito');
        }
        
        // Confirmar transacción
        $conn->commit();
        
        logError('Reserva exitosa para pedido ID: '.$pedidoId);
        echo json_encode(['success' => true, 'pedidoId' => $pedidoId]);
        
    } catch (Exception $e) {
        $conn->rollback();
        logError('Error en transacción: '.$e->getMessage());
        throw $e;
    }
} catch (Exception $e) {
    logError('Error general: '.$e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>