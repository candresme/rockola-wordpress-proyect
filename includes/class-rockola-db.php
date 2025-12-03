/**
 * Obtener canciones disponibles hoy - CON LÍMITE DINÁMICO
 * REEMPLAZA EL MÉTODO get_remaining_songs_today EN class-rockola-db.php
 */
public function get_remaining_songs_today($user_id) {
    global $wpdb;
    
    if (empty($user_id) || $user_id == 0) {
        return $this->get_daily_limit();
    }
    
    $today = current_time('Y-m-d');
    $max_per_day = $this->get_daily_limit();
    
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_submissions} LIKE 'user_id'");
    
    if (empty($column_exists)) {
        error_log("⚠️ ADVERTENCIA: Columna user_id NO existe en submissions");
        return $max_per_day;
    }
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$this->table_submissions} 
        WHERE user_id = %d AND DATE(created_at) = %s",
        $user_id, $today
    ));
    
    $remaining = max(0, $max_per_day - intval($count));
    
    error_log("get_remaining_songs_today: User {$user_id} | Máx: {$max_per_day} | Enviadas: {$count} | Restantes: {$remaining}");
    
    return $remaining;
}

/**
 * Obtener límite diario desde configuración
 * AGREGA ESTE MÉTODO NUEVO A class-rockola-db.php
 */
public function get_daily_limit() {
    $options = get_option('rockola_settings', array());
    $limit = isset($options['daily_limit']) ? intval($options['daily_limit']) : 3;
    
    // Validar que esté en rango válido
    if ($limit < 1 || $limit > 100) {
        $limit = 3;
    }
    
    return $limit;
}

/**
 * Verificar límite diario - CON LÍMITE DINÁMICO
 * REEMPLAZA EL MÉTODO check_daily_limit EN class-rockola-db.php
 */
public function check_daily_limit($user_id) {
    $remaining = $this->get_remaining_songs_today($user_id);
    $limit = $this->get_daily_limit();
    
    error_log("check_daily_limit: User {$user_id} | Restantes: {$remaining}/{$limit}");
    
    return $remaining > 0;
}
