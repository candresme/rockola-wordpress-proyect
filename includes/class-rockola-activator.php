<?php
/**
 * Fired during plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Rockola_Activator {
    
    /**
     * Activar el plugin
     */
    public function activate() {
        global $wpdb;
        
        // Cargar clase DB si no está cargada
        if (!class_exists('Rockola_DB')) {
            require_once ROCKOLA_PLUGIN_PATH . 'includes/class-rockola-db.php';
        }
        
        // Crear instancia de DB
        $db = new Rockola_DB();
        
        // Crear tablas
        $db->create_tables();
        
        // Ejecutar migración para agregar user_id si no existe
        $db->migrate_database();
        
        // Guardar versión del plugin
        update_option('rockola_version', ROCKOLA_PLUGIN_VERSION);
        update_option('rockola_activated', current_time('mysql'));
        
        // Verificar que las tablas se crearon
        $tables = array(
            $wpdb->prefix . 'rockola_submissions',
            $wpdb->prefix . 'rockola_users',
            $wpdb->prefix . 'rockola_requests'
        );
        
        $all_exist = true;
        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if (!$exists) {
                $all_exist = false;
                error_log("❌ Tabla no existe: {$table}");
            } else {
                error_log("✅ Tabla existe: {$table}");
            }
        }
        
        if ($all_exist) {
            error_log('✅ Rockola Plugin Activated Successfully - All tables created');
        } else {
            error_log('⚠️ Rockola Plugin Activated but some tables are missing');
        }
        
        // Flush rewrite rules para shortcodes
        flush_rewrite_rules();
    }
    
    /**
     * Desactivar el plugin
     */
    public static function deactivate() {
        // Limpiar transients de Spotify
        delete_transient('rockola_spotify_access_token');
        
        // Limpiar cron jobs si existen
        wp_clear_scheduled_hook('rockola_daily_cleanup');
        wp_clear_scheduled_hook('rockola_token_refresh');
        
        error_log('⚠️ Rockola Plugin Deactivated');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}