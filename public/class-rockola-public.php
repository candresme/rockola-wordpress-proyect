<?php
/**
 * Clase principal del frontend de Rockola
 * Versión 2.0 - Mejorada con UX optimizada y diseño moderno
 */

class Rockola_Public {
    
    public function __construct() {
        // Constructor simple
    }
    
    public function init() {
        // El shortcode se registra desde el Core
    }
    
    /**
     * Display the rockola form - VERSIÓN MEJORADA
     */
    public function display_rockola_form($atts = array()) {
        // Obtener límite desde configuración (por defecto 3)
        $options = get_option('rockola_settings', array());
        $daily_limit = isset($options['daily_limit']) ? intval($options['daily_limit']) : 3;
        
        // Atributos por defecto
        $atts = shortcode_atts(array(
            'max_songs' => $daily_limit,
            'show_preview' => 'yes'
        ), $atts);
        
        ob_start();
        ?>
        <div id="rockola-spotify-app" class="rockola-container">
            
            <!-- PASO 0: Verificación simplificada -->
            <div class="rockola-step" id="rockola-step-0">
                <h3>🎵 Bienvenido a la Rockola</h3>
                <p>Pide tus canciones favoritas y disfruta de la mejor música</p>
                
                <div class="rockola-form">
                    <div class="rockola-form-group">
                        <label>Identificación *</label>
                        <input type="email" id="rockola-verify-email" placeholder="Correo electrónico">
                        <small>O</small>
                        <input type="tel" id="rockola-verify-whatsapp" placeholder="Número de WhatsApp" style="margin-top: 12px;">
                        <small style="margin-top: 8px;">Ingresa al menos uno para continuar</small>
                    </div>
                    
                    <div id="rockola-verify-message" class="rockola-message" style="display: none;"></div>
                    
                    <button type="button" id="rockola-verify-user" class="rockola-btn">
                        Continuar →
                    </button>
                </div>
            </div>
            
            <!-- PASO 1: Registro/Actualización -->
            <div class="rockola-step" id="rockola-step-1" style="display: none;">
                <h3 id="rockola-step1-title">Completa tu Información</h3>
                <p id="rockola-step1-description"></p>
                
                <div class="rockola-form">
                    <div class="rockola-form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" id="rockola-name" name="name" required>
                    </div>
                    
                    <div class="rockola-form-group">
                        <label>Correo Electrónico *</label>
                        <input type="email" id="rockola-email" name="email" required>
                    </div>
                    
                    <div class="rockola-form-group">
                        <label>WhatsApp *</label>
                        <input type="tel" id="rockola-whatsapp" name="whatsapp" required>
                    </div>
                    
                    <div class="rockola-form-group">
                        <label>Fecha de Cumpleaños (Opcional)</label>
                        <input type="date" id="rockola-birthday" name="birthday">
                        <small>Te enviaremos una sorpresa en tu día especial 🎂</small>
                    </div>
                    
                    <div id="rockola-session-info" style="display: none;" class="rockola-message success">
                        <small>👋 Bienvenido de nuevo. Tus datos han sido cargados.</small>
                    </div>
                    
                    <div class="rockola-actions">
                        <button type="button" id="rockola-back-to-verify" class="rockola-btn rockola-btn-secondary">
                            ← Volver
                        </button>
                        <button type="button" id="rockola-save-and-continue" class="rockola-btn">
                            Guardar y Continuar →
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- PASO 2: Buscar y seleccionar música -->
            <div class="rockola-step" id="rockola-step-2" style="display: none;">
                
                <!-- User Info Card -->
                <div class="rockola-user-info">
                    <p>
                        <strong>👋 Hola, <span id="rockola-user-name"></span></strong>
                        <small>Disponibles hoy: <strong id="rockola-daily-remaining">...</strong> canciones</small>
                    </p>
                    <button id="rockola-edit-info" class="rockola-btn-icon" title="Editar información">
                        ✏️ Editar
                    </button>
                </div>
                
                <h3>🎶 Buscar Canciones</h3>
                
                <!-- Barra de búsqueda -->
                <div class="rockola-search-box">
                    <input type="text" id="rockola-search" placeholder="Buscar canción o artista...">
                    <button id="rockola-search-btn" class="rockola-btn">Buscar</button>
                </div>
                
                <!-- Resultados de búsqueda -->
                <div id="rockola-results" class="rockola-results"></div>
                
                <!-- Lista flotante de canciones seleccionadas -->
                <div id="rockola-selected-container" class="rockola-selected" style="display: none;">
                    <h4>🎵 Tu Lista: <span id="rockola-count">0</span>/<?php echo $atts['max_songs']; ?></h4>
                    <div id="rockola-selected-list"></div>
                    <button id="rockola-submit-main" class="rockola-btn rockola-btn-primary" disabled>
                        🚀 Enviar a Spotify
                    </button>
                </div>
                
            </div>
            
            <div id="rockola-message"></div>
        </div>
        
        <!-- Popup Overlay -->
        <div id="rockola-popup-overlay" style="display: none;">
            <div id="rockola-popup">
                <h3 id="rockola-popup-title"></h3>
                <p id="rockola-popup-message"></p>
                <button id="rockola-popup-close" class="rockola-btn">Entendido</button>
            </div>
        </div>
        
        <style>
        /* ESTILOS MODERNOS ESTILO SPOTIFY */
        
        :root {
            --spotify-green: #1DB954;
            --spotify-green-hover: #1ed760;
            --spotify-black: #191414;
            --spotify-dark-gray: #121212;
            --spotify-gray: #535353;
            --spotify-light-gray: #b3b3b3;
            --spotify-white: #ffffff;
            --spotify-card: #282828;
        }
        
        .rockola-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .rockola-step {
            background: var(--spotify-card);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            margin-bottom: 24px;
            animation: fadeInUp 0.4s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .rockola-step h3 {
            color: var(--spotify-white);
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 12px 0;
            letter-spacing: -0.04em;
        }
        
        .rockola-step p {
            color: var(--spotify-light-gray);
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 32px 0;
        }
        
        .rockola-form-group {
            margin-bottom: 24px;
        }
        
        .rockola-form-group label {
            display: block;
            color: var(--spotify-white);
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        
        .rockola-form-group input {
            width: 100%;
            padding: 14px 16px;
            background: var(--spotify-black);
            border: 2px solid transparent;
            border-radius: 8px;
            color: var(--spotify-white);
            font-size: 16px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        
        .rockola-form-group input:focus {
            border-color: var(--spotify-green);
            outline: none;
            background: var(--spotify-dark-gray);
        }
        
        .rockola-form-group input::placeholder {
            color: var(--spotify-gray);
        }
        
        .rockola-form-group small {
            display: block;
            margin-top: 8px;
            color: var(--spotify-light-gray);
            font-size: 13px;
        }
        
        .rockola-btn {
            background: var(--spotify-green);
            color: var(--spotify-white);
            border: none;
            padding: 16px 48px;
            border-radius: 500px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            letter-spacing: 0.1em;
            box-shadow: 0 4px 12px rgba(29, 185, 84, 0.3);
            width: 100%;
        }
        
        .rockola-btn:hover:not(:disabled) {
            background: var(--spotify-green-hover);
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(29, 185, 84, 0.4);
        }
        
        .rockola-btn:disabled {
            background: var(--spotify-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            opacity: 0.5;
        }
        
        .rockola-btn-secondary {
            background: transparent;
            border: 2px solid var(--spotify-light-gray);
            color: var(--spotify-white);
            box-shadow: none;
        }
        
        .rockola-btn-secondary:hover {
            border-color: var(--spotify-white);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .rockola-btn-primary {
            background: var(--spotify-green);
            font-size: 18px;
            padding: 18px;
        }
        
        .rockola-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .rockola-actions .rockola-btn {
            flex: 1;
        }
        
        .rockola-user-info {
            background: linear-gradient(135deg, var(--spotify-green) 0%, #1aa34a 100%);
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 4px 12px rgba(29, 185, 84, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .rockola-user-info p {
            margin: 0;
            color: var(--spotify-white);
        }
        
        .rockola-user-info strong {
            font-size: 20px;
            display: block;
            margin-bottom: 4px;
        }
        
        .rockola-user-info small {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        
        .rockola-btn-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 500px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .rockola-btn-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        
        .rockola-search-box {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .rockola-search-box input {
            flex: 1;
            padding: 16px 20px;
            background: var(--spotify-black);
            border: 2px solid transparent;
            border-radius: 500px;
            color: var(--spotify-white);
            font-size: 16px;
            transition: all 0.2s ease;
        }
        
        .rockola-search-box input:focus {
            border-color: var(--spotify-green);
            outline: none;
        }
        
        .rockola-search-box .rockola-btn {
            width: auto;
            padding: 16px 32px;
        }
        
        .rockola-results {
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 24px;
            border-radius: 12px;
            background: var(--spotify-black);
        }
        
        .rockola-results::-webkit-scrollbar {
            width: 12px;
        }
        
        .rockola-results::-webkit-scrollbar-track {
            background: var(--spotify-dark-gray);
        }
        
        .rockola-results::-webkit-scrollbar-thumb {
            background: var(--spotify-gray);
            border-radius: 12px;
        }
        
        .rockola-track {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .rockola-track:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .rockola-track img {
            width: 56px;
            height: 56px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .rockola-track-info {
            flex: 1;
        }
        
        .rockola-track-info strong {
            display: block;
            color: var(--spotify-white);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .rockola-track-info small {
            color: var(--spotify-light-gray);
            font-size: 14px;
        }
        
        .rockola-add-btn {
            background: transparent;
            color: var(--spotify-green);
            border: 2px solid var(--spotify-green);
            padding: 10px 24px;
            border-radius: 500px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        
        .rockola-add-btn:hover:not(:disabled):not(.added) {
            background: var(--spotify-green);
            color: var(--spotify-white);
            transform: scale(1.05);
        }
        
        .rockola-add-btn.added {
            background: var(--spotify-green);
            color: var(--spotify-white);
            border-color: var(--spotify-green);
            cursor: default;
        }
        
        .rockola-add-btn:disabled {
            border-color: var(--spotify-gray);
            color: var(--spotify-gray);
            cursor: not-allowed;
        }
        
        .rockola-selected {
            position: sticky;
            bottom: 20px;
            background: linear-gradient(180deg, transparent 0%, var(--spotify-card) 20%, var(--spotify-card) 100%);
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.5);
            z-index: 100;
            animation: slideUp 0.4s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .rockola-selected h4 {
            color: var(--spotify-white);
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 16px 0;
        }
        
        .rockola-selected h4 span {
            color: var(--spotify-green);
        }
        
        .rockola-selected-item {
            background: var(--spotify-black);
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--spotify-green);
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .rockola-selected-item strong {
            color: var(--spotify-white);
            font-size: 15px;
            display: block;
        }
        
        .rockola-selected-item small {
            color: var(--spotify-light-gray);
            font-size: 13px;
        }
        
        .rockola-remove-btn {
            background: transparent;
            color: #ff4444;
            border: none;
            padding: 8px 16px;
            border-radius: 500px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        
        .rockola-remove-btn:hover {
            background: rgba(255, 68, 68, 0.1);
            transform: scale(1.05);
        }
        
        #rockola-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #rockola-popup {
            background: var(--spotify-card);
            padding: 40px;
            border-radius: 16px;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.8);
            animation: popIn 0.3s ease-out;
        }
        
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        #rockola-popup-title {
            color: var(--spotify-white);
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 16px 0;
        }
        
        #rockola-popup-message {
            color: var(--spotify-light-gray);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .rockola-message {
            padding: 16px 20px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 14px;
            animation: slideInDown 0.3s ease-out;
        }
        
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .rockola-message.success {
            background: rgba(29, 185, 84, 0.2);
            color: var(--spotify-green);
            border: 1px solid var(--spotify-green);
        }
        
        .rockola-message.error {
            background: rgba(255, 68, 68, 0.2);
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        
        .rockola-message.warning {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }
        
        @media (max-width: 768px) {
            .rockola-step {
                padding: 24px;
            }
            
            .rockola-step h3 {
                font-size: 24px;
            }
            
            .rockola-btn {
                padding: 14px 32px;
                font-size: 14px;
            }
            
            .rockola-search-box {
                flex-direction: column;
            }
            
            .rockola-actions {
                flex-direction: column;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Configuración
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
            
            console.log("🎵 Rockola inicializada - Límite diario:", maxSongs);
            
            // ==================== FUNCIONES AUXILIARES ====================
            
            function showPopup(title, message, type = 'info') {
                const colors = {
                    'info': '#1DB954',
                    'warning': '#f0ad4e',
                    'error': '#dc3545',
                    'success': '#28a745'
                };
                
                const icons = {
                    'info': 'ℹ️',
                    'warning': '⚠️',
                    'error': '❌',
                    'success': '✅'
                };
                
                const popupColor = colors[type] || colors.info;
                
                $('#rockola-popup-title')
                    .html(icons[type] + ' ' + title)
                    .css('color', popupColor);
                
                $('#rockola-popup-message').html(message);
                $('#rockola-popup-close').css('background', popupColor);
                $('#rockola-popup-overlay').fadeIn(300);
                
                setTimeout(() => $('#rockola-popup-close').focus(), 300);
                
                if (type === 'success') {
                    setTimeout(() => $('#rockola-popup-overlay').fadeOut(300), 5000);
                }
            }
            
            function showMessage(text, type) {
                const $msg = $('#rockola-message');
                $msg.removeClass('success error warning')
                    .addClass('rockola-message ' + type)
                    .html(text)
                    .fadeIn();
                
                setTimeout(() => $msg.fadeOut(), 4000);
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
            
            // ==================== EVENTOS DEL POPUP ====================
            
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
            
            // ==================== PASO 0: VERIFICACIÓN SIMPLIFICADA ====================
            
            $('#rockola-verify-user').on('click', function() {
                const email = $('#rockola-verify-email').val().trim();
                const whatsapp = $('#rockola-verify-whatsapp').val().trim();
                
                if (!email && !whatsapp) {
                    showMessage('Por favor, ingresa tu correo o número de WhatsApp', 'error');
                    return;
                }
                
                if (email && !isValidEmail(email)) {
                    showMessage('Correo electrónico inválido', 'error');
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
                                userSession = {
                                    id: userData.id,
                                    name: userData.name,
                                    email: userData.email,
                                    whatsapp: userData.whatsapp,
                                    birthday: userData.birthday || '',
                                    dailyRemaining: remaining,
                                    isNewUser: false
                                };
                                
                                $('#rockola-user-name').text(userData.name);
                                $('#rockola-daily-remaining').text(remaining);
                                
                                showMessage('¡Bienvenido de nuevo ' + userData.name + '!', 'success');
                                
                                setTimeout(() => {
                                    $('#rockola-step-0').hide();
                                    $('#rockola-step-2').show();
                                }, 1000);
                                
                            } else {
                                userSession = {
                                    id: null,
                                    name: '',
                                    email: email || '',
                                    whatsapp: whatsapp || '',
                                    birthday: '',
                                    dailyRemaining: maxSongs,
                                    isNewUser: true
                                };
                                
                                $('#rockola-email').val(email || '');
                                $('#rockola-whatsapp').val(whatsapp || '');
                                
                                $('#rockola-step1-title').text('Completa tu Registro');
                                $('#rockola-step1-description').html('Es tu primera vez aquí. Completa tus datos para continuar.');
                                
                                $('#rockola-session-info').hide();
                                
                                showMessage('Nuevo usuario detectado', 'info');
                                
                                setTimeout(() => {
                                    $('#rockola-step-0').hide();
                                    $('#rockola-step-1').show();
                                }, 1000);
                            }
                        } else {
                            showMessage('Error al verificar usuario', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Error de conexión', 'error');
                    },
                    complete: function() {
                        $('#rockola-verify-user').prop('disabled', false).text('Continuar →');
                    }
                });
            });
            
            $('#rockola-back-to-verify').on('click', function() {
                $('#rockola-step-1').hide();
                $('#rockola-step-0').show();
            });
            
            // ==================== PASO 1: GUARDAR INFORMACIÓN ====================
            
            $('#rockola-save-and-continue').on('click', function() {
                const name = $('#rockola-name').val().trim();
                const email = $('#rockola-email').val().trim();
                const whatsapp = $('#rockola-whatsapp').val().trim();
                
                if (!name || !email || !whatsapp) {
                    showMessage('Por favor, completa todos los campos requeridos', 'error');
                    return;
                }
                
                if (!isValidEmail(email)) {
                    showMessage('Correo electrónico inválido', 'error');
                    return;
                }
                
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
                            userSession.name = userData.name;
                            userSession.email = userData.email;
                            userSession.whatsapp = userData.whatsapp;
                            userSession.birthday = userData.birthday;
                            userSession.dailyRemaining = response.data.remaining || maxSongs;
                            userSession.id = response.data.user_id || userSession.id;
                            
                            $('#rockola-user-name').text(userData.name);
                            $('#rockola-daily-remaining').text(userSession.dailyRemaining);
                            
                            showMessage('Información guardada correctamente', 'success');
                            
                            setTimeout(() => {
                                $('#rockola-step-1').hide();
                                $('#rockola-step-2').show();
                            }, 1000);
                            
                        } else {
                            showMessage('Error al guardar: ' + (response.data.message || 'Error desconocido'), 'error');
                        }
                    },
                    error: function() {
                        showMessage('Error de conexión', 'error');
                    },
                    complete: function() {
                        $('#rockola-save-and-continue').prop('disabled', false).text('Guardar y Continuar →');
                    }
                });
            });
            
            // ==================== BOTÓN EDITAR INFORMACIÓN ====================
            
            $('#rockola-edit-info').on('click', function() {
                $('#rockola-name').val(userSession.name);
                $('#rockola-email').val(userSession.email);
                $('#rockola-whatsapp').val(userSession.whatsapp);
                $('#rockola-birthday').val(userSession.birthday);
                
                $('#rockola-step1-title').text('Editar Información');
                $('#rockola-step1-description').html('Actualiza tus datos según sea necesario.');
                $('#rockola-session-info').show();
                
                userSession.isNewUser = false;
                
                $('#rockola-step-2').hide();
                $('#rockola-step-1').show();
            });
            
            // ==================== PASO 2: BÚSQUEDA Y SELECCIÓN ====================
            
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
                
                $('#rockola-results').html('<div style="padding: 40px; text-align: center; color: #b3b3b3;"><div style="font-size: 48px; margin-bottom: 16px;">🔍</div>Buscando canciones...</div>');
                
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
                            showMessage('Error en la búsqueda', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Error de conexión', 'error');
                    }
                });
            }
            
            function displayResults(tracks) {
                if (tracks.length === 0) {
                    $('#rockola-results').html('<div style="padding: 40px; text-align: center; color: #b3b3b3;"><div style="font-size: 48px; margin-bottom: 16px;">😕</div>No se encontraron canciones</div>');
                    return;
                }
                
                let html = '';
                tracks.forEach(track => {
                    const trackName = escapeHtml(track.name);
                    const trackArtist = escapeHtml(track.artist);
                    const isAdded = addedTrackUris.includes(track.uri);
                    const canAdd = userSession.dailyRemaining > selectedSongs.length;
                    
                    html += `
                    <div class="rockola-track">
                        <img src="${track.image || ''}" alt="${trackName}">
                        <div class="rockola-track-info">
                            <strong>${trackName}</strong>
                            <small>${trackArtist}</small>
                        </div>
                        <button class="rockola-add-btn ${isAdded ? 'added' : ''}" 
                                data-track='${JSON.stringify(track)}'
                                ${isAdded || !canAdd ? 'disabled' : ''}>
                            ${isAdded ? '✓ Añadida' : (canAdd ? '+ Añadir' : 'Límite')}
                        </button>
                    </div>`;
                });
                
                $('#rockola-results').html(html);
                
                $('.rockola-add-btn:not(.added):not(:disabled)').on('click', function() {
                    const track = JSON.parse($(this).attr('data-track'));
                    addTrack(track, $(this));
                });
            }
            
            function addTrack(track, $btn) {
                if (selectedSongs.length >= maxSongs) {
                    showPopup(
                        'Límite de Selección',
                        `Solo puedes seleccionar hasta <strong>${maxSongs}</strong> canciones por vez.<br><br>
                        <small>Envía las canciones seleccionadas primero.</small>`,
                        'warning'
                    );
                    return;
                }
                
                if (selectedSongs.find(t => t.uri === track.uri)) {
                    showMessage('Esta canción ya está en tu lista', 'warning');
                    return;
                }
                
                if (selectedSongs.length >= userSession.dailyRemaining) {
                    showPopup(
                        'Límite Diario Alcanzado',
                        `Solo puedes añadir <strong>${userSession.dailyRemaining}</strong> canciones hoy.<br><br>
                        <small>Podrás enviar más canciones mañana.</small>`,
                        'warning'
                    );
                    return;
                }
                
                selectedSongs.push(track);
                addedTrackUris.push(track.uri);
                
                if ($btn) {
                    $btn.addClass('added')
                        .text('✓ Añadida')
                        .prop('disabled', true);
                }
                
                updateSelectedList();
                showMessage(`✓ "${track.name}" añadida a tu lista`, 'success');
            }
            
            function updateSelectedList() {
                $('#rockola-count').text(selectedSongs.length);
                
                if (selectedSongs.length === 0) {
                    $('#rockola-selected-container').hide();
                    return;
                }
                
                $('#rockola-selected-container').show();
                
                let html = '';
                selectedSongs.forEach((track, index) => {
                    html += `
                    <div class="rockola-selected-item">
                        <div>
                            <strong>${escapeHtml(track.name)}</strong>
                            <small>${escapeHtml(track.artist)}</small>
                        </div>
                        <button class="rockola-remove-btn" data-index="${index}">
                            ✕ Quitar
                        </button>
                    </div>`;
                });
                
                $('#rockola-selected-list').html(html);
                
                $('#rockola-submit-main').prop('disabled', selectedSongs.length === 0);
                
                $('.rockola-remove-btn').on('click', function() {
                    const index = $(this).data('index');
                    const removedTrack = selectedSongs[index];
                    
                    selectedSongs.splice(index, 1);
                    addedTrackUris = addedTrackUris.filter(uri => uri !== removedTrack.uri);
                    
                    $(`.rockola-add-btn[data-track*='"${removedTrack.uri}"']`)
                        .removeClass('added')
                        .text('+ Añadir')
                        .prop('disabled', false);
                    
                    updateSelectedList();
                    showMessage(`Canción removida`, 'warning');
                });
            }
            
            // ==================== ENVIAR A SPOTIFY ====================
            
            $('#rockola-submit-main').on('click', function() {
                if (selectedSongs.length === 0) return;
                
                const $btn = $(this);
                const totalSongs = selectedSongs.length;
                
                $btn.prop('disabled', true).html(`🚀 Enviando... 0/${totalSongs}`);
                
                let sentCount = 0;
                let errors = [];
                let successfulSongs = [];
                
                function sendNextSong() {
                    if (sentCount >= totalSongs) {
                        $btn.prop('disabled', false).html('🚀 Enviar a Spotify');
                        
                        if (errors.length === 0) {
                            showPopup(
                                '¡Canciones Enviadas!',
                                `<strong>${totalSongs}</strong> ${totalSongs === 1 ? 'canción ha' : 'canciones han'} sido añadidas a Spotify.<br><br>
                                <strong>Te quedan ${userSession.dailyRemaining} ${userSession.dailyRemaining === 1 ? 'canción' : 'canciones'} disponibles hoy.</strong><br><br>
                                <small>🎵 Tus canciones sonarán pronto</small>`,
                                'success'
                            );
                            
                            selectedSongs = [];
                            addedTrackUris = [];
                            updateSelectedList();
                            
                            if ($('#rockola-search').val().trim()) {
                                searchTracks();
                            }
                            
                        } else if (successfulSongs.length > 0) {
                            showPopup(
                                'Envío Parcial',
                                `Se enviaron <strong>${successfulSongs.length} de ${totalSongs}</strong> canciones.<br><br>
                                <strong>Errores:</strong><br>${errors.slice(0, 3).join('<br>')}<br><br>
                                <small>Te quedan ${userSession.dailyRemaining} canciones disponibles.</small>`,
                                'warning'
                            );
                            
                            selectedSongs = selectedSongs.filter(track => 
                                !successfulSongs.includes(track.name)
                            );
                            updateSelectedList();
                            
                        } else {
                            showPopup(
                                'Error al Enviar',
                                `No se pudo enviar ninguna canción.<br><br>
                                <small>Por favor, intenta de nuevo.</small>`,
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
                            user_id: userSession.id,
                            name: userSession.name,
                            email: userSession.email,
                            whatsapp: userSession.whatsapp,
                            birthday: userSession.birthday,
                            track_uri: track.uri
                        },
                        success: function(response) {
                            sentCount++;
                            $btn.html(`🚀 Enviando... ${sentCount}/${totalSongs}`);
                            
                            if (response.success) {
                                successfulSongs.push(track.name);
                                
                                if (response.data && response.data.remaining_today !== undefined) {
                                    userSession.dailyRemaining = response.data.remaining_today;
                                    $('#rockola-daily-remaining').text(userSession.dailyRemaining);
                                }
                            } else {
                                errors.push(track.name);
                            }
                            
                            sendNextSong();
                        },
                        error: function() {
                            errors.push(track.name);
                            sentCount++;
                            sendNextSong();
                        }
                    });
                }
                
                sendNextSong();
            });
            
            // ==================== AUTO-LOAD ====================
            
            const savedEmail = localStorage.getItem('rockola_user_email');
            const savedWhatsapp = localStorage.getItem('rockola_user_whatsapp');
            
            if (savedEmail) {
                $('#rockola-verify-email').val(savedEmail);
            }
            if (savedWhatsapp) {
                $('#rockola-verify-whatsapp').val(savedWhatsapp);
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
