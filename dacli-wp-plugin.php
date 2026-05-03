<?php
/**
 * Plugin Name: DACLI WP Plugin
 * Description: Gestión profesional de laboratorios vía API para dAcli Sistemas.
 * Version: 1.3.0
 * Author: David Domínguez
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Constantes
define( 'DACLI_VERSION', '1.3.0' );
define( 'DACLI_DEFAULT_API', 'https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/lista_web.php' );

// 1. MENÚ DE ADMINISTRACIÓN
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

// 2. PÁGINA DE CONFIGURACIÓN
function dacli_settings_page() {
    // Guardar ajustes
    if ( isset( $_POST['dacli_submit'] ) && check_admin_referer( 'dacli_update_settings' ) ) {
        update_option( 'dacli_api_url', sanitize_text_field( $_POST['api_url'] ) );
        update_option( 'dacli_cache_time', absint( $_POST['cache_time'] ) );
        update_option( 'dacli_show_rif', isset( $_POST['show_rif'] ) ? 1 : 0 );
        update_option( 'dacli_show_address', isset( $_POST['show_address'] ) ? 1 : 0 );
        update_option( 'dacli_show_testimonial', isset( $_POST['show_testimonial'] ) ? 1 : 0 );
        update_option( 'dacli_show_phone', isset( $_POST['show_phone'] ) ? 1 : 0 );
        update_option( 'dacli_show_web', isset( $_POST['show_web'] ) ? 1 : 0 );
        echo '<div class="updated"><p>Configuración guardada correctamente.</p></div>';
    }

    // Limpiar caché (vía POST normal en la misma página)
    if ( isset( $_POST['dacli_clear_cache'] ) && check_admin_referer( 'dacli_cache_action' ) ) {
        delete_transient( 'dacli_labs_cache' );
        echo '<div class="updated"><p>Caché eliminada. La próxima carga consultará la API nuevamente.</p></div>';
    }

    $api_url      = get_option( 'dacli_api_url', DACLI_DEFAULT_API );
    $cache_time   = get_option( 'dacli_cache_time', 60 );
    $show_rif     = get_option( 'dacli_show_rif', 1 );
    $show_address = get_option( 'dacli_show_address', 1 );
    $show_testimonial = get_option( 'dacli_show_testimonial', 1 );
    $show_phone   = get_option( 'dacli_show_phone', 1 );
    $show_web     = get_option( 'dacli_show_web', 1 );
    ?>
    <div class="wrap">
        <h1>Configuración dAcli API</h1>
        <form method="post">
            <?php wp_nonce_field( 'dacli_update_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="api_url">URL del Endpoint (JSON)</label></th>
                    <td><input type="text" id="api_url" name="api_url" value="<?php echo esc_attr( $api_url ); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th><label for="cache_time">Tiempo de Caché (minutos)</label></th>
                    <td><input type="number" id="cache_time" name="cache_time" value="<?php echo esc_attr( $cache_time ); ?>" min="1" max="1440" /> </td>
                </tr>
            </table>
            <h3>Mostrar en el frontend:</h3>
            <table class="form-table">
                <tr>
                    <th>Campos visibles</th>
                    <td>
                        <label><input type="checkbox" name="show_rif" <?php checked( $show_rif ); ?> /> RIF</label><br>
                        <label><input type="checkbox" name="show_address" <?php checked( $show_address ); ?> /> Dirección</label><br>
                        <label><input type="checkbox" name="show_testimonial" <?php checked( $show_testimonial ); ?> /> Testimonio</label><br>
                        <label><input type="checkbox" name="show_phone" <?php checked( $show_phone ); ?> /> Teléfono</label><br>
                        <label><input type="checkbox" name="show_web" <?php checked( $show_web ); ?> /> Sitio web</label>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Guardar Ajustes', 'primary', 'dacli_submit' ); ?>
        </form>
        <hr>
        <form method="post">
            <?php wp_nonce_field( 'dacli_cache_action' ); ?>
            <button type="submit" name="dacli_clear_cache" class="button button-secondary">Limpiar Caché Ahora</button>
        </form>
        <hr>
        <p>Usa el shortcode <code>[dacli_lista]</code> en cualquier página o entrada. Atributos opcionales: <code>columns</code> (1-4), <code>show_phone</code> (yes/no), etc.</p>
    </div>
    <?php
}

// 3. FUNCIÓN PARA OBTENER DATOS DE LA API (con caché)
function dacli_get_labs_data() {
    $labs = get_transient( 'dacli_labs_cache' );

    if ( false === $labs ) {
        $url = get_option( 'dacli_api_url', DACLI_DEFAULT_API );
        if ( empty( $url ) ) {
            error_log( '[DACLI] No hay URL de API configurada.' );
            return [];
        }

        $args = array(
            'timeout'     => 20,
            'redirection' => 5,
            'sslverify'   => false,
            'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) dAcliBot/1.0',
        );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            error_log( '[DACLI] Error de conexión: ' . $response->get_error_message() );
            return [];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            error_log( "[DACLI] La API respondió con código HTTP $code" );
            return [];
        }

        $body = wp_remote_retrieve_body( $response );
        $labs = json_decode( $body, true );

        if ( ! is_array( $labs ) ) {
            error_log( '[DACLI] El JSON decodificado no es un array.' );
            return [];
        }

        $cache_min = max( 1, absint( get_option( 'dacli_cache_time', 60 ) ) );
        set_transient( 'dacli_labs_cache', $labs, $cache_min * MINUTE_IN_SECONDS );
    }

    return $labs;
}

// 4. RENDERIZADO FRONTEND (shortcode y opciones)
function dacli_render_labs_html( $atts = [] ) {
    // Atributos del shortcode
    $atts = shortcode_atts( array(
        'columns'      => 'auto',
        'show_phone'   => 'default', // yes, no, default
        'show_web'     => 'default',
        'show_rif'     => 'default',
        'show_address' => 'default',
        'show_testimonial' => 'default',
    ), $atts );

    // Obtener opciones globales
    $opt_rif     = get_option( 'dacli_show_rif', 1 );
    $opt_address = get_option( 'dacli_show_address', 1 );
    $opt_testim  = get_option( 'dacli_show_testimonial', 1 );
    $opt_phone   = get_option( 'dacli_show_phone', 1 );
    $opt_web     = get_option( 'dacli_show_web', 1 );

    // Determinar visibilidad final
    $show_rif     = ( $atts['show_rif'] === 'default' ) ? $opt_rif : ( $atts['show_rif'] === 'yes' );
    $show_address = ( $atts['show_address'] === 'default' ) ? $opt_address : ( $atts['show_address'] === 'yes' );
    $show_testim  = ( $atts['show_testimonial'] === 'default' ) ? $opt_testim : ( $atts['show_testimonial'] === 'yes' );
    $show_phone   = ( $atts['show_phone'] === 'default' ) ? $opt_phone : ( $atts['show_phone'] === 'yes' );
    $show_web     = ( $atts['show_web'] === 'default' ) ? $opt_web : ( $atts['show_web'] === 'yes' );

    $labs = dacli_get_labs_data();

    if ( empty( $labs ) ) {
        return '<div class="dacli-error" style="text-align:center; padding:20px; border:1px dashed #ccc;">⚠️ No se pudieron cargar los laboratorios. Verifica la configuración del plugin o contacta al administrador.</div>';
    }

    // Grid CSS
    $grid_class = ( $atts['columns'] === 'auto' ) ? 'dacli-grid-auto' : 'dacli-grid-' . intval( $atts['columns'] );
    $output = '<style>
        .dacli-grid-auto { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin: 30px 0; }
        .dacli-grid-1 { display: grid; grid-template-columns: 1fr; gap: 25px; }
        .dacli-grid-2 { display: grid; grid-template-columns: repeat(2,1fr); gap: 25px; }
        .dacli-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 25px; }
        .dacli-grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 25px; }
        @media (max-width: 768px) {
            .dacli-grid-2, .dacli-grid-3, .dacli-grid-4 { grid-template-columns: 1fr; }
        }
        .dacli-card { border: 1px solid #e1e8ed; padding: 25px; border-radius: 12px; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.3s ease; }
        .dacli-card:hover { transform: translateY(-7px); box-shadow: 0 12px 20px rgba(0,0,0,0.1); border-color: #0d6efd; }
        .dacli-logo { max-height: 70px; width: auto; object-fit: contain; margin-bottom: 20px; display: block; }
        .dacli-name { font-size: 1.3rem; font-weight: bold; margin: 0 0 10px 0; color: #1a1a1a; }
        .dacli-info { font-size: 0.95rem; color: #4a4a4a; margin-bottom: 8px; }
        .dacli-testimonio { font-style: italic; color: #6c757d; font-size: 0.9rem; margin: 15px 0; padding-left: 10px; border-left: 3px solid #0d6efd; }
        .dacli-btn { display: inline-block; background-color: #0d6efd; color: #fff !important; text-decoration: none; padding: 10px 20px; border-radius: 6px; text-align: center; font-weight: bold; margin-top: 15px; transition: background 0.2s; margin-right: 10px; }
        .dacli-btn-phone { background-color: #28a745; }
        .dacli-btn-phone:hover { background-color: #218838; }
        .dacli-btn:hover { background-color: #0b5ed7; }
        .dacli-buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; }
    </style>';

    $output .= "<div class='{$grid_class}'>";

    foreach ( $labs as $lab ) {
        // Sanitizar campos
        $nombre   = isset( $lab['nombre_lab'] ) ? esc_html( $lab['nombre_lab'] ) : 'Laboratorio';
        $nombre_corto = isset( $lab['nombre_lab_corto'] ) ? esc_attr( $lab['nombre_lab_corto'] ) : '';
        $rif      = isset( $lab['rif'] ) ? esc_html( $lab['rif'] ) : '';
        $direccion= isset( $lab['direccion_lab'] ) ? esc_html( $lab['direccion_lab'] ) : '';
        $testimonio = isset( $lab['testimonio'] ) ? esc_html( $lab['testimonio'] ) : '';
        $telefono = isset( $lab['telefono_lab'] ) ? esc_html( $lab['telefono_lab'] ) : '';
        $web      = isset( $lab['web_laboratorio'] ) ? esc_url( $lab['web_laboratorio'] ) : '';
        $logo_src = isset( $lab['logo'] ) ? esc_url( $lab['logo'] ) : '';

        $output .= '<div class="dacli-card">';
        $output .= '<div>';

       if ( ! empty( $logo_src ) ) {
    $output .= '<img src="' . $logo_src . '" class="dacli-logo" alt="Logo ' . $nombre_corto . '" style="display: block; margin-left: auto; margin-right: auto;">';
}

        $output .= '<h3 class="dacli-name">' . $nombre . '</h3>';

        if ( $show_rif && ! empty( $rif ) ) {
            $output .= '<p class="dacli-info">📄 RIF: ' . $rif . '</p>';
        }
        if ( $show_address && ! empty( $direccion ) ) {
            $output .= '<p class="dacli-info">📍 Ubicación: ' . $direccion . '</p>';
        }
        if ( $show_phone && ! empty( $telefono ) ) {
            $output .= '<p class="dacli-info">📞 Teléfono: ' . $telefono . '</p>';
        }
        if ( $show_testim && ! empty( $testimonio ) ) {
            $output .= '<p class="dacli-testimonio">“' . $testimonio . '”</p>';
        }

        $output .= '</div>'; // cierre del contenedor de texto        
        $output .= '</div>'; // cierre card
    }

    $output .= '</div>';
    return $output;
}
add_shortcode( 'dacli_lista', 'dacli_render_labs_html' );
