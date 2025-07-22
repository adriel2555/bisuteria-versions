    // Actualizar la página cada 5 minutos (300,000 milisegundos)
    setTimeout(function() {
        window.location.reload();
    }, 300000);

    document.querySelector('.menu-toggle').addEventListener('click', function() {
        document.querySelector('.menu-admin').classList.toggle('active');
    });

    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const menuAdmin = document.querySelector('.menu-admin');
        
        if (menuToggle && menuAdmin) {
            menuToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                menuAdmin.classList.toggle('active');
                
                // Cerrar el menú al hacer clic en un enlace
                const menuLinks = menuAdmin.querySelectorAll('a');
                menuLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        menuToggle.classList.remove('active');
                        menuAdmin.classList.remove('active');
                    });
                });
            });
        }
        
        // Cerrar el menú al hacer clic fuera de él
        document.addEventListener('click', function(e) {
            if (!menuToggle.contains(e.target) && !menuAdmin.contains(e.target)) {
                menuToggle.classList.remove('active');
                menuAdmin.classList.remove('active');
            }
        });
    });