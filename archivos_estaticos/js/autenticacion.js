// Validación del formulario de restablecimiento
document.getElementById('formulario-restablecer')?.addEventListener('submit', function(e) {
    const contrasena = document.getElementById('contrasena').value;
    const confirmar = document.getElementById('confirmar_contrasena').value;
    
    // Validar que las contraseñas coincidan
    if (contrasena !== confirmar) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    // Validar fortaleza de la contraseña
    if (contrasena.length < 8 || !/\d/.test(contrasena) || !/[A-Z]/.test(contrasena)) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 8 caracteres, un número y una letra mayúscula');
        return false;
    }
    
    return true;
});

// Mostrar mensajes de error según parámetros URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error) {
        let mensaje = '';
        
        switch(error) {
            case 'contrasenas_no_coinciden':
                mensaje = 'Las contraseñas no coinciden';
                break;
            case 'contrasena_debil':
                mensaje = 'La contraseña debe tener al menos 8 caracteres, un número y una letra mayúscula';
                break;
            case 'token_invalido':
                mensaje = 'El enlace de recuperación no es válido o ha expirado';
                break;
            case 'email_no_encontrado':
                mensaje = 'No existe una cuenta con ese correo electrónico';
                break;
            default:
                mensaje = 'Ocurrió un error al procesar tu solicitud';
        }
        
        const contenedorError = document.createElement('div');
        contenedorError.className = 'mensaje-error';
        contenedorError.textContent = mensaje;
        
        const formulario = document.querySelector('.formulario-autenticacion form');
        if (formulario) {
            formulario.prepend(contenedorError);
        }
    }
});

//---PARA EL OJO DE INPUT CONTRASEÑA--///


document.addEventListener("DOMContentLoaded", () => {
    const togglePassword = document.getElementById("toggle-password");
    const passwordInput = document.getElementById("contrasena");

    const toggleConfirmPassword = document.getElementById("toggle-confirm-password");
    const confirmPasswordInput = document.getElementById("confirmarContrasena");

    togglePassword.addEventListener("click", () => {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        togglePassword.textContent = type === "password" ? "👁️" : "👁️";
    });

    toggleConfirmPassword.addEventListener("click", () => {
        const type = confirmPasswordInput.getAttribute("type") === "password" ? "text" : "password";
        confirmPasswordInput.setAttribute("type", type);
        toggleConfirmPassword.textContent = type === "password" ? "👁️" : "👁️";
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error === 'email_duplicado') {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'mensaje-error';
        errorDiv.textContent = 'El correo electrónico ya está registrado. Por favor usa otro correo.';
        
        const formulario = document.getElementById('formulario-registro');
        if (formulario) {
            formulario.prepend(errorDiv);
        }
    } else if (error === 'general') {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'mensaje-error';
        errorDiv.textContent = 'Ocurrió un error al registrar. Por favor intenta nuevamente.';
        
        const formulario = document.getElementById('formulario-registro');
        if (formulario) {
            formulario.prepend(errorDiv);
        }
    }
});
