<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aranzábal - Insumos de Bisutería</title>
    <link rel="stylesheet" href="../archivos_estaticos/css/estilos.css">
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
                <li><a href="productos.php">Productos</a></li>
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
                <li><a href="../vista/autenticacion/iniciar-sesion.html" class="enlace-autenticacion">Iniciar Sesión</a></li>
                <?php endif; ?>

                <li><a href="carrito/carrito.php" class="enlace-carrito">Carrito (<span id="contador-carrito">0</span>)</a>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="contenido-hero">
                <img src="../archivos_estaticos/img/cuscofondo.webp" alt="cuscofondo">
                <h2>Insumos de bisutería de calidad en Cusco</h2>
                <p>Descubre nuestra amplia variedad de materiales para crear joyas únicas</p>
                <a href="productos.php" class="boton-principal">Ver Catálogo</a>
            </div>
        </section>

        <section class="categorias-destacadas">
            <h2>Categorías Destacadas</h2>
            <div class="rejilla-categorias">
                <div class="tarjeta-categoria">
                    <img src="../archivos_estaticos/img/piedras.jpg" alt="Piedras semipreciosas">
                    <h3>Piedras</h3>
                    <a href="productos.php?categoria=piedras">Ver productos</a>
                </div>
                <div class="tarjeta-categoria">
                    <img src="../archivos_estaticos/img/herramientas.webp" alt="Herramientas">
                    <h3>Herramientas</h3>
                    <a href="productos.php?categoria=herramientas">Ver productos</a>
                </div>
                <div class="tarjeta-categoria">
                    <img src="../archivos_estaticos/img/accesorios.jpg" alt="Accesorios">
                    <h3>Accesorios</h3>
                    <a href="productos.php?categoria=accesorios">Ver productos</a>
                </div>
            </div>
        </section>

        <section class="sobre-nosotros">
            <div class="contenido-sobre-nosotros">
                <h2>Sobre Aranzábal</h2>
                <p>Desde 2015 en Wanchaq, Cusco, ofrecemos los mejores insumos para bisutería artesanal. Ahora también
                    disponibles en nuestra tienda online.</p>
                <a href="nosotros.php" class="boton-secundario">Conoce más</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="contenedor-footer">
            <div class="info-contacto">
                <h3>Contacto</h3>
                <p>Calle Tupac Amaru 155-A, Mercado San Pedro, Cusco</p>
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

    <script src="../archivos_estaticos/js/principal.js"></script>
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