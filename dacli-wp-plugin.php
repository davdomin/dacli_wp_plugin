<?php
/**
 * Plugin Name: DACLI WP Plugin
 * Description: Gestión profesional de laboratorios vía API para dAcli Sistemas.
 * Version: 1.2.0
 * Author: David Domínguez
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. CREACIÓN DEL MENÚ DE ADMINISTRACIÓN
add_action( 'admin_menu', 'dacli_add_admin_menu' );
function dacli_add_admin_menu() {
    add_menu_page( 
        'DACLI Settings', 
        'dAcli Labs', 
        'manage_options', 
        'dacli_settings', 
        'dacli_settings_page', 
        'dashicons-rest-api' 
    );
}

// 2. PÁGINA DE CONFIGURACIÓN EN EL BACKEND
function dacli_settings_page() {
    // Manejar limpieza de caché
    if ( isset( $_POST['dacli_clear_cache'] ) ) {
        check_admin_referer( 'dacli_cache_action' );
        delete_transient( 'dacli_labs_cache' );
        echo '<div class="updated"><p>Caché eliminada. La próxima carga consultará la API nuevamente.</p></div>';
    }

    // Guardar ajustes
    if ( isset( $_POST['dacli_submit'] ) ) {
        check_admin_referer( 'dacli_update_settings' );
        update_option( 'dacli_api_url', sanitize_text_field( $_POST['api_url'] ) );
        update_option( 'dacli_cache_time', absint( $_POST['cache_time'] ) );
        echo '<div class="updated"><p>Configuración guardada correctamente.</p></div>';
    }

    $api_url = get_option( 'dacli_api_url', 'https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/lista_web.php' );
    $cache_time = get_option( 'dacli_cache_time', 60 );
    
    echo '<div class="wrap"><h1>Configuración dAcli API</h1><form method="post">';
    wp_nonce_field( 'dacli_update_settings' );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="api_url">URL del Endpoint (JSON)</label></th>
            <td><input type="text" id="api_url" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label for="cache_time">Tiempo de Caché (minutos)</label></th>
            <td><input type="number" id="cache_time" name="cache_time" value="<?php echo esc_attr($cache_time); ?>" /></td>
        </tr>
    </table>
    <?php submit_button('Guardar Ajustes', 'primary', 'dacli_submit'); ?>
    </form>
    
    <hr>
    <form method="post">
        <?php wp_nonce_field( 'dacli_cache_action' ); ?>
        <button type="submit" name="dacli_clear_cache" class="button button-secondary">Limpiar Caché Ahora</button>
    </form>
    </div>
    <?php
}

// 3. FUNCIÓN PARA OBTENER LOS DATOS (LÓGICA DE API)
function dacli_get_labs_data() {
    $labs = get_transient( 'dacli_labs_cache' );
    $labs = false;
    if ( false === $labs ) {
        $url = get_option( 'dacli_api_url' );
        if ( empty($url) ) return [];

        $args = array(
            'timeout'     => 20,
            'redirection' => 5,
            'sslverify'   => false, 
            'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) dAcliBot/1.0',
        );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return [];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return [];
        }

        $body = wp_remote_retrieve_body( $response );
        $labs = json_decode( $body, true );

        if ( ! is_array( $labs ) ) {
            return [];
        }

        $cache_min = get_option( 'dacli_cache_time', 60 );
        set_transient( 'dacli_labs_cache', $labs, $cache_min * MINUTE_IN_SECONDS );
    }
    return $labs;
}

// 4. RENDERIZADO DEL HTML PARA EL FRONTEND
function dacli_render_labs_html() {
    $labs = dacli_get_labs_data();
    
    if ( empty( $labs ) ) {
        return '<p style="text-align:center; padding:20px; border:1px dashed #ccc;">No se pudieron cargar los laboratorios. Intente limpiar la caché en el panel de control.</p>';
    }

    $output = '
    <style>
        .dacli-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin: 30px 0; font-family: sans-serif; }
        .dacli-card { border: 1px solid #e1e8ed; padding: 25px; border-radius: 12px; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.3s ease; }
        .dacli-card:hover { transform: translateY(-7px); box-shadow: 0 12px 20px rgba(0,0,0,0.1); border-color: #0d6efd; }
        .dacli-logo { max-height: 70px; width: auto; object-fit: contain; margin-bottom: 20px; display: block; }
        .dacli-name { font-size: 1.3rem; font-weight: bold; margin: 0 0 10px 0; color: #1a1a1a; line-height: 1.2; }
        .dacli-info { font-size: 0.95rem; color: #4a4a4a; margin-bottom: 8px; line-height: 1.4; }
        .dacli-testimonio { font-style: italic; color: #6c757d; font-size: 0.9rem; margin: 15px 0; padding-left: 10px; border-left: 3px solid #0d6efd; }
        .dacli-btn { display: inline-block; background-color: #0d6efd; color: #fff !important; text-decoration: none; padding: 10px 20px; border-radius: 6px; text-align: center; font-weight: bold; margin-top: 15px; transition: background 0.2s; }
        .dacli-btn:hover { background-color: #0b5ed7; }
    </style>';

    $output .= '<div class="dacli-grid">';
    
    foreach ( $labs as $lab ) {
        //$base_url_logos = 'https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/';
        //$logo_src = !empty($lab['logo']) ? $base_url_logos . $lab['logo'] : '';
        $logo_src = !empty($lab['logo']) ? $lab['logo'] : '';

        $output .= '<div class="dacli-card">';
        $output .= '<div>';
        
        if ( !empty($logo_src) ) {
            $output .= '<img src="'. esc_url( $logo_src ) .'" class="dacli-logo" alt="Logo '. esc_attr($lab['nombre_lab_corto']) .'">';
        }
        
        $output .= '<h3 class="dacli-name">'. esc_html( $lab['nombre_lab'] ) .'</h3>';
        $output .= '<p class="dacli-info"> '. esc_html( $lab['rif'] ) .'</p>';
        $output .= '<p class="dacli-info"><strong>Ubicación:</strong> '. esc_html( $lab['direccion_lab'] ) .'</p>';
        
        if ( !empty($lab['testimonio']) ) {
            $output .= '<p class="dacli-testimonio">"'. esc_html( $lab['testimonio'] ) .'"</p>';
        }
        $output .= '</div>';

        // LÓGICA ACTUALIZADA: Enlace a la web del laboratorio en lugar de link_laboratorio
        // Nota: Asegúrate de que la clave del JSON sea 'web_laboratorio'
        if ( !empty($lab['web_laboratorio']) && $lab['web_laboratorio'] !== 'http:' ) {
            $output .= '<a href="'. esc_url( $lab['web_laboratorio'] ) .'" target="_blank" rel="noopener noreferrer">'. $lab['web_laboratorio']  .'</a>';
        }

        $output .= '</div>'; 
    }
    
    $output .= '</div>';
    return $output;
}

// 5. REGISTRO DEL SHORTCODE [dacli_lista]
add_shortcode( 'dacli_lista', 'dacli_render_labs_html' );
