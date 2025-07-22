document.addEventListener('DOMContentLoaded', function() {
    const formularioContacto = document.getElementById('formulario-contacto');
    
    if (formularioContacto) {
        formularioContacto.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const nombre = this.nombre.value;
            const email = this.email.value;
            const telefono = this.telefono.value;
            const asunto = this.asunto.value;
            const mensaje = this.mensaje.value;
            
            // Validación básica
            if (!nombre || !email || !asunto || !mensaje) {
                mostrarError('Por favor completa todos los campos requeridos');
                return;
            }
            
            if (!validarEmail(email)) {
                mostrarError('Por favor ingresa un email válido');
                return;
            }
            
            // Simular envío del formulario
            mostrarExito('Tu mensaje ha sido enviado. Nos pondremos en contacto contigo pronto.');
            
            // Limpiar formulario
            this.reset();
        });
    }
    
    // Función para validar email
    function validarEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Función para mostrar mensaje de error
    function mostrarError(mensaje) {
        const contenedorError = document.querySelector('.mensaje-error');
        
        if (!contenedorError) {
            const divError = document.createElement('div');
            divError.className = 'mensaje-error';
            divError.textContent = mensaje;
            
            formularioContacto.prepend(divError);
            
            setTimeout(() => {
                divError.classList.add('mostrar');
            }, 10);
            
            setTimeout(() => {
                divError.classList.remove('mostrar');
                setTimeout(() => {
                    divError.remove();
                }, 300);
            }, 5000);
        }
    }
    
    // Función para mostrar mensaje de éxito
    function mostrarExito(mensaje) {
        const divExito = document.createElement('div');
        divExito.className = 'mensaje-exito';
        divExito.textContent = mensaje;
        
        formularioContacto.prepend(divExito);
        
        setTimeout(() => {
            divExito.classList.add('mostrar');
        }, 10);
        
        setTimeout(() => {
            divExito.classList.remove('mostrar');
            setTimeout(() => {
                divExito.remove();
            }, 300);
        }, 5000);
    }
});