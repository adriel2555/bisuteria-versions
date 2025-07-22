document.addEventListener('DOMContentLoaded', function() {
    // Productos de ejemplo (en un proyecto real vendrían de una API o base de datos)
    const productos = [
        {
            id: 1,
            nombre: "Piedras de cuarzo rosa",
            descripcion: "Paquete de 20 piedras de cuarzo rosa para bisutería",
            precio: 25.00,
            precioAnterior: 30.00,
            categoria: "piedras",
            imagen: "img/producto1.jpg",
            nuevo: true
        },
        {
            id: 2,
            nombre: "Alicates para bisutería",
            descripcion: "Kit de 3 alicates profesionales",
            precio: 45.00,
            categoria: "herramientas",
            imagen: "img/alicates.jfif"
        },
        // Más productos...
    ];

    const rejillaProductos = document.querySelector('.rejilla-productos');
    const selectCategoria = document.getElementById('categoria');
    const selectOrden = document.getElementById('orden');
    const inputBusqueda = document.getElementById('busqueda');
    const botonBuscar = document.querySelector('.buscador button');

    // Mostrar productos en la página
    function mostrarProductos(productosMostrar) {
        rejillaProductos.innerHTML = '';
        
        productosMostrar.forEach(producto => {
            const productoHTML = `
                <div class="tarjeta-producto" data-id="${producto.id}">
                    <div class="imagen-producto">
                        <img src="${producto.imagen}" alt="${producto.nombre}">
                        ${producto.nuevo ? '<span class="etiqueta-nuevo">Nuevo</span>' : ''}
                    </div>
                    <div class="info-producto">
                        <h3>${producto.nombre}</h3>
                        <p class="descripcion-producto">${producto.descripcion}</p>
                        <div class="precio-producto">
                            <span class="precio-actual">S/ ${producto.precio.toFixed(2)}</span>
                            ${producto.precioAnterior ? `<span class="precio-anterior">S/ ${producto.precioAnterior.toFixed(2)}</span>` : ''}
                        </div>
                        <button class="boton-agregar-carrito">Agregar al carrito</button>
                    </div>
                </div>
            `;
            rejillaProductos.insertAdjacentHTML('beforeend', productoHTML);
        });

        // Agregar event listeners a los botones
        document.querySelectorAll('.boton-agregar-carrito').forEach(boton => {
            boton.addEventListener('click', function() {
                const tarjeta = this.closest('.tarjeta-producto');
                const productoId = tarjeta.dataset.id;
                const producto = productos.find(p => p.id == productoId);
                
                agregarAlCarrito(
                    producto.id,
                    producto.nombre,
                    producto.precio
                );
            });
        });
    }

    // Filtrar y ordenar productos
    function filtrarYOrdenarProductos() {
        let productosFiltrados = [...productos];
        
        // Filtrar por categoría
        if (selectCategoria.value !== 'todas') {
            productosFiltrados = productosFiltrados.filter(
                producto => producto.categoria === selectCategoria.value
            );
        }
        
        // Filtrar por búsqueda
        const terminoBusqueda = inputBusqueda.value.toLowerCase();
        if (terminoBusqueda) {
            productosFiltrados = productosFiltrados.filter(
                producto => producto.nombre.toLowerCase().includes(terminoBusqueda) || 
                           producto.descripcion.toLowerCase().includes(terminoBusqueda)
            );
        }
        
        // Ordenar productos
        switch (selectOrden.value) {
            case 'precio-asc':
                productosFiltrados.sort((a, b) => a.precio - b.precio);
                break;
            case 'precio-desc':
                productosFiltrados.sort((a, b) => b.precio - a.precio);
                break;
            case 'nuevos':
                productosFiltrados.sort((a, b) => (b.nuevo || false) - (a.nuevo || false));
                break;
            default:
                // Orden por defecto (relevantes)
                break;
        }
        
        mostrarProductos(productosFiltrados);
    }

    // Event listeners para filtros
    selectCategoria.addEventListener('change', filtrarYOrdenarProductos);
    selectOrden.addEventListener('change', filtrarYOrdenarProductos);
    botonBuscar.addEventListener('click', filtrarYOrdenarProductos);
    inputBusqueda.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            filtrarYOrdenarProductos();
        }
    });

    // Mostrar todos los productos al cargar la página
    filtrarYOrdenarProductos();
});
document.addEventListener('DOMContentLoaded', function() {
    // Productos de ejemplo
    const productos = [
        {
            id: 1,
            nombre: "Piedras de cuarzo rosa",
            descripcion: "Paquete de 20 piedras de cuarzo rosa para bisutería",
            precio: 25.00,
            precioAnterior: 30.00,
            categoria: "piedras",
            imagen: "img/producto1.jpg",
            nuevo: true
        },
        {
            id: 2,
            nombre: "Alicates para bisutería",
            descripcion: "Kit de 5 alicates profesionales",
            precio: 45.00,
            categoria: "herramientas",
            imagen: "img/alicates.jfif"
        }
    ];

    // Mostrar productos en la página
    function mostrarProductos() {
        const rejillaProductos = document.querySelector('.rejilla-productos');
        rejillaProductos.innerHTML = '';

        productos.forEach(producto => {
            const productoHTML = `
                <div class="tarjeta-producto" data-id="${producto.id}">
                    <div class="imagen-producto">
                        <img src="${producto.imagen}" alt="${producto.nombre}">
                        ${producto.nuevo ? '<span class="etiqueta-nuevo">Nuevo</span>' : ''}
                    </div>
                    <div class="info-producto">
                        <h3>${producto.nombre}</h3>
                        <p class="descripcion-producto">${producto.descripcion}</p>
                        <div class="precio-producto">
                            <span class="precio-actual">S/ ${producto.precio.toFixed(2)}</span>
                            ${producto.precioAnterior ? `<span class="precio-anterior">S/ ${producto.precioAnterior.toFixed(2)}</span>` : ''}
                        </div>
                        <button class="boton-agregar-carrito" data-id="${producto.id}">Agregar al carrito</button>
                    </div>
                </div>
            `;
            rejillaProductos.insertAdjacentHTML('beforeend', productoHTML);
        });

        // Agregar event listeners a los botones
        document.querySelectorAll('.boton-agregar-carrito').forEach(boton => {
            boton.addEventListener('click', function() {
                const productoId = this.dataset.id;
                const producto = productos.find(p => p.id == productoId);
                
                agregarAlCarrito(
                    producto.id,
                    producto.nombre,
                    producto.precio,
                    producto.imagen,
                    1 // Cantidad por defecto
                );
                
                // Mostrar notificación
                mostrarNotificacion(`${producto.nombre} agregado al carrito`);
            });
        });
    }

    // Función para agregar al carrito
    function agregarAlCarrito(productoId, nombre, precio, imagen, cantidad = 1) {
        let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
        
        // Verificar si el producto ya está en el carrito
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
    }

    // Actualizar contador del carrito
    function actualizarContadorCarrito() {
        const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
        const contador = document.getElementById('contador-carrito');
        if (contador) {
            contador.textContent = carrito.reduce((total, item) => total + item.cantidad, 0);
        }
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

    // Estilo para notificaciones
    const estiloNotificacion = document.createElement('style');
    estiloNotificacion.textContent = `
    .notificacion {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #8e44ad;
        color: white;
        padding: 15px 25px;
        border-radius: 5px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 1000;
    }
    .notificacion.mostrar {
        transform: translateY(0);
        opacity: 1;
    }
    `;
    document.head.appendChild(estiloNotificacion);

    // Inicializar página
    mostrarProductos();
    actualizarContadorCarrito();
});