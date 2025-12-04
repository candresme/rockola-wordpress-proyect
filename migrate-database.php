<?php
/**
 * Script de Migración de Base de Datos
 * Ejecuta este archivo SOLO UNA VEZ para actualizar las tablas
 *
 * Accede a: https://tu-sitio.com/wp-content/plugins/WP Spotify Rockola Pro/migrate-database.php
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que sea admin
if (!current_user_can('manage_options')) {
    die('❌ Acceso denegado. Debes ser administrador.');
}

echo '<h1>🔧 Migración de Base de Datos - Rockola Plugin</h1>';
echo '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; } pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; } .success { color: #28a745; } .error { color: #dc3545; } .warning { color: #ffc107; }</style>';

// Cargar clase DB
require_once(ROCKOLA_PLUGIN_PATH . 'includes/class-rockola-db.php');

$db = new Rockola_DB();

echo '<h2>📋 Paso 1: Crear/Actualizar Tablas</h2>';
try {
    $db->create_tables();
    echo '<p class="success">✅ Tablas creadas/actualizadas correctamente</p>';
} catch (Exception $e) {
    echo '<p class="error">❌ Error al crear tablas: ' . $e->getMessage() . '</p>';
}

echo '<h2>📋 Paso 2: Migrar Columnas Faltantes</h2>';
try {
    $db->migrate_database();
    echo '<p class="success">✅ Migración completada correctamente</p>';
} catch (Exception $e) {
    echo '<p class="error">❌ Error en migración: ' . $e->getMessage() . '</p>';
}

echo '<h2>📊 Paso 3: Verificar Estructura de Tablas</h2>';

global $wpdb;
$table_submissions = $wpdb->prefix . 'rockola_submissions';
$table_users = $wpdb->prefix . 'rockola_users';

// Verificar submissions
echo '<h3>Tabla: ' . $table_submissions . '</h3>';
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_submissions}");

if ($columns) {
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td><strong>' . $column->Field . '</strong></td>';
        echo '<td>' . $column->Type . '</td>';
        echo '<td>' . $column->Null . '</td>';
        echo '<td>' . ($column->Default ?: 'NULL') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p class="error">❌ No se pudo obtener estructura de la tabla</p>';
}

// Verificar usuarios
echo '<h3>Tabla: ' . $table_users . '</h3>';
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_users}");

if ($columns) {
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td><strong>' . $column->Field . '</strong></td>';
        echo '<td>' . $column->Type . '</td>';
        echo '<td>' . $column->Null . '</td>';
        echo '<td>' . ($column->Default ?: 'NULL') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p class="error">❌ No se pudo obtener estructura de la tabla</p>';
}

echo '<h2>📊 Paso 4: Verificar Datos</h2>';

$total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$table_users}");
$total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM {$table_submissions}");

echo '<p><strong>Total de usuarios:</strong> ' . $total_users . '</p>';
echo '<p><strong>Total de canciones:</strong> ' . $total_submissions . '</p>';

echo '<hr>';
echo '<h2 class="success">✅ Migración Completada</h2>';
echo '<p>La base de datos ha sido actualizada correctamente.</p>';
echo '<p class="warning">⚠️ <strong>IMPORTANTE:</strong> Por seguridad, elimina este archivo después de ejecutarlo.</p>';
echo '<pre>rm ' . __FILE__ . '</pre>';
