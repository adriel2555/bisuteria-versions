<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto | Aranzábal</title>
    <link rel="stylesheet" href="../archivos_estaticos/css/estilos.css">
    <link rel="stylesheet" href="../archivos_estaticos/css/contacto.css">
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
                <li><a href="contacto.php" class="activo">Contacto</a></li>

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

                <li><a href="carrito/carrito.php" class="enlace-carrito">Carrito (<span
                            id="contador-carrito">0</span>)</a></li>
            </ul>
        </nav>
    </header>

    <main class="contenedor-contacto">
        <section class="hero-contacto">
            <div class="contenido-hero">
                <img src="../archivos_estaticos/img/cuscofondo.webp" alt="cuscofondo">
                <h2>Contáctanos</h2>
                <p>Estamos aquí para ayudarte con cualquier consulta</p>
            </div>
        </section>

        <section class="informacion-contacto">
            <div class="contenedor-info">
                <div class="info">
                    <h2>Información de Contacto</h2>
                    <div class="item-info">
                        <img src="../archivos_estaticos/img/ubicacion.PNG" alt="Ubicación">
                        <div>
                            <h3>Dirección</h3>
                            <p>Calle Tupac Amaru 155-A, Mercado San Pedro,Cusco</p>
                        </div>
                    </div>
                    <div class="item-info">
                        <img src="../archivos_estaticos/img/telefono.PNG" alt="Teléfono">
                        <div>
                            <h3>Teléfono</h3>
                            <p>987 963 921</p>
                        </div>
                    </div>
                    <div class="item-info">
                        <img src="../archivos_estaticos/img/email.PNG" alt="Email">
                        <div>
                            <h3>Email</h3>
                            <p></p>
                        </div>
                    </div>
                    <div class="item-info">
                        <img src="../archivos_estaticos/img/horario.PNG" alt="Horario">
                        <div>
                            <h3>Horario de Atención</h3>
                            <p>Lunes a Sábado: 9:00 am - 8:00 pm</p>
                        </div>
                    </div>
                    <div class="container-fluid px-0">
                        <div class="map-container">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m26!1m12!1m3!1d576.6521057193684!2d-71.98257631244103!3d-13.521010809079167!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m11!3e2!4m5!1s0x916dd6767a1d01b5%3A0xc48c3567b1b31533!2sCalle%20Tupac%20Amaru%20155%2C%20Cusco%2008002!3m2!1d-13.5210986!2d-71.9820077!4m3!3m2!1d-13.5211908!2d-71.9821606!5e0!3m2!1ses-419!2spe!4v1752943033778!5m2!1ses-419!2spe"
                                width="900" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <section class="registro-contacto">
            <div class="contenido-registro">
                <h2>¿Aún no tienes una cuenta?</h2>
                <p>Regístrate para hacer tus compras más fácilmente.</p>
                <a href="autenticacion/registro.html" class="boton-registro">Regístrate Aquí</a>
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
    <script src="../archivos_estaticos/js/contacto.js"></script>
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
    </script>
</body>

</html>