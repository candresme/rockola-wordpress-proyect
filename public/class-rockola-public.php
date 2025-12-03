<?php
class Rockola_Public {
    
    public function __construct() {
        // Constructor simple
    }
    
    public function init() {
        // El shortcode se registra desde el Core
    }
    
    /**
     * Display the rockola form - CON VALIDACIÓN DE USUARIO
     */
    public function display_rockola_form($atts = array()) {
        // Atributos por defecto
        $atts = shortcode_atts(array(
            'max_songs' => 3,
            'show_preview' => 'yes'
        ), $atts);
        
        ob_start();
        ?>
        <div id="rockola-spotify-app" class="rockola-container">
            <!-- PASO 0: Validación de usuario -->
            <div class="rockola-step" id="rockola-step-0">
                <h3>🎵 Bienvenido a la Rockola</h3>
                <p style="margin-bottom: 20px; color: #666;">Para solicitar canciones, primero verifica tu identidad</p>
                
                <div class="rockola-form">
                    <div class="rockola-form-group">
                        <label for="rockola-verify-email">Correo Electrónico *</label>
                        <input type="email" id="rockola-verify-email" name="verify_email" placeholder="ejemplo@email.com" required>
                        <small style="display: block; margin-top: 5px; color: #666;">O ingresa tu número de WhatsApp</small>
                    </div>
                    
                    <div class="rockola-form-group">
                        <label for="rockola-verify-whatsapp">Número de WhatsApp</label>
                        <input type="tel" id="rockola-verify-whatsapp" name="verify_whatsapp" placeholder="+57 300 123 4567">
                        <small style="display: block; margin-top: 5px; color: #666;">Ingresa al menos uno de los dos campos</small>
                    </div>
                    
                    <div id="rockola-verify-message" style="display: none; margin-bottom: 15px; padding: 10px; border-radius: 5px;"></div>
                    
                    <button type="button" id="rockola-verify-user" class="rockola-btn">
                        Verificar y Continuar →
                    </button>
                </div>
            </div>
            
            <!-- PASO 1: Registro o Actualización (oculto inicialmente) -->
            <div class="rockola-step" id="rockola-step-1" style="display: none;">
                <h3 id="rockola-step1-title">Completa tu Información</h3>
                <p id="rockola-step1-description" style="margin-bottom: 20px; color: #666;"></p>
                
                <div class="rockola-form">
                    <div class="rockola-form-group">
                        <label for="rockola-name">Nombre Completo *</label>
                        <input type="text" id="rockola-name" name="name" required>
                    </div>
                    
                    <div class="rockola-form-group">
                        <label for="rockola-email">Correo Electrónico *</label>
                        <input type="email" id="rockola-email" name="email" required readonly style="background-color: #f5f5f5;">
                    </div>
                    
                    <div class="rockola-form-group">
                        <label for="rockola-whatsapp">WhatsApp *</label>
                        <input type="tel" id="rockola-whatsapp" name="whatsapp" required>
                    </div>
                    
                    <div class="rockola-form-group">
                        <label for="rockola-birthday">Fecha de Cumpleaños</label>
                        <input type="date" id="rockola-birthday" name="birthday">
                        <small style="display: block; margin-top: 5px; color: #666;">Opcional, para celebrar tu cumpleaños</small>
                    </div>
                    
                    <div id="rockola-session-info" style="display: none; background: #e8f4ff; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <small>👋 Bienvenido de nuevo. Tus datos han sido cargados automáticamente.</small>
                    </div>
                    
                    <div class="rockola-actions">
                        <button type="button" id="rockola-back-to-verify" class="rockola-btn" style="background: #6c757d;">
                            ← Volver
                        </button>
                        <button type="button" id="rockola-next-step" class="rockola-btn">
                            Buscar Canciones →
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- PASO 2: Buscar canciones (oculto inicialmente) -->
            <div class="rockola-step" id="rockola-step-2" style="display: none;">
                <h3>🎶 Buscar Canción</h3>
                <div id="rockola-user-info" style="background: #e8f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="margin: 0;">
                        <strong>👋 Hola, <span id="rockola-user-name"></span></strong><br>
                        <small>Disponibles hoy: <span id="rockola-daily-remaining" style="font-weight: bold;">Cargando...</span> canciones</small>
                    </p>
                </div>
                
                <div class="rockola-search-box">
                    <input type="text" id="rockola-search" placeholder="Buscar canción o artista...">
                    <button id="rockola-search-btn" class="rockola-btn">Buscar</button>
                </div>
                
                <div id="rockola-results" class="rockola-results"></div>
                
                <div id="rockola-selected" class="rockola-selected">
                    <h4>Seleccionadas: <span id="rockola-count">0</span>/<?php echo $atts['max_songs']; ?></h4>
                    <div id="rockola-selected-list"></div>
                </div>
                
                <div class="rockola-actions">
                    <button id="rockola-prev-step" class="rockola-btn">← Atrás</button>
                    <button id="rockola-submit" class="rockola-btn" disabled>Enviar Solicitud</button>
                </div>
            </div>
            
            <div id="rockola-message"></div>
        </div>
        
        <!-- Popup Overlay (OCULTO por defecto - sin display:flex!) -->
        <div id="rockola-popup-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999;">
            <div id="rockola-popup" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); text-align: center;">
                <h3 id="rockola-popup-title" style="margin-top: 0;"></h3>
                <p id="rockola-popup-message"></p>
                <button id="rockola-popup-close" class="rockola-btn" style="width: 100%; margin-top: 20px;">Entendido</button>
            </div>
        </div>
        
        <style>
        .rockola-container {
            max-width: 600px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .rockola-step {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .rockola-form-group {
            margin-bottom: 20px;
        }
        .rockola-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .rockola-form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .rockola-form-group input:focus {
            border-color: #1DB954;
            outline: none;
        }
        .rockola-form-group input:read-only {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        .rockola-btn {
            background: #1DB954;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .rockola-btn:hover {
            background: #1ed760;
            transform: translateY(-1px);
        }
        .rockola-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .rockola-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 20px;
        }
        .rockola-search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .rockola-search-box input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        .rockola-results {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .rockola-selected {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        #rockola-message {
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            display: none;
        }
        .rockola-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .rockola-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .rockola-message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .rockola-add-btn {
            background: #1DB954;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .rockola-add-btn:hover {
            background: #1ed760;
        }
        .rockola-add-btn.added {
            background: #28a745 !important;
            cursor: default !important;
            opacity: 0.7;
        }
        .rockola-add-btn:disabled {
            background: #6c757d !important;
            cursor: not-allowed !important;
        }
        .rockola-track {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: background 0.3s;
        }
        .rockola-track:hover {
            background: #f8f9fa;
        }
        .rockola-track img {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            object-fit: cover;
        }
        @keyframes pulseAdded {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        .track-added {
            animation: pulseAdded 0.5s ease-in-out;
        }
        /* Popup Overlay - VERSIÓN CORREGIDA */
        #rockola-popup-overlay {
            display: none; /* ⬅️ SIN !important */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
        }

        #rockola-popup {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }

        #rockola-popup-title {
            margin-top: 0;
            font-size: 24px;
        }

        #rockola-popup-message {
            font-size: 16px;
            line-height: 1.6;
        }

        #rockola-popup-close {
            background: #1DB954;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            transition: background 0.3s;
        }

        #rockola-popup-close:hover {
            background: #1ed760;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Variables globales
            let selectedSongs = [];
            let addedTrackUris = [];
            const maxSongs = <?php echo $atts['max_songs']; ?>;
            let userSession = {
                id: null,
                name: '',
                email: '',
                whatsapp: '',
                birthday: '',
                dailyRemaining: 0,
                isNewUser: false
            };
            
            // FUNCIÓN PRINCIPAL DEL POPUP - VERSIÓN CORREGIDA 1
            function showPopup(title, message, type = 'info') {
                console.log("🔍 showPopup llamado:", {title, message, type});
                console.log("🔍 Popup overlay existe:", $('#rockola-popup-overlay').length);
                console.log("🔍 Popup overlay visible:", $('#rockola-popup-overlay').is(':visible'));
                
                const colors = {
                    'info': '#1DB954',
                    'warning': '#f0ad4e',
                    'error': '#dc3545',
                    'success': '#28a745'
                };
                
                const popupColor = colors[type] || colors.info;
                
                // Actualizar contenido
                $('#rockola-popup-title').text(title).css('color', popupColor);
                $('#rockola-popup-message').html(message);
                $('#rockola-popup-close').css('background', popupColor);
                
                // Mostrar con animación
                $('#rockola-popup-overlay').fadeIn(300);
                
                // Enfocar el botón
                setTimeout(() => {
                    $('#rockola-popup-close').focus();
                }, 300);
            }
            
                // CONFIGURAR EVENTOS DEL POPUP - CORREGIDO
                $(document).on('click', '#rockola-popup-close', function() {
                    $('#rockola-popup-overlay').fadeOut(300);
                });
                
                $(document).on('click', '#rockola-popup-overlay', function(e) {
                    if (e.target.id === 'rockola-popup-overlay') {
                        $(this).fadeOut(300);
                    }
                });
                
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && $('#rockola-popup-overlay').is(':visible')) {
                        $('#rockola-popup-overlay').fadeOut(300);
                    }
            });
            
            // Prevenir que clic en el popup cierre el overlay
            $(document).on('click', '#rockola-popup', function(e) {
                e.stopPropagation();
            });
            
            // ==================== PASO 0: VERIFICACIÓN DE USUARIO ====================
            $('#rockola-verify-user').on('click', function() {
                const email = $('#rockola-verify-email').val().trim();
                const whatsapp = $('#rockola-verify-whatsapp').val().trim();
                
                if (!email && !whatsapp) {
                    showVerificationMessage('Por favor, ingresa tu correo o número de WhatsApp', 'error');
                    return;
                }
                
                if (email && !isValidEmail(email)) {
                    showVerificationMessage('Correo electrónico inválido', 'error');
                    return;
                }
                
                $(this).prop('disabled', true).text('Verificando...');
                
                $.ajax({
                    url: rockola_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'rockola_verify_user',
                        nonce: rockola_ajax.nonce,
                        email: email,
                        whatsapp: whatsapp
                    },
                    success: function(response) {
                        if (response.success) {
                            const userData = response.data.user;
                            const remaining = response.data.remaining || 0;
                            
                            if (userData) {
                                // Usuario existe
                                userSession = {
                                    id: userData.id,
                                    name: userData.name,
                                    email: userData.email,
                                    whatsapp: userData.whatsapp,
                                    birthday: userData.birthday || '',
                                    dailyRemaining: remaining,
                                    isNewUser: false
                                };
                                
                                // Mostrar paso 1 con datos precargados
                                $('#rockola-name').val(userData.name);
                                $('#rockola-email').val(userData.email).prop('readonly', true);
                                $('#rockola-whatsapp').val(userData.whatsapp);
                                if (userData.birthday) {
                                    $('#rockola-birthday').val(userData.birthday);
                                }
                                
                                $('#rockola-step1-title').text('Actualiza tu Información');
                                $('#rockola-step1-description').html(`
                                    Hola <strong>${escapeHtml(userData.name)}</strong>!<br>
                                    <small>Tienes <strong>${remaining}</strong> canciones disponibles para hoy.</small>
                                `);
                                
                                $('#rockola-session-info').show();
                                showVerificationMessage('Usuario verificado correctamente', 'success');
                                
                                setTimeout(() => {
                                    $('#rockola-step-0').hide();
                                    $('#rockola-step-1').show();
                                }, 1000);
                                
                            } else {
                                // Usuario NO existe - mostrar formulario de registro
                                userSession = {
                                    id: null,
                                    name: '',
                                    email: email || '',
                                    whatsapp: whatsapp || '',
                                    birthday: '',
                                    dailyRemaining: maxSongs,
                                    isNewUser: true
                                };
                                
                                $('#rockola-email').val(email || '').prop('readonly', !!email);
                                $('#rockola-whatsapp').val(whatsapp || '');
                                
                                $('#rockola-step1-title').text('Completa tu Registro');
                                $('#rockola-step1-description').html(`
                                    Parece que es tu primera vez aquí.<br>
                                    <small>Completa los siguientes datos para continuar.</small>
                                `);
                                
                                $('#rockola-session-info').hide();
                                showVerificationMessage('Nuevo usuario detectado', 'info');
                                
                                setTimeout(() => {
                                    $('#rockola-step-0').hide();
                                    $('#rockola-step-1').show();
                                }, 1000);
                            }
                        } else {
                            showVerificationMessage('Error al verificar usuario: ' + (response.data.message || 'Error desconocido'), 'error');
                        }
                    },
                    error: function() {
                        showVerificationMessage('Error de conexión con el servidor', 'error');
                    },
                    complete: function() {
                        $('#rockola-verify-user').prop('disabled', false).text('Verificar y Continuar →');
                    }
                });
            });
            
            function showVerificationMessage(text, type) {
                const $msg = $('#rockola-verify-message');
                $msg.removeClass().addClass('rockola-message ' + type)
                    .html(text)
                    .fadeIn();
                
                if (type === 'success' || type === 'info') {
                    setTimeout(() => {
                        $msg.fadeOut();
                    }, 3000);
                }
            }
            
            // Volver a verificación desde paso 1
            $('#rockola-back-to-verify').on('click', function() {
                $('#rockola-step-1').hide();
                $('#rockola-step-0').show();
                $('#rockola-verify-message').hide();
            });
            
            // ==================== PASO 1: REGISTRO/ACTUALIZACIÓN ====================
            $('#rockola-next-step').on('click', function() {
                // Validar formulario
                let valid = true;
                const name = $('#rockola-name').val().trim();
                const email = $('#rockola-email').val().trim();
                const whatsapp = $('#rockola-whatsapp').val().trim();
                
                if (!name) {
                    valid = false;
                    $('#rockola-name').css('border-color', '#dc3545');
                } else {
                    $('#rockola-name').css('border-color', '#ddd');
                }
                
                if (!email || !isValidEmail(email)) {
                    valid = false;
                    $('#rockola-email').css('border-color', '#dc3545');
                } else {
                    $('#rockola-email').css('border-color', '#ddd');
                }
                
                if (!whatsapp) {
                    valid = false;
                    $('#rockola-whatsapp').css('border-color', '#dc3545');
                } else {
                    $('#rockola-whatsapp').css('border-color', '#ddd');
                }
                
                if (!valid) {
                    showMessage('Por favor, completa todos los campos requeridos correctamente', 'error');
                    return;
                }
                
                // Guardar/Actualizar usuario
                const userData = {
                    name: name,
                    email: email,
                    whatsapp: whatsapp,
                    birthday: $('#rockola-birthday').val() || ''
                };
                
                $(this).prop('disabled', true).text('Guardando...');
                
                const action = userSession.isNewUser ? 'rockola_register_user' : 'rockola_update_user';
                
                $.ajax({
                    url: rockola_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: action,
                        nonce: rockola_ajax.nonce,
                        ...userData,
                        user_id: userSession.id
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar sesión
                            userSession.name = userData.name;
                            userSession.email = userData.email;
                            userSession.whatsapp = userData.whatsapp;
                            userSession.birthday = userData.birthday;
                            userSession.dailyRemaining = response.data.remaining || maxSongs;
                            userSession.id = response.data.user_id || userSession.id;
                            
                            // Actualizar información en paso 2
                            $('#rockola-user-name').text(userData.name);
                            $('#rockola-daily-remaining').text(userSession.dailyRemaining);
                            
                            // Guardar en localStorage
                            localStorage.setItem('rockola_user_id', userSession.id);
                            localStorage.setItem('rockola_user_name', userData.name);
                            localStorage.setItem('rockola_user_email', userData.email);
                            localStorage.setItem('rockola_user_whatsapp', userData.whatsapp);
                            localStorage.setItem('rockola_last_visit', new Date().toISOString());
                            
                            // Ir al paso 2
                            $('#rockola-step-1').hide();
                            $('#rockola-step-2').show();
                            
                        } else {
                            showMessage('Error: ' + (response.data.message || 'No se pudo guardar la información'), 'error');
                        }
                    },
                    error: function() {
                        showMessage('Error de conexión', 'error');
                    },
                    complete: function() {
                        $('#rockola-next-step').prop('disabled', false).text('Buscar Canciones →');
                    }
                });
            });
            
            // ==================== PASO 2: BUSCAR CANCIONES ====================
            $('#rockola-prev-step').on('click', function() {
                $('#rockola-step-2').hide();
                $('#rockola-step-1').show();
            });
            
            // Búsqueda
            $('#rockola-search-btn').on('click', searchTracks);
            $('#rockola-search').on('keypress', function(e) {
                if (e.which === 13) searchTracks();
            });
            
            function searchTracks() {
                const query = $('#rockola-search').val().trim();
                if (!query) {
                    showMessage('Ingresa un término de búsqueda', 'error');
                    return;
                }
                
                $('#rockola-results').html('<div style="padding: 30px; text-align: center; color: #666;">Buscando canciones...</div>');
                
                $.ajax({
                    url: rockola_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'rockola_search_tracks',
                        nonce: rockola_ajax.nonce,
                        query: query
                    },
                    success: function(response) {
                        if (response.success) {
                            displayResults(response.data.tracks || []);
                        } else {
                            showMessage('Error: ' + (response.data.message || 'Búsqueda falló'), 'error');
                        }
                    },
                    error: function() {
                        showMessage('Error al conectar con el servidor', 'error');
                    }
                });
            }
            
            function displayResults(tracks) {
                if (tracks.length === 0) {
                    $('#rockola-results').html('<div style="padding: 30px; text-align: center; color: #666;">No se encontraron canciones</div>');
                    return;
                }
                
                let html = '';
                tracks.forEach(track => {
                    const trackName = escapeHtml(track.name);
                    const trackArtist = escapeHtml(track.artist);
                    const isAdded = addedTrackUris.includes(track.uri);
                    
                    html += `
                    <div class="rockola-track" data-uri="${track.uri}">
                        <img src="${track.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIGZpbGw9IiMyQzJDMkMiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0id2hpdGUiIGZvbnQtc2l6ZT0iMjQiPjxhPC90ZXh0Pjwvc3ZnPg=='}" alt="${trackName}">
                        <div style="flex: 1;">
                            <strong>${trackName}</strong><br>
                            <small>${trackArtist}</small>
                        </div>
                        <button class="rockola-add-btn ${isAdded ? 'added' : ''}" 
                                data-track-id="${track.id}"
                                data-track-name="${trackName}"
                                data-track-artist="${trackArtist}"
                                data-track-image="${track.image || ''}"
                                data-track-uri="${track.uri}"
                                ${isAdded ? 'disabled' : ''}
                                ${userSession.dailyRemaining <= 0 ? 'disabled' : ''}>
                            ${isAdded ? '✓ Añadido' : (userSession.dailyRemaining <= 0 ? 'Límite alcanzado' : 'Añadir')}
                        </button>
                    </div>`;
                });
                
                $('#rockola-results').html(html);
                
                // Bind add buttons
                $('.rockola-add-btn:not(.added):not(:disabled)').on('click', function() {
                    const $btn = $(this);
                    const track = {
                        id: $btn.data('track-id'),
                        name: $btn.data('track-name'),
                        artist: $btn.data('track-artist'),
                        image: $btn.data('track-image'),
                        uri: $btn.data('track-uri')
                    };
                    addTrack(track, $btn);
                });
            }
            
            function addTrack(track, $btn = null) {
                // Verificar límite de selección por sesión
                if (selectedSongs.length >= maxSongs) {
                    showPopup(
                        'Límite de Selección',
                        `Solo puedes seleccionar hasta ${maxSongs} canciones por visita.<br><br>
                        <small>Envía las canciones seleccionadas para poder seleccionar más.</small>`,
                        'warning'
                    );
                    return;
                }
                
                // Verificar si ya fue añadido
                if (selectedSongs.find(t => t.uri === track.uri)) {
                    showMessage('Esta canción ya está seleccionada', 'warning');
                    return;
                }
                
                // Verificar límite diario - CORREGIDO
                if (userSession.dailyRemaining <= 0) {
                    showPopup(
                        'Límite Diario Alcanzado',
                        'Has alcanzado tu límite de canciones para hoy.<br><br>Podrás enviar más canciones mañana.',
                        'warning'
                    );
                    return;
                }
                
                // Verificar que al añadir esta canción no exceda el límite diario
                if (selectedSongs.length + 1 > userSession.dailyRemaining) {
                    showPopup(
                        'Límite Diario',
                        `Solo te quedan ${userSession.dailyRemaining} canción${userSession.dailyRemaining > 1 ? 'es' : ''} disponible${userSession.dailyRemaining > 1 ? 's' : ''} para hoy.<br><br>
                        <small>Envía las canciones seleccionadas primero.</small>`,
                        'warning'
                    );
                    return;
                }
                
                // Añadir a selección
                selectedSongs.push(track);
                addedTrackUris.push(track.uri);
                updateSelectedList();
                updateSubmitButton();
                
                // Actualizar botón
                if ($btn) {
                    $btn.addClass('added')
                        .text('✓ Añadido')
                        .prop('disabled', true);
                    
                    // Animación
                    $btn.closest('.rockola-track').addClass('track-added');
                    setTimeout(() => {
                        $btn.closest('.rockola-track').removeClass('track-added');
                    }, 500);
                }
                
                showMessage(`✓ "${track.name}" añadida a tu selección`, 'success');
            }
            
            function updateSelectedList() {
                $('#rockola-count').text(selectedSongs.length);
                
                let html = '';
                selectedSongs.forEach((track, index) => {
                    html += `
                    <div class="rockola-selected-item" style="background: white; padding: 10px; margin: 5px 0; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-left: 3px solid #1DB954;">
                        <div style="flex: 1;">
                            <strong>${escapeHtml(track.name)}</strong><br>
                            <small style="color: #666;">${escapeHtml(track.artist)}</small>
                        </div>
                        <button class="rockola-remove-btn" data-index="${index}" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            Eliminar
                        </button>
                    </div>`;
                });
                
                $('#rockola-selected-list').html(html);
                
                // Bind remove buttons
                $('.rockola-remove-btn').on('click', function() {
                    const index = $(this).data('index');
                    const removedTrack = selectedSongs[index];
                    
                    selectedSongs.splice(index, 1);
                    addedTrackUris = addedTrackUris.filter(uri => uri !== removedTrack.uri);
                    
                    // Reactivar botón en resultados
                    $(`.rockola-add-btn[data-track-uri="${removedTrack.uri}"]`)
                        .removeClass('added')
                        .text('Añadir')
                        .prop('disabled', false);
                    
                    updateSelectedList();
                    updateSubmitButton();
                    showMessage(`Canción "${removedTrack.name}" removida', 'warning`);
                });
            }
            
            function updateSubmitButton() {
                // Verificar dos condiciones:
                // 1. Hay canciones seleccionadas
                // 2. Las canciones seleccionadas no exceden el límite diario
                const totalSelected = selectedSongs.length;
                const canSubmit = totalSelected > 0 && totalSelected <= userSession.dailyRemaining;
                
                $('#rockola-submit').prop('disabled', !canSubmit);
                
                if (totalSelected > 0) {
                    const remainingAfterSubmit = userSession.dailyRemaining - totalSelected;
                    let text = `Enviar ${totalSelected} canción${totalSelected > 1 ? 'es' : ''}`;
                    
                    if (remainingAfterSubmit < 0) {
                        text += ' (excede límite)';
                    }
                    
                    $('#rockola-submit').text(text);
                } else {
                    $('#rockola-submit').text('Enviar Solicitud');
                }
            }
            
            // Enviar solicitud - VERSIÓN CORREGIDA
            $('#rockola-submit').on('click', function() {
                if (selectedSongs.length === 0) return;
                
                const userData = {
                    user_id: userSession.id,
                    name: userSession.name,
                    email: userSession.email,
                    whatsapp: userSession.whatsapp,
                    birthday: userSession.birthday
                };
                
                const $submitBtn = $(this);
                const totalSongs = selectedSongs.length;
                $submitBtn.prop('disabled', true).text(`Enviando 0/${totalSongs}`);
                
                let sentCount = 0;
                let errors = [];
                let successfulSongs = [];
                
                function sendNextSong() {
                    if (sentCount >= totalSongs) {
                        // Todas enviadas
                        $submitBtn.prop('disabled', false).text('Enviar Solicitud');
                        
                        if (errors.length === 0) {
                            // ✅ ÉXITO TOTAL - MOSTRAR POPUP
                            showPopup(
                                '🎉 ¡Canciones Enviadas!',
                                `<strong>${totalSongs}</strong> ${totalSongs === 1 ? 'canción ha' : 'canciones han'} sido añadidas a la cola de reproducción.<br><br>
                                <strong>Te quedan ${userSession.dailyRemaining} ${userSession.dailyRemaining === 1 ? 'canción' : 'canciones'} disponibles hoy.</strong><br><br>
                                <small>🎵 Tus canciones sonarán pronto en Spotify</small>`,
                                'success'
                            );
                            
                            // Resetear selección
                            selectedSongs = [];
                            addedTrackUris = [];
                            updateSelectedList();
                            updateSubmitButton();
                            
                        } else if (successfulSongs.length > 0) {
                            // ⚠️ ÉXITO PARCIAL
                            showPopup(
                                '⚠️ Envío Parcial',
                                `Se enviaron <strong>${successfulSongs.length} de ${totalSongs}</strong> canciones.<br><br>
                                <strong>Canciones exitosas:</strong><br>
                                ${successfulSongs.map(s => '• ' + s).join('<br>')}<br><br>
                                <strong>Errores:</strong><br>
                                ${errors.map(e => '• ' + e).join('<br>')}`,
                                'warning'
                            );
                            
                            // Resetear solo las exitosas
                            selectedSongs = selectedSongs.filter(track => 
                                !successfulSongs.includes(track.name)
                            );
                            updateSelectedList();
                            updateSubmitButton();
                            
                        } else {
                            // ❌ TODO FALLÓ
                            showPopup(
                                '❌ Error al Enviar',
                                `No se pudo enviar ninguna canción.<br><br>
                                <strong>Errores:</strong><br>
                                ${errors.map(e => '• ' + e).join('<br>')}<br><br>
                                <small>Por favor, intenta de nuevo o contacta al administrador.</small>`,
                                'error'
                            );
                        }
                        
                        return;
                    }
                    
                    const track = selectedSongs[sentCount];
                    
                    $.ajax({
                        url: rockola_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'rockola_add_to_queue',
                            nonce: rockola_ajax.nonce,
                            ...userData,
                            track_uri: track.uri
                        },
                        success: function(response) {
                            sentCount++;
                            $submitBtn.text(`Enviando ${sentCount}/${totalSongs}`);
                            
                            if (response.success) {
                                successfulSongs.push(track.name);
                                
                                // Actualizar contador
                                if (response.data && response.data.remaining_today !== undefined) {
                                    userSession.dailyRemaining = response.data.remaining_today;
                                    $('#rockola-daily-remaining').text(userSession.dailyRemaining);
                                    updateSubmitButton();
                                }
                            } else {
                                errors.push(track.name + ': ' + (response.data?.message || 'Error desconocido'));
                            }
                            sendNextSong();
                        },
                        error: function(xhr, status, error) {
                            errors.push(track.name + ': Error de conexión');
                            sentCount++;
                            sendNextSong();
                        }
                    });
                }
                
                sendNextSong();
            });

            

            // Eventos del popup (si no los tienes ya)
            $(document).on('click', '#rockola-popup-close', function() {
                $('#rockola-popup-overlay').fadeOut(300);
            });

            $(document).on('click', '#rockola-popup-overlay', function(e) {
                if (e.target.id === 'rockola-popup-overlay') {
                    $(this).fadeOut(300);
                }
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#rockola-popup-overlay').is(':visible')) {
                    $('#rockola-popup-overlay').fadeOut(300);
                }
            });

            $(document).on('click', '#rockola-popup', function(e) {
                e.stopPropagation();
            });
            
            // ==================== FUNCIONES UTILITARIAS ====================
            function showMessage(text, type) {
                const $msg = $('#rockola-message');
                $msg.removeClass('success error warning')
                    .addClass(type)
                    .html(text)
                    .fadeIn();
                
                setTimeout(() => {
                    $msg.fadeOut();
                }, 4000);
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            // Auto-completar desde localStorage si existe
            const savedEmail = localStorage.getItem('rockola_user_email');
            const savedWhatsapp = localStorage.getItem('rockola_user_whatsapp');
            
            if (savedEmail) {
                $('#rockola-verify-email').val(savedEmail);
                if (savedWhatsapp) {
                    $('#rockola-verify-whatsapp').val(savedWhatsapp);
                }
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
// NO agregues nada después de esta línea