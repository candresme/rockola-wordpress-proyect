<?php
/**
 * Clase de Administración de Rockola
 * Versión 2.0 - Con configuración de límite diario
 */

class Rockola_Admin {
    
    private $db;
    
    public function __construct() {
        $this->db = new Rockola_DB();
    }
    
    public function init() {
        // Menú de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Guardar configuraciones
        add_action('admin_init', array($this, 'register_settings'));
        
        // Estilos y scripts del admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            'Rockola Spotify',
            'Rockola',
            'manage_options',
            'rockola-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-controls-volumeon',
            30
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
            'Canciones Pedidas',
            'Canciones',
            'manage_options',
            'rockola-submissions',
            array($this, 'display_submissions')
        );
        
        add_submenu_page(
            'rockola-dashboard',
            'Usuarios',
            'Usuarios',
            'manage_options',
            'rockola-users',
            array($this, 'display_users')
        );
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('rockola_settings_group', 'rockola_settings', array($this, 'sanitize_settings'));

        // Usar el hook correcto para WordPress 5.5+ con prioridad alta
        add_filter('allowed_options', array($this, 'add_allowed_options'), 1);
    }

    /**
     * Agregar opciones permitidas (WordPress 5.5+)
     */
    public function add_allowed_options($allowed_options) {
        if (!isset($allowed_options['rockola_settings_group'])) {
            $allowed_options['rockola_settings_group'] = array();
        }
        if (!in_array('rockola_settings', $allowed_options['rockola_settings_group'])) {
            $allowed_options['rockola_settings_group'][] = 'rockola_settings';
        }
        return $allowed_options;
    }
    
    /**
     * Sanitizar configuraciones
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Credenciales de Spotify
        if (isset($input['client_id'])) {
            $sanitized['client_id'] = sanitize_text_field($input['client_id']);
        }
        
        if (isset($input['client_secret'])) {
            $sanitized['client_secret'] = sanitize_text_field($input['client_secret']);
        }
        
        // Límite diario (NUEVO)
        if (isset($input['daily_limit'])) {
            $limit = intval($input['daily_limit']);
            $sanitized['daily_limit'] = ($limit > 0 && $limit <= 100) ? $limit : 3;
        } else {
            $sanitized['daily_limit'] = 3;
        }
        
        // Mensaje personalizado
        if (isset($input['welcome_message'])) {
            $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message']);
        }
        
        return $sanitized;
    }
    
    /**
     * Cargar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'rockola') === false) {
            return;
        }
        
        wp_enqueue_style('rockola-admin-css', ROCKOLA_PLUGIN_URL . 'admin/css/admin-styles.css', array(), '2.0');
        wp_enqueue_script('rockola-admin-js', ROCKOLA_PLUGIN_URL . 'admin/js/admin-scripts.js', array('jquery'), '2.0', true);
        
        wp_localize_script('rockola-admin-js', 'rockolaAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rockola_admin_nonce')
        ));
    }
    
    /**
     * Dashboard principal
     */
    public function display_dashboard() {
        $total_users = $this->db->get_total_users();
        $total_requests = $this->db->get_total_requests();
        $today_requests = $this->db->get_today_requests();
        $options = get_option('rockola_settings', array());
        $daily_limit = isset($options['daily_limit']) ? $options['daily_limit'] : 3;
        
        ?>
        <div class="wrap rockola-dashboard">
            <h1>🎵 Rockola Spotify - Dashboard</h1>
            
            <div class="rockola-stats-grid">
                <div class="rockola-stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>Usuarios Registrados</p>
                    </div>
                </div>
                
                <div class="rockola-stat-card">
                    <div class="stat-icon">🎵</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_requests); ?></h3>
                        <p>Canciones Totales</p>
                    </div>
                </div>
                
                <div class="rockola-stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo count($today_requests); ?></h3>
                        <p>Canciones Hoy</p>
                    </div>
                </div>
                
                <div class="rockola-stat-card">
                    <div class="stat-icon">⚙️</div>
                    <div class="stat-content">
                        <h3><?php echo $daily_limit; ?></h3>
                        <p>Límite Diario</p>
                    </div>
                </div>
            </div>
            
            <div class="rockola-section">
                <h2>Canciones de Hoy</h2>
                
                <?php if (empty($today_requests)): ?>
                    <div class="rockola-empty-state">
                        <div class="empty-icon">🎵</div>
                        <p>No hay canciones pedidas hoy</p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Usuario</th>
                                <th>Canción</th>
                                <th>Artista</th>
                                <th>Género</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_requests as $request): ?>
                            <tr>
                                <td><?php echo date('H:i', strtotime($request->created_at)); ?></td>
                                <td><?php echo esc_html($request->user_name ?: $request->name); ?></td>
                                <td><strong><?php echo esc_html($request->track_name); ?></strong></td>
                                <td><?php echo esc_html($request->artist_name); ?></td>
                                <td><span class="rockola-genre-badge"><?php echo esc_html($request->genre); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <style>
            .rockola-dashboard {
                padding: 20px;
            }
            
            .rockola-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            
            .rockola-stat-card {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 24px;
                display: flex;
                align-items: center;
                gap: 16px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .stat-icon {
                font-size: 48px;
                line-height: 1;
            }
            
            .stat-content h3 {
                margin: 0;
                font-size: 32px;
                font-weight: 700;
                color: #1DB954;
            }
            
            .stat-content p {
                margin: 4px 0 0 0;
                color: #666;
                font-size: 14px;
            }
            
            .rockola-section {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 24px;
                margin-top: 20px;
            }
            
            .rockola-section h2 {
                margin-top: 0;
                color: #333;
            }
            
            .rockola-empty-state {
                text-align: center;
                padding: 60px 20px;
            }
            
            .empty-icon {
                font-size: 64px;
                margin-bottom: 16px;
                opacity: 0.5;
            }
            
            .rockola-genre-badge {
                display: inline-block;
                padding: 4px 12px;
                background: #1DB954;
                color: white;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Página de configuración
     */
    public function display_settings() {
        $options = get_option('rockola_settings', array());
        $client_id = isset($options['client_id']) ? $options['client_id'] : '';
        $client_secret = isset($options['client_secret']) ? $options['client_secret'] : '';
        $daily_limit = isset($options['daily_limit']) ? $options['daily_limit'] : 3;
        $welcome_message = isset($options['welcome_message']) ? $options['welcome_message'] : '';
        
        $refresh_token = get_option('rockola_spotify_refresh_token');
        $is_connected = !empty($refresh_token);
        
        // Crear instancia de API para auth
        $spotify_api = new Rockola_Spotify_API();
        $auth_url = $spotify_api->get_auth_url();
        
        ?>
        <div class="wrap rockola-settings">
            <h1>⚙️ Configuración de Rockola</h1>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Spotify conectado exitosamente!</strong></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong>❌ Error al conectar con Spotify:</strong> <?php echo esc_html($_GET['error']); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('rockola_settings_group'); ?>
                
                <div class="rockola-settings-section">
                    <h2>🔐 Credenciales de Spotify</h2>
                    <p>Obtén tus credenciales en <a href="https://developer.spotify.com/dashboard" target="_blank">Spotify Developer Dashboard</a></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="client_id">Client ID</label></th>
                            <td>
                                <input type="text" id="client_id" name="rockola_settings[client_id]" 
                                       value="<?php echo esc_attr($client_id); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="client_secret">Client Secret</label></th>
                            <td>
                                <input type="password" id="client_secret" name="rockola_settings[client_secret]" 
                                       value="<?php echo esc_attr($client_secret); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th>Estado de Conexión</th>
                            <td>
                                <?php if ($is_connected): ?>
                                    <span class="rockola-status connected">✅ Conectado</span>
                                <?php else: ?>
                                    <span class="rockola-status disconnected">❌ No conectado</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($client_id) && !empty($client_secret)): ?>
                                    <br><br>
                                    <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">
                                        🔗 Conectar con Spotify
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="rockola-settings-section">
                    <h2>📊 Configuración de Límites</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="daily_limit">Límite de Canciones Diarias por Usuario</label></th>
                            <td>
                                <input type="number" id="daily_limit" name="rockola_settings[daily_limit]" 
                                       value="<?php echo esc_attr($daily_limit); ?>" min="1" max="100" class="small-text">
                                <p class="description">Número máximo de canciones que cada usuario puede pedir por día (1-100)</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="rockola-settings-section">
                    <h2>💬 Personalización</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="welcome_message">Mensaje de Bienvenida</label></th>
                            <td>
                                <textarea id="welcome_message" name="rockola_settings[welcome_message]" 
                                          rows="4" class="large-text"><?php echo esc_textarea($welcome_message); ?></textarea>
                                <p class="description">Mensaje personalizado que verán los usuarios (opcional)</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('💾 Guardar Configuración'); ?>
            </form>
            
            <style>
            .rockola-settings-section {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 24px;
                margin: 20px 0;
            }
            
            .rockola-settings-section h2 {
                margin-top: 0;
                color: #333;
                border-bottom: 2px solid #1DB954;
                padding-bottom: 12px;
            }
            
            .rockola-status {
                display: inline-block;
                padding: 8px 16px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 14px;
            }
            
            .rockola-status.connected {
                background: #d4edda;
                color: #155724;
            }
            
            .rockola-status.disconnected {
                background: #f8d7da;
                color: #721c24;
            }
            </style>
        </div>
        <?php
    }
    
    /**
     * Página de submissions
     */
    public function display_submissions() {
        $submissions = $this->db->get_recent_submissions();
        
        ?>
        <div class="wrap">
            <h1>🎵 Canciones Pedidas</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Canción</th>
                        <th>Artista</th>
                        <th>Álbum</th>
                        <th>Género</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                No hay canciones pedidas aún
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($sub->created_at)); ?></td>
                            <td><?php echo esc_html($sub->user_name ?: $sub->name); ?></td>
                            <td><strong><?php echo esc_html($sub->track_name); ?></strong></td>
                            <td><?php echo esc_html($sub->artist_name); ?></td>
                            <td><?php echo esc_html($sub->album_name); ?></td>
                            <td><span class="rockola-genre-badge"><?php echo esc_html($sub->genre); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Página de usuarios
     */
    public function display_users() {
        global $wpdb;
        $users_table = $wpdb->prefix . 'rockola_users';
        $users = $wpdb->get_results("SELECT * FROM {$users_table} ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1>👥 Usuarios Registrados</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                        <th>Cumpleaños</th>
                        <th>Registrado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                No hay usuarios registrados aún
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user->id; ?></td>
                            <td><strong><?php echo esc_html($user->name); ?></strong></td>
                            <td><?php echo esc_html($user->email); ?></td>
                            <td><?php echo esc_html($user->whatsapp); ?></td>
                            <td><?php echo $user->birthday ? date('d/m/Y', strtotime($user->birthday)) : '-'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($user->created_at)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
