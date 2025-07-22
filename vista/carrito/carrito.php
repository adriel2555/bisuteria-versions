<?php
session_start();
require_once '../../configuracion/conexion.php';

// Depuración: Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Depuración: Ver datos del carrito
echo "<!-- Usuario ID: " . ($_SESSION['email'] ?? 'No logueado') . " -->";
$test_query = "SELECT * FROM Carrito WHERE UsuarioID = (SELECT UsuarioID FROM Usuarios WHERE Email = ? LIMIT 1)";
$stmt = $conn->prepare($test_query);
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
echo "<!-- Items en carrito: " . $result->num_rows . " -->";

// Inicializar variables
$usuario_id = null;
$carrito_items = [];
$error = null;

// Verificar sesión y obtener usuario
// Inicializar variables
$usuario_id = null;
$carrito_items = [];
$error = null;

try {
    // Obtener ID de usuario
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT UsuarioID FROM Usuarios WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $usuario_id = $usuario['UsuarioID'];
        
        // Consulta optimizada para obtener items del carrito
        $sql = "SELECT 
                c.CarritoID, 
                c.ProductoID, 
                c.Cantidad, 
                c.PrecioUnitario,
                p.NombreProducto, 
                p.Descripcion, 
                COALESCE(p.UrlImagen, '../../archivos_estaticos/img/producto-default.jpg') as Imagen,
                p.CantidadStock
            FROM Carrito c
            JOIN Productos p ON c.ProductoID = p.ProductoID
            WHERE c.UsuarioID = ?";
            
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            $carrito_items[] = $item;
        }
    } else {
        $error = "Usuario no encontrado en la base de datos";
    }
} catch (Exception $e) {
    $error = "Error al obtener los datos del carrito: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras | Aranzábal</title>
    <link rel="stylesheet" href="../../archivos_estaticos/css/estilos.css">
    <link rel="stylesheet" href="../../archivos_estaticos/css/carrito.css">
    <link rel="stylesheet" href="../../archivos_estaticos/css/responsivo.css">
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

                <li><a href="carrito.php" class="enlace-carrito activo">Carrito (<span id="contador-carrito">0</span>)</a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="contenido-carrito">
        <h2>Tu Carrito de Compras</h2>

        <div class="resumen-carrito">
            <div class="lista-productos-carrito">
                <?php if (!empty($error)): ?>
                    <div class="error-carrito"><?php echo $error; ?></div>
                <?php elseif (empty($carrito_items)): ?>
                    <div class="carrito-vacio">
                        <img src="../../archivos_estaticos/img/carrito.png" alt="Carrito vacío">
                        <h3>Tu carrito está vacío</h3>
                        <p>Agrega algunos productos para comenzar</p>
                        <a href="../productos.php" class="boton-ver-productos">Ver productos</a>
                    </div>
                <?php else: ?>
                    <div class="encabezado-lista">
                        <span>Producto</span>
                        <span>Precio</span>
                        <span>Cantidad</span>
                        <span>Total</span>
                    </div>
                    
                    <?php 
                    $subtotal = 0;
                    foreach ($carrito_items as $item): 
                        $total_producto = $item['PrecioUnitario'] * $item['Cantidad'];
                        $subtotal += $total_producto;
                    ?>
                        <div class="item-carrito" data-id="<?php echo $item['ProductoID']; ?>" data-carrito-id="<?php echo $item['CarritoID']; ?>">
                            <div class="info-producto-carrito">
                                <img src="<?php echo $item['Imagen']; ?>" alt="<?php echo htmlspecialchars($item['NombreProducto']); ?>">
                                <div class="detalles-producto">
                                    <h3><?php echo htmlspecialchars($item['NombreProducto']); ?></h3>
                                    <button class="eliminar-producto" data-producto-id="<?php echo $item['ProductoID']; ?>">Eliminar</button>
                                </div>
                            </div>
                            <div class="precio-producto-carrito">
                                S/ <?php echo number_format($item['PrecioUnitario'], 2); ?>
                            </div>
                            <div class="cantidad-producto-carrito">
                                <button class="disminuir-cantidad" data-producto-id="<?php echo $item['ProductoID']; ?>">-</button>
                                <span class="cantidad"><?php echo $item['Cantidad']; ?></span>
                                <button class="aumentar-cantidad" data-producto-id="<?php echo $item['ProductoID']; ?>">+</button>
                            </div>
                            <div class="total-producto-carrito">
                                S/ <?php echo number_format($total_producto, 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($carrito_items)): ?>
            <div class="resumen-compra">
                <h3>Resumen de Compra</h3>
                <div class="detalle-resumen">
                    <div class="fila-resumen">
                        <span>Subtotal:</span>
                        <span id="subtotal">S/ <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="fila-resumen">
                        <span>Envío:</span>
                        <span id="envio">S/ <?php echo number_format(($subtotal > 100) ? 0 : 10.00, 2); ?></span>
                    </div>
                    <div class="fila-resumen total">
                        <span>Total:</span>
                        <span id="total">S/ <?php echo number_format($subtotal + (($subtotal > 100) ? 0 : 10.00), 2); ?></span>
                    </div>
                </div>
                <button class="boton-pagar" id="procesar-reserva">Procesar Reserva</button>
                <a href="../productos.php" class="seguir-comprando">Seguir comprando</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obtener el ID del usuario desde la sesión PHP
    const usuarioId = <?php echo $usuario_id ?? 'null'; ?>;
    
    if (!usuarioId) {
        alert('Error: No se pudo identificar al usuario');
        return;
    }

    // Función para actualizar el contador del carrito
    function actualizarContadorCarrito() {
        const totalItems = Array.from(document.querySelectorAll('.item-carrito')).reduce((sum, item) => {
            return sum + parseInt(item.querySelector('.cantidad').textContent);
        }, 0);
        document.getElementById('contador-carrito').textContent = totalItems;
    }

    // Función para actualizar los totales
    function actualizarTotales() {
        let subtotal = 0;
        document.querySelectorAll('.item-carrito').forEach(item => {
            subtotal += parseFloat(item.querySelector('.total-producto-carrito').textContent.replace('S/ ', ''));
        });
        
        const envio = subtotal > 100 ? 0 : 10;
        const total = subtotal + envio;
        
        // Actualizar UI
        document.getElementById('subtotal').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('envio').textContent = 'S/ ' + envio.toFixed(2);
        document.getElementById('total').textContent = 'S/ ' + total.toFixed(2);
        
        // Actualizar contador
        actualizarContadorCarrito();
    }

    // Función para eliminar un producto del carrito
    async function eliminarProducto(productoId) {
        try {
            const response = await fetch('../../controladores/eliminar_del_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `producto_id=${productoId}&usuario_id=${usuarioId}`
            });
            
            const result = await response.text();
            if (result === '1' || result === '2') {
                const item = document.querySelector(`.item-carrito[data-id="${productoId}"]`);
                if (item) {
                    item.remove();
                    actualizarTotales();
                }
            } else {
                alert('Error al eliminar el producto del carrito');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ocurrió un error al eliminar el producto');
        }
    }

    // Función para actualizar la cantidad de un producto
    async function actualizarCantidad(productoId, nuevaCantidad) {
        try {
            const response = await fetch('../../controladores/actualizar_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `producto_id=${productoId}&usuario_id=${usuarioId}&cantidad=${nuevaCantidad}`
            });
            
            const result = await response.text();
            
            if (result === '1') {
                // Éxito - actualizar la UI
                const item = document.querySelector(`.item-carrito[data-id="${productoId}"]`);
                if (item) {
                    item.querySelector('.cantidad').textContent = nuevaCantidad;
                    const precio = parseFloat(item.querySelector('.precio-producto-carrito').textContent.replace('S/ ', ''));
                    item.querySelector('.total-producto-carrito').textContent = 'S/ ' + (precio * nuevaCantidad).toFixed(2);
                    actualizarTotales();
                }
            } else if (result === '2') {
                alert('No hay suficiente stock disponible para esta cantidad.');
            } else if (result === '3') {
                // El producto ya no está en el carrito
                const item = document.querySelector(`.item-carrito[data-id="${productoId}"]`);
                if (item) {
                    item.remove();
                    actualizarTotales();
                }
            } else {
                alert('Error al actualizar la cantidad. Verifica el stock disponible.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ocurrió un error al actualizar la cantidad');
        }
    }

    // Event listeners para los botones de eliminar
    document.querySelectorAll('.eliminar-producto').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productoId = this.getAttribute('data-producto-id');
            if (confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
                eliminarProducto(productoId);
            }
        });
    });

    // Event listeners para los botones de cantidad
    document.querySelectorAll('.disminuir-cantidad, .aumentar-cantidad').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const productoId = this.getAttribute('data-producto-id');
            const cantidadElement = this.closest('.cantidad-producto-carrito').querySelector('.cantidad');
            let cantidad = parseInt(cantidadElement.textContent);
            
            if (this.classList.contains('aumentar-cantidad')) {
                cantidad += 1;
                await actualizarCantidad(productoId, cantidad);
            } else {
                cantidad -= 1;
                if (cantidad < 1) {
                    if (confirm('¿Eliminar este producto del carrito?')) {
                        await eliminarProducto(productoId);
                    }
                } else {
                    await actualizarCantidad(productoId, cantidad);
                }
            }
        });
    });

    // Inicializar el contador del carrito
    actualizarContadorCarrito();
});

document.getElementById('procesar-reserva').addEventListener('click', async function() {
    try {
        // Mostrar loader
        this.disabled = true;
        this.textContent = 'Procesando...';
        
        // Calcular totales y recoger items
        let subtotal = 0;
        const items = [];
        
        document.querySelectorAll('.item-carrito').forEach(item => {
            const productoId = parseInt(item.getAttribute('data-id'));
            const cantidad = parseInt(item.querySelector('.cantidad').textContent);
            const precio = parseFloat(item.querySelector('.precio-producto-carrito').textContent.replace('S/ ', ''));
            const total = precio * cantidad;
            
            subtotal += total;
            items.push({
                productoId,
                cantidad,
                precio,
                total
            });
        });

        const envio = subtotal > 100 ? 0 : 10;
        const total = subtotal + envio;

        // Enviar datos al servidor
        const response = await fetch('../../controladores/procesar_reserva.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                items,
                subtotal,
                envio,
                total
            })
        });

        const result = await response.json();
        
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Error al procesar la reserva');
        }
        
        // Redirigir a confirmación
        window.location.href = `reserva_exitosa.php?pedido_id=${result.pedidoId}`;
        
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'Ocurrió un error al procesar la reserva');
        
        // Restaurar botón
        const btn = document.getElementById('procesar-reserva');
        btn.disabled = false;
        btn.textContent = 'Procesar Reserva';
    }
});
</script>
</body>
</html>