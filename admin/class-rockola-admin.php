<?php
class Rockola_Admin {
    
    public function __construct() {
        // Constructor vacío - sin parámetros
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            'Rockola Spotify Pro',
            'Rockola Spotify',
            'manage_options',
            'rockola-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-format-audio',
            30
        );
        
        // Submenús
        add_submenu_page(
            'rockola-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'rockola-dashboard',
            array($this, 'display_dashboard')
        );
        
        add_submenu_page(
            'rockola-dashboard',
            'Configuración',
            'Configuración',
            'manage_options',
            'rockola-settings',
            array($this, 'display_settings')
        );
        
        add_submenu_page(
            'rockola-dashboard',
            'Solicitudes',
            'Solicitudes',
            'manage_options',
            'rockola-submissions',
            array($this, 'display_submissions')
        );
    }
    
    public function display_dashboard() {
        ?>
        <div class="wrap">
            <h1>Rockola Spotify Pro - Dashboard</h1>
            
            <div style="background: #fff; padding: 20px; border-radius: 5px; margin-top: 20px;">
                <h2>Bienvenido al Plugin Rockola Spotify Pro</h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
                    <div style="background: #f7f7f7; padding: 20px; border-radius: 5px; border-left: 4px solid #0073aa;">
                        <h3>Configuración Inicial</h3>
                        <p>1. Ve a <a href="<?php echo admin_url('admin.php?page=rockola-settings'); ?>">Configuración</a></p>
                        <p>2. Conecta con Spotify</p>
                        <p>3. Usa el shortcode: <code>[rockola_spotify_form]</code></p>
                    </div>
                    
                    <div style="background: #f7f7f7; padding: 20px; border-radius: 5px; border-left: 4px solid #46b450;">
                        <h3>Estadísticas</h3>
                        <p>Total de solicitudes: <strong>0</strong></p>
                        <p>Usuarios únicos: <strong>0</strong></p>
                        <p><a href="<?php echo admin_url('admin.php?page=rockola-submissions'); ?>">Ver todas las solicitudes</a></p>
                    </div>
                    
                    <div style="background: #f7f7f7; padding: 20px; border-radius: 5px; border-left: 4px solid #f56e28;">
                        <h3>Ayuda Rápida</h3>
                        <p><strong>Shortcode:</strong> [rockola_spotify_form]</p>
                        <p><strong>Atributos opcionales:</strong></p>
                        <ul>
                            <li>max_songs="3"</li>
                            <li>show_preview="yes"</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function display_settings() {

        // Panel de debug AJAX
        echo '<div style="background: #1d2327; color: white; padding: 15px; margin-top: 20px; border-radius: 5px;">';
        echo '<h4>🔧 Debug AJAX</h4>';
        echo '<button onclick="testRockolaAJAX()" class="button">Test AJAX Connection</button>';
        echo '<div id="ajax-debug" style="margin-top: 10px; font-family: monospace; font-size: 12px;"></div>';
        echo '</div>';

        echo '<script>
        function testRockolaAJAX() {
            const debugDiv = document.getElementById("ajax-debug");
            debugDiv.innerHTML = "Testing...";
            
            jQuery.ajax({
                url: "' . admin_url('admin-ajax.php') . '",
                type: "POST",
                data: {
                    action: "rockola_test_connection",
                    nonce: "' . wp_create_nonce('rockola_nonce') . '"
                },
                success: function(response) {
                    debugDiv.innerHTML = "<span style=\"color: #00d084;\">✅ Success: " + JSON.stringify(response) + "</span>";
                    console.log("Rockola AJAX Test Success:", response);
                },
                error: function(xhr, status, error) {
                    debugDiv.innerHTML = "<span style=\"color: #f86368;\">❌ Error: " + error + "</span>";
                    console.error("Rockola AJAX Test Error:", {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                }
            });
        }
        </script>';


        $settings = get_option('rockola_settings', array(
            'client_id' => '',
            'client_secret' => '',
            'max_songs' => 3
        ));
        
        if (isset($_POST['save_settings'])) {
            check_admin_referer('rockola_settings_nonce');
            
            $settings['client_id'] = sanitize_text_field($_POST['client_id']);
            $settings['client_secret'] = sanitize_text_field($_POST['client_secret']);
            $settings['max_songs'] = intval($_POST['max_songs']);
            
            update_option('rockola_settings', $settings);
            echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Configuración - Rockola Spotify Pro</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('rockola_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="client_id">Client ID de Spotify</label></th>
                        <td>
                            <input type="text" id="client_id" name="client_id" 
                                   value="<?php echo esc_attr($settings['client_id']); ?>" 
                                   class="regular-text">
                            <p class="description">Obtén este valor desde el <a href="https://developer.spotify.com/dashboard" target="_blank">Spotify Developer Dashboard</a></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="client_secret">Client Secret de Spotify</label></th>
                        <td>
                            <input type="password" id="client_secret" name="client_secret" 
                                   value="<?php echo esc_attr($settings['client_secret']); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="max_songs">Máximo de canciones por usuario</label></th>
                        <td>
                            <input type="number" id="max_songs" name="max_songs" 
                                   value="<?php echo esc_attr($settings['max_songs']); ?>" 
                                   min="1" max="10" class="small-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Estado de Spotify</th>
                        <td>
                            <?php
                            $refresh_token = get_option('rockola_spotify_refresh_token');
                            if ($refresh_token) {
                                echo '<span style="color: green;">✓ Conectado a Spotify</span>';
                            } else {
                                echo '<span style="color: #f56e28;">✗ No conectado</span>';
                                if ($settings['client_id'] && $settings['client_secret']) {
                                    echo '<p><a href="' . home_url('/wp-json/rockola/v1/callback') . '" class="button button-primary">Conectar con Spotify</a></p>';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_settings" class="button button-primary" value="Guardar Cambios">
                </p>
            </form>
            
            <div style="background: #f7f7f7; padding: 20px; border-radius: 5px; margin-top: 30px;">
                <h3>Instrucciones de Configuración</h3>
                <ol>
                    <li>Crea una aplicación en <a href="https://developer.spotify.com/dashboard" target="_blank">Spotify Developer Dashboard</a></li>
                    <li>En la configuración de la app, añade esta Redirect URI:
                        <br><code><?php echo home_url('/wp-json/rockola/v1/callback'); ?></code>
                    </li>
                    <li>Copia el Client ID y Client Secret en los campos de arriba</li>
                    <li>Guarda los cambios y haz clic en "Conectar con Spotify"</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    public function display_submissions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rockola_submissions';
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50", ARRAY_A);
        ?>
        <div class="wrap">
            <h1>Solicitudes de Canciones</h1>
            
            <div style="margin: 20px 0;">
                <a href="#" class="button">Exportar CSV</a>
                <a href="#" class="button">Exportar JSON</a>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Canción</th>
                        <th>Artista</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($submissions): ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo $submission['id']; ?></td>
                                <td><?php echo esc_html($submission['name']); ?></td>
                                <td><?php echo esc_html($submission['email']); ?></td>
                                <td><?php echo esc_html($submission['track_name']); ?></td>
                                <td><?php echo esc_html($submission['artist_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($submission['created_at'])); ?></td>
                                <td><?php echo $submission['hour']; ?>:00</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No hay solicitudes aún.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}