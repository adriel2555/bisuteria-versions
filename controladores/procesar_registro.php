<?php
// 1. Incluir el archivo de conexión a la base de datos
require_once '../configuracion/conexion.php';

// 2. Verificar que el formulario fue enviado con método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Obtener y limpiar los datos del formulario
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmarContrasena'];

    // 4. Validar que las contraseñas coincidan
    if ($contrasena !== $confirmar_contrasena) {
        die("Error: Las contraseñas no coinciden. Por favor, vuelve atrás e inténtalo de nuevo.");
    }

    // 5. Hashear la contraseña para almacenamiento seguro
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // 6. Preparar la consulta SQL usando parámetros para evitar inyecciones
    $sql = "INSERT INTO Usuarios (Nombre, Apellido, Email, ContrasenaHash, FechaRegistro) 
            VALUES (?, ?, ?, ?, NOW())";
    
    // 7. Crear la sentencia preparada
    $stmt = $conn->prepare($sql);
    
    // 8. Verificar si la preparación fue exitosa
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    // 9. Vincular los parámetros a la sentencia
    $stmt->bind_param("ssss", $nombre, $apellido, $email, $contrasena_hash);
    
    // 10. Ejecutar la sentencia
    if ($stmt->execute()) {
        header('Location: ../vista/autenticacion/registro-exitoso.html');
        exit();
    } else {
        // Manejar errores específicos
        if ($stmt->errno == 1062 || $conn->errno == 1062) { // Error de duplicado
            header('Location: ../vista/autenticacion/registro.html?error=email_duplicado');
            exit();
        } else {
            // Mostrar mensaje genérico de error con información para depuración
            error_log("Error al registrar: " . $stmt->error . " [Código: " . $conn->errno . "]");
            header('Location: ../vista/autenticacion/registro.html?error=general');
            exit();
        }
    }

    // 13. Cerrar recursos
    $stmt->close();
    $conn->close();
} else {
    // 14. Si alguien intenta acceder directamente a este archivo, redirigir
    header('Location: ../vista/autenticacion/registro.html');
    exit();
}
?>