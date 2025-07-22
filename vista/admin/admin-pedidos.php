<?php
session_start();
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Incluir archivo de conexión
require_once '../../configuracion/conexion.php';

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $pedidoID = $_POST['pedido_id'];
    $nuevoEstado = $_POST['nuevo_estado'];
    
    $sql = "UPDATE Pedidos SET EstadoPedido = ? WHERE PedidoID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevoEstado, $pedidoID);
    $stmt->execute();
    
    // Si se cancela, restaurar inventario
    if ($nuevoEstado === 'Cancelado') {
        // Obtener artículos del pedido
        $sqlArticulos = "SELECT ProductoID, Cantidad FROM ArticulosPedido WHERE PedidoID = ?";
        $stmtArticulos = $conn->prepare($sqlArticulos);
        $stmtArticulos->bind_param("i", $pedidoID);
        $stmtArticulos->execute();
        $resultArticulos = $stmtArticulos->get_result();
        
        while ($articulo = $resultArticulos->fetch_assoc()) {
            // Actualizar inventario
            $sqlUpdateInventario = "UPDATE Inventario SET CantidadDisponible = CantidadDisponible + ? WHERE ProductoID = ?";
            $stmtUpdate = $conn->prepare($sqlUpdateInventario);
            $stmtUpdate->bind_param("ii", $articulo['Cantidad'], $articulo['ProductoID']);
            $stmtUpdate->execute();
        }
    }
}

// Procesar salida de inventario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_salida'])) {
    $pedidoID = $_POST['pedido_id'];
    $usuarioResponsable = "Administrador"; // En producción sería $_SESSION['usuario']
    
    // Obtener artículos del pedido
    $sqlArticulos = "SELECT ProductoID, Cantidad FROM ArticulosPedido WHERE PedidoID = ?";
    $stmtArticulos = $conn->prepare($sqlArticulos);
    $stmtArticulos->bind_param("i", $pedidoID);
    $stmtArticulos->execute();
    $resultArticulos = $stmtArticulos->get_result();
    
    while ($articulo = $resultArticulos->fetch_assoc()) {
        // Registrar salida
        $sqlSalida = "INSERT INTO SalidasInventario (ProductoID, Cantidad, FechaSalida, TipoSalida, PedidoID, UsuarioResponsable)
                      VALUES (?, ?, NOW(), 'Venta', ?, ?)";
        $stmtSalida = $conn->prepare($sqlSalida);
        $stmtSalida->bind_param("iiis", $articulo['ProductoID'], $articulo['Cantidad'], $pedidoID, $usuarioResponsable);
        $stmtSalida->execute();
        
        // Actualizar inventario
        $sqlUpdateInventario = "UPDATE Inventario SET CantidadDisponible = CantidadDisponible - ? WHERE ProductoID = ?";
        $stmtUpdate = $conn->prepare($sqlUpdateInventario);
        $stmtUpdate->bind_param("ii", $articulo['Cantidad'], $articulo['ProductoID']);
        $stmtUpdate->execute();
    }
    
    // Actualizar estado del pedido
    $sqlEstado = "UPDATE Pedidos SET EstadoPedido = 'Entregado' WHERE PedidoID = ?";
    $stmtEstado = $conn->prepare($sqlEstado);
    $stmtEstado->bind_param("i", $pedidoID);
    $stmtEstado->execute();
}

// Obtener pedidos
$sql = "SELECT p.PedidoID, u.Nombre, u.Apellido, p.FechaPedido, p.MontoTotal, p.EstadoPedido 
        FROM Pedidos p
        JOIN Usuarios u ON p.UsuarioID = u.UsuarioID
        ORDER BY p.FechaPedido DESC";
$result = $conn->query($sql);

// Obtener parámetros de filtrado
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtroFecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Construir consulta SQL con filtros
$sql = "SELECT p.PedidoID, u.Nombre, u.Apellido, p.FechaPedido, p.MontoTotal, p.EstadoPedido 
        FROM Pedidos p
        JOIN Usuarios u ON p.UsuarioID = u.UsuarioID";

// Añadir condiciones según los filtros
$condiciones = [];
if ($filtroEstado && $filtroEstado !== 'todos') {
    $condiciones[] = "p.EstadoPedido = '$filtroEstado'";
}
if ($filtroFecha) {
    $condiciones[] = "DATE(p.FechaPedido) = '$filtroFecha'";
}

if (!empty($condiciones)) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}

$sql .= " ORDER BY p.FechaPedido DESC";

$result = $conn->query($sql);

// Procesar venta directa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_venta_directa'])) {
    $pedidoID = $_POST['pedido_id'];
    $metodoPago = $_POST['metodo_pago'];
    $referenciaPago = $_POST['referencia_pago'] ?? null;
    $usuarioResponsable = "Administrador"; // En producción sería $_SESSION['usuario']
    
    // Registrar salida de inventario
    $sqlArticulos = "SELECT ProductoID, Cantidad FROM ArticulosPedido WHERE PedidoID = ?";
    $stmtArticulos = $conn->prepare($sqlArticulos);
    $stmtArticulos->bind_param("i", $pedidoID);
    $stmtArticulos->execute();
    $resultArticulos = $stmtArticulos->get_result();
    
    while ($articulo = $resultArticulos->fetch_assoc()) {
        // Registrar salida
        $sqlSalida = "INSERT INTO SalidasInventario (ProductoID, Cantidad, FechaSalida, TipoSalida, PedidoID, UsuarioResponsable, Notas)
                      VALUES (?, ?, NOW(), 'Venta Directa', ?, ?, ?)";
        $stmtSalida = $conn->prepare($sqlSalida);
        $notas = "Método de pago: $metodoPago" . ($referenciaPago ? ", Referencia: $referenciaPago" : "");
        $stmtSalida->bind_param("iiiss", $articulo['ProductoID'], $articulo['Cantidad'], $pedidoID, $usuarioResponsable, $notas);
        $stmtSalida->execute();
        
        // Actualizar inventario
        $sqlUpdateInventario = "UPDATE Inventario SET CantidadDisponible = CantidadDisponible - ? WHERE ProductoID = ?";
        $stmtUpdate = $conn->prepare($sqlUpdateInventario);
        $stmtUpdate->bind_param("ii", $articulo['Cantidad'], $articulo['ProductoID']);
        $stmtUpdate->execute();
    }
    
    // Actualizar estado del pedido y método de pago
    $sqlEstado = "UPDATE Pedidos SET EstadoPedido = 'Venta Directa', MetodoPago = ?, IDTransaccion = ? WHERE PedidoID = ?";
    $stmtEstado = $conn->prepare($sqlEstado);
    $stmtEstado->bind_param("ssi", $metodoPago, $referenciaPago, $pedidoID);
    $stmtEstado->execute();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Pedidos | Aranzábal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .contenedor-admin {
            display: flex;
            min-height: 100vh;
        }

        .sidebar-admin {
            width: 250px;
            background: linear-gradient(to bottom, #2c3e50, #1a2530);
            color: white;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo-admin {
            text-align: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo-admin img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
            border-radius: 50%;
            border: 2px solid #fff;
            padding: 5px;
        }

        .logo-admin h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #f8f9fa;
        }

        .menu-admin ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-admin li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .menu-admin li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-admin li a.activo {
            background-color: #8e44ad;
        }

        .menu-admin li a i {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }

        .cerrar-sesion-admin {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cerrar-sesion-admin a {
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
        }

        .cerrar-sesion-admin a i {
            margin-right: 10px;
        }

        .contenido-admin {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .cabecera-admin {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .cabecera-admin h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .usuario-admin {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar-usuario {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6a11cb;
            font-weight: bold;
        }

        .resumen-estadisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .tarjeta-estadistica {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .tarjeta-estadistica:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .icono-estadistica {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: white;
        }

        .ventas .icono-estadistica {
            background: linear-gradient(to right, #00b09b, #96c93d);
        }

        .pedidos .icono-estadistica {
            background: linear-gradient(to right, #2193b0, #6dd5ed);
        }

        .productos .icono-estadistica {
            background: linear-gradient(to right, #8e2de2, #4a00e0);
        }

        .clientes .icono-estadistica {
            background: linear-gradient(to right, #f46b45, #eea849);
        }

        .info-estadistica h3 {
            margin: 0 0 5px;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .info-estadistica .valor {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .acciones-pedidos {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }

        .filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filtros select,
        .filtros input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }

        .filtros button {
            padding: 8px 15px;
            background: #6a11cb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .filtros button:hover {
            background: #4a00e0;
        }

        .buscador {
            display: flex;
            align-items: center;
        }

        .buscador input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            width: 250px;
        }

        .buscador button {
            padding: 8px 15px;
            background: #2575fc;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            transition: background 0.3s;
        }

        .buscador button:hover {
            background: #1a5fd0;
        }

        .lista-pedidos {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .tabla-pedidos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-pedidos th {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            color: #495057;
            text-align: left;
            padding: 15px;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .tabla-pedidos td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .tabla-pedidos tr:hover td {
            background-color: #f8f9fa;
        }

        .estado {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .estado.pendiente {
            background-color: #fff3cd;
            color: #856404;
        }

        .estado.procesando {
            background-color: #cce5ff;
            color: #004085;
        }

        .estado.enviado {
            background-color: #d4edda;
            color: #155724;
        }

        .estado.entregado {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .estado.cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .acciones {
            display: flex;
            gap: 8px;
        }

        .btn-accion {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-ver {
            background-color: #17a2b8;
            color: white;
        }

        .btn-ver:hover {
            background-color: #138496;
        }

        .btn-editar {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-editar:hover {
            background-color: #e0a800;
        }

        .btn-cancelar {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancelar:hover {
            background-color: #c82333;
        }

        .btn-registrar {
            background-color: #28a745;
            color: white;
        }

        .btn-registrar:hover {
            background-color: #218838;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-contenido {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-cabecera {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-cabecera h2 {
            margin: 0;
            font-size: 1.4rem;
        }

        .cerrar-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-cuerpo {
            padding: 20px;
        }

        .info-pedido {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-item strong {
            display: block;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tabla-productos th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
        }

        .tabla-productos td {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .tabla-productos img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 10px;
        }

        .acciones-modal {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .form-cambiar-estado {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .form-cambiar-estado select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }

        @media (max-width: 1024px) {
            .contenedor-admin {
                flex-direction: column;
            }

            .sidebar-admin {
                width: 100%;
                flex-direction: row;
                padding: 10px 0;
                align-items: center;
                justify-content: space-between;
            }

            .logo-admin {
                padding: 0 15px;
                border-bottom: none;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .logo-admin img {
                width: 40px;
                margin-bottom: 0;
            }

            .logo-admin h2,
            .logo-admin p {
                display: inline;
                font-size: 0.9rem;
            }

            .logo-admin p {
                display: none;
            }

            .menu-admin {
                display: none;
            }

            .menu-admin.active {
                display: block;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #2c3e50;
                z-index: 1000;
            }

            .cerrar-sesion-admin {
                padding: 0 15px;
                border-top: none;
                margin-top: 0;
            }

            .contenido-admin {
                margin-top: 60px;
            }

            .acciones-pedidos {
                flex-direction: column;
            }

            .filtros,
            .buscador {
                width: 100%;
            }

            .buscador input {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .tabla-pedidos {
                display: block;
                overflow-x: auto;
            }

            .resumen-estadisticas {
                grid-template-columns: 1fr;
            }

            .modal-contenido {
                width: 95%;
            }
        }

        /* Mejoras para los filtros */
        .filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .filtros label {
            font-weight: 500;
            color: #495057;
            font-size: 0.9rem;
        }

        .filtros select,
        .filtros input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            background: white;
            transition: border-color 0.3s;
        }

        .filtros select:focus,
        .filtros input:focus {
            border-color: #6a11cb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
        }

        .filtros button {
            padding: 8px 15px;
            background: #6a11cb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .filtros button:hover {
            background: #4a00e0;
        }

        .filtros .btn-limpiar {
            background: #6c757d;
        }

        .filtros .btn-limpiar:hover {
            background: #5a6268;
        }

        .contador-resultados {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
            font-style: italic;
        }

        /* Agregar al final de la sección de estilos */
#ventaDirectaForm {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}

#ventaDirectaForm h3 {
    margin-top: 0;
    color: #2c3e50;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 10px;
}

#ventaDirectaForm label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #495057;
}
    </style>
</head>

<body>
    <div class="contenedor-admin">
        <aside class="sidebar-admin">
            <div class="logo-admin">
                <img src="../../archivos_estaticos/img/diamanteblanco.png" alt="Aranzábal">
                <h2>Aranzábal</h2>
                <p>Panel de Administración</p>
            </div>
            <nav class="menu-admin">
                <ul>
                    <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i>Resumen</a></li>
                    <li><a href="admin_producto.php"><i class="fas fa-box"></i>Productos</a></li>
                    <li><a href="admin-pedidos.php" class="activo"><i class="fas fa-shopping-cart"></i>Pedidos</a></li>
                    <li><a href="admin-clientes.php"><i class="fas fa-users"></i>Clientes</a></li>
                    <li><a href="admin-inventario.php"><i class="fas fa-warehouse"></i>Inventario</a></li>
                    <li><a href="admin_reportes.php"><i class="fas fa-chart-bar"></i>Reportes</a></li>
                </ul>
            </nav>
            <div class="cerrar-sesion-admin">
                <a href="../../controladores/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</a>
            </div>
        </aside>

        <main class="contenido-admin">
            <header class="cabecera-admin">
                <h1><i class="fas fa-shopping-cart"></i> Administración de Pedidos</h1>
                <div class="usuario-admin">
                    <div class="avatar-usuario">A</div>
                    <span>Administrador</span>
                </div>
            </header>

            <div class="resumen-estadisticas">
                <div class="tarjeta-estadistica pedidos">
                    <div class="icono-estadistica">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="info-estadistica">
                        <h3>Pedidos Hoy</h3>
                        <p class="valor">15</p>
                    </div>
                </div>

                <div class="tarjeta-estadistica ventas">
                    <div class="icono-estadistica">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="info-estadistica">
                        <h3>Ventas Hoy</h3>
                        <p class="valor">S/ 1,250.75</p>
                    </div>
                </div>

                <div class="tarjeta-estadistica clientes">
                    <div class="icono-estadistica">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="info-estadistica">
                        <h3>Nuevos Clientes</h3>
                        <p class="valor">3</p>
                    </div>
                </div>

                <div class="tarjeta-estadistica productos">
                    <div class="icono-estadistica">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="info-estadistica">
                        <h3>Productos Vendidos</h3>
                        <p class="valor">42</p>
                    </div>
                </div>
            </div>

            <!-- Filtros funcionales -->
            <form method="GET" class="filtros" id="filtroForm">
                <div>
                    <label for="filtro-estado">Estado:</label>
                    <select id="filtro-estado" name="estado">
                        <option value="todos">Todos los estados</option>
                        <option value="Pendiente" <?=$filtroEstado==='Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="Procesando" <?=$filtroEstado==='Procesando' ? 'selected' : '' ?>>Procesando
                        </option>
                        <option value="Enviado" <?=$filtroEstado==='Enviado' ? 'selected' : '' ?>>Enviado</option>
                        <option value="Entregado" <?=$filtroEstado==='Entregado' ? 'selected' : '' ?>>Entregado</option>
                        <option value="Cancelado" <?=$filtroEstado==='Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <div>
                    <label for="filtro-fecha">Fecha:</label>
                    <input type="date" id="filtro-fecha" name="fecha" value="<?= $filtroFecha ?>">
                </div>

                <div>
                    <button type="submit" id="btn-filtrar">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                </div>

                <div>
                    <button type="button" id="btn-limpiar" class="btn-limpiar">
                        <i class="fas fa-times"></i> Limpiar Filtros
                    </button>
                </div>
            </form>

            <div class="contador-resultados">
                Mostrando
                <?= $result->num_rows ?> pedido(s)
            </div>

            <div class="lista-pedidos">
                <div class="lista-pedidos">
                    <table class="tabla-pedidos">
                        <thead>
                            <tr>
                                <th>ID Pedido</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                        if ($result->num_rows > 0) {
                            while ($pedido = $result->fetch_assoc()) {
                                $nombreCliente = $pedido['Nombre'] . ' ' . $pedido['Apellido'];
                                $fecha = date('d/m/Y', strtotime($pedido['FechaPedido']));
                                $total = number_format($pedido['MontoTotal'], 2);
                                $estado = $pedido['EstadoPedido'];
                                
                                // Clase CSS según el estado
                                $claseEstado = strtolower($estado);
                                
                                echo "<tr>
                                    <td>#{$pedido['PedidoID']}</td>
                                    <td>{$nombreCliente}</td>
                                    <td>{$fecha}</td>
                                    <td>S/ {$total}</td>
                                    <td><span class=\"estado {$claseEstado}\">{$estado}</span></td>
                                    <td class=\"acciones\">
                                        <button class=\"btn-accion btn-ver\" onclick=\"verPedido({$pedido['PedidoID']})\">
                                            <i class=\"fas fa-eye\"></i> Detalles
                                        </button>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan=\"6\" style=\"text-align: center; padding: 20px;\">No se encontraron pedidos con los filtros seleccionados</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
        </main>
    </div>

    <!-- Agregar esto en el modal-cuerpo, después de la tabla de productos -->
    <div id="ventaDirectaForm" style="display: none; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
        <h3>Registrar Venta Directa</h3>
        <form method="post" onsubmit="return confirm('¿Registrar esta venta directa?')">
            <input type="hidden" name="pedido_id" id="ventaPedidoId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label for="metodo_pago">Método de Pago:</label>
                    <select name="metodo_pago" id="metodo_pago" required style="width: 100%; padding: 8px;">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pago Móvil">Pago Móvil</option>
                        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                    </select>
                </div>
                <div>
                    <label for="referencia_pago">Referencia (opcional):</label>
                    <input type="text" name="referencia_pago" id="referencia_pago" style="width: 100%; padding: 8px;">
                </div>
            </div>
            <button type="submit" name="registrar_venta_directa" class="btn-accion btn-registrar" style="width: 100%;">
                <i class="fas fa-cash-register"></i> Registrar Venta Directa
            </button>
        </form>
    </div>

    <!-- Modal para ver detalles del pedido -->
    <div class="modal" id="modalPedido">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h2 id="modalTitulo">Detalles del Pedido</h2>
                <button class="cerrar-modal" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-cuerpo" id="modalContenido">
                <!-- El contenido se cargará dinámicamente aquí -->
            </div>
        </div>
    </div>

    <script>
        // Limpiar filtros
        document.getElementById('btn-limpiar').addEventListener('click', function () {
            document.getElementById('filtro-estado').value = 'todos';
            document.getElementById('filtro-fecha').value = '';
            document.getElementById('filtroForm').submit();
        });

        // Función para abrir modal con detalles del pedido
        function verPedido(pedidoId) {
            // Simulación de datos obtenidos desde PHP
            const pedidoData = {
                id: pedidoId,
                cliente: "María González",
                email: "maria.gonzalez@example.com",
                telefono: "998765432",
                direccion: "Calle Ayacucho 202, Cusco",
                fecha: "15/06/2023",
                total: "S/ 78.90",
                estado: "Procesando",
                productos: [
                    { id: 8, nombre: "Piedra Jade Verde 10mm", imagen: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM0ZGEzZjQiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSIxMCIvPjwvc3ZnPg==", precio: 15.00, cantidad: 3, subtotal: 45.00 },
                    { id: 9, nombre: "Forro de Gamuza Sintética", imagen: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM4ZTQ0YWQiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cmVjdCB4PSIzIiB5PSIzIiB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHJ4PSIyIiByeT0iMiIvPjwvc3ZnPg==", precio: 25.00, cantidad: 1, subtotal: 25.00 },
                    { id: 4, nombre: "Set de Cierres de Langosta", imagen: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM2YzY1N2QiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTIgM2g3YTIgMiAwIDAgMSAyIDJ2MTRhMiAyIDAgMCAxLTIgMkg1YTIgMiAwIDAgMS0yLTJ2LTciLz48cGF0aCBkPSJNMTggM3Y0YTIgMiAwIDAgMS0yIDJIOGEyIDIgMCAwIDEtMi0yVjMiLz48cGF0aCBkPSJNMyAxMGg0Ii8+PC9zdmc+", precio: 7.50, cantidad: 1, subtotal: 7.50 },
                    { id: 6, nombre: "Perlas de Río Cultivadas", imagen: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiMwMGMwZmYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSIxMCIvPjwvc3ZnPg==", precio: 3.50, cantidad: 0.4, subtotal: 1.40 }
                ]
            };

            // Construir el contenido HTML
            let contenido = `
                <div class="info-pedido">
                    <div class="info-item">
                        <strong>Cliente</strong>
                        <div>${pedidoData.cliente}</div>
                    </div>
                    <div class="info-item">
                        <strong>Email</strong>
                        <div>${pedidoData.email}</div>
                    </div>
                    <div class="info-item">
                        <strong>Teléfono</strong>
                        <div>${pedidoData.telefono}</div>
                    </div>
                    <div class="info-item">
                        <strong>Dirección de Envío</strong>
                        <div>${pedidoData.direccion}</div>
                    </div>
                    <div class="info-item">
                        <strong>Fecha del Pedido</strong>
                        <div>${pedidoData.fecha}</div>
                    </div>
                    <div class="info-item">
                        <strong>Total</strong>
                        <div>${pedidoData.total}</div>
                    </div>
                    <div class="info-item">
                        <strong>Estado</strong>
                        <div><span class="estado ${pedidoData.estado.toLowerCase()}">${pedidoData.estado}</span></div>
                    </div>
                </div>
                
                <h3>Productos</h3>
                <table class="tabla-productos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio Unitario</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>`;

            pedidoData.productos.forEach(producto => {
                contenido += `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <img src="${producto.imagen}" alt="${producto.nombre}">
                                <span>${producto.nombre}</span>
                            </div>
                        </td>
                        <td>S/ ${producto.precio.toFixed(2)}</td>
                        <td>${producto.cantidad}</td>
                        <td>S/ ${producto.subtotal.toFixed(2)}</td>
                    </tr>`;
            });

            contenido += `
                    </tbody>
                </table>
                
                <div class="acciones-modal">
                    <form class="form-cambiar-estado" method="post" onsubmit="return confirm('¿Estás seguro de cambiar el estado de este pedido?')">
                        <input type="hidden" name="pedido_id" value="${pedidoData.id}">
                        <select name="nuevo_estado">
                            <option value="Pendiente" ${pedidoData.estado === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="Procesando" ${pedidoData.estado === 'Procesando' ? 'selected' : ''}>Procesando</option>
                            <option value="Enviado" ${pedidoData.estado === 'Enviado' ? 'selected' : ''}>Enviado</option>
                            <option value="Entregado" ${pedidoData.estado === 'Entregado' ? 'selected' : ''}>Entregado</option>
                            <option value="Cancelado" ${pedidoData.estado === 'Cancelado' ? 'selected' : ''}>Cancelado</option>
                        </select>
                        <button type="submit" name="cambiar_estado" class="btn-accion btn-editar">
                            <i class="fas fa-sync-alt"></i> Cambiar Estado
                        </button>
                    </form>
                    
                    <form method="post" onsubmit="return confirm('¿Registrar salida de inventario para este pedido? Esta acción disminuirá el stock de los productos.')">
                        <input type="hidden" name="pedido_id" value="${pedidoData.id}">
                        <button type="submit" name="registrar_salida" class="btn-accion btn-registrar">
                            <i class="fas fa-check-circle"></i> Registrar Salida
                        </button>
                    </form>
                </div>`;
                // Después de construir el contenido, agregar:
    if (pedidoData.estado === 'Cancelado') {
        contenido += `
            <div class="acciones-modal">
                <button onclick="mostrarVentaDirecta(${pedidoData.id})" class="btn-accion btn-registrar">
                    <i class="fas fa-cash-register"></i> Registrar Venta Directa
                </button>
            </div>
        `;
    }

            // Actualizar modal
            document.getElementById('modalTitulo').textContent = `Pedido #${pedidoData.id}`;
            document.getElementById('modalContenido').innerHTML = contenido;
            document.getElementById('modalPedido').style.display = 'flex';

            
        }

        // Nueva función para mostrar el formulario de venta directa
function mostrarVentaDirecta(pedidoId) {
    document.getElementById('ventaPedidoId').value = pedidoId;
    document.getElementById('ventaDirectaForm').style.display = 'block';
    window.scrollTo(0, document.body.scrollHeight);
}

        // Función para cerrar modal
        function cerrarModal() {
            document.getElementById('modalPedido').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera del contenido
        window.onclick = function (event) {
            const modal = document.getElementById('modalPedido');
            if (event.target === modal) {
                cerrarModal();
            }
        };
    </script>
</body>

</html>