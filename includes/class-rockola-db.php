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

        $result = $wpdb->insert(
            $this->table_submissions,
            array(
                'user_id' => $data['user_id'],
                'track_name' => $data['track_name'],
                'artist_name' => $data['artist_name'],
                'album_name' => isset($data['album_name']) ? $data['album_name'] : '',
                'spotify_uri' => isset($data['spotify_uri']) ? $data['spotify_uri'] : '',
                'genre' => isset($data['genre']) ? $data['genre'] : '',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
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
}
