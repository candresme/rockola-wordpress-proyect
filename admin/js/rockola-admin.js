(function( $ ) {
	'use strict';

	$(document).ready(function() {
        
        // Verificar si estamos en la página del dashboard y si existe el canvas
        const ctx = document.getElementById('rockolaGenreChart');
        
        if (ctx && typeof rockola_admin_vars !== 'undefined') {
            
            // Datos pasados desde PHP (wp_localize_script)
            const stats = rockola_admin_vars.stats_genre;
            
            // Procesar datos para Chart.js
            const labels = stats.map(item => item.genre ? item.genre : 'Otros');
            const dataCounts = stats.map(item => item.count);
            
            // Colores estilo Spotify / Dark
            const backgroundColors = [
                '#1DB954', // Spotify Green
                '#191414', // Spotify Black
                '#535353', // Dark Grey
                '#B3B3B3', // Light Grey
                '#FF5733', // Accent
                '#337aff',
                '#f9c80e'
            ];

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pedidos por Género',
                        data: dataCounts,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Distribución de Géneros Musicales'
                        }
                    }
                }
            });
        }

	});

})( jQuery );