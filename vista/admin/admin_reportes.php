<?php
// Iniciar sesión y verificar si es administrador
session_start();
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Incluir archivo de conexión
require_once '../../configuracion/conexion.php';

// Procesamiento de parámetros para reportes
$reporteTipo = filter_input(INPUT_GET, 'reporte', FILTER_SANITIZE_STRING) ?? 'ventas';
$fechaInicio = filter_input(INPUT_GET, 'fecha_inicio', FILTER_SANITIZE_STRING) ?? date('Y-m-01');
$fechaFin = filter_input(INPUT_GET, 'fecha_fin', FILTER_SANITIZE_STRING) ?? date('Y-m-d');
$categoriaId = filter_input(INPUT_GET, 'categoria', FILTER_SANITIZE_STRING) ?? '';

// Obtener categorías para el selector
$categorias = [];
$sqlCategorias = "SELECT CategoriaID, NombreCategoria FROM Categorias";
$resultCategorias = $conn->query($sqlCategorias);
if ($resultCategorias->num_rows > 0) {
    while($row = $resultCategorias->fetch_assoc()) {
        $categorias[$row['CategoriaID']] = $row['NombreCategoria'];
    }
}

// Generar reporte según tipo seleccionado
$reporteData = [];
$reporteTitulo = '';

switch ($reporteTipo) {
    case 'ventas':
        $reporteTitulo = 'Reporte de Ventas';
        $sql = "SELECT DATE(FechaPedido) as Fecha, SUM(MontoTotal) as TotalVentas, COUNT(*) as CantidadPedidos
                FROM Pedidos
                WHERE DATE(FechaPedido) BETWEEN '$fechaInicio' AND '$fechaFin'
                GROUP BY DATE(FechaPedido)
                ORDER BY Fecha DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $reporteData[] = $row;
            }
        }
        break;
        
    case 'productos':
        $reporteTitulo = 'Productos Más Vendidos';
        $sql = "SELECT p.NombreProducto, c.NombreCategoria, SUM(ap.Cantidad) as CantidadVendida, SUM(ap.Subtotal) as TotalVentas
                FROM ArticulosPedido ap
                JOIN Productos p ON ap.ProductoID = p.ProductoID
                JOIN Categorias c ON p.CategoriaID = c.CategoriaID
                JOIN Pedidos pd ON ap.PedidoID = pd.PedidoID
                WHERE pd.FechaPedido BETWEEN '$fechaInicio' AND '$fechaFin'
                GROUP BY p.ProductoID
                ORDER BY CantidadVendida DESC
                LIMIT 10";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $reporteData[] = $row;
            }
        }
        break;
        
    case 'categorias':
        $reporteTitulo = 'Ventas por Categoría';
        $categoriaCondicion = $categoriaId ? " AND p.CategoriaID = '$categoriaId'" : '';
        $sql = "SELECT c.NombreCategoria, SUM(ap.Subtotal) as TotalVentas, COUNT(DISTINCT pd.PedidoID) as Pedidos
                FROM ArticulosPedido ap
                JOIN Productos p ON ap.ProductoID = p.ProductoID
                JOIN Categorias c ON p.CategoriaID = c.CategoriaID
                JOIN Pedidos pd ON ap.PedidoID = pd.PedidoID
                WHERE pd.FechaPedido BETWEEN '$fechaInicio' AND '$fechaFin' $categoriaCondicion
                GROUP BY c.CategoriaID
                ORDER BY TotalVentas DESC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $reporteData[] = $row;
            }
        }
        break;
        
    case 'clientes':
        $reporteTitulo = 'Clientes Más Valiosos';
        $sql = "SELECT u.Nombre, u.Apellido, u.Email, SUM(pd.MontoTotal) as TotalCompras, COUNT(pd.PedidoID) as Pedidos
                FROM Pedidos pd
                JOIN Usuarios u ON pd.UsuarioID = u.UsuarioID
                WHERE pd.FechaPedido BETWEEN '$fechaInicio' AND '$fechaFin'
                GROUP BY u.UsuarioID
                ORDER BY TotalCompras DESC
                LIMIT 10";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $reporteData[] = $row;
            }
        }
        break;
        
    case 'inventario':
        $reporteTitulo = 'Estado de Inventario';
        $sql = "SELECT p.NombreProducto, c.NombreCategoria, i.CantidadDisponible, i.CantidadReservada, i.CantidadMinima
                FROM Inventario i
                JOIN Productos p ON i.ProductoID = p.ProductoID
                JOIN Categorias c ON p.CategoriaID = c.CategoriaID
                ORDER BY (i.CantidadDisponible - i.CantidadMinima) ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $reporteData[] = $row;
            }
        }
        break;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | Aranzábal</title>
    <link rel="stylesheet" href="../../archivos_estaticos/css/admin_reportes.css">
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
                    <li><a href="admin-pedidos.php" ><i class="fas fa-shopping-cart"></i>Pedidos</a></li>
                    <li><a href="admin-clientes.php"><i class="fas fa-users"></i>Clientes</a></li>
                    <li><a href="admin-inventario.php"><i class="fas fa-warehouse"></i>Inventario</a></li>
                    <li><a href="admin_reportes.php" class="activo"><i class="fas fa-chart-bar"></i>Reportes</a></li>
                </ul>
            </nav>
            <div class="cerrar-sesion-admin">
                <a href="../../controladores/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</a>
            </div>
        </aside>

        <main class="contenido-admin">
            <header class="cabecera-admin">
                <div>
                    <h1>Reportes de Administración</h1>
                </div>
                <div class="usuario-admin">
                    <div class="avatar-usuario">A</div>
                    <span>Administrador</span>
                </div>
            </header>

            <div class="contenido-principal-admin">
                <div class="panel-reportes">
                    <form method="GET" action="admin_reportes.php">
                        <div class="filtros-reporte">
                            <div class="filtro-grupo">
                                <label for="reporte">Tipo de Reporte</label>
                                <select id="reporte" name="reporte">
                                    <option value="ventas" <?= $reporteTipo == 'ventas' ? 'selected' : '' ?>>Ventas Diarias</option>
                                    <option value="productos" <?= $reporteTipo == 'productos' ? 'selected' : '' ?>>Productos Más Vendidos</option>
                                    <option value="categorias" <?= $reporteTipo == 'categorias' ? 'selected' : '' ?>>Ventas por Categoría</option>
                                    <option value="clientes" <?= $reporteTipo == 'clientes' ? 'selected' : '' ?>>Clientes Más Valiosos</option>
                                    <option value="inventario" <?= $reporteTipo == 'inventario' ? 'selected' : '' ?>>Estado de Inventario</option>
                                </select>
                            </div>
                            
                            <div class="filtro-grupo">
                                <label for="fecha_inicio">Fecha Inicio</label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $fechaInicio ?>">
                            </div>
                            
                            <div class="filtro-grupo">
                                <label for="fecha_fin">Fecha Fin</label>
                                <input type="date" id="fecha_fin" name="fecha_fin" value="<?= $fechaFin ?>">
                            </div>
                            
                            <?php if($reporteTipo == 'categorias'): ?>
                            <div class="filtro-grupo">
                                <label for="categoria">Categoría</label>
                                <select id="categoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach($categorias as $id => $nombre): ?>
                                        <option value="<?= $id ?>" <?= $categoriaId == $id ? 'selected' : '' ?>><?= $nombre ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                                <input type="hidden" name="categoria" value="">
                            <?php endif; ?>
                            
                            <button type="submit" class="boton-generar">Generar Reporte</button>
                        </div>
                    </form>
                    
                    <div class="resultado-reporte">
                        <div class="resultado-titulo">
                            <h2><?= $reporteTitulo ?></h2>
                            <button class="boton-exportar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                </svg>
                                Exportar a Excel
                            </button>
                        </div>
                        
                        <?php if(empty($reporteData)): ?>
                            <div class="sin-resultados">
                                <p>No se encontraron resultados con los filtros seleccionados.</p>
                            </div>
                        <?php else: ?>
                            <div class="tabla-contenedor">
                                <table class="tabla-reporte">
                                    <thead>
                                        <tr>
                                            <?php 
                                            // Encabezados dinámicos según tipo de reporte
                                            switch ($reporteTipo) {
                                                case 'ventas':
                                                    echo '<th>Fecha</th>
                                                          <th class="numero">Total Ventas</th>
                                                          <th class="numero">Pedidos</th>';
                                                    break;
                                                    
                                                case 'productos':
                                                    echo '<th>Producto</th>
                                                          <th>Categoría</th>
                                                          <th class="numero">Cantidad Vendida</th>
                                                          <th class="numero">Total Ventas</th>';
                                                    break;
                                                    
                                                case 'categorias':
                                                    echo '<th>Categoría</th>
                                                          <th class="numero">Total Ventas</th>
                                                          <th class="numero">Pedidos</th>';
                                                    break;
                                                    
                                                case 'clientes':
                                                    echo '<th>Cliente</th>
                                                          <th>Email</th>
                                                          <th class="numero">Total Compras</th>
                                                          <th class="numero">Pedidos</th>';
                                                    break;
                                                    
                                                case 'inventario':
                                                    echo '<th>Producto</th>
                                                          <th>Categoría</th>
                                                          <th class="numero">Disponible</th>
                                                          <th class="numero">Reservado</th>
                                                          <th class="numero">Mínimo</th>';
                                                    break;
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($reporteData as $fila): ?>
                                            <tr>
                                                <?php 
                                                switch ($reporteTipo) {
                                                    case 'ventas':
                                                        echo "<td>{$fila['Fecha']}</td>
                                                              <td class='numero'>S/ " . number_format($fila['TotalVentas'], 2) . "</td>
                                                              <td class='numero'>{$fila['CantidadPedidos']}</td>";
                                                        break;
                                                        
                                                    case 'productos':
                                                        echo "<td>{$fila['NombreProducto']}</td>
                                                              <td>{$fila['NombreCategoria']}</td>
                                                              <td class='numero'>{$fila['CantidadVendida']}</td>
                                                              <td class='numero'>S/ " . number_format($fila['TotalVentas'], 2) . "</td>";
                                                        break;
                                                        
                                                    case 'categorias':
                                                        echo "<td>{$fila['NombreCategoria']}</td>
                                                              <td class='numero'>S/ " . number_format($fila['TotalVentas'], 2) . "</td>
                                                              <td class='numero'>{$fila['Pedidos']}</td>";
                                                        break;
                                                        
                                                    case 'clientes':
                                                        echo "<td>{$fila['Nombre']} {$fila['Apellido']}</td>
                                                              <td>{$fila['Email']}</td>
                                                              <td class='numero'>S/ " . number_format($fila['TotalCompras'], 2) . "</td>
                                                              <td class='numero'>{$fila['Pedidos']}</td>";
                                                        break;
                                                        
                                                    case 'inventario':
                                                        $claseBajoStock = $fila['CantidadDisponible'] < $fila['CantidadMinima'] ? 'bajo-stock' : '';
                                                        echo "<td>{$fila['NombreProducto']}</td>
                                                              <td>{$fila['NombreCategoria']}</td>
                                                              <td class='numero $claseBajoStock'>{$fila['CantidadDisponible']}</td>
                                                              <td class='numero'>{$fila['CantidadReservada']}</td>
                                                              <td class='numero'>{$fila['CantidadMinima']}</td>";
                                                        break;
                                                }
                                                ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const reporteSelect = document.getElementById('reporte');
        const categoriaGroup = document.querySelector('.filtro-grupo:has(#categoria)');
        const categoriaHidden = document.querySelector('input[name="categoria"]');
        
        // Función para manejar la visibilidad del filtro de categoría
        function toggleCategoriaFiltro() {
            if (reporteSelect.value === 'categorias') {
                if (categoriaGroup) categoriaGroup.style.display = 'flex';
                if (categoriaHidden) categoriaHidden.type = 'hidden';
            } else {
                if (categoriaGroup) categoriaGroup.style.display = 'none';
                if (categoriaHidden) {
                    categoriaHidden.type = 'text';
                    categoriaHidden.value = '';
                }
            }
        }
        
        // Inicializar visibilidad
        toggleCategoriaFiltro();
        
        // Escuchar cambios en el select
        reporteSelect.addEventListener('change', toggleCategoriaFiltro);
        
        // Establecer fechas por defecto si no están definidas
        const fechaFin = document.getElementById('fecha_fin');
        const fechaInicio = document.getElementById('fecha_inicio');
        
        if (fechaFin && !fechaFin.value) {
            fechaFin.valueAsDate = new Date();
        }
        
        if (fechaInicio && !fechaInicio.value) {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            fechaInicio.valueAsDate = firstDay;
        }
    });
    </script>
</body>
</html>