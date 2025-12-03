<?php
// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Prevenir redeclaración de clase
if (class_exists('Rockola_DB')) {
    return;
}

class Rockola_DB {
    private $table_submissions;
    private $table_users;
    private $table_requests;

    public function __construct() {
        global $wpdb;
        $this->table_submissions = $wpdb->prefix . 'rockola_submissions';
        $this->table_users = $wpdb->prefix . 'rockola_users';
        $this->table_requests = $wpdb->prefix . 'rockola_requests';
        
        $this->create_tables();
        $this->migrate_database(); // ⭐ NUEVO: Migrar esquema
    }

    /**
     * Migrar base de datos - Agregar columnas faltantes
     */
    public function migrate_database() {
        global $wpdb;
        
        // Verificar si user_id existe en submissions
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_submissions} LIKE 'user_id'");
        
        if (empty($column_exists)) {
            error_log("🔧 Rockola: Agregando columna user_id a submissions");
            
            // Agregar columna user_id
            $wpdb->query("ALTER TABLE {$this->table_submissions} 
                ADD COLUMN user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER id");
            
            // Agregar índice
            $wpdb->query("ALTER TABLE {$this->table_submissions} 
                ADD KEY user_id (user_id)");
            
            // Agregar índice compuesto
            $wpdb->query("ALTER TABLE {$this->table_submissions} 
                ADD KEY user_date (user_id, created_at)");
            
            error_log("✅ Rockola: Columna user_id agregada correctamente");
            
            // Intentar asociar registros antiguos con usuarios existentes
            $updated = $wpdb->query("
                UPDATE {$this->table_submissions} s
                INNER JOIN {$this->table_users} u ON s.email = u.email
                SET s.user_id = u.id
                WHERE s.user_id = 0
            ");
            
            if ($updated) {
                error_log("✅ Rockola: {$updated} registros antiguos asociados con usuarios");
            }
        }
    }

    /**
     * Crear tablas necesarias
     */
    public function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de usuarios
        $sql_users = "CREATE TABLE IF NOT EXISTS {$this->table_users} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            whatsapp VARCHAR(50) NOT NULL,
            birthday DATE NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            UNIQUE KEY whatsapp (whatsapp),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_users);
        
        // Tabla submissions - AHORA CON user_id desde el inicio
        $sql_submissions = "CREATE TABLE IF NOT EXISTS {$this->table_submissions} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            whatsapp VARCHAR(50) NOT NULL,
            birthday DATE NULL,
            track_id VARCHAR(255) NOT NULL,
            track_uri VARCHAR(255) NOT NULL,
            track_name TEXT NOT NULL,
            artist_name TEXT NOT NULL,
            album_name TEXT NOT NULL,
            genre VARCHAR(100),
            preview_url TEXT,
            image_url TEXT,
            spotify_user_device_id VARCHAR(255),
            user_ip VARCHAR(45),
            created_at DATETIME NOT NULL,
            weekday VARCHAR(20),
            hour INT(2),
            custom_hash VARCHAR(32),
            PRIMARY KEY (id),
            KEY email (email),
            KEY track_id (track_id),
            KEY created_at (created_at),
            KEY custom_hash (custom_hash),
            KEY user_id (user_id),
            KEY user_date (user_id, created_at)
        ) $charset_collate;";
        
        dbDelta($sql_submissions);
        
        // Tabla de requests
        $sql_requests = "CREATE TABLE IF NOT EXISTS {$this->table_requests} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            whatsapp VARCHAR(50) NOT NULL,
            birthday DATE NULL,
            track_uri VARCHAR(255) NOT NULL,
            track_id VARCHAR(255) NOT NULL,
            track_name TEXT NOT NULL,
            artist_name TEXT NOT NULL,
            album_name TEXT NOT NULL,
            genre VARCHAR(100),
            image_url TEXT,
            preview_url TEXT,
            user_ip VARCHAR(45),
            spotify_user_device_id VARCHAR(255),
            status VARCHAR(50) DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            weekday VARCHAR(20),
            hour INT(2),
            custom_hash VARCHAR(32),
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email),
            KEY track_id (track_id),
            KEY created_at (created_at),
            KEY status (status),
            KEY custom_hash (custom_hash),
            KEY user_date (user_id, created_at)
        ) $charset_collate;";
        
        dbDelta($sql_requests);
    }

    /**
     * Buscar usuario por email o whatsapp
     */
    public function find_user($email, $whatsapp) {
        global $wpdb;
        
        if (!empty($email)) {
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_users} WHERE email = %s",
                $email
            ));
            if ($user) return $user;
        }
        
        if (!empty($whatsapp)) {
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_users} WHERE whatsapp = %s",
                $whatsapp
            ));
            if ($user) return $user;
        }
        
        return null;
    }

    /**
     * Crear nuevo usuario
     */
    public function create_user($data) {
        global $wpdb;
        
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'whatsapp' => sanitize_text_field($data['whatsapp']),
            'birthday' => !empty($data['birthday']) ? sanitize_text_field($data['birthday']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_users, $insert_data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Actualizar usuario existente
     */
    public function update_user($user_id, $data) {
        global $wpdb;
        
        $update_data = array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'whatsapp' => sanitize_text_field($data['whatsapp']),
            'birthday' => !empty($data['birthday']) ? sanitize_text_field($data['birthday']) : null,
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $this->table_users,
            $update_data,
            array('id' => $user_id)
        );
        
        return $result !== false;
    }

    /**
     * Verificar límite diario
     */
    public function check_daily_limit($user_id) {
        $remaining = $this->get_remaining_songs_today($user_id);
        return $remaining > 0;
    }

    /**
     * Obtener canciones disponibles hoy - CON VALIDACIÓN DE COLUMNA
     */
    public function get_remaining_songs_today($user_id) {
        global $wpdb;
        
        if (empty($user_id) || $user_id == 0) {
            error_log("get_remaining_songs_today: User ID inválido, retornando 3");
            return 3;
        }
        
        $today = current_time('Y-m-d');
        $max_per_day = 3;
        
        // Verificar que la columna user_id existe
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_submissions} LIKE 'user_id'");
        
        if (empty($column_exists)) {
            error_log("⚠️ ADVERTENCIA: Columna user_id NO existe en submissions");
            return 3; // Retornar el máximo por seguridad
        }
        
        // Contar canciones del día
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_submissions} 
            WHERE user_id = %d AND DATE(created_at) = %s",
            $user_id, $today
        ));
        
        $remaining = max(0, $max_per_day - intval($count));
        
        error_log("get_remaining_songs_today: User {$user_id} | Hoy: {$today} | Enviadas: {$count} | Restantes: {$remaining}");
        
        return $remaining;
    }

    /**
     * Guardar submission - VERSIÓN FINAL
     */
    public function save_submission($data) {
        global $wpdb;
        
        // Validar que user_id exista
        if (empty($data['user_id']) || $data['user_id'] == 0) {
            error_log("❌ ERROR save_submission: user_id es 0 o vacío");
            error_log("Datos recibidos: " . print_r($data, true));
            return false;
        }
        
        // Preparar datos para insertar
        $insert_data = array(
            'user_id' => intval($data['user_id']),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'whatsapp' => sanitize_text_field($data['whatsapp'] ?? ''),
            'birthday' => !empty($data['birthday']) ? sanitize_text_field($data['birthday']) : null,
            'track_id' => sanitize_text_field($data['track_id'] ?? ''),
            'track_uri' => sanitize_text_field($data['track_uri'] ?? ''),
            'track_name' => sanitize_text_field($data['track_name'] ?? ''),
            'artist_name' => sanitize_text_field($data['artist_name'] ?? ''),
            'album_name' => sanitize_text_field($data['album_name'] ?? ''),
            'genre' => sanitize_text_field($data['genre'] ?? ''),
            'preview_url' => esc_url_raw($data['preview_url'] ?? ''),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'spotify_user_device_id' => sanitize_text_field($data['spotify_user_device_id'] ?? ''),
            'user_ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'created_at' => current_time('mysql'),
            'weekday' => date('l'),
            'hour' => intval(date('G')),
            'custom_hash' => md5(($data['email'] ?? '') . ($data['track_id'] ?? '') . current_time('Y-m-d H'))
        );
        
        error_log("💾 save_submission: Guardando user_id = " . $insert_data['user_id']);
        
        // Insertar en submissions
        $result = $wpdb->insert($this->table_submissions, $insert_data);
        
        if ($result === false) {
            error_log("❌ Error SQL: " . $wpdb->last_error);
            return false;
        }
        
        $inserted_id = $wpdb->insert_id;
        error_log("✅ Guardado en submissions con ID: {$inserted_id}");
        
        // También guardar en requests (opcional)
        $request_data = array_merge($insert_data, array('status' => 'pending'));
        $wpdb->insert($this->table_requests, $request_data);
        
        return $inserted_id;
    }

    /**
     * Verificar duplicados hoy
     */
    public function check_duplicate_today($user_id, $track_id) {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_submissions} 
            WHERE user_id = %d 
            AND track_id = %s 
            AND DATE(created_at) = %s",
            $user_id, $track_id, $today
        ));
        
        return intval($count) > 0;
    }

    /**
     * Obtener usuario por ID
     */
    public function get_user_by_id($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_users} WHERE id = %d",
            $user_id
        ));
    }

    /**
     * Obtener requests de un usuario
     */
    public function get_user_requests($user_id, $limit = 10) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_submissions} 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $user_id, $limit
        ));
    }

    /**
     * Stats y utilidades
     */
    public function get_stats_by_genre() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT genre, COUNT(*) as count 
            FROM {$this->table_submissions} 
            WHERE genre IS NOT NULL AND genre != '' 
            GROUP BY genre 
            ORDER BY count DESC 
            LIMIT 10"
        );
    }

    public function get_recent_submissions() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT s.*, u.name as user_name 
            FROM {$this->table_submissions} s
            LEFT JOIN {$this->table_users} u ON s.user_id = u.id
            ORDER BY s.created_at DESC 
            LIMIT 50"
        );
    }

    public function get_total_users() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_users}");
    }

    public function get_total_requests() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_submissions}");
    }

    public function get_today_requests() {
        global $wpdb;
        $today = current_time('Y-m-d');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.name as user_name 
            FROM {$this->table_submissions} s
            LEFT JOIN {$this->table_users} u ON s.user_id = u.id
            WHERE DATE(s.created_at) = %s
            ORDER BY s.created_at DESC",
            $today
        ));
    }
    
    public function get_or_create_client_user($email, $phone, $name) {
        $user = $this->find_user($email, $phone);
        
        if ($user) {
            return (object) array(
                'ID' => $user->id,
                'user_email' => $user->email,
                'display_name' => $user->name
            );
        }
        
        $user_id = $this->create_user(array(
            'name' => $name,
            'email' => $email,
            'whatsapp' => $phone
        ));
        
        if ($user_id) {
            return (object) array(
                'ID' => $user_id,
                'user_email' => $email,
                'display_name' => $name
            );
        }
        
        return new WP_Error('user_creation_failed', 'No se pudo crear el usuario');
    }
}