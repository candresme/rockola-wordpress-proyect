<?php
class Rockola_AJAX {
    
    private $spotify_api;
    private $db;
    
    public function __construct($spotify_api = null, $db = null) {
        if ($spotify_api === null && class_exists('Rockola_Spotify_API')) {
            $this->spotify_api = new Rockola_Spotify_API();
        } else {
            $this->spotify_api = $spotify_api;
        }
        
        if ($db === null && class_exists('Rockola_DB')) {
            $this->db = new Rockola_DB();
        } else {
            $this->db = $db;
        }
    }
    
    public function init() {
        add_action('wp_ajax_rockola_search_tracks', array($this, 'ajax_search_tracks'));
        add_action('wp_ajax_nopriv_rockola_search_tracks', array($this, 'ajax_search_tracks'));
        
        add_action('wp_ajax_rockola_add_to_queue', array($this, 'ajax_add_to_queue'));
        add_action('wp_ajax_nopriv_rockola_add_to_queue', array($this, 'ajax_add_to_queue'));
        
        add_action('wp_ajax_rockola_verify_user', array($this, 'ajax_verify_user'));
        add_action('wp_ajax_nopriv_rockola_verify_user', array($this, 'ajax_verify_user'));
        
        add_action('wp_ajax_rockola_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_nopriv_rockola_register_user', array($this, 'ajax_register_user'));
        
        add_action('wp_ajax_rockola_update_user', array($this, 'ajax_update_user'));
        add_action('wp_ajax_nopriv_rockola_update_user', array($this, 'ajax_update_user'));

        add_action('wp_ajax_rockola_get_currently_playing', array($this, 'ajax_get_currently_playing'));
        add_action('wp_ajax_nopriv_rockola_get_currently_playing', array($this, 'ajax_get_currently_playing'));
    }
    
    public function ajax_search_tracks() {
        if (!check_ajax_referer('rockola_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Nonce verification failed'));
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query)) {
            wp_send_json_error(array('message' => 'Search query is empty'));
            return;
        }
        
        if (!$this->spotify_api) {
            wp_send_json_error(array('message' => 'Spotify API not available'));
            return;
        }
        
        try {
            $result = $this->spotify_api->search_tracks($query);
            
            if (isset($result['error'])) {
                wp_send_json_error(array('message' => 'Spotify API error: ' . $result['error']));
                return;
            }
            
            if (empty($result['tracks']['items'])) {
                wp_send_json_success(array('tracks' => array(), 'message' => 'No tracks found'));
                return;
            }
            
            $tracks = array();
            foreach ($result['tracks']['items'] as $item) {
                $artists = array();
                foreach ($item['artists'] as $artist) {
                    $artists[] = $artist['name'];
                }
                
                $tracks[] = array(
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'artist' => implode(', ', $artists),
                    'album' => $item['album']['name'],
                    'image' => isset($item['album']['images'][0]['url']) ? $item['album']['images'][0]['url'] : '',
                    'preview_url' => $item['preview_url'] ?? '',
                    'uri' => $item['uri'],
                    'duration_ms' => $item['duration_ms']
                );
            }
            
            wp_send_json_success(array('tracks' => $tracks));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Añadir canción - VERSIÓN DEFINITIVA CON DEBUG
     */
    public function ajax_add_to_queue() {
        error_log("========== AJAX ADD TO QUEUE ==========");
        error_log("POST completo: " . print_r($_POST, true));
        
        if (!check_ajax_referer('rockola_nonce', 'nonce', false)) {
            error_log("❌ Nonce failed");
            wp_send_json_error(array('message' => 'Nonce verification failed'));
            return;
        }
        
        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'whatsapp' => isset($_POST['whatsapp']) ? sanitize_text_field($_POST['whatsapp']) : '',
            'birthday' => isset($_POST['birthday']) ? sanitize_text_field($_POST['birthday']) : '',
            'track_uri' => isset($_POST['track_uri']) ? sanitize_text_field($_POST['track_uri']) : '',
            'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : 0,
        );
        
        error_log("user_id recibido: " . $data['user_id']);
        
        if (empty($data['name']) || empty($data['email']) || empty($data['whatsapp']) || empty($data['track_uri'])) {
            error_log("❌ Faltan campos");
            wp_send_json_error(array('message' => 'Faltan campos requeridos'));
            return;
        }
        
        try {
            // 1. OBTENER/CREAR USUARIO
            if ($data['user_id'] > 0) {
                $user = $this->db->get_user_by_id($data['user_id']);
                if (!$user) {
                    error_log("❌ Usuario {$data['user_id']} no encontrado");
                    wp_send_json_error(array('message' => 'Usuario no encontrado'));
                    return;
                }
                error_log("✅ Usuario encontrado: {$user->name} (ID: {$user->id})");
            } else {
                $user = $this->db->find_user($data['email'], $data['whatsapp']);
                
                if (!$user) {
                    error_log("Creando nuevo usuario...");
                    $user_id = $this->db->create_user(array(
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'whatsapp' => $data['whatsapp'],
                        'birthday' => $data['birthday']
                    ));
                    
                    if (!$user_id) {
                        error_log("❌ Error al crear usuario");
                        wp_send_json_error(array('message' => 'Error al crear usuario'));
                        return;
                    }
                    
                    $user = $this->db->get_user_by_id($user_id);
                    $data['user_id'] = $user_id;
                    error_log("✅ Usuario creado: ID {$user_id}");
                } else {
                    $data['user_id'] = $user->id;
                    error_log("✅ Usuario existente: ID {$user->id}");
                }
            }

            // 2. VERIFICAR DUPLICADOS
            $track_id = str_replace('spotify:track:', '', $data['track_uri']);
            if ($this->db->check_duplicate_today($data['user_id'], $track_id)) {
                error_log("❌ Canción duplicada");
                wp_send_json_error(array(
                    'message' => 'Ya enviaste esta canción hoy'
                ));
                return;
            }

            // 3. AÑADIR A SPOTIFY
            error_log("🎵 Añadiendo a Spotify: {$data['track_uri']}");
            if ($this->spotify_api && method_exists($this->spotify_api, 'add_to_queue')) {
                $success = $this->spotify_api->add_to_queue($data['track_uri']);
                
                if (!$success) {
                    error_log("❌ Error en Spotify API");
                    wp_send_json_error(array('message' => 'Error al añadir a Spotify'));
                    return;
                }
                error_log("✅ Añadido a Spotify");
            }

            // 4. OBTENER DETALLES
            $track_details = $this->spotify_api->get_track_details($track_id);
            
            if (!$track_details) {
                $track_details = array(
                    'name' => 'Desconocido',
                    'artists' => array(array('name' => 'Desconocido')),
                    'album' => array('name' => 'Desconocido', 'images' => array())
                );
            }
            
            $genre = 'Unknown';
            if (isset($track_details['artists'][0]['id'])) {
                $genre_result = $this->spotify_api->get_artist_genre($track_details['artists'][0]['id']);
                if (!empty($genre_result)) {
                    $genre = is_array($genre_result) ? implode(', ', $genre_result) : $genre_result;
                }
            }
            
            $artist_name = 'Unknown';
            if (isset($track_details['artists'])) {
                $artists = array();
                foreach ($track_details['artists'] as $artist) {
                    $artists[] = $artist['name'];
                }
                $artist_name = implode(', ', $artists);
            }

            // 5. PREPARAR DATOS - CRÍTICO: user_id debe estar aquí
            $submission_data = array(
                'user_id' => $data['user_id'], // ⭐ CRUCIAL
                'name' => $data['name'],
                'email' => $data['email'],
                'whatsapp' => $data['whatsapp'],
                'birthday' => $data['birthday'],
                'track_uri' => $data['track_uri'],
                'track_id' => $track_id,
                'track_name' => $track_details['name'] ?? 'Unknown',
                'artist_name' => $artist_name,
                'album_name' => $track_details['album']['name'] ?? 'Unknown',
                'genre' => $genre,
                'image_url' => isset($track_details['album']['images'][0]['url']) ? $track_details['album']['images'][0]['url'] : '',
                'preview_url' => $track_details['preview_url'] ?? '',
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'spotify_user_device_id' => ''
            );
            
            error_log("💾 Guardando con user_id: " . $submission_data['user_id']);

            // 6. GUARDAR EN BD
            $saved = $this->db->save_submission($submission_data);

            if (!$saved) {
                error_log("❌ Error al guardar en BD");
                wp_send_json_error(array('message' => 'Error al guardar en BD'));
                return;
            }

            error_log("✅ Guardado con ID: {$saved}");
            error_log("========== FIN AJAX ==========");

            wp_send_json_success(array(
                'message' => 'Canción añadida exitosamente',
                'user_id' => $data['user_id'],
                'saved_id' => $saved
            ));
            
        } catch (Exception $e) {
            error_log("❌ Exception: " . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    public function ajax_verify_user() {
        if (!check_ajax_referer('rockola_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Nonce verification failed'));
            return;
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
        
        if (empty($email) && empty($whatsapp)) {
            wp_send_json_error(array('message' => 'Ingresa email o WhatsApp'));
            return;
        }
        
        try {
            $user = $this->db->find_user($email, $whatsapp);
            
            if ($user) {
                $remaining = $this->db->get_remaining_songs_today($user->id);
                
                error_log("verify_user: Usuario {$user->id} tiene {$remaining} restantes");
                
                wp_send_json_success(array(
                    'user' => array(
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'whatsapp' => $user->whatsapp,
                        'birthday' => $user->birthday
                    ),
                    'remaining' => $remaining
                ));
            } else {
                wp_send_json_success(array(
                    'user' => null,
                    'remaining' => 3
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    public function ajax_register_user() {
        if (!check_ajax_referer('rockola_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Nonce verification failed'));
            return;
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
        $birthday = sanitize_text_field($_POST['birthday'] ?? '');
        
        if (empty($name) || empty($email) || empty($whatsapp)) {
            wp_send_json_error(array('message' => 'Faltan datos requeridos'));
            return;
        }
        
        try {
            $existing = $this->db->find_user($email, $whatsapp);
            
            if ($existing) {
                wp_send_json_error(array('message' => 'Usuario ya registrado'));
                return;
            }
            
            $user_id = $this->db->create_user(array(
                'name' => $name,
                'email' => $email,
                'whatsapp' => $whatsapp,
                'birthday' => $birthday
            ));
            
            if ($user_id) {
                $remaining = $this->db->get_remaining_songs_today($user_id);
                
                wp_send_json_success(array(
                    'user_id' => $user_id,
                    'remaining' => $remaining,
                    'message' => 'Usuario registrado'
                ));
            } else {
                wp_send_json_error(array('message' => 'Error al registrar'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    public function ajax_update_user() {
        if (!check_ajax_referer('rockola_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Nonce verification failed'));
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
        $birthday = sanitize_text_field($_POST['birthday'] ?? '');
        
        if (empty($user_id) || empty($name) || empty($email) || empty($whatsapp)) {
            wp_send_json_error(array('message' => 'Faltan datos'));
            return;
        }
        
        try {
            $updated = $this->db->update_user($user_id, array(
                'name' => $name,
                'email' => $email,
                'whatsapp' => $whatsapp,
                'birthday' => $birthday
            ));
            
            if ($updated) {
                $remaining = $this->db->get_remaining_songs_today($user_id);
                
                wp_send_json_success(array(
                    'user_id' => $user_id,
                    'remaining' => $remaining,
                    'message' => 'Actualizado'
                ));
            } else {
                wp_send_json_error(array('message' => 'Error al actualizar'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }

    public function ajax_get_currently_playing() {
        if (!check_ajax_referer('rockola_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Nonce verification failed'));
            return;
        }

        try {
            $currently_playing = $this->spotify_api->get_currently_playing();

            if ($currently_playing) {
                wp_send_json_success(array('track' => $currently_playing));
            } else {
                wp_send_json_success(array('track' => null));
            }
        } catch (Exception $e) {
            error_log("❌ Error en ajax_get_currently_playing: " . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
}