// Función para cambiar entre pestañas
        function showTab(tabId) {
            // Ocultar todos los contenidos de pestañas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Desactivar todas las pestañas
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activar pestaña seleccionada
            document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('active');
            
            // Mostrar contenido de pestaña seleccionada
            document.getElementById(`${tabId}-tab`).classList.add('active');
        }
        
        // Búsqueda de productos
        document.getElementById('search-product').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.product-table tbody tr');
            
            rows.forEach(row => {
                const productName = row.cells[1].textContent.toLowerCase();
                row.style.display = productName.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Mostrar mensaje de conexión a BD
        document.addEventListener('DOMContentLoaded', function() {
            const dbMessage = document.createElement('div');
            dbMessage.className = 'message info';
            dbMessage.innerHTML = `
                <div>🔌</div>
                <div>Conectado a la base de datos: bisuteria | Tabla: Productos</div>
            `;
            
            document.querySelector('.contenido-principal-admin').insertBefore(dbMessage, document.querySelector('.tabs'));
            
            // Simular carga de datos
            setTimeout(() => {
                dbMessage.innerHTML = `
                    <div>✅</div>
                    <div>Conexión exitosa a la base de datos | Productos cargados</div>
                `;
                dbMessage.className = 'message success';
            }, 1500);
        });