// Mensaje simple en consola (opcional)
console.log("Página 404 cargada - Bisutería en Cusco");

// Efecto opcional: mensaje de bienvenida al pasar el mouse sobre el botón
const btnHome = document.querySelector('.btn-home');

if (btnHome) {
  btnHome.addEventListener('mouseover', () => {
    btnHome.textContent = "Regresar a nuestra tienda 💎";
  });

  btnHome.addEventListener('mouseout', () => {
    btnHome.textContent = "Volver a la tienda";
  });
}