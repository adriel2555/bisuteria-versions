<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nosotros | Aranzábal</title>
    <link rel="stylesheet" href="../archivos_estaticos/css/estilos.css">
    <link rel="stylesheet" href="../archivos_estaticos/css/nosotros.css">
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
                <li><a href="nosotros.php" class="activo">Nosotros</a></li>
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

    <main class="contenedor-nosotros">
        <section class="hero-nosotros">
            <div class="contenido-hero">
                <img src="../archivos_estaticos/img/cuscofondo.webp" alt="cuscofondo">
                <h2>Nuestra Historia</h2>
                <p>Conoce más sobre Aranzábal y nuestra pasión por la bisutería</p>
            </div>
        </section>
        <section class="historia">
            <div class="contenido-historia">
                <h2>Desde 2015 en Calle Tupac Amaru, Cusco</h2>
                <p>Aranzábal nació como un pequeño emprendimiento familiar con el objetivo de brindar materiales de calidad para la creación de joyas artesanales. Lo que comenzó como un pasatiempo, pronto se convirtió en nuestro negocio principal gracias al apoyo de nuestra comunidad.</p>
                <p>Hoy, con más de 8 años de experiencia, continuamos comprometidos con ofrecer los mejores insumos para bisutería, combinando tradición e innovación en cada uno de nuestros productos.</p>
            </div>
            <div class="imagen-historia">
                <img src="../archivos_estaticos/img/Bisuteria.jpeg" alt="Nuestro local en Wanchaq">
            </div>
        </section>

        <section class="valores">
            <h2>Nuestros Valores</h2>
            <div class="tarjetas-valores">
                <div class="tarjeta-valor">
                    <img src="../archivos_estaticos/img/calidad.png" alt="Calidad">
                    <h3>Calidad</h3>
                    <p>Seleccionamos cuidadosamente cada material para garantizar productos duraderos y hermosos.</p>
                </div>
                <div class="tarjeta-valor">
                    <img src="../archivos_estaticos/img/pasion.png" alt="Pasión">
                    <h3>Pasión</h3>
                    <p>Amamos lo que hacemos y eso se refleja en cada detalle de nuestro trabajo.</p>
                </div>
                <div class="tarjeta-valor">
                    <img src="../archivos_estaticos/img/comunidad.png" alt="Comunidad">
                    <h3>Comunidad</h3>
                    <p>Creemos en apoyar a los artesanos locales y en crecer junto a nuestros clientes.</p>
                </div>
            </div>
        </section>

        <section class="equipo">
            <h2>Conoce a Nuestro Equipo</h2>
            <div class="miembros-equipo">
                <div class="miembro">
                    <img src="../archivos_estaticos/img/iconmujer.png" alt="María López">
                    <h3>María López</h3>
                    <p>Fundadora y Gerente General</p>
                </div>
                <div class="miembro">
                    <img src="../archivos_estaticos/img/iconvaron.png" alt="Carlos Rodríguez">
                    <h3>Carlos Rodríguez</h3>
                    <p>Especialista en Productos</p>
                </div>
                <div class="miembro">
                    <img src="../archivos_estaticos/img/iconmujer.png" alt="Lucía Fernández">
                    <h3>Lucía Fernández</h3>
                    <p>Atención al Cliente</p>
                </div>
            </div>
        </section>

        <section class="invitacion">
            <div class="contenido-invitacion">
                <h2>¿Quieres unirte a nuestra comunidad?</h2>
                <p>Regístrate para recibir noticias, promociones exclusivas y consejos para tus creaciones.</p>
                <a href="autenticacion/registro.html" class="boton-principal">Regístrate Ahora</a>
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
    </script>
</body>
</html>