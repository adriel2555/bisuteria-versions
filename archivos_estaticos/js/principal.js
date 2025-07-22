// Función para actualizar el contador del carrito


// Inicializar contador del carrito al cargar la página
document.addEventListener('DOMContentLoaded', actualizarContadorCarrito);

// Función para agregar productos al carrito

// Función para mostrar notificaciones
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

// Estilo para notificaciones (se puede agregar al CSS)
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