<?php
session_start();
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Incluir archivo de conexión
require_once '../../configuracion/conexion.php';

// Variables para manejar acciones
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agregar'])) {
        // Lógica para agregar nuevo producto al inventario
        $productoID = intval($_POST['producto']);
        $cantidad = intval($_POST['cantidad']);
        $minimo = intval($_POST['minimo']);
        $maximo = intval($_POST['maximo']);
        $ubicacion = $conn->real_escape_string($_POST['ubicacion']);
        
        $sql = "INSERT INTO Inventario (ProductoID, CantidadDisponible, CantidadMinima, CantidadMaxima, Ubicacion) 
                VALUES ($productoID, $cantidad, $minimo, $maximo, '$ubicacion')";
        
        if ($conn->query($sql)) {
            $mensaje = "Producto agregado al inventario correctamente";
        } else {
            $error = "Error al agregar producto: " . $conn->error;
        }
    } elseif (isset($_POST['actualizar'])) {
        // Lógica para actualizar inventario
        $inventarioID = intval($_POST['inventario_id']);
        $cantidad = intval($_POST['cantidad']);
        $minimo = intval($_POST['minimo']);
        $maximo = intval($_POST['maximo']);
        $ubicacion = $conn->real_escape_string($_POST['ubicacion']);
        
        $sql = "UPDATE Inventario SET 
                CantidadDisponible = $cantidad,
                CantidadMinima = $minimo,
                CantidadMaxima = $maximo,
                Ubicacion = '$ubicacion'
                WHERE InventarioID = $inventarioID";
        
        if ($conn->query($sql)) {
            $mensaje = "Inventario actualizado correctamente";
        } else {
            $error = "Error al actualizar inventario: " . $conn->error;
        }
    }
} elseif ($accion == 'eliminar' && $id > 0) {
    $sql = "DELETE FROM Inventario WHERE InventarioID = $id";
    if ($conn->query($sql)) {
        $mensaje = "Registro de inventario eliminado correctamente";
    } else {
        $error = "Error al eliminar registro: " . $conn->error;
    }
}

// Obtener categorías para el filtro
$categorias = [];
$sqlCategorias = "SELECT * FROM Categorias";
$resultCategorias = $conn->query($sqlCategorias);
if ($resultCategorias->num_rows > 0) {
    while ($row = $resultCategorias->fetch_assoc()) {
        $categorias[$row['CategoriaID']] = $row['NombreCategoria'];
    }
}

// Obtener productos para el formulario de agregar
$productos = [];
$sqlProductos = "SELECT ProductoID, NombreProducto FROM Productos";
$resultProductos = $conn->query($sqlProductos);
if ($resultProductos->num_rows > 0) {
    while ($row = $resultProductos->fetch_assoc()) {
        $productos[$row['ProductoID']] = $row['NombreProducto'];
    }
}

// Obtener inventario con filtro de categoría
$filtroCategoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$sql = "SELECT i.InventarioID, i.ProductoID, i.CantidadDisponible, i.CantidadReservada, 
        i.CantidadMinima, i.CantidadMaxima, i.Ubicacion, 
        p.NombreProducto, p.Precio, 
        c.NombreCategoria,
        (SELECT pr.NombreProveedor 
         FROM ProductosProveedores pp 
         JOIN Proveedores pr ON pp.ProveedorID = pr.ProveedorID 
         WHERE pp.ProductoID = p.ProductoID AND pp.EsPrincipal = 1 LIMIT 1) AS ProveedorPrincipal
        FROM Inventario i
        JOIN Productos p ON i.ProductoID = p.ProductoID
        JOIN Categorias c ON p.CategoriaID = c.CategoriaID
        WHERE ($filtroCategoria = 0 OR p.CategoriaID = $filtroCategoria)";


// Obtener término de búsqueda
$busqueda = isset($_GET['busqueda']) ? $conn->real_escape_string($_GET['busqueda']) : '';

// Modificar la consulta SQL para incluir la búsqueda
$sql = "SELECT i.InventarioID, i.ProductoID, i.CantidadDisponible, i.CantidadReservada, 
        i.CantidadMinima, i.CantidadMaxima, i.Ubicacion, 
        p.NombreProducto, p.Precio, 
        c.NombreCategoria,
        (SELECT pr.NombreProveedor 
         FROM ProductosProveedores pp 
         JOIN Proveedores pr ON pp.ProveedorID = pr.ProveedorID 
         WHERE pp.ProductoID = p.ProductoID AND pp.EsPrincipal = 1 LIMIT 1) AS ProveedorPrincipal
        FROM Inventario i
        JOIN Productos p ON i.ProductoID = p.ProductoID
        JOIN Categorias c ON p.CategoriaID = c.CategoriaID
        WHERE ($filtroCategoria = 0 OR p.CategoriaID = $filtroCategoria)
        AND (p.NombreProducto LIKE '%$busqueda%' 
             OR c.NombreCategoria LIKE '%$busqueda%'
             OR i.Ubicacion LIKE '%$busqueda%')";


$result = $conn->query($sql);
$inventario = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $inventario[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Inventario | Aranzábal</title>

    <link rel="stylesheet" href="../../archivos_estaticos/css/responsivo_admin.css">

    <link rel="stylesheet" href="../../archivos_estaticos/css/admin_inventario.css">
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
        .contenido-principal-admin {
            width: 100%;
        }
        .tabla-inventario {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* Para un scroll suave en iOS */
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
            <!-- 1. Se agrega el <span> a todos los textos -->
            <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i> <span>Resumen</span></a></li>
            <li><a href="admin_producto.php"><i class="fas fa-box"></i> <span>Productos</span></a></li>
            <li><a href="admin-pedidos.php"><i class="fas fa-shopping-cart"></i> <span>Pedidos</span></a></li>
            <li><a href="admin-clientes.php"><i class="fas fa-users"></i> <span>Clientes</span></a></li>
            <!-- 2. Se mueve la clase "activo" al enlace correcto (Inventario) -->
            <li><a href="admin-inventario.php" class="activo"><i class="fas fa-warehouse"></i> <span>Inventario</span></a></li>
            <li><a href="admin_reportes.php"><i class="fas fa-chart-bar"></i> <span>Reportes</span></a></li>
        </ul>
    </nav>
    <div class="cerrar-sesion-admin">
        <!-- 3. Se unifica el botón de cerrar sesión para usar un ícono y un span -->
        <a href="../../controladores/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a>
    </div>
</aside>

        <main class="contenido-admin">
            <header class="cabecera-admin">
                <div class="buscador-admin">
                    <form method="GET" action="admin-inventario.php">
                        <input type="text" name="busqueda" placeholder="Buscar producto..." value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : ''; ?>">
                        <button type="submit">
                            Buscar
                        </button>
                    </form>
                </div>
                <div class="usuario-admin">
                    <div class="avatar-usuario">A</div>
                    <span>Administrador</span>
                </div>
            </header>

            <div class="contenido-principal-admin">
                <h1>Administración de Inventario</h1>
                
                <?php if ($error): ?>
                    <div class="mensaje error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($mensaje): ?>
                    <div class="mensaje success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                
                <div class="filtros">
                    <div class="filtro-categorias">
                        <label for="categoria">Filtrar por categoría:</label>
                        <select id="categoria" onchange="filtrarCategoria()">
                            <option value="0">Todas las categorías</option>
                            <?php foreach ($categorias as $id => $nombre): ?>
                                <option value="<?php echo $id; ?>" <?php echo $filtroCategoria == $id ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn-agregar" onclick="mostrarFormularioAgregar()">+ Agregar Nuevo</button>
                </div>
                
                <div id="formularioAgregar" class="contenedor-formulario">
                    <h2>Agregar Producto al Inventario</h2>
                    <form method="POST" action="admin-inventario.php">
                        <div class="form-group">
                            <label for="producto">Producto</label>
                            <select id="producto" name="producto" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($productos as $id => $nombre): ?>
                                    <option value="<?php echo $id; ?>"><?php echo $nombre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="cantidad">Cantidad Disponible</label>
                            <input type="number" id="cantidad" name="cantidad" min="0" value="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="minimo">Cantidad Mínima</label>
                            <input type="number" id="minimo" name="minimo" min="0" value="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="maximo">Cantidad Máxima</label>
                            <input type="number" id="maximo" name="maximo" min="0" value="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="ubicacion">Ubicación en Almacén</label>
                            <input type="text" id="ubicacion" name="ubicacion" required>
                        </div>
                        
                        <button type="submit" name="agregar">Guardar</button>
                        <button type="button" class="btn-cancelar" onclick="ocultarFormulario()">Cancelar</button>
                    </form>
                </div>
                
                <div id="formularioEditar" class="contenedor-formulario">
                    <h2>Editar Registro de Inventario</h2>
                    <form method="POST" action="admin-inventario.php">
                        <input type="hidden" id="inventario_id" name="inventario_id">
                        <div class="form-group">
                            <label>Producto</label>
                            <input type="text" id="editar_producto" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_cantidad">Cantidad Disponible</label>
                            <input type="number" id="editar_cantidad" name="cantidad" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_minimo">Cantidad Mínima</label>
                            <input type="number" id="editar_minimo" name="minimo" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_maximo">Cantidad Máxima</label>
                            <input type="number" id="editar_maximo" name="maximo" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_ubicacion">Ubicación en Almacén</label>
                            <input type="text" id="editar_ubicacion" name="ubicacion" required>
                        </div>
                        
                        <button type="submit" name="actualizar">Actualizar</button>
                        <button type="button" class="btn-cancelar" onclick="ocultarFormulario()">Cancelar</button>
                    </form>
                </div>
                
                <div class="tabla-inventario">
                    <h2>Inventario Actual</h2>
                    <table class="tabla-pedidos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Proveedor Principal</th>
                                <th>Disponible</th>
                                <th>Reservada</th>
                                <th>Estado</th>
                                <th>Mínima</th>
                                <th>Máxima</th>
                                <th>Ubicación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($inventario) > 0): ?>
                                <?php foreach ($inventario as $item): ?>
                                    <?php
                                    // Determinar estado del inventario
                                    $disponible = $item['CantidadDisponible'];
                                    $minimo = $item['CantidadMinima'];
                                    $maximo = $item['CantidadMaxima'];
                                    
                                    $estado = '';
                                    $claseEstado = '';
                                    
                                    if ($disponible <= $minimo) {
                                        $estado = 'Bajo';
                                        $claseEstado = 'estado-bajo';
                                    } elseif ($disponible >= $maximo) {
                                        $estado = 'Alto';
                                        $claseEstado = 'estado-alto';
                                    } else {
                                        $estado = 'Óptimo';
                                        $claseEstado = 'estado-optimo';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $item['InventarioID']; ?></td>
                                        <td><?php echo $item['NombreProducto']; ?></td>
                                        <td><?php echo $item['NombreCategoria']; ?></td>
                                        <td><?php echo $item['ProveedorPrincipal'] ?? 'N/A'; ?></td>
                                        <td><?php echo $disponible; ?></td>
                                        <td><?php echo $item['CantidadReservada']; ?></td>
                                        <td><span class="estado-inventario <?php echo $claseEstado; ?>"><?php echo $estado; ?></span></td>
                                        <td><?php echo $minimo; ?></td>
                                        <td><?php echo $maximo; ?></td>
                                        <td><?php echo $item['Ubicacion']; ?></td>
                                        <td class="acciones-inventario">
                                            <button class="boton-accion editar" onclick="editarInventario(
                                                <?php echo $item['InventarioID']; ?>,
                                                '<?php echo $item['NombreProducto']; ?>',
                                                <?php echo $disponible; ?>,
                                                <?php echo $item['CantidadReservada']; ?>,
                                                <?php echo $minimo; ?>,
                                                <?php echo $maximo; ?>,
                                                '<?php echo $item['Ubicacion']; ?>'
                                            )">Editar</button>
                                            <a href="admin-inventario.php?accion=eliminar&id=<?php echo $item['InventarioID']; ?>" class="btn-eliminar" onclick="return confirm('¿Está seguro de eliminar este registro?');">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11">No se encontraron registros de inventario.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../../archivos_estaticos/js/admin.js"></script>
    <script>
        function mostrarFormularioAgregar() {
            document.getElementById('formularioAgregar').classList.add('activo');
            document.getElementById('formularioEditar').classList.remove('activo');
        }
        
        function ocultarFormulario() {
            document.getElementById('formularioAgregar').classList.remove('activo');
            document.getElementById('formularioEditar').classList.remove('activo');
        }
        
        function editarInventario(id, producto, disponible, reservada, minimo, maximo, ubicacion) {
            document.getElementById('inventario_id').value = id;
            document.getElementById('editar_producto').value = producto;
            document.getElementById('editar_cantidad').value = disponible;
            document.getElementById('editar_minimo').value = minimo;
            document.getElementById('editar_maximo').value = maximo;
            document.getElementById('editar_ubicacion').value = ubicacion;
            
            document.getElementById('formularioEditar').classList.add('activo');
            document.getElementById('formularioAgregar').classList.remove('activo');
            
            // Scroll al formulario
            document.getElementById('formularioEditar').scrollIntoView({behavior: 'smooth'});
        }
        
        function filtrarCategoria() {
            const categoriaId = document.getElementById('categoria').value;
            window.location.href = `admin-inventario.php?categoria=${categoriaId}`;
        }
        
        // Ocultar formularios si hay un mensaje de éxito o error
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($mensaje || $error): ?>
                document.getElementById('formularioAgregar').classList.remove('activo');
                document.getElementById('formularioEditar').classList.remove('activo');
            <?php endif; ?>
        });
    </script>
</body>
</html>