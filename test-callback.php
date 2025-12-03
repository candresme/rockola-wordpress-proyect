<?php
/**
 * Test direct callback para debug
 */
if (!defined('ABSPATH')) exit;

// Forzar mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<h1>Spotify Callback Debug</h1>';
echo '<pre>';

// Mostrar todos los parámetros GET
echo "GET Parameters:\n";
print_r($_GET);

// Mostrar todos los parámetros POST
echo "\nPOST Parameters:\n";
print_r($_POST);

// Mostrar headers
echo "\nHeaders:\n";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}

// Verificar si es llamado por Spotify
echo "\nUser Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'None');
echo "\nReferer: " . ($_SERVER['HTTP_REFERER'] ?? 'None');

echo '</pre>';

// Guardar en log
error_log('Spotify Callback Test: ' . print_r($_GET, true));