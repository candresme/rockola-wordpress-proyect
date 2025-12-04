<?php
/**
 * Clase de Base de Datos de Rockola
 * Maneja todas las operaciones de base de datos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Rockola_DB {

    private $table_users;
    private $table_submissions;
    private $table_stats;

    public function __construct() {
        global $wpdb;

        $this->table_users = $wpdb->prefix . 'rockola_users';
        $this->table_submissions = $wpdb->prefix . 'rockola_submissions';
        $this->table_stats = $wpdb->prefix . 'rockola_stats';
    }

    /**
     * Crear tablas necesarias para el plugin
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabla de usuarios
        $sql_users = "CREATE TABLE IF NOT EXISTS {$this->table_users} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            whatsapp varchar(50) DEFAULT '',
            birthday date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY whatsapp (whatsapp)
        ) $charset_collate;";

        dbDelta($sql_users);

        // Tabla de submissions (canciones pedidas)
        $sql_submissions = "CREATE TABLE IF NOT EXISTS {$this->table_submissions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            name varchar(255) DEFAULT '',
            email varchar(255) DEFAULT '',
            whatsapp varchar(50) DEFAULT '',
            birthday date DEFAULT NULL,
            track_id varchar(100) DEFAULT '',
            track_name varchar(255) NOT NULL,
            artist_name varchar(255) NOT NULL,
            album_name varchar(255) DEFAULT '',
            spotify_uri varchar(255) DEFAULT '',
            genre varchar(100) DEFAULT '',
            image_url text DEFAULT '',
            preview_url text DEFAULT '',
            user_ip varchar(50) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY track_id (track_id)
        ) $charset_collate;";

        dbDelta($sql_submissions);

        // Tabla de estadísticas
        $sql_stats = "CREATE TABLE IF NOT EXISTS {$this->table_stats} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            stat_date date NOT NULL,
            total_requests int(11) DEFAULT 0,
            total_users int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY stat_date (stat_date)
        ) $charset_collate;";

        dbDelta($sql_stats);

        error_log('✅ Tablas de Rockola creadas/actualizadas correctamente');
    }

    /**
     * Migrar base de datos - agregar columnas faltantes
     */
    public function migrate_database() {
        global $wpdb;

        // Verificar y agregar columna user_id si no existe
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_submissions} LIKE 'user_id'"
        );

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_submissions} ADD COLUMN user_id bigint(20) DEFAULT 0 AFTER id");
            error_log('✅ Columna user_id agregada a submissions');
        }

        // Verificar y agregar columna track_id si no existe
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_submissions} LIKE 'track_id'"
        );

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_submissions} ADD COLUMN track_id varchar(100) DEFAULT '' AFTER birthday");
            error_log('✅ Columna track_id agregada a submissions');
        }

        // Verificar y agregar columna image_url si no existe
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_submissions} LIKE 'image_url'"
        );

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_submissions} ADD COLUMN image_url text DEFAULT '' AFTER genre");
            error_log('✅ Columna image_url agregada a submissions');
        }

        // Verificar y agregar columna preview_url si no existe
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_submissions} LIKE 'preview_url'"
        );

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_submissions} ADD COLUMN preview_url text DEFAULT '' AFTER image_url");
            error_log('✅ Columna preview_url agregada a submissions');
        }

        // Verificar y agregar columna user_ip si no existe
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_submissions} LIKE 'user_ip'"
        );

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_submissions} ADD COLUMN user_ip varchar(50) DEFAULT '' AFTER preview_url");
            error_log('✅ Columna user_ip agregada a submissions');
        }

        // Verificar y agregar columna spotify_uri si no existe
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_submissions} LIKE 'spotify_uri'"
        );

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_submissions} ADD COLUMN spotify_uri varchar(255) DEFAULT '' AFTER album_name");
            error_log('✅ Columna spotify_uri agregada a submissions');
        }

        error_log('✅ Migración de base de datos completada');
    }

    /**
     * Obtener límite diario desde configuración
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
     * Obtener canciones disponibles hoy - CON LÍMITE DINÁMICO
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
     * Verificar límite diario - CON LÍMITE DINÁMICO
     */
    public function check_daily_limit($user_id) {
        $remaining = $this->get_remaining_songs_today($user_id);
        $limit = $this->get_daily_limit();

        error_log("check_daily_limit: User {$user_id} | Restantes: {$remaining}/{$limit}");

        return $remaining > 0;
    }

    /**
     * Obtener total de usuarios registrados
     */
    public function get_total_users() {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_users}");

        return intval($count);
    }

    /**
     * Obtener total de solicitudes
     */
    public function get_total_requests() {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_submissions}");

        return intval($count);
    }

    /**
     * Obtener solicitudes de hoy
     */
    public function get_today_requests() {
        global $wpdb;

        $today = current_time('Y-m-d');

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.name as user_name
            FROM {$this->table_submissions} s
            LEFT JOIN {$this->table_users} u ON s.user_id = u.id
            WHERE DATE(s.created_at) = %s
            ORDER BY s.created_at DESC",
            $today
        ));

        return $results ?: array();
    }

    /**
     * Obtener solicitudes recientes
     */
    public function get_recent_submissions($limit = 100) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.name as user_name
            FROM {$this->table_submissions} s
            LEFT JOIN {$this->table_users} u ON s.user_id = u.id
            ORDER BY s.created_at DESC
            LIMIT %d",
            $limit
        ));

        return $results ?: array();
    }

    /**
     * Guardar usuario
     */
    public function save_user($data) {
        global $wpdb;

        // Verificar si el email ya existe
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_users} WHERE email = %s",
            $data['email']
        ));

        if ($existing) {
            // Actualizar usuario existente
            $wpdb->update(
                $this->table_users,
                array(
                    'name' => $data['name'],
                    'whatsapp' => isset($data['whatsapp']) ? $data['whatsapp'] : '',
                    'birthday' => isset($data['birthday']) ? $data['birthday'] : null,
                ),
                array('id' => $existing),
                array('%s', '%s', '%s'),
                array('%d')
            );

            return $existing;
        } else {
            // Insertar nuevo usuario
            $wpdb->insert(
                $this->table_users,
                array(
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'whatsapp' => isset($data['whatsapp']) ? $data['whatsapp'] : '',
                    'birthday' => isset($data['birthday']) ? $data['birthday'] : null,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );

            return $wpdb->insert_id;
        }
    }

    /**
     * Guardar solicitud de canción
     */
    public function save_submission($data) {
        global $wpdb;

        $insert_data = array(
            'user_id' => isset($data['user_id']) ? $data['user_id'] : 0,
            'track_name' => isset($data['track_name']) ? $data['track_name'] : 'Unknown',
            'artist_name' => isset($data['artist_name']) ? $data['artist_name'] : 'Unknown',
            'album_name' => isset($data['album_name']) ? $data['album_name'] : '',
            'spotify_uri' => isset($data['track_uri']) ? $data['track_uri'] : (isset($data['spotify_uri']) ? $data['spotify_uri'] : ''),
            'genre' => isset($data['genre']) ? $data['genre'] : '',
            'created_at' => current_time('mysql')
        );

        $formats = array('%d', '%s', '%s', '%s', '%s', '%s', '%s');

        // Agregar campos opcionales si existen en la tabla
        if (isset($data['track_id'])) {
            $insert_data['track_id'] = $data['track_id'];
            $formats[] = '%s';
        }

        if (isset($data['image_url'])) {
            $insert_data['image_url'] = $data['image_url'];
            $formats[] = '%s';
        }

        if (isset($data['preview_url'])) {
            $insert_data['preview_url'] = $data['preview_url'];
            $formats[] = '%s';
        }

        if (isset($data['user_ip'])) {
            $insert_data['user_ip'] = $data['user_ip'];
            $formats[] = '%s';
        }

        $result = $wpdb->insert(
            $this->table_submissions,
            $insert_data,
            $formats
        );

        if ($result === false) {
            error_log('Error al guardar submission: ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener usuario por email
     */
    public function get_user_by_email($email) {
        global $wpdb;

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_users} WHERE email = %s",
            $email
        ));

        return $user;
    }

    /**
     * Obtener usuario por ID
     */
    public function get_user_by_id($user_id) {
        global $wpdb;

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_users} WHERE id = %d",
            $user_id
        ));

        return $user;
    }

    /**
     * Obtener estadísticas generales
     */
    public function get_stats() {
        global $wpdb;

        $stats = array(
            'total_users' => $this->get_total_users(),
            'total_requests' => $this->get_total_requests(),
            'today_requests' => count($this->get_today_requests()),
            'daily_limit' => $this->get_daily_limit()
        );

        return $stats;
    }

    /**
     * Buscar usuario por email o whatsapp
     */
    public function find_user($email, $whatsapp) {
        global $wpdb;

        $conditions = array();
        $values = array();

        if (!empty($email)) {
            $conditions[] = "email = %s";
            $values[] = $email;
        }

        if (!empty($whatsapp)) {
            $conditions[] = "whatsapp = %s";
            $values[] = $whatsapp;
        }

        if (empty($conditions)) {
            return null;
        }

        $where = implode(' OR ', $conditions);
        $query = "SELECT * FROM {$this->table_users} WHERE {$where} LIMIT 1";

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        $user = $wpdb->get_row($query);

        return $user;
    }

    /**
     * Crear nuevo usuario
     */
    public function create_user($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_users,
            array(
                'name' => $data['name'],
                'email' => $data['email'],
                'whatsapp' => isset($data['whatsapp']) ? $data['whatsapp'] : '',
                'birthday' => isset($data['birthday']) ? $data['birthday'] : null,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('Error al crear usuario: ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Actualizar usuario existente
     */
    public function update_user($user_id, $data) {
        global $wpdb;

        $update_data = array();
        $formats = array();

        if (isset($data['name'])) {
            $update_data['name'] = $data['name'];
            $formats[] = '%s';
        }

        if (isset($data['email'])) {
            $update_data['email'] = $data['email'];
            $formats[] = '%s';
        }

        if (isset($data['whatsapp'])) {
            $update_data['whatsapp'] = $data['whatsapp'];
            $formats[] = '%s';
        }

        if (isset($data['birthday'])) {
            $update_data['birthday'] = $data['birthday'];
            $formats[] = '%s';
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update(
            $this->table_users,
            $update_data,
            array('id' => $user_id),
            $formats,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Verificar si el usuario ya envió esta canción hoy
     */
    public function check_duplicate_today($user_id, $track_id) {
        global $wpdb;

        $today = current_time('Y-m-d');

        // Verificar si existe columna spotify_uri o track_id
        $track_uri = 'spotify:track:' . $track_id;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_submissions}
            WHERE user_id = %d
            AND DATE(created_at) = %s
            AND (spotify_uri = %s OR track_id = %s)",
            $user_id,
            $today,
            $track_uri,
            $track_id
        ));

        return intval($count) > 0;
    }
}
