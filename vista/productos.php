<?php 
session_start();
require_once '../configuracion/conexion.php';

// Configuración básica
$productosPorPagina = 8;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $productosPorPagina;

// Obtener parámetros de filtrado con valores por defecto
$categoriaSeleccionada = isset($_GET['categoria']) ? $_GET['categoria'] : 'todas';
$ordenSeleccionado = isset($_GET['orden']) ? $_GET['orden'] : 'relevantes';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Consulta base con JOIN para categorías
$query = "SELECT p.*, c.NombreCategoria FROM Productos p 
          JOIN Categorias c ON p.CategoriaID = c.CategoriaID 
          WHERE p.EstaActivo = 1";

// Aplicar filtro de categoría si no es 'todas'
if ($categoriaSeleccionada != 'todas' && is_numeric($categoriaSeleccionada)) {
    $categoria_id = intval($categoriaSeleccionada);
    $query .= " AND p.CategoriaID = $categoria_id";
}

// Aplicar filtro de búsqueda si hay texto
if (!empty($busqueda)) {
    $busqueda = $conn->real_escape_string($busqueda);
    $query .= " AND (p.NombreProducto LIKE '%$busqueda%' OR p.Descripcion LIKE '%$busqueda%')";
}

// Aplicar ordenación según selección
switch ($ordenSeleccionado) {
    case 'precio-asc':
        $query .= " ORDER BY p.Precio ASC";
        break;
    case 'precio-desc':
        $query .= " ORDER BY p.Precio DESC";
        break;
    case 'nuevos':
        $query .= " ORDER BY p.FechaCreacion DESC";
        break;
    default:
        $query .= " ORDER BY p.ProductoID ASC"; // Orden por defecto
}

// Consulta para contar total de productos (para paginación)
$queryContar = str_replace('SELECT p.*, c.NombreCategoria', 'SELECT COUNT(*) as total', $query);
$resultContar = $conn->query($queryContar);
$totalProductos = $resultContar->fetch_assoc()['total'];
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// Añadir límite para paginación
$query .= " LIMIT $offset, $productosPorPagina";

// Ejecutar consulta principal
$result = $conn->query($query);

// Verificar si hay errores en la consulta
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos | Aranzábal</title>
    <link rel="stylesheet" href="../archivos_estaticos/css/estilos.css">
    <link rel="stylesheet" href="../archivos_estaticos/css/productos.css">
    <link rel="stylesheet" href="../archivos_estaticos/css/responsivo.css">
</head>
<body>
    <header>
        <div class="contenedor-logo">
            <img src="../archivos_estaticos/img/diamanteblanco.png" alt="Joyitas Felices" class="logo">
            <h1>Aranzábal</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="productos.php" class="activo">Productos</a></li>
                <li><a href="nosotros.php">Nosotros</a></li>
                <li><a href="contacto.php">Contacto</a></li>

                <?php if(isset($_SESSION['email'])): ?>
                <li class="menu-usuario">
                    <a href="perfil.php" class="enlace-autenticacion">
                        <?php echo $_SESSION['email']; ?>
                    </a>
                    <ul class="submenu">
                        <li><a href="perfil.php">Mi Perfil</a></li>
                        <li><a href="../controladores/cerrar_sesion.php">Cerrar Sesión</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li><a href="autenticacion/iniciar-sesion.html" class="enlace-autenticacion">Iniciar Sesión</a></li>
                <?php endif; ?>

                <li><a href="carrito/carrito.php" class="enlace-carrito">Carrito (<span id="contador-carrito">0</span>)</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="filtros-productos">
            <div class="contenedor-filtros">
                <h2>Nuestros Productos</h2>
                <form method="GET" action="productos.php" class="controles-filtros">
                    <input type="hidden" name="pagina" value="1">
                    
                    <div class="grupo-filtro">
                        <label for="categoria">Categoría:</label>
                        <select id="categoria" name="categoria" onchange="this.form.submit()">
                            <option value="todas" <?= $categoriaSeleccionada == 'todas' ? 'selected' : '' ?>>Todas las categorías</option>
                            <?php
                            $query_categorias = "SELECT * FROM Categorias ORDER BY NombreCategoria";
                            $result_categorias = $conn->query($query_categorias);
                            
                            if ($result_categorias->num_rows > 0) {
                                while($categoria = $result_categorias->fetch_assoc()) {
                                    $selected = ($categoriaSeleccionada == $categoria['CategoriaID']) ? 'selected' : '';
                                    echo "<option value='{$categoria['CategoriaID']}' $selected>{$categoria['NombreCategoria']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="grupo-filtro">
                        <label for="orden">Ordenar por:</label>
                        <select id="orden" name="orden" onchange="this.form.submit()">
                            <option value="relevantes" <?= $ordenSeleccionado == 'relevantes' ? 'selected' : '' ?>>Más relevantes</option>
                            <option value="precio-asc" <?= $ordenSeleccionado == 'precio-asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                            <option value="precio-desc" <?= $ordenSeleccionado == 'precio-desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                            <option value="nuevos" <?= $ordenSeleccionado == 'nuevos' ? 'selected' : '' ?>>Más nuevos primero</option>
                        </select>
                    </div>
                    
                    <div class="buscador">
                        <input type="text" placeholder="Buscar productos..." id="busqueda" name="busqueda" 
                               value="<?= htmlspecialchars($busqueda) ?>">
                        <button type="submit">Buscar</button>
                    </div>
                </form>
            </div>
        </section>

<section class="lista-productos">
        <?php if ($result->num_rows > 0): ?>
        <div class="rejilla-productos">
            <?php while($producto = $result->fetch_assoc()): 
                $precio = number_format($producto['Precio'], 2);
                $esNuevo = (strtotime($producto['FechaCreacion']) > strtotime('-30 days'));
                $imagenProducto = !empty($producto['UrlImagen']) ? $producto['UrlImagen'] : 'img/producto-default.jpg';
            ?>
            <div class="tarjeta-producto">
                <div class="imagen-producto">
                    <?php if (!empty($producto['UrlImagen'])): ?>
                        <img src="<?= $producto['UrlImagen'] ?>" 
                             alt="<?= htmlspecialchars($producto['NombreProducto']) ?>" 
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'imagen-fallback\'>Imagen no disponible</div>'">
                    <?php else: ?>
                        <div class="imagen-fallback">Imagen no disponible</div>
                    <?php endif; ?>
                    <?= $esNuevo ? '<span class="etiqueta-nuevo">Nuevo</span>' : '' ?>
                </div>
                <div class="info-producto">
                    <h3><?= htmlspecialchars($producto['NombreProducto']) ?></h3>
                    <p class="categoria-producto"><?= htmlspecialchars($producto['NombreCategoria']) ?></p>
                    <p class="descripcion-producto"><?= htmlspecialchars($producto['Descripcion']) ?></p>
                    <div class="precio-producto">
                        <span class="precio-actual">S/ <?= $precio ?></span>
                    </div>
                    <p class="stock-disponible">Disponibles: <?= $producto['CantidadStock'] ?></p>
                    <button class="boton-agregar-carrito <?= !isset($_SESSION['email']) ? 'deshabilitado' : '' ?>" 
                            data-id="<?= $producto['ProductoID'] ?>" 
                            data-nombre="<?= htmlspecialchars($producto['NombreProducto']) ?>" 
                            data-precio="<?= $producto['Precio'] ?>" 
                            data-imagen="<?= $imagenProducto ?>"
                            <?= !isset($_SESSION['email']) ? 'title="Debe iniciar sesión para agregar al carrito"' : '' ?>>
                        <?= isset($_SESSION['email']) ? 'Agregar al carrito' : 'Inicie sesión' ?>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

            <?php if ($totalPaginas > 1): ?>
            <div class="paginacion">
                <?php if ($paginaActual > 1): ?>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['pagina'] = $paginaActual - 1;
                        echo http_build_query($params);
                    ?>">Anterior</a>
                <?php else: ?>
                    <span class="disabled">Anterior</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <?php if ($i == $paginaActual): ?>
                        <span class="pagina-actual"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?php 
                            $params = $_GET;
                            $params['pagina'] = $i;
                            echo http_build_query($params);
                        ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($paginaActual < $totalPaginas): ?>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['pagina'] = $paginaActual + 1;
                        echo http_build_query($params);
                    ?>">Siguiente</a>
                <?php else: ?>
                    <span class="disabled">Siguiente</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="sin-resultados">
                <p>No se encontraron productos con los filtros seleccionados.</p>
                <a href="productos.php" class="boton-principal">Ver todos los productos</a>
            </div>
            <?php endif; ?>
        </section>
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
                    <li><a href="terminos_y_condiciones.html">Términos y Condiciones</a></li>
                    <li><a href="politica_privacidad.html">Política de Privacidad</a></li>
                </ul>
            </div>
            <div class="redes-sociales">
                <h3>Síguenos</h3>
                <div class="iconos-redes">
                    <a href="#"><img src="../archivos_estaticos/img/iconfb.png" alt="Facebook"></a>
                    <a href="#"><img src="../archivos_estaticos/img/iconig.webp" alt="Instagram"></a>
                    <a href="#"><img src="../archivos_estaticos/img/iconwsp.webp" alt="WhatsApp"></a>
                </div>
            </div>
        </div>
        <div class="derechos-autor">
            <p>2025 Aranzábal. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar productos al carrito (solo para usuarios logueados)
        document.querySelectorAll('.boton-agregar-carrito:not(.deshabilitado)').forEach(boton => {
            boton.addEventListener('click', function() {
                const productoId = this.dataset.id;
                const productoNombre = this.dataset.nombre;
                const productoPrecio = parseFloat(this.dataset.precio);
                const productoImagen = this.dataset.imagen;
                
                agregarAlCarrito(
                    productoId,
                    productoNombre,
                    productoPrecio,
                    productoImagen,
                    1
                );
                
                mostrarNotificacion(`${productoNombre} agregado al carrito`);
            });
        });
        
        // Para botones deshabilitados (redirigir a login)
        document.querySelectorAll('.boton-agregar-carrito.deshabilitado').forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();
                // Redirigir a login con parámetro para volver después
                window.location.href = 'autenticacion/iniciar-sesion.html?redirect=' + 
                    encodeURIComponent(window.location.pathname + window.location.search);
            });
        });
        
        actualizarContadorCarrito();
    });
    
    // En productos.php, reemplaza la función agregarAlCarrito con esta versión mejorada:
    function agregarAlCarrito(productoId, nombre, precio, imagen, cantidad = 1) {
        // Verificar si el usuario está logueado (sesión PHP)
        <?php if(isset($_SESSION['email'])): ?>
            // Hacer llamada AJAX para guardar en la base de datos
            fetch('../controladores/agregar_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `producto_id=${productoId}&cantidad=${cantidad}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Actualizar el carrito en localStorage
                    let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
                    
                    const productoExistente = carrito.find(item => item.id === productoId);
                    
                    if (productoExistente) {
                        productoExistente.cantidad += cantidad;
                    } else {
                        carrito.push({
                            id: productoId,
                            nombre: nombre,
                            precio: precio,
                            imagen: imagen,
                            cantidad: cantidad
                        });
                    }
                    
                    localStorage.setItem('carrito', JSON.stringify(carrito));
                    actualizarContadorCarrito();
                    mostrarNotificacion(`${nombre} agregado al carrito`);
                } else {
                    mostrarNotificacion('Error al agregar al carrito: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al comunicarse con el servidor');
            });
        <?php else: ?>
            // Para usuarios no logueados, seguir usando solo localStorage
            let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            
            const productoExistente = carrito.find(item => item.id === productoId);
            
            if (productoExistente) {
                productoExistente.cantidad += cantidad;
            } else {
                carrito.push({
                    id: productoId,
                    nombre: nombre,
                    precio: precio,
                    imagen: imagen,
                    cantidad: cantidad
                });
            }
            
            localStorage.setItem('carrito', JSON.stringify(carrito));
            actualizarContadorCarrito();
            mostrarNotificacion(`${nombre} agregado al carrito (local)`);
        <?php endif; ?>
    }
    
    function actualizarContadorCarrito() {
        <?php if(isset($_SESSION['email'])): ?>
            // Para usuarios logueados, obtener el conteo de la base de datos
            fetch('../controladores/contar_carrito.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const contador = document.getElementById('contador-carrito');
                        if (contador) {
                            contador.textContent = data.count;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al obtener conteo del carrito:', error);
                    // Fallback a localStorage si hay error
                    const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
                    const contador = document.getElementById('contador-carrito');
                    if (contador) {
                        contador.textContent = carrito.reduce((total, item) => total + item.cantidad, 0);
                    }
                });
        <?php else: ?>
            // Para usuarios no logueados, usar solo localStorage
            const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            const contador = document.getElementById('contador-carrito');
            if (contador) {
                contador.textContent = carrito.reduce((total, item) => total + item.cantidad, 0);
            }
        <?php endif; ?>
    }
    
    function mostrarNotificacion(mensaje) {
        const notificacion = document.createElement('div');
        notificacion.className = 'notificacion';
        notificacion.textContent = mensaje;
        
        document.body.appendChild(notificacion);
        
        setTimeout(() => {
            notificacion.classList.add('mostrar');
        }, 10);
        
        setTimeout(() => {
            notificacion.classList.remove('mostrar');
            setTimeout(() => {
                document.body.removeChild(notificacion);
            }, 300);
        }, 3000);
    }
    </script>
</body>
</html>
<?php
$conn->close();
?>