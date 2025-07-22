document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay usuario autenticado
    const usuario = JSON.parse(sessionStorage.getItem('usuarioAutenticado'));
    const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
    
    if (!usuario) {
        sessionStorage.setItem('urlRedireccion', 'checkout.html');
        window.location.href = 'iniciar-sesion.html';
        return;
    }
    
    if (carrito.length === 0) {
        window.location.href = 'productos.html';
        return;
    }
    
    // Elementos del DOM
    const formularioCheckout = document.getElementById('formulario-checkout');
    const resumenPedido = document.querySelector('.resumen-pedido');
    const metodoPago = document.getElementById('metodo-pago');
    const detallesTarjeta = document.getElementById('detalles-tarjeta');
    const detallesYape = document.getElementById('detalles-yape');
    
    // Mostrar resumen del pedido
    function mostrarResumenPedido() {
        let subtotal = 0;
        let htmlProductos = '';
        
        carrito.forEach(item => {
            const totalProducto = item.precio * item.cantidad;
            subtotal += totalProducto;
            
            htmlProductos += `
                <div class="item-resumen">
                    <span class="nombre-producto">${item.nombre} x${item.cantidad}</span>
                    <span class="precio-producto">S/ ${totalProducto.toFixed(2)}</span>
                </div>
            `;
        });
        
        const envio = 10.00; // Costo fijo de envío
        const total = subtotal + envio;
        
        resumenPedido.innerHTML = `
            <h3>Resumen de tu pedido</h3>
            <div class="productos-resumen">
                ${htmlProductos}
            </div>
            <div class="totales-resumen">
                <div class="fila-total">
                    <span>Subtotal:</span>
                    <span>S/ ${subtotal.toFixed(2)}</span>
                </div>
                <div class="fila-total">
                    <span>Envío:</span>
                    <span>S/ ${envio.toFixed(2)}</span>
                </div>
                <div class="fila-total total">
                    <span>Total:</span>
                    <span>S/ ${total.toFixed(2)}</span>
                </div>
            </div>
        `;
    }
    
    // Mostrar detalles de pago según método seleccionado
    metodoPago.addEventListener('change', function() {
        detallesTarjeta.style.display = 'none';
        detallesYape.style.display = 'none';
        
        switch (this.value) {
            case 'tarjeta':
                detallesTarjeta.style.display = 'block';
                break;
            case 'yape':
                detallesYape.style.display = 'block';
                break;
        }
    });
    
    // Procesar el pago
    formularioCheckout.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar datos del formulario
        const direccion = this.direccion.value;
        const telefono = this.telefono.value;
        const metodo = this.metodo.value;
        
        if (!direccion || !telefono) {
            mostrarError('Por favor completa todos los campos requeridos');
            return;
        }
        
        if (metodo === 'tarjeta') {
            const numeroTarjeta = this.numeroTarjeta.value;
            const nombreTarjeta = this.nombreTarjeta.value;
            const expiracion = this.expiracion.value;
            const cvv = this.cvv.value;
            
            if (!numeroTarjeta || !nombreTarjeta || !expiracion || !cvv) {
                mostrarError('Por favor completa todos los datos de la tarjeta');
                return;
            }
            
            if (!validarTarjeta(numeroTarjeta)) {
                mostrarError('El número de tarjeta no es válido');
                return;
            }
        }
        
        // Simular procesamiento del pago
        mostrarExito('Procesando tu pago...');
        
        // Crear pedido
        const pedido = {
            id: Date.now(),
            fecha: new Date().toISOString(),
            usuario: {
                id: usuario.id,
                nombre: usuario.nombre,
                email: usuario.email
            },
            direccion,
            telefono,
            metodoPago: metodo,
            productos: [...carrito],
            subtotal: carrito.reduce((total, item) => total + (item.precio * item.cantidad), 0),
            envio: 10.00,
            total: carrito.reduce((total, item) => total + (item.precio * item.cantidad), 0) + 10.00,
            estado: 'procesando'
        };
        
        // Guardar pedido (en un proyecto real sería una llamada a la API)
        const pedidos = JSON.parse(localStorage.getItem('pedidos')) || [];
        pedidos.push(pedido);
        localStorage.setItem('pedidos', JSON.stringify(pedidos));
        
        // Limpiar carrito
        localStorage.removeItem('carrito');
        actualizarContadorCarrito();
        
        // Redirigir a confirmación después de 2 segundos
        setTimeout(() => {
            window.location.href = `confirmacion-pedido.html?id=${pedido.id}`;
        }, 2000);
    });
    
    // Función para validar número de tarjeta (algoritmo de Luhn)
    function validarTarjeta(numero) {
        // Eliminar espacios y caracteres no numéricos
        numero = numero.replace(/\D/g, '');
        
        // Verificar que sea una cadena de números
        if (!/^\d+$/.test(numero)) return false;
        
        // Algoritmo de Luhn
        let suma = 0;
        let alternar = false;
        
        for (let i = numero.length - 1; i >= 0; i--) {
            let digito = parseInt(numero.charAt(i));
            
            if (alternar) {
                digito *= 2;
                if (digito > 9) {
                    digito = (digito % 10) + 1;
                }
            }
            
            suma += digito;
            alternar = !alternar;
        }
        
        return (suma % 10 === 0);
    }
    
    // Mostrar resumen al cargar la página
    mostrarResumenPedido();
});