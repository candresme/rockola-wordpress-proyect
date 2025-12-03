<?php
/**
 * Plugin Name: WP Spotify Rockola Pro
 * Plugin URI: https://tu-sitio.com/wp-spotify-rockola-pro
 * Description: Sistema completo de rockola integrado con Spotify para restaurantes
 * Version: 1.0.0
 * Author: Tu Nombre
 * License: GPL v2 or later
 * Text Domain: rockola-spotify
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// DEFINIR TODAS LAS CONSTANTES AQUÍ - NOMBRES CONSISTENTES
define('ROCKOLA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ROCKOLA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ROCKOLA_PLUGIN_VERSION', '1.0.0');
define('ROCKOLA_TABLE_SUBMISSIONS', 'rockola_submissions');
define('ROCKOLA_TABLE_STATS', 'rockola_stats');

// Cargar SOLO lo necesario para activación
require_once ROCKOLA_PLUGIN_PATH . 'includes/class-rockola-activator.php';

// Hook de activación SIMPLE
register_activation_hook(__FILE__, 'rockola_simple_activate');
function rockola_simple_activate() {
    $activator = new Rockola_Activator();
    $activator->activate();
}

// Hook de desactivación SIMPLE
register_deactivation_hook(__FILE__, 'rockola_simple_deactivate');
function rockola_simple_deactivate() {
    delete_transient('rockola_spotify_access_token');
}

// Cargar el plugin cuando WordPress esté listo
add_action('init', 'rockola_init_plugin_safe');
function rockola_init_plugin_safe() {
    // Lista de archivos en orden CORRECTO
    $files = [
        'includes/class-rockola-db.php',
        'includes/class-rockola-spotify-api.php',
        'includes/class-rockola-ajax.php',
        'includes/class-rockola-core.php',
        'admin/class-rockola-admin.php',
        'public/class-rockola-public.php'
    ];
    
    // Cargar cada archivo con verificación
    foreach ($files as $file) {
        $file_path = ROCKOLA_PLUGIN_PATH . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // Inicializar si existe el Core
    if (class_exists('Rockola_Core')) {
        $core = Rockola_Core::get_instance();
        $core->run();
    }
}

// Shortcode de emergencia
add_shortcode('rockola_spotify_form', function($atts) {
    return '<div class="rockola-placeholder" style="padding: 30px; background: #f5f5f5; text-align: center; border-radius: 5px;">
        <h3>🎵 Rockola Spotify Pro</h3>
        <p>El sistema de música está listo para usar.</p>
        <p><em>Si no ves el formulario, contacta al administrador.</em></p>
    </div>';
});