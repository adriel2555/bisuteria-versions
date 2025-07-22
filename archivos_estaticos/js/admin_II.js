        // Script para el menú responsive
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar-admin').classList.toggle('active');
        });
        
        // Simular actualización de datos en tiempo real
        setInterval(function() {
            // Actualizar solo los valores numéricos
            const valores = document.querySelectorAll('.valor');
            valores.forEach(valor => {
                // Incrementar ligeramente los valores para simular cambios
                let num = parseFloat(valor.textContent.replace('S/ ', '').replace(',', ''));
                if (!isNaN(num)) {
                    const incremento = num * 0.01;
                    valor.textContent = 'S/ ' + (num + incremento).toFixed(2);
                } else {
                    num = parseInt(valor.textContent);
                    if (!isNaN(num)) {
                        const incremento = Math.floor(Math.random() * 3);
                        valor.textContent = num + incremento;
                    }
                }
            });
        }, 10000); // Actualizar cada 10 segundos

        