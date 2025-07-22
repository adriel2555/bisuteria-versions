<?php
// Iniciar sesión y verificar si es administrador
session_start();
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Incluir archivo de conexión
require_once '../../configuracion/conexion.php';

// Variables para mensajes
$mensaje = '';
$tipoMensaje = '';

// Procesar nuevo producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_producto'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $categoria = $_POST['categoria'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $imagen = $_POST['imagen'];
    
    $sql = "INSERT INTO Productos (NombreProducto, Descripcion, CategoriaID, Precio, CantidadStock, UrlImagen) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssidss", $nombre, $descripcion, $categoria, $precio, $stock, $imagen);
    
    if ($stmt->execute()) {
        $mensaje = "Producto registrado exitosamente!";
        $tipoMensaje = "success";
        
        // Registrar entrada en inventario
        $productoId = $stmt->insert_id;
        $sqlEntrada = "INSERT INTO EntradasInventario (ProductoID, ProveedorID, Cantidad, PrecioUnitario, UsuarioResponsable, Notas) 
                       VALUES (?, 1, ?, ?, 'Admin Maestro', 'Registro inicial')";
        $stmtEntrada = $conn->prepare($sqlEntrada);
        $stmtEntrada->bind_param("iid", $productoId, $stock, $precio);
        $stmtEntrada->execute();
    } else {
        $mensaje = "Error al registrar producto: " . $stmt->error;
        $tipoMensaje = "error";
    }
    $stmt->close();
}

// Procesar actualización de stock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_stock'])) {
    $productoId = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $motivo = $_POST['motivo'];
    $notas = $_POST['notas'];
    
    // Obtener información del producto
    $sqlProducto = "SELECT Precio FROM Productos WHERE ProductoID = ?";
    $stmtProducto = $conn->prepare($sqlProducto);
    $stmtProducto->bind_param("i", $productoId);
    $stmtProducto->execute();
    $resultProducto = $stmtProducto->get_result();
    $producto = $resultProducto->fetch_assoc();
    
    // Actualizar stock
    $sqlUpdate = "UPDATE Productos SET CantidadStock = CantidadStock + ? WHERE ProductoID = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ii", $cantidad, $productoId);
    
    if ($stmtUpdate->execute()) {
        $mensaje = "Stock actualizado exitosamente!";
        $tipoMensaje = "success";
        
        // Registrar entrada/salida en inventario
        $tipo = ($cantidad > 0) ? 'Entrada' : 'Salida';
        $precioUnitario = $producto['Precio'];
        
        if ($cantidad > 0) {
            // Entrada de inventario
            $sqlEntrada = "INSERT INTO EntradasInventario (ProductoID, ProveedorID, Cantidad, PrecioUnitario, UsuarioResponsable, Notas) 
                           VALUES (?, 1, ?, ?, 'Admin Maestro', ?)";
            $stmtEntrada = $conn->prepare($sqlEntrada);
            $stmtEntrada->bind_param("iids", $productoId, $cantidad, $precioUnitario, $notas);
            $stmtEntrada->execute();
        } else {
            // Salida de inventario
            $cantidadAbs = abs($cantidad);
            $sqlSalida = "INSERT INTO SalidasInventario (ProductoID, Cantidad, TipoSalida, UsuarioResponsable, Notas) 
                          VALUES (?, ?, ?, 'Admin Maestro', ?)";
            $stmtSalida = $conn->prepare($sqlSalida);
            $stmtSalida->bind_param("iiss", $productoId, $cantidadAbs, $motivo, $notas);
            $stmtSalida->execute();
        }
    } else {
        $mensaje = "Error al actualizar stock: " . $stmtUpdate->error;
        $tipoMensaje = "error";
    }
    $stmtUpdate->close();
}

// Obtener productos existentes
$sqlProductos = "SELECT p.*, c.NombreCategoria 
                 FROM Productos p 
                 JOIN Categorias c ON p.CategoriaID = c.CategoriaID";
$resultProductos = $conn->query($sqlProductos);

// Obtener categorías
$sqlCategorias = "SELECT * FROM Categorias";
$resultCategorias = $conn->query($sqlCategorias);

// Obtener reporte de compras
$sqlCompras = "SELECT e.EntradaID, p.NombreProducto, e.Cantidad, pr.NombreProveedor, e.PrecioUnitario, e.FechaEntrada 
               FROM EntradasInventario e 
               JOIN Productos p ON e.ProductoID = p.ProductoID 
               JOIN Proveedores pr ON e.ProveedorID = pr.ProveedorID 
               ORDER BY e.FechaEntrada DESC 
               LIMIT 10";
$resultCompras = $conn->query($sqlCompras);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos | Aranzábal</title>
    <link rel="stylesheet" href="../../archivos_estaticos/css/admin_productos.css">
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
                    <li><a href="admin_producto.php" class="activo"><i class="fas fa-box"></i>Productos</a></li>
                    <li><a href="admin-pedidos.php" ><i class="fas fa-shopping-cart"></i>Pedidos</a></li>
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
                <div></div> <!-- Espacio vacío para alinear a la derecha -->
                <div class="usuario-admin">
                    <div class="avatar-usuario">A</div>
                    <span>Administrador</span>
                </div>
            </header>

            <div class="contenido-principal-admin">
                <h1>Gestión de Productos</h1>
                
                <!-- Mensajes de estado -->
                <?php if ($mensaje): ?>
                    <div class="message <?php echo $tipoMensaje; ?>">
                        <div><?php echo $tipoMensaje == 'success' ? '✅' : '❌'; ?></div>
                        <div><?php echo $mensaje; ?></div>
                    </div>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="tabs">
                    <div class="tab active" onclick="showTab('products')">Productos Existentes</div>
                    <div class="tab" onclick="showTab('new-product')">Registrar Nuevo Producto</div>
                    <div class="tab" onclick="showTab('update-stock')">Actualizar Stock</div>
                </div>
                
                <!-- Sección de Productos Existentes -->
                <div class="tab-content active" id="products-tab">
                    <div class="form-group">
                        <input type="text" class="form-control" placeholder="Buscar productos..." id="search-product">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($producto = $resultProductos->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($producto['UrlImagen']): ?>
                                            <img src="<?php echo $producto['UrlImagen']; ?>" alt="Producto" class="product-img">
                                        <?php else: ?>
                                            <div class="product-img" style="display: flex; align-items: center; justify-content: center; background: #f0f0f0;">📦</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['NombreProducto']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['NombreCategoria']); ?></td>
                                    <td>S/ <?php echo number_format($producto['Precio'], 2); ?></td>
                                    <td class="<?php echo $producto['CantidadStock'] < 20 ? 'stock-low' : 'stock-ok'; ?>">
                                        <?php echo $producto['CantidadStock']; ?>
                                    </td>
                                    <td>
                                        <?php if ($producto['EstaActivo']): ?>
                                            <span style="color: #43a047; font-weight: bold;">Activo</span>
                                        <?php else: ?>
                                            <span style="color: #e53935; font-weight: bold;">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="message info">
                        <div>ℹ️</div>
                        <div>Mostrando todos los productos disponibles. Los productos con stock bajo están resaltados en rojo.</div>
                    </div>
                </div>
                
                <!-- Sección de Nuevo Producto -->
                <div class="tab-content" id="new-product-tab">
                    <div class="message info">
                        <div>ℹ️</div>
                        <div>Completa todos los campos para registrar un nuevo producto en el inventario.</div>
                    </div>
                    
                    <form id="product-form" method="POST" action="">
                        <div class="form-container">
                            <div class="form-section">
                                <div class="form-group">
                                    <label for="product-name">Nombre del Producto *</label>
                                    <input type="text" class="form-control" id="product-name" name="nombre" placeholder="Ej: Perlas de Río Cultivadas 8mm" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="product-category">Categoría *</label>
                                    <select class="form-control" id="product-category" name="categoria" required>
                                        <option value="">Seleccione una categoría</option>
                                        <?php while($categoria = $resultCategorias->fetch_assoc()): ?>
                                            <option value="<?php echo $categoria['CategoriaID']; ?>">
                                                <?php echo htmlspecialchars($categoria['NombreCategoria']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="product-description">Descripción</label>
                                    <textarea class="form-control" id="product-description" name="descripcion" rows="3" placeholder="Descripción detallada del producto"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-group">
                                    <label for="product-price">Precio (S/) *</label>
                                    <input type="number" class="form-control" id="product-price" name="precio" min="0" step="0.01" placeholder="Ej: 15.50" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="product-stock">Stock Inicial *</label>
                                    <input type="number" class="form-control" id="product-stock" name="stock" min="0" placeholder="Ej: 100" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="product-image">Imagen del Producto (URL)</label>
                                    <input type="text" class="form-control" id="product-image" name="imagen" placeholder="https://...">
                                </div>
                                
                                <button type="submit" name="registrar_producto" class="btn pulse">
                                    <i>➕</i> Registrar Producto
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Sección de Actualizar Stock -->
                <div class="tab-content" id="update-stock-tab">
                    <div class="message info">
                        <div>ℹ️</div>
                        <div>Selecciona un producto existente para actualizar su stock. Usa valores positivos para aumentar stock y negativos para reducirlo.</div>
                    </div>
                    
                    <form id="stock-form" method="POST" action="">
                        <div class="form-container">
                            <div class="form-section">
                                <div class="form-group">
                                    <label for="select-product">Seleccionar Producto *</label>
                                    <select class="form-control" id="select-product" name="producto_id" required>
                                        <option value="">Seleccione un producto</option>
                                        <?php 
                                            $resultProductos->data_seek(0); // Resetear puntero
                                            while($producto = $resultProductos->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $producto['ProductoID']; ?>">
                                                <?php echo htmlspecialchars($producto['NombreProducto']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="stock-quantity">Cantidad a Añadir/Reducir *</label>
                                    <input type="number" class="form-control" id="stock-quantity" name="cantidad" placeholder="Ej: +50 o -10" required>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-group">
                                    <label for="stock-reason">Motivo *</label>
                                    <select class="form-control" id="stock-reason" name="motivo" required>
                                        <option value="">Seleccione un motivo</option>
                                        <option value="compra">Compra a proveedor</option>
                                        <option value="venta">Venta a cliente</option>
                                        <option value="ajuste">Ajuste de inventario</option>
                                        <option value="devolucion">Devolución de cliente</option>
                                        <option value="perdida">Pérdida o daño</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="stock-notes">Notas</label>
                                    <textarea class="form-control" id="stock-notes" name="notas" rows="2" placeholder="Detalles adicionales..."></textarea>
                                </div>
                                
                                <button type="submit" name="actualizar_stock" class="btn pulse">
                                    <i>🔄</i> Actualizar Stock
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Reporte de compras -->
                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <i>📊</i> Reporte de Compras Recientes
                    </h2>
                    
                    <div class="table-responsive">
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Precio Unitario</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($compra = $resultCompras->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $compra['FechaEntrada']; ?></td>
                                    <td><?php echo htmlspecialchars($compra['NombreProducto']); ?></td>
                                    <td><?php echo $compra['Cantidad']; ?></td>
                                    <td><?php echo htmlspecialchars($compra['NombreProveedor']); ?></td>
                                    <td>S/ <?php echo number_format($compra['PrecioUnitario'], 2); ?></td>
                                    <td>S/ <?php echo number_format($compra['Cantidad'] * $compra['PrecioUnitario'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <button class="btn btn-outline">
                            <i>📥</i> Exportar Reporte Completo
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../archivos_estaticos/js/admin_productos.js"></script>
</body>
</html>