document.addEventListener('DOMContentLoaded', function() {
    // Verificar si el usuario es administrador
    const usuario = JSON.parse(sessionStorage.getItem('usuarioAutenticado'));
    
    if (!usuario || !usuario.esAdmin) {
        window.location.href = 'iniciar-sesion.html';
        return;
    }
    
    // Cargar datos para el dashboard
    const pedidos = JSON.parse(localStorage.getItem('pedidos')) || [];
    const productos = JSON.parse(localStorage.getItem('productos')) || [];
    const usuarios = JSON.parse(localStorage.getItem('usuarios')) || [];
    
    // Mostrar estadísticas
    function mostrarEstadisticas() {
        // Ventas de hoy
        const hoy = new Date().toISOString().split('T')[0];
        const ventasHoy = pedidos
            .filter(p => p.fecha.startsWith(hoy))
            .reduce((total, p) => total + p.total, 0);
        
        // Pedidos de hoy
        const pedidosHoy = pedidos.filter(p => p.fecha.startsWith(hoy)).length;
        
        // Total de productos
        const totalProductos = productos.length;
        
        // Nuevos clientes (últimos 7 días)
        const sieteDiasAtras = new Date();
        sieteDiasAtras.setDate(sieteDiasAtras.getDate() - 7);
        
        const nuevosClientes = usuarios.filter(u => {
            const fechaRegistro = new Date(u.fechaRegistro || Date.now());
            return fechaRegistro >= sieteDiasAtras;
        }).length;
        
        // Actualizar UI
        document.querySelector('.tarjeta-estadistica.ventas .valor').textContent = `S/ ${ventasHoy.toFixed(2)}`;
        document.querySelector('.tarjeta-estadistica.pedidos .valor').textContent = pedidosHoy;
        document.querySelector('.tarjeta-estadistica.productos .valor').textContent = totalProductos;
        document.querySelector('.tarjeta-estadistica.clientes .valor').textContent = nuevosClientes;
    }
    
    // Mostrar últimos pedidos
    function mostrarUltimosPedidos() {
        const tablaPedidos = document.querySelector('.tabla-pedidos tbody');
        tablaPedidos.innerHTML = '';
        
        const pedidosRecientes = [...pedidos]
            .sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
            .slice(0, 5);
        
        pedidosRecientes.forEach(pedido => {
            const fila = document.createElement('tr');
            
            fila.innerHTML = `
                <td>#${pedido.id.toString().slice(-5)}</td>
                <td>${pedido.usuario.nombre}</td>
                <td>${new Date(pedido.fecha).toLocaleDateString()}</td>
                <td>S/ ${pedido.total.toFixed(2)}</td>
                <td><span class="estado ${pedido.estado}">${pedido.estado.charAt(0).toUpperCase() + pedido.estado.slice(1)}</span></td>
                <td>
                    <button class="boton-accion ver" data-id="${pedido.id}">
                        <img src="imagenes/view-icon.png" alt="Ver">
                    </button>
                    <button class="boton-accion editar" data-id="${pedido.id}">
                        <img src="imagenes/edit-icon.png" alt="Editar">
                    </button>
                </td>
            `;
            
            tablaPedidos.appendChild(fila);
        });
        
        // Event listeners para botones
        document.querySelectorAll('.boton-accion.ver').forEach(boton => {
            boton.addEventListener('click', function() {
                const pedidoId = this.dataset.id;
                // Aquí podrías abrir un modal con los detalles del pedido
                console.log('Ver pedido:', pedidoId);
            });
        });
        
        document.querySelectorAll('.boton-accion.editar').forEach(boton => {
            boton.addEventListener('click', function() {
                const pedidoId = this.dataset.id;
                // Aquí podrías redirigir a una página de edición
                console.log('Editar pedido:', pedidoId);
            });
        });
    }
    
    // Inicializar dashboard
    mostrarEstadisticas();
    mostrarUltimosPedidos();
    
    // Manejar cierre de sesión
    document.querySelector('.cerrar-sesion-admin a').addEventListener('click', function(e) {
        e.preventDefault();
        sessionStorage.removeItem('usuarioAutenticado');
        window.location.href = 'iniciar-sesion.html';
    });
});