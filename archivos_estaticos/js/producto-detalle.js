document.addEventListener('DOMContentLoaded', function() {
    // Obtener ID del producto de la URL
    const params = new URLSearchParams(window.location.search);
    const productoId = params.get('id');
    
    // Productos de ejemplo (en un proyecto real sería una llamada a la API)
    const productos = [
        {
            id: 1,
            nombre: "Piedras de cuarzo rosa",
            descripcion: "Paquete de 20 piedras de cuarzo rosa para bisutería. Estas piedras naturales son perfectas para crear joyas únicas y personalizadas. Cada piedra tiene un tamaño aproximado de 8mm.",
            precio: 25.00,
            precioAnterior: 30.00,
            categoria: "piedras",
            imagenes: [
                "imagenes/producto1.jpg",
                "imagenes/producto1-2.jpg",
                "imagenes/producto1-3.jpg"
            ],
            especificaciones: {
                material: "Cuarzo rosa natural",
                cantidad: "20 piezas",
                tamaño: "8mm",
                color: "Rosa",
                origen: "Brasil"
            },
            nuevo: true,
            stock: 15
        },
        // Más productos...
    ];
    
    const producto = productos.find(p => p.id == productoId);
    
    if (!producto) {
        window.location.href = 'productos.html';
        return;
    }
    
    // Elementos del DOM
    const contenedorProducto = document.querySelector('.contenedor-producto');
    const galeriaImagenes = document.querySelector('.galeria-imagenes');
    const imagenPrincipal = document.querySelector('.imagen-principal');
    const nombreProducto = document.querySelector('.nombre-producto');
    const precioProducto = document.querySelector('.precio-producto');
    const precioAnterior = document.querySelector('.precio-anterior');
    const descripcionProducto = document.querySelector('.descripcion-producto');
    const selectorCantidad = document.querySelector('.selector-cantidad');
    const botonAgregarCarrito = document.querySelector('.boton-agregar-carrito');
    const listaEspecificaciones = document.querySelector('.lista-especificaciones');
    const stockDisponible = document.querySelector('.stock-disponible');
    
    // Mostrar producto
    function mostrarProducto() {
        // Galería de imágenes
        galeriaImagenes.innerHTML = '';
        producto.imagenes.forEach((imagen, index) => {
            const img = document.createElement('img');
            img.src = imagen;
            img.alt = `${producto.nombre} ${index + 1}`;
            img.addEventListener('click', () => {
                imagenPrincipal.src = imagen;
            });
            galeriaImagenes.appendChild(img);
        });
        
        // Imagen principal
        imagenPrincipal.src = producto.imagenes[0];
        
        // Información del producto
        nombreProducto.textContent = producto.nombre;
        precioProducto.textContent = `S/ ${producto.precio.toFixed(2)}`;
        
        if (producto.precioAnterior) {
            precioAnterior.textContent = `S/ ${producto.precioAnterior.toFixed(2)}`;
            precioAnterior.style.display = 'inline';
        } else {
            precioAnterior.style.display = 'none';
        }
        
        descripcionProducto.textContent = producto.descripcion;
        stockDisponible.textContent = producto.stock > 0 ? 
            `Disponible (${producto.stock} unidades)` : 'Agotado';
        
        // Especificaciones
        listaEspecificaciones.innerHTML = '';
        for (const [clave, valor] of Object.entries(producto.especificaciones)) {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${clave}:</strong> ${valor}`;
            listaEspecificaciones.appendChild(li);
        }
        
        // Selector de cantidad
        selectorCantidad.innerHTML = '';
        const maxCantidad = Math.min(producto.stock, 10);
        
        for (let i = 1; i <= maxCantidad; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            selectorCantidad.appendChild(option);
        }
        
        // Botón de agregar al carrito
        if (producto.stock > 0) {
            botonAgregarCarrito.disabled = false;
            botonAgregarCarrito.textContent = 'Agregar al carrito';
        } else {
            botonAgregarCarrito.disabled = true;
            botonAgregarCarrito.textContent = 'Producto agotado';
        }
    }
    
    // Evento para agregar al carrito
    botonAgregarCarrito.addEventListener('click', function() {
        const cantidad = parseInt(selectorCantidad.value);
        
        agregarAlCarrito(
            producto.id,
            producto.nombre,
            producto.precio,
            cantidad
        );
        
        // Mostrar notificación
        mostrarNotificacion(`${cantidad} ${producto.nombre} agregado(s) al carrito`);
    });
    
    // Inicializar página
    mostrarProducto();
});