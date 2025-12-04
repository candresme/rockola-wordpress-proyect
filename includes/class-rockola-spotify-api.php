<?php
class Rockola_Spotify_API {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $core;

    public function __construct() {
        $options = get_option('rockola_settings');
        $this->client_id = $options['client_id'] ?? '';
        $this->client_secret = $options['client_secret'] ?? '';
        $this->redirect_uri = site_url('/wp-json/rockola/v1/callback');

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function init() {
        $this->core = Rockola_Core::get_instance();
        // Cualquier otra inicialización que necesites
    }

    public function register_routes() {
        register_rest_route('rockola/v1', '/callback', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_auth_callback'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function get_auth_url() {
        $scopes = 'user-modify-playback-state user-read-playback-state user-read-currently-playing';
        return "https://accounts.spotify.com/authorize?response_type=code&client_id={$this->client_id}&scope=" . urlencode($scopes) . "&redirect_uri=" . urlencode($this->redirect_uri);
    }

    public function handle_auth_callback($request) {
        // Debug inicial
        error_log('=== SPOTIFY CALLBACK STARTED ===');
        
        $code = $request->get_param('code');
        $error = $request->get_param('error');
        
        if ($error) {
            error_log('Spotify returned error: ' . $error);
            wp_redirect(admin_url('admin.php?page=rockola-settings&error=' . urlencode($error)));
            exit;
        }
        
        if (!$code) {
            error_log('No code parameter received');
            wp_redirect(admin_url('admin.php?page=rockola-settings&error=no_code'));
            exit;
        }
        
        // VERIFICAR CREDENCIALES
        if (empty($this->client_id) || empty($this->client_secret)) {
            error_log('Missing client_id or client_secret');
            wp_redirect(admin_url('admin.php?page=rockola-settings&error=missing_credentials'));
            exit;
        }
        
        // PREPARAR PETICIÓN CORRECTAMENTE
        $token_url = 'https://accounts.spotify.com/api/token';
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirect_uri,
            ),
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
        );
        
        error_log('Sending request to Spotify token endpoint');
        error_log('Redirect URI: ' . $this->redirect_uri);
        error_log('Code length: ' . strlen($code));
        
        $response = wp_remote_post($token_url, $args);
        
        // DEBUG DE RESPUESTA
        if (is_wp_error($response)) {
            error_log('WP Error: ' . $response->get_error_message());
            error_log('WP Error Code: ' . $response->get_error_code());
            wp_redirect(admin_url('admin.php?page=rockola-settings&error=wp_error'));
            exit;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        error_log('Response Code: ' . $response_code);
        error_log('Response Body: ' . $response_body);
        error_log('Response Headers: ' . print_r($response_headers, true));
        
        if ($response_code !== 200) {
            error_log('Spotify returned non-200 status: ' . $response_code);
            wp_redirect(admin_url('admin.php?page=rockola-settings&error=spotify_' . $response_code));
            exit;
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON Parse Error: ' . json_last_error_msg());
            wp_redirect(admin_url('admin.php?page=rockola-settings&error=json_error'));
            exit;
        }
        
        if (!isset($data['access_token']) || !isset($data['refresh_token'])) {
            error_log('Missing tokens in response: ' . print_r($data, true));
            wp_redirect(admin_url('admin.php?page=rockola-settings&error=missing_tokens'));
            exit;
        }
        
        // GUARDAR TOKENS
        update_option('rockola_spotify_refresh_token', $data['refresh_token']);
        set_transient('rockola_spotify_access_token', $data['access_token'], $data['expires_in'] - 300);
        
        error_log('Tokens saved successfully!');
        error_log('Access Token: ' . substr($data['access_token'], 0, 20) . '...');
        error_log('Refresh Token: ' . substr($data['refresh_token'], 0, 20) . '...');
        error_log('Expires in: ' . $data['expires_in'] . ' seconds');
        
        // REDIRIGIR CON ÉXITO
        wp_redirect(admin_url('admin.php?page=rockola-settings&success=1'));
        exit;
    }

    public function get_access_token() {
        $token = get_transient('rockola_spotify_access_token');
        if ($token) return $token;

        // Refrescar token
        $refresh_token = get_option('rockola_spotify_refresh_token');
        if (!$refresh_token) return false;

        $response = wp_remote_post('https://accounts.spotify.com/api/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
            ],
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('Spotify Token Refresh Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['access_token'])) {
            set_transient('rockola_spotify_access_token', $body['access_token'], $body['expires_in'] - 60);
            return $body['access_token'];
        }

        return false;
    }

    public function search_tracks($query) {
        $token = $this->get_access_token();
        if (!$token) return ['error' => 'No access token available'];

        $response = wp_remote_get("https://api.spotify.com/v1/search?q=" . urlencode($query) . "&type=track&limit=10", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('Spotify Search Error: ' . $response->get_error_message());
            return ['error' => 'Search failed: ' . $response->get_error_message()];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function add_to_queue($uri) {
        error_log("🎵 add_to_queue iniciado para: {$uri}");
        
        $token = $this->get_access_token();
        
        if (!$token) {
            error_log("❌ No hay token de Spotify disponible");
            error_log("- Client ID: " . (!empty($this->client_id) ? 'SET' : 'MISSING'));
            error_log("- Client Secret: " . (!empty($this->client_secret) ? 'SET' : 'MISSING'));
            error_log("- Refresh Token: " . (get_option('rockola_spotify_refresh_token') ? 'EXISTS' : 'MISSING'));
            return false;
        }
        
        error_log("✅ Token obtenido: " . substr($token, 0, 30) . "...");
        
        // Verificar si hay un dispositivo activo
        $device_check = wp_remote_get("https://api.spotify.com/v1/me/player/devices", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 10
        ]);
        
        if (!is_wp_error($device_check)) {
            $devices_body = json_decode(wp_remote_retrieve_body($device_check), true);
            error_log("📱 Dispositivos disponibles: " . print_r($devices_body, true));
            
            if (isset($devices_body['devices'])) {
                $active_devices = array_filter($devices_body['devices'], function($d) {
                    return $d['is_active'] ?? false;
                });
                
                if (empty($active_devices)) {
                    error_log("⚠️ NO hay dispositivos activos. Dispositivos encontrados:");
                    foreach ($devices_body['devices'] as $device) {
                        error_log("  - {$device['name']} (ID: {$device['id']}) - Active: " . ($device['is_active'] ? 'YES' : 'NO'));
                    }
                } else {
                    error_log("✅ Dispositivo activo encontrado:");
                    foreach ($active_devices as $device) {
                        error_log("  - {$device['name']} (ID: {$device['id']})");
                    }
                }
            }
        } else {
            error_log("❌ Error al verificar dispositivos: " . $device_check->get_error_message());
        }
        
        // Intentar añadir a la cola
        $url = "https://api.spotify.com/v1/me/player/queue?uri=" . urlencode($uri);
        error_log("🌐 URL: {$url}");
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            error_log("❌ Error WP en add_to_queue: " . $response->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("📡 Spotify Response Code: {$code}");
        error_log("📡 Spotify Response Body: " . ($body ?: 'empty'));
        
        if ($code === 204 || $code === 200) {
            error_log("✅ Canción añadida exitosamente a la cola de Spotify");
            return true;
        }
        
        // Errores específicos de Spotify
        $error_messages = [
            400 => 'Bad Request - URI inválida o parámetros incorrectos',
            401 => 'Unauthorized - Token expirado o inválido',
            403 => 'Forbidden - Usuario no tiene Spotify Premium',
            404 => 'Not Found - No se encontró un dispositivo activo',
            429 => 'Too Many Requests - Límite de rate excedido',
            500 => 'Internal Server Error - Error en Spotify',
            502 => 'Bad Gateway - Spotify no disponible',
            503 => 'Service Unavailable - Spotify temporalmente no disponible'
        ];
        
        $error_msg = $error_messages[$code] ?? 'Error desconocido';
        error_log("❌ Spotify add_to_queue failed - Status: {$code} - {$error_msg}");
        
        if ($code === 404) {
            error_log("💡 SOLUCIÓN: Abre Spotify en algún dispositivo y reproduce cualquier canción");
        } elseif ($code === 403) {
            error_log("💡 SOLUCIÓN: Esta funcionalidad requiere Spotify Premium");
        } elseif ($code === 401) {
            error_log("💡 SOLUCIÓN: Reconecta la cuenta de Spotify en wp-admin");
        }

        return false;
    }

    public function get_currently_playing() {
        error_log("🎵 get_currently_playing: iniciando...");

        $token = $this->get_access_token();

        if (!$token) {
            error_log("❌ No hay token para obtener canción actual");
            return null;
        }

        error_log("✅ Token obtenido, llamando a Spotify API...");

        $response = wp_remote_get("https://api.spotify.com/v1/me/player/currently-playing", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            error_log("❌ Error al obtener canción actual: " . $response->get_error_message());
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log("📡 Spotify currently-playing response code: {$code}");

        // Si no hay nada sonando, Spotify devuelve 204 (No Content)
        if ($code === 204 || empty($body)) {
            error_log("ℹ️ No hay nada sonando actualmente");
            return null;
        }

        if ($code === 200) {
            $data = json_decode($body, true);

            if (!$data || !isset($data['item'])) {
                error_log("⚠️ Respuesta sin item");
                return null;
            }

            $track = $data['item'];
            $artists = array_map(function($artist) {
                return $artist['name'];
            }, $track['artists']);

            $result = array(
                'name' => $track['name'],
                'artist' => implode(', ', $artists),
                'album' => $track['album']['name'],
                'image' => $track['album']['images'][0]['url'] ?? '',
                'uri' => $track['uri'],
                'is_playing' => $data['is_playing'] ?? false,
                'progress_ms' => $data['progress_ms'] ?? 0,
                'duration_ms' => $track['duration_ms'] ?? 0
            );

            error_log("✅ Canción actual: " . $result['name'] . " - " . $result['artist']);
            return $result;
        }

        error_log("❌ Error al obtener canción actual - Status: {$code}");
        return null;
    }

    public function get_artist_genre($artist_id) {
        $token = $this->get_access_token();
        if (!$token) return 'Unknown';

        $response = wp_remote_get("https://api.spotify.com/v1/artists/{$artist_id}", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('Spotify Artist Genre Error: ' . $response->get_error_message());
            return 'Unknown';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['genres'][0]) ? $body['genres'][0] : 'Unknown';
    }

    /**
     * Obtener detalles completos de una canción
     */
    public function get_track_details($track_id) {
        $token = $this->get_access_token();
        if (!$token) return false;

        $response = wp_remote_get("https://api.spotify.com/v1/tracks/{$track_id}", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('Spotify Track Details Error: ' . $response->get_error_message());
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}