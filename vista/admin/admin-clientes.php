<?php
session_start();
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Incluir archivo de conexión
require_once '../../configuracion/conexion.php';

// Obtener lista de clientes
$clientes = [];
$sql = "SELECT UsuarioID, Nombre, Apellido, Email, Telefono, EsAdministrador FROM Usuarios";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// Procesar formulario de cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['guardar_cliente'])) {
        $usuarioId = $_POST['clienteId'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';
        $departamento = $_POST['departamento'] ?? '';
        $codigoPostal = $_POST['codigoPostal'] ?? '';
        $estado = $_POST['estado'] ?? 1;
        
        if (empty($usuarioId)) {
            // Insertar nuevo cliente
            $stmt = $conn->prepare("INSERT INTO Usuarios (Nombre, Apellido, Email, Telefono, Direccion, Ciudad, Departamento, CodigoPostal, EsAdministrador) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssi", $nombre, $apellido, $email, $telefono, $direccion, $ciudad, $departamento, $codigoPostal, $estado);
        } else {
            // Actualizar cliente existente
            $stmt = $conn->prepare("UPDATE Usuarios SET Nombre=?, Apellido=?, Email=?, Telefono=?, Direccion=?, Ciudad=?, Departamento=?, CodigoPostal=?, EsAdministrador=? WHERE UsuarioID=?");
            $stmt->bind_param("ssssssssii", $nombre, $apellido, $email, $telefono, $direccion, $ciudad, $departamento, $codigoPostal, $estado, $usuarioId);
        }
        
        if ($stmt->execute()) {
            $mensajeExito = "Cliente " . (empty($usuarioId) ? "creado" : "actualizado") . " correctamente.";
            // Recargar lista de clientes
            $result = $conn->query($sql);
            $clientes = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $clientes[] = $row;
                }
            }
        } else {
            $mensajeError = "Error al guardar el cliente: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['eliminar_cliente'])) {
        $usuarioId = $_POST['clienteId'] ?? '';
        $stmt = $conn->prepare("DELETE FROM Usuarios WHERE UsuarioID=?");
        $stmt->bind_param("i", $usuarioId);
        if ($stmt->execute()) {
            $mensajeExito = "Cliente eliminado correctamente.";
            // Recargar lista de clientes
            $result = $conn->query($sql);
            $clientes = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $clientes[] = $row;
                }
            }
        } else {
            $mensajeError = "Error al eliminar el cliente: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['cambiar_contrasena'])) {
        $usuarioId = $_POST['clienteId'] ?? '';
        $nuevaContrasena = password_hash($_POST['nueva_contrasena'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE Usuarios SET ContrasenaHash=? WHERE UsuarioID=?");
        $stmt->bind_param("si", $nuevaContrasena, $usuarioId);
        if ($stmt->execute()) {
            $mensajeExito = "Contraseña actualizada correctamente.";
        } else {
            $mensajeError = "Error al actualizar la contraseña: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes | Aranzábal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos generales */
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

        /* Sidebar */
        .sidebar-admin {
            width: 250px;
            background: linear-gradient(to bottom, #2c3e50, #1a2530);
            color: white;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .logo-admin {
            text-align: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo-admin img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .logo-admin h2 {
            margin: 0;
            font-size: 1.2rem;
        }

        .logo-admin p {
            margin: 5px 0 0;
            font-size: 0.8rem;
            color: #bdc3c7;
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
            background-color: rgba(255,255,255,0.1);
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
            border-top: 1px solid rgba(255,255,255,0.1);
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

        /* Contenido principal */
        .contenido-admin {
            flex: 1;
            background-color: #f5f6fa;
            overflow-y: auto;
        }

        .cabecera-admin {
            background-color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .buscador-admin {
            display: flex;
            align-items: center;
            background: #f0f2f5;
            border-radius: 30px;
            padding: 5px 15px;
            width: 300px;
        }

        .buscador-admin input {
            padding: 8px;
            background: transparent;
            border: none;
            width: 100%;
            outline: none;
        }

        .buscador-admin button {
            background: none;
            border: none;
            cursor: pointer;
            color: #7f8c8d;
        }

        .usuario-admin {
            display: flex;
            align-items: center;
        }

        .usuario-admin .avatar-usuario {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 10px;
            background: #8e44ad;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .contenido-principal-admin {
            padding: 25px;
        }

        .contenido-principal-admin h1 {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: #2c3e50;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Mensajes */
        .mensaje {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
            display: none;
            transition: all 0.3s ease;
        }

        .mensaje.mostrar {
            display: block;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mensaje-exito {
            background-color: #d5f5e3;
            color: #27ae60;
            border: 1px solid #a3e4b9;
        }

        .mensaje-error {
            background-color: #ffebee;
            color: #e53935;
            border: 1px solid #f8c5c5;
        }

        /* Formulario */
        .formulario-cliente {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            max-height: 1000px;
            overflow: hidden;
        }

        .formulario-cliente.oculto {
            max-height: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-bottom: 0;
            opacity: 0;
        }

        .formulario-contrasena {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
        }

        .formulario-contrasena.mostrar {
            max-height: 500px;
            opacity: 1;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #8e44ad;
            outline: none;
            box-shadow: 0 0 0 2px rgba(142, 68, 173, 0.2);
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-col {
            flex: 1;
        }

        .boton-principal {
            background: #8e44ad;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 1rem;
        }

        .boton-principal:hover {
            background: #732d91;
        }

        .boton-secundario {
            background: #f5f6fa;
            color: #7f8c8d;
            border: 1px solid #ddd;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            margin-left: 10px;
        }

        .boton-secundario:hover {
            background: #e0e0e0;
        }

        /* Tabla de clientes */
        .tabla-clientes {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        .tabla-clientes h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-datos th, .tabla-datos td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .tabla-datos th {
            background-color: #f8f9fa;
            color: #7f8c8d;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .tabla-datos tr:hover {
            background-color: #f9f9f9;
        }

        .estado-cliente {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .activo {
            background-color: #d5f5e3;
            color: #27ae60;
        }

        .inactivo {
            background-color: #ffebee;
            color: #e53935;
        }

        .boton-accion {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            margin-right: 5px;
            color: #7f8c8d;
            transition: color 0.3s;
        }

        .boton-accion:hover {
            color: #8e44ad;
        }

        /* Responsivo */
        @media (max-width: 1024px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        @media (max-width: 768px) {
            .sidebar-admin {
                width: 70px;
            }
            
            .logo-admin h2, .logo-admin p, .menu-admin li a span, .cerrar-sesion-admin a span {
                display: none;
            }
            
            .logo-admin {
                padding: 10px;
            }
            
            .menu-admin li a {
                justify-content: center;
            }
            
            .menu-admin li a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .contenido-admin {
                margin-left: 70px;
            }
            
            .cabecera-admin {
                flex-direction: column;
                gap: 15px;
            }
            
            .buscador-admin {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .contenido-principal-admin {
                padding: 15px;
            }
            
            .formulario-cliente, .tabla-clientes {
                padding: 15px;
            }
            
            .boton-principal, .boton-secundario {
                width: 100%;
                margin: 5px 0;
            }
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
                    <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i> <span>Resumen</span></a></li>
                    <li><a href="admin_producto.php"><i class="fas fa-box"></i> <span>Productos</span></a></li>
                    <li><a href="admin-pedidos.php"><i class="fas fa-shopping-cart"></i> <span>Pedidos</span></a></li>
                    <li><a href="admin-clientes.php" class="activo"><i class="fas fa-users"></i> <span>Clientes</span></a></li>
                    <li><a href="admin-inventario.php"><i class="fas fa-warehouse"></i> <span>Inventario</span></a></li>
                    <li><a href="admin_reportes.php"><i class="fas fa-chart-bar"></i> <span>Reportes</span></a></li>
                </ul>
            </nav>
            <div class="cerrar-sesion-admin">
                <a href="../../controladores/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a>
            </div>
        </aside>

        <main class="contenido-admin">
            <header class="cabecera-admin">
                <div class="buscador-admin">
                    <input type="text" placeholder="Buscar cliente..." id="inputBuscar">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="usuario-admin">
                    <div class="avatar-usuario">A</div>
                    <span>Administrador</span>
                </div>
            </header>

            <div class="contenido-principal-admin">
                <h1>
                    Gestión de Clientes
                    <button class="boton-principal" id="btnNuevoCliente">
                        <i class="fas fa-plus"></i> Nuevo Cliente
                    </button>
                </h1>
                
                <?php if (isset($mensajeExito)): ?>
                <div class="mensaje-exito mensaje mostrar" id="mensajeExito">
                    <?php echo $mensajeExito; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($mensajeError)): ?>
                <div class="mensaje-error mensaje mostrar" id="mensajeError">
                    <?php echo $mensajeError; ?>
                </div>
                <?php endif; ?>
                
                <div class="formulario-cliente <?php echo isset($_POST['clienteId']) ? '' : 'oculto'; ?>" id="formularioCliente">
                    <form id="clienteForm" method="POST">
                        <input type="hidden" id="clienteId" name="clienteId">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono">
                        </div>
                        
                        <div class="form-group">
                            <label for="direccion">Dirección</label>
                            <input type="text" id="direccion" name="direccion">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="ciudad">Ciudad</label>
                                    <input type="text" id="ciudad" name="ciudad">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="departamento">Departamento</label>
                                    <input type="text" id="departamento" name="departamento">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="codigoPostal">Código Postal</label>
                                    <input type="text" id="codigoPostal" name="codigoPostal">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="boton-principal" name="guardar_cliente">Guardar Cliente</button>
                        <button type="button" class="boton-secundario" id="btnCancelar">Cancelar</button>
                    </form>
                </div>

                <div class="formulario-contrasena" id="formularioContrasena">
                    <form id="contrasenaForm" method="POST">
                        <input type="hidden" id="clienteIdContrasena" name="clienteId">
                        <div class="form-group">
                            <label for="nueva_contrasena">Nueva Contraseña</label>
                            <input type="password" id="nueva_contrasena" name="nueva_contrasena" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmar_contrasena">Confirmar Contraseña</label>
                            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
                        </div>
                        <button type="submit" class="boton-principal" name="cambiar_contrasena">Cambiar Contraseña</button>
                        <button type="button" class="boton-secundario" id="btnCancelarContrasena">Cancelar</button>
                    </form>
                </div>

                <div class="tabla-clientes">
                    <h2>Lista de Clientes</h2>
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo $cliente['UsuarioID']; ?></td>
                                <td><?php echo htmlspecialchars($cliente['Nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['Apellido']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['Email']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['Telefono']); ?></td>
                                <td>
                                    <span class="estado-cliente <?php echo $cliente['EsAdministrador'] ? 'activo' : 'inactivo'; ?>">
                                        <?php echo $cliente['EsAdministrador'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="boton-accion editar" title="Editar" data-id="<?php echo $cliente['UsuarioID']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="boton-accion contrasena" title="Cambiar contraseña" data-id="<?php echo $cliente['UsuarioID']; ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="boton-accion eliminar" title="Eliminar" data-id="<?php echo $cliente['UsuarioID']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formCliente = document.getElementById('clienteForm');
            const formContrasena = document.getElementById('contrasenaForm');
            const btnNuevoCliente = document.getElementById('btnNuevoCliente');
            const btnCancelar = document.getElementById('btnCancelar');
            const btnCancelarContrasena = document.getElementById('btnCancelarContrasena');
            const mensajeExito = document.getElementById('mensajeExito');
            const mensajeError = document.getElementById('mensajeError');
            const formularioCliente = document.getElementById('formularioCliente');
            const formularioContrasena = document.getElementById('formularioContrasena');
            const inputBuscar = document.getElementById('inputBuscar');
            
            // Ocultar mensajes después de 5 segundos
            setTimeout(() => {
                if (mensajeExito) mensajeExito.classList.remove('mostrar');
                if (mensajeError) mensajeError.classList.remove('mostrar');
            }, 5000);
            
            // Mostrar formulario para nuevo cliente
            btnNuevoCliente.addEventListener('click', function() {
                formCliente.reset();
                document.getElementById('clienteId').value = '';
                formularioContrasena.classList.remove('mostrar');
                formularioCliente.classList.remove('oculto');
                formularioCliente.scrollIntoView({behavior: 'smooth'});
            });
            
            // Cancelar edición
            btnCancelar.addEventListener('click', function() {
                formCliente.reset();
                document.getElementById('clienteId').value = '';
                formularioCliente.classList.add('oculto');
            });
            
            // Cancelar cambio de contraseña
            btnCancelarContrasena.addEventListener('click', function() {
                formContrasena.reset();
                document.getElementById('clienteIdContrasena').value = '';
                formularioContrasena.classList.remove('mostrar');
            });
            
            // Editar cliente
            document.querySelectorAll('.editar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const clienteId = this.getAttribute('data-id');
                    // Aquí deberías hacer una petición AJAX para obtener los datos del cliente
                    // Por simplicidad, aquí simulamos que encontramos el cliente en la tabla
                    const row = this.closest('tr');
                    document.getElementById('clienteId').value = clienteId;
                    document.getElementById('nombre').value = row.cells[1].textContent;
                    document.getElementById('apellido').value = row.cells[2].textContent;
                    document.getElementById('email').value = row.cells[3].textContent;
                    document.getElementById('telefono').value = row.cells[4].textContent;
                    
                    const estado = row.cells[5].textContent.trim() === 'Activo' ? '1' : '0';
                    document.getElementById('estado').value = estado;
                    
                    formularioContrasena.classList.remove('mostrar');
                    formularioCliente.classList.remove('oculto');
                    formularioCliente.scrollIntoView({behavior: 'smooth'});
                });
            });
            
            // Cambiar contraseña
            document.querySelectorAll('.contrasena').forEach(btn => {
                btn.addEventListener('click', function() {
                    const clienteId = this.getAttribute('data-id');
                    document.getElementById('clienteIdContrasena').value = clienteId;
                    
                    formularioCliente.classList.add('oculto');
                    formularioContrasena.classList.add('mostrar');
                    formularioContrasena.scrollIntoView({behavior: 'smooth'});
                });
            });
            
            // Eliminar cliente
            document.querySelectorAll('.eliminar').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('¿Está seguro de eliminar este cliente?')) {
                        const clienteId = this.getAttribute('data-id');
                        // Crear un formulario dinámico para enviar la solicitud de eliminación
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '';
                        
                        const inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'clienteId';
                        inputId.value = clienteId;
                        form.appendChild(inputId);
                        
                        const inputEliminar = document.createElement('input');
                        inputEliminar.type = 'hidden';
                        inputEliminar.name = 'eliminar_cliente';
                        inputEliminar.value = '1';
                        form.appendChild(inputEliminar);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            
            // Validar contraseñas coincidan
            formContrasena.addEventListener('submit', function(e) {
                const nuevaContrasena = document.getElementById('nueva_contrasena').value;
                const confirmarContrasena = document.getElementById('confirmar_contrasena').value;
                
                if (nuevaContrasena !== confirmarContrasena) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden');
                }
            });
            
            // Buscar clientes
            inputBuscar.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('.tabla-datos tbody tr');
                
                rows.forEach(row => {
                    const nombre = row.cells[1].textContent.toLowerCase();
                    const apellido = row.cells[2].textContent.toLowerCase();
                    const email = row.cells[3].textContent.toLowerCase();
                    
                    if (nombre.includes(searchTerm) || apellido.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>