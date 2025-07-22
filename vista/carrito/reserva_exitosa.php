<?php
session_start();
require_once '../../configuracion/conexion.php';

// Verificar si hay un ID de pedido
if (!isset($_GET['pedido_id'])) {
    header('Location: ../index.php');
    exit;
}

$pedidoId = $_GET['pedido_id'];
$usuarioId = $_SESSION['email'] ? null : null;

// Obtener información del pedido
$pedido = [];
$items = [];

try {
    if ($usuarioId) {
        // Obtener detalles del pedido
        $stmt = $conn->prepare("
            SELECT p.*, u.Nombre, u.Apellido, u.Email 
            FROM Pedidos p
            JOIN Usuarios u ON p.UsuarioID = u.UsuarioID
            WHERE p.PedidoID = ? AND p.UsuarioID = (SELECT UsuarioID FROM Usuarios WHERE Email = ?)
        ");
        $stmt->bind_param("is", $pedidoId, $_SESSION['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $pedido = $result->fetch_assoc();
        
        if (!$pedido) {
            throw new Exception("Pedido no encontrado");
        }
        
        // Obtener items del pedido
        $stmt = $conn->prepare("
            SELECT ap.*, pr.NombreProducto, pr.Descripcion, 
                   COALESCE(pr.UrlImagen, '../../archivos_estaticos/img/producto-default.jpg') as Imagen
            FROM ArticulosPedido ap
            JOIN Productos pr ON ap.ProductoID = pr.ProductoID
            WHERE ap.PedidoID = ?
        ");
        $stmt->bind_param("i", $pedidoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            $items[] = $item;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva Exitosa | Aranzábal</title>
    <link rel="stylesheet" href="../../archivos_estaticos/css/estilos.css">
    <link rel="stylesheet" href="../../archivos_estaticos/css/carrito.css">
    <style>
        .contenedor-reserva {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .reserva-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reserva-header h2 {
            color: #8e44ad;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .reserva-header .icono-exito {
            font-size: 60px;
            color: #2ecc71;
            margin-bottom: 20px;
        }
        
        .resumen-reserva {
            margin-bottom: 30px;
        }
        
        .detalle-reserva {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detalle-reserva .grupo {
            margin-bottom: 15px;
        }
        
        .detalle-reserva .etiqueta {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        
        .lista-productos-reserva {
            margin-top: 30px;
        }
        
        .item-reserva {
            display: grid;
            grid-template-columns: 80px 1fr 100px;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .item-reserva img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .acciones-reserva {
            text-align: center;
            margin-top: 30px;
        }
        
        .boton-volver {
            display: inline-block;
            padding: 12px 30px;
            background-color: #8e44ad;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .boton-volver:hover {
            background-color: #732d91;
        }
    </style>
</head>
<body>
    <header>
        <div class="contenedor-logo">
            <img src="../../archivos_estaticos/img/diamanteblanco.png" alt="Joyitas Felices" class="logo">
            <h1>Aranzábal</h1>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="../productos.php">Productos</a></li>
                <li><a href="../nosotros.php">Nosotros</a></li>
                <li><a href="../contacto.php">Contacto</a></li>

                <?php if(isset($_SESSION['email'])): ?>
                <li class="menu-usuario">
                    <a href="../perfil.php" class="enlace-autenticacion">
                        <?php echo $_SESSION['email']; ?>
                    </a>
                    <ul class="submenu">
                        <li><a href="../perfil.php">Mi Perfil</a></li>
                        <li><a href="../../controladores/cerrar_sesion.php">Cerrar Sesión</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li><a href="../autenticacion/iniciar-sesion.html" class="enlace-autenticacion">Iniciar Sesión</a></li>
                <?php endif; ?>

                <li><a href="carrito.php" class="enlace-carrito">Carrito (<span id="contador-carrito">0</span>)</a></li>
            </ul>
        </nav>
    </header>

    <main class="contenido-carrito">
        <div class="contenedor-reserva">
            <?php if (isset($error)): ?>
                <div class="error-carrito"><?php echo $error; ?></div>
                <div class="acciones-reserva">
                    <a href="../index.php" class="boton-volver">Volver al inicio</a>
                </div>
            <?php else: ?>
                <div class="reserva-header">
                    <div class="icono-exito">✓</div>
                    <h2>¡Reserva Exitosa!</h2>
                    <p>Gracias por tu reserva. Hemos recibido tu pedido correctamente.</p>
                </div>
                
                <div class="resumen-reserva">
                    <h3>Detalles de la Reserva</h3>
                    <div class="detalle-reserva">
                        <div>
                            <div class="grupo">
                                <div class="etiqueta">Número de Pedido:</div>
                                <div>#<?php echo $pedidoId; ?></div>
                            </div>
                            <div class="grupo">
                                <div class="etiqueta">Fecha:</div>
                                <div><?php echo date('d/m/Y H:i'); ?></div>
                            </div>
                            <div class="grupo">
                                <div class="etiqueta">Estado:</div>
                                <div>Procesando</div>
                            </div>
                        </div>
                        <div>
                            <div class="grupo">
                                <div class="etiqueta">Método de Pago:</div>
                                <div>Pendiente de confirmación</div>
                            </div>
                            <div class="grupo">
                                <div class="etiqueta">Total:</div>
                                <div>S/ <?php echo number_format($pedido['MontoTotal'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lista-productos-reserva">
                    <h3>Productos Reservados</h3>
                    <?php foreach ($items as $item): ?>
                        <div class="item-reserva">
                            <img src="<?php echo $item['Imagen']; ?>" alt="<?php echo htmlspecialchars($item['NombreProducto']); ?>">
                            <div>
                                <h4><?php echo htmlspecialchars($item['NombreProducto']); ?></h4>
                                <p>Cantidad: <?php echo $item['Cantidad']; ?></p>
                            </div>
                            <div>S/ <?php echo number_format($item['Subtotal'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="acciones-reserva">
                    <a href="../index.php" class="boton-volver">Volver al inicio</a>
                    <a href="../perfil.php" class="boton-volver" style="margin-left: 15px;">Ver mis pedidos</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="contenedor-footer">
            <div class="info-contacto">
                <h3>Contacto</h3>
                <p>Calle Tupac Amaru 155-A, Mercado San Pedro,Cusco</p>
                <p>Teléfono: 987 963 921</p>
                <p>Gmail:</p>
            </div>
            <div class="enlaces-rapidos">
                <h3>Enlaces rápidos</h3>
                <ul>
                    <li><a href="preguntas-frecuentes.html">Preguntas Frecuentes</a></li>
                    <li><a href="../terminos_y_condiciones.html">Términos y Condiciones</a></li>
                    <li><a href="../politica_privacidad.html">Política de Privacidad</a></li>
                </ul>
            </div>
            <div class="redes-sociales">
                <h3>Síguenos</h3>
                <div class="iconos-redes">
                    <a href="#"><img src="../../archivos_estaticos/img/iconfb.png" alt="Facebook"></a>
                    <a href="#"><img src="../../archivos_estaticos/img/iconig.webp" alt="Instagram"></a>
                    <a href="#"><img src="../../archivos_estaticos/img/iconwsp.webp" alt="WhatsApp"></a>
                </div>
            </div>
        </div>
        <div class="derechos-autor">
            <p>2025 Aranzábal. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>