<?php
class Rockola_Core {
    
    private static $instance = null;
    private static $initialized = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor vacío
    }
    
    public function run() {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        $this->initialize_components_safely();
        $this->register_hooks();
    }
    
    private function initialize_components_safely() {
        // Inicializar SOLO con clases que no requieren parámetros en constructor
        // O modificar las clases para que los parámetros sean opcionales
        
        // 1. Spotify API - puede necesitar init()
        if (class_exists('Rockola_Spotify_API')) {
            $api = new Rockola_Spotify_API();
            if (method_exists($api, 'init')) {
                $api->init();
            }
        }

        // 2. AJAX - Inicializar siempre
        if (class_exists('Rockola_AJAX')) {
            $ajax = new Rockola_AJAX();
            if (method_exists($ajax, 'init')) {
                $ajax->init();
            }
        } else {
            error_log('❌ Rockola: AJAX class not found');
        }

        // 3. Admin - ya lo corregimos sin parámetros
        if (class_exists('Rockola_Admin')) {
            $admin = new Rockola_Admin();
            if (method_exists($admin, 'init')) {
                $admin->init();
            }
        }
        
        // 4. Public - verificar si necesita parámetros
        if (class_exists('Rockola_Public')) {
            $reflection = new ReflectionClass('Rockola_Public');
            $constructor = $reflection->getConstructor();
            
            if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
                error_log('Rockola: Public class requires constructor parameters');
            } else {
                $public = new Rockola_Public();
                if (method_exists($public, 'init')) {
                    $public->init();
                }
                
                // Registrar shortcode
                add_shortcode('rockola_spotify_form', array($public, 'display_rockola_form'));
            }
        }
    }
    
    private function register_hooks() {
        // Assets - solo una vez
        if (!has_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'))) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        }
        
        if (!has_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'))) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // Shortcode alternativo por si falla la clase Public
        if (!shortcode_exists('rockola_spotify_form_alt')) {
            add_shortcode('rockola_spotify_form_alt', array($this, 'display_fallback_form'));
        }
    }
    
    public function display_fallback_form() {
        return '<div class="rockola-fallback" style="padding: 30px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <h3 style="color: #0073aa;">🎵 Rockola Spotify Pro</h3>
            <p>Formulario de solicitud de canciones</p>
            <p><small>El sistema está en proceso de carga. Recarga la página o contacta al administrador.</small></p>
        </div>';
    }
    
    // SOLO UNA VEZ ESTA FUNCIÓN
    public function enqueue_public_assets() {
        if (!defined('ROCKOLA_PLUGIN_URL') || !defined('ROCKOLA_PLUGIN_VERSION')) {
            return;
        }
        
        // Usar URL segura siempre
        $plugin_url = ROCKOLA_PLUGIN_URL;
        
        // Forzar HTTPS si el sitio usa HTTPS
        if (is_ssl()) {
            $plugin_url = str_replace('http://', 'https://', $plugin_url);
        }
        
        // Registrar script solo si no existe
        if (!wp_script_is('rockola-public-js', 'registered')) {
            wp_register_script(
                'rockola-public-js',
                $plugin_url . 'public/js/rockola-public.js',
                array('jquery'),
                ROCKOLA_PLUGIN_VERSION,
                true
            );
        }
        
        // Registrar estilo solo si no existe
        if (!wp_style_is('rockola-public-css', 'registered')) {
            wp_register_style(
                'rockola-public-css',
                $plugin_url . 'public/css/rockola-public.css',
                array(),
                ROCKOLA_PLUGIN_VERSION
            );
        }
        
        // Encolar si no está ya
        if (!wp_script_is('rockola-public-js', 'enqueued')) {
            wp_enqueue_script('rockola-public-js');
        }
        
        if (!wp_style_is('rockola-public-css', 'enqueued')) {
            wp_enqueue_style('rockola-public-css');
        }
        
        // Localizar script con HTTPS seguro
        wp_localize_script('rockola-public-js', 'rockola_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'),
            'nonce' => wp_create_nonce('rockola_nonce'),
            'max_songs' => 3,
            'site_url' => site_url(),
            'is_ssl' => is_ssl()
        ));
    }
    
    // SOLO UNA VEZ ESTA FUNCIÓN
    public function enqueue_admin_assets($hook) {
        if (!defined('ROCKOLA_PLUGIN_URL') || !defined('ROCKOLA_PLUGIN_VERSION')) {
            return;
        }
        
        if (strpos($hook, 'rockola') === false) return;
        
        // Usar URL segura siempre
        $plugin_url = ROCKOLA_PLUGIN_URL;
        
        // Forzar HTTPS si el sitio usa HTTPS
        if (is_ssl()) {
            $plugin_url = str_replace('http://', 'https://', $plugin_url);
        }
        
        // Registrar script solo si no existe
        if (!wp_script_is('rockola-admin-js', 'registered')) {
            wp_register_script(
                'rockola-admin-js',
                $plugin_url . 'admin/js/rockola-admin.js',
                array('jquery'),
                ROCKOLA_PLUGIN_VERSION,
                true
            );
        }
        
        // Registrar estilo solo si no existe
        if (!wp_style_is('rockola-admin-css', 'registered')) {
            wp_register_style(
                'rockola-admin-css',
                $plugin_url . 'admin/css/rockola-admin.css',
                array(),
                ROCKOLA_PLUGIN_VERSION
            );
        }
        
        // Registrar Chart.js solo si no existe
        if (!wp_script_is('chart-js', 'registered')) {
            wp_register_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array(),
                '3.9.1',
                true
            );
        }
        
        // Encolar si no está ya
        if (!wp_script_is('rockola-admin-js', 'enqueued')) {
            wp_enqueue_script('rockola-admin-js');
        }
        
        if (!wp_style_is('rockola-admin-css', 'enqueued')) {
            wp_enqueue_style('rockola-admin-css');
        }
        
        if (!wp_script_is('chart-js', 'enqueued')) {
            wp_enqueue_script('chart-js');
        }
    }
}
// NO agregues nada después de esta línea