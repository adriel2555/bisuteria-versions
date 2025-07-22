document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos del DOM
    const listaProductosCarrito = document.querySelector('.lista-productos-carrito');
    const resumenCompra = document.querySelector('.resumen-compra');
    const contadorCarrito = document.getElementById('contador-carrito');
    
    // Obtener carrito de localStorage o de la base de datos
    let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
    
    // Si hay datos del carrito en la base de datos, usarlos
    if (carritoDesdeBD && carritoDesdeBD.length > 0) {
        carrito = carritoDesdeBD;
    }
    
    // Mostrar productos en el carrito
    function mostrarCarrito() {
        // Si el carrito está vacío
        if (carrito.length === 0) {
            listaProductosCarrito.innerHTML = `
                <div class="carrito-vacio">
                    <img src="../../archivos_estaticos/img/carrito.png" alt="Carrito vacío">
                    <h3>Tu carrito está vacío</h3>
                    <p>Agrega algunos productos para comenzar</p>
                    <a href="../productos.php" class="boton-ver-productos">Ver productos</a>
                </div>
            `;
            resumenCompra.style.display = 'none';
            actualizarContadorCarrito();
            return;
        }
        
        // Mostrar productos cuando hay items
        listaProductosCarrito.innerHTML = `
            <div class="encabezado-lista">
                <span>Producto</span>
                <span>Precio</span>
                <span>Cantidad</span>
                <span>Total</span>
            </div>
        `;
        
        let subtotal = 0;
        
        carrito.forEach(item => {
            const totalProducto = item.precio * item.cantidad;
            subtotal += totalProducto;
            
            const itemHTML = `
                <div class="item-carrito" data-id="${item.id}" data-carrito-id="${item.carrito_id || ''}">
                    <div class="info-producto-carrito">
                        <img src="${item.imagen}" alt="${item.nombre}" onerror="this.src='../../archivos_estaticos/img/producto-default.jpg'">
                        <div class="detalles-producto">
                            <h3>${item.nombre}</h3>
                            <button class="eliminar-producto">Eliminar</button>
                        </div>
                    </div>
                    <div class="precio-producto-carrito">
                        S/ ${item.precio.toFixed(2)}
                    </div>
                    <div class="cantidad-producto-carrito">
                        <button class="disminuir-cantidad">-</button>
                        <span class="cantidad">${item.cantidad}</span>
                        <button class="aumentar-cantidad">+</button>
                    </div>
                    <div class="total-producto-carrito">
                        S/ ${totalProducto.toFixed(2)}
                    </div>
                </div>
            `;
            
            listaProductosCarrito.insertAdjacentHTML('beforeend', itemHTML);
        });
        
        // Calcular envío y total
        const envio = calcularEnvio(subtotal);
        const total = subtotal + envio;
        
        // Mostrar resumen de compra
        resumenCompra.innerHTML = `
            <h3>Resumen de Compra</h3>
            <div class="detalle-resumen">
                <div class="fila-resumen">
                    <span>Subtotal:</span>
                    <span>S/ ${subtotal.toFixed(2)}</span>
                </div>
                <div class="fila-resumen">
                    <span>Envío:</span>
                    <span>S/ ${envio.toFixed(2)}</span>
                </div>
                <div class="fila-resumen total">
                    <span>Total:</span>
                    <span>S/ ${total.toFixed(2)}</span>
                </div>
            </div>
            <button class="boton-pagar">Proceder Pedido</button>
            <a href="../productos.php" class="seguir-comprando">Seguir comprando</a>
        `;
        
        resumenCompra.style.display = 'block';
        
        // Agregar event listeners
        agregarEventListenersCarrito();
        
        // Actualizar contador
        actualizarContadorCarrito();
    }
    
    // Calcular costo de envío
    function calcularEnvio(subtotal) {
        // Envío gratuito para compras mayores a S/ 100
        if (subtotal > 100) {
            return 0;
        }
        return 10.00; // Costo fijo de envío
    }
    
    // Función para agregar todos los event listeners del carrito
    function agregarEventListenersCarrito() {
        // Eliminar producto
        document.querySelectorAll('.eliminar-producto').forEach(boton => {
            boton.addEventListener('click', function() {
                const itemElement = this.closest('.item-carrito');
                const itemId = itemElement.dataset.id;
                const carritoId = itemElement.dataset.carritoId;
                
                eliminarDelCarrito(itemId, carritoId);
            });
        });
        
        // Disminuir cantidad
        document.querySelectorAll('.disminuir-cantidad').forEach(boton => {
            boton.addEventListener('click', function() {
                const itemElement = this.closest('.item-carrito');
                const itemId = itemElement.dataset.id;
                const carritoId = itemElement.dataset.carritoId;
                
                actualizarCantidad(itemId, -1, carritoId);
            });
        });
        
        // Aumentar cantidad
        document.querySelectorAll('.aumentar-cantidad').forEach(boton => {
            boton.addEventListener('click', function() {
                const itemElement = this.closest('.item-carrito');
                const itemId = itemElement.dataset.id;
                const carritoId = itemElement.dataset.carritoId;
                
                actualizarCantidad(itemId, 1, carritoId);
            });
        });
        
        // Botón de pago
        document.querySelector('.boton-pagar')?.addEventListener('click', function() {
            // Verificar si el usuario está autenticado
            <?php if(isset($_SESSION['email'])): ?>
                window.location.href = 'checkout.php';
            <?php else: ?>
                // Guardar la URL actual para redireccionar después del login
                sessionStorage.setItem('urlRedireccion', 'carrito.php');
                window.location.href = '../autenticacion/iniciar-sesion.html';
            <?php endif; ?>
        });
    }
    
    // Eliminar producto del carrito
    function eliminarDelCarrito(productoId, carritoId) {
        <?php if(isset($_SESSION['email'])): ?>
            // Usuario logueado - eliminar de la base de datos
            fetch('../../controladores/eliminar_del_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `carrito_id=${carritoId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Eliminar del carrito local
                    carrito = carrito.filter(item => item.id != productoId);
                    actualizarCarrito();
                    mostrarNotificacion('Producto eliminado del carrito');
                } else {
                    mostrarNotificacion('Error al eliminar el producto: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al comunicarse con el servidor');
            });
        <?php else: ?>
            // Usuario no logueado - eliminar solo de localStorage
            carrito = carrito.filter(item => item.id != productoId);
            actualizarCarrito();
            mostrarNotificacion('Producto eliminado del carrito');
        <?php endif; ?>
    }
    
    // Actualizar cantidad de un producto
    function actualizarCantidad(productoId, cambio, carritoId) {
        const item = carrito.find(item => item.id == productoId);
        
        if (item) {
            const nuevaCantidad = item.cantidad + cambio;
            
            if (nuevaCantidad < 1) {
                eliminarDelCarrito(productoId, carritoId);
                return;
            }
            
            <?php if(isset($_SESSION['email'])): ?>
                // Usuario logueado - actualizar en la base de datos
                fetch('../../controladores/actualizar_carrito.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `carrito_id=${carritoId}&cantidad=${nuevaCantidad}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        item.cantidad = nuevaCantidad;
                        actualizarCarrito();
                    } else {
                        mostrarNotificacion('Error al actualizar cantidad: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error al comunicarse con el servidor');
                });
            <?php else: ?>
                // Usuario no logueado - actualizar solo en localStorage
                item.cantidad = nuevaCantidad;
                actualizarCarrito();
            <?php endif; ?>
        }
    }
    
    // Actualizar carrito en localStorage y UI
    function actualizarCarrito() {
        localStorage.setItem('carrito', JSON.stringify(carrito));
        mostrarCarrito();
    }
    
    // Actualizar contador del carrito
    function actualizarContadorCarrito() {
        const totalItems = carrito.reduce((total, item) => total + item.cantidad, 0);
        contadorCarrito.textContent = totalItems;
    }
    
    // Mostrar notificación
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
    
    // Inicializar carrito
    mostrarCarrito();
});