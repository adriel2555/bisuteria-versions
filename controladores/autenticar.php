<?php
session_start();
require_once '../configuracion/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $contrasena = $_POST['contrasena'];

    // Buscar usuario en la base de datos
    $sql = "SELECT UsuarioID, Nombre, Email, ContrasenaHash, EsAdministrador 
            FROM Usuarios 
            WHERE Email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $usuario = $result->fetch_assoc();
        
        // Verificar contraseña
        if (password_verify($contrasena, $usuario['ContrasenaHash'])) {
            // Guardar datos en sesión
            $_SESSION['usuario_id'] = $usuario['UsuarioID'];
            $_SESSION['nombre'] = $usuario['Nombre'];
            $_SESSION['email'] = $usuario['Email'];
            $_SESSION['es_admin'] = $usuario['EsAdministrador'];

            // Redirigir según tipo de usuario
            if ($usuario['EsAdministrador'] == 1) {
                header("Location: ../vista/admin/admin.php");
            } else {
                header("Location: ../vista/index.php");
            }
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }

    $stmt->close();
    // Guardar error en sesión para mostrar en el login
    $_SESSION['error_login'] = $error;
    header("Location: ../vista/autenticacion/iniciar-sesion.html");
    exit();
}

$conn->close();
?>