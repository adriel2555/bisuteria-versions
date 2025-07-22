<?php
// Iniciar sesión y destruirla
session_start();
session_unset();
session_destroy();

// Redirigir a la página de inicio
header("Location: ../vista/index.php");
exit();
?>