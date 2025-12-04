<?php
/**
 * Script de Emergencia: Agregar columna spotify_uri
 *
 * Accede a: https://purococinahonesta.com/wp-content/plugins/WP Spotify Rockola Pro/fix-spotify-uri.php
 *
 * Este script agregará la columna faltante sin necesidad de desactivar el plugin
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que sea admin
if (!current_user_can('manage_options')) {
    die('❌ Acceso denegado. Debes ser administrador.');
}

echo '<h1>🔧 Fix: Agregar Columna spotify_uri</h1>';
echo '<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        padding: 40px;
        max-width: 900px;
        margin: 0 auto;
        background: #f5f5f5;
    }
    .container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    pre {
        background: #f8f8f8;
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
        border-left: 4px solid #1DB954;
    }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #17a2b8; }
    hr { margin: 30px 0; border: none; border-top: 2px solid #eee; }
</style>';

echo '<div class="container">';

global $wpdb;
$table = $wpdb->prefix . 'rockola_submissions';

echo '<h2>📋 Paso 1: Verificar Tabla</h2>';

// Verificar que la tabla existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

if (!$table_exists) {
    echo '<p class="error">❌ ERROR: La tabla ' . $table . ' no existe.</p>';
    echo '<p>Debes activar el plugin primero.</p>';
    echo '</div>';
    exit;
}

echo '<p class="success">✅ Tabla encontrada: ' . $table . '</p>';

echo '<h2>📋 Paso 2: Verificar Columna spotify_uri</h2>';

// Verificar si la columna ya existe
$column_exists = $wpdb->get_results(
    "SHOW COLUMNS FROM {$table} LIKE 'spotify_uri'"
);

if (!empty($column_exists)) {
    echo '<p class="warning">⚠️ La columna spotify_uri YA EXISTE en la tabla.</p>';
    echo '<p>No es necesario ejecutar este script.</p>';

    // Mostrar estructura actual
    echo '<h3>Estructura actual de la columna:</h3>';
    echo '<pre>';
    print_r($column_exists[0]);
    echo '</pre>';

} else {
    echo '<p class="error">❌ La columna spotify_uri NO EXISTE. Agregándola...</p>';

    echo '<h2>📋 Paso 3: Agregar Columna</h2>';

    // Agregar la columna
    $query = "ALTER TABLE {$table} ADD COLUMN spotify_uri varchar(255) DEFAULT '' AFTER album_name";

    echo '<p class="info">Ejecutando query:</p>';
    echo '<pre>' . $query . '</pre>';

    $result = $wpdb->query($query);

    if ($result === false) {
        echo '<p class="error">❌ ERROR al agregar la columna:</p>';
        echo '<pre>' . $wpdb->last_error . '</pre>';
    } else {
        echo '<p class="success">✅ Columna spotify_uri agregada exitosamente!</p>';

        // Verificar que se agregó
        $verify = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'spotify_uri'");

        if (!empty($verify)) {
            echo '<p class="success">✅ VERIFICADO: La columna ahora existe en la tabla.</p>';
            echo '<h3>Detalles de la columna:</h3>';
            echo '<pre>';
            print_r($verify[0]);
            echo '</pre>';
        }
    }
}

echo '<hr>';

echo '<h2>📊 Paso 4: Estructura Completa de la Tabla</h2>';

$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");

if ($columns) {
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%; margin-top: 20px;">';
    echo '<thead style="background: #1DB954; color: white;">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($columns as $column) {
        $highlight = ($column->Field == 'spotify_uri') ? 'background: #d4edda; font-weight: bold;' : '';
        echo '<tr style="' . $highlight . '">';
        echo '<td><strong>' . $column->Field . '</strong></td>';
        echo '<td>' . $column->Type . '</td>';
        echo '<td>' . $column->Null . '</td>';
        echo '<td>' . ($column->Key ?: '-') . '</td>';
        echo '<td>' . ($column->Default ?: 'NULL') . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

echo '<hr>';
echo '<h2 class="success">✅ Proceso Completado</h2>';
echo '<p><strong>Ahora puedes:</strong></p>';
echo '<ol>';
echo '<li>Probar enviar una canción desde el formulario</li>';
echo '<li>Verificar que se guarde correctamente en la base de datos</li>';
echo '<li>Revisar el panel "Rockola → Canciones" en WordPress</li>';
echo '</ol>';

echo '<p class="warning"><strong>⚠️ IMPORTANTE:</strong> Por seguridad, elimina este archivo después de ejecutarlo:</p>';
echo '<pre style="background: #fff3cd; border-left-color: #ffc107;">rm ' . __FILE__ . '</pre>';

echo '<p style="margin-top: 30px; text-align: center; color: #666;">';
echo '<small>Script ejecutado el ' . date('Y-m-d H:i:s') . '</small>';
echo '</p>';

echo '</div>';
?>
