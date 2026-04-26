<?php
/**
 * Plugin Name: DACLI WP Plugin
 * Description: Gestión profesional de laboratorios vía API para dAcli.
 * Version: 1.0.0
 * Author: David Domínguez
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Menu de administración
add_action( 'admin_menu', 'dacli_add_admin_menu' );
function dacli_add_admin_menu() {
    add_menu_page( 'DACLI Settings', 'dAcli Labs', 'manage_options', 'dacli_settings', 'dacli_settings_page', 'dashicons-rest-api' );
}

function dacli_settings_page() {
    // Limpiar Caché manual
    if ( isset( $_POST['dacli_clear_cache'] ) ) {
        check_admin_referer( 'dacli_cache_action' );
        delete_transient( 'dacli_labs_cache' );
        echo '<div class="updated"><p>Caché eliminada correctamente.</p></div>';
    }

    // Guardar Ajustes
    if ( isset( $_POST['dacli_submit'] ) ) {
        check_admin_referer( 'dacli_update_settings' );
        update_option( 'dacli_api_url', sanitize_text_field( $_POST['api_url'] ) );
        update_option( 'dacli_cache_time', absint( $_POST['cache_time'] ) );
        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }

    $api_url = get_option( 'dacli_api_url', 'https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/lista_web.php' );
    $cache_time = get_option( 'dacli_cache_time', 60 );
    
    echo '<div class="wrap"><h1>Configuración dAcli API</h1><form method="post">';
    wp_nonce_field( 'dacli_update_settings' );
    ?>
    <table class="form-table">
        <tr>
            <th><label>URL de la API</label></th>
            <td><input type="text" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label>Caché (minutos)</label></th>
            <td><input type="number" name="cache_time" value="<?php echo esc_attr($cache_time); ?>" /></td>
        </tr>
    </table>
    <?php submit_button('Guardar Cambios', 'primary', 'dacli_submit'); ?>
    </form>
    
    <hr>
    <form method="post">
        <?php wp_nonce_field( 'dacli_cache_action' ); ?>
        <button type="submit" name="dacli_clear_cache" class="button button-secondary">Limpiar Caché Ahora</button>
    </form>
    </div>
    <?php
}

// Función principal para obtener datos
<?php
/**
 * Plugin Name: DACLI WP Plugin
 * Description: Gestión profesional de laboratorios vía API para dAcli.
 * Version: 1.0.0
 * Author: David Domínguez
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Menu de administración
add_action( 'admin_menu', 'dacli_add_admin_menu' );
function dacli_add_admin_menu() {
    add_menu_page( 'DACLI Settings', 'dAcli Labs', 'manage_options', 'dacli_settings', 'dacli_settings_page', 'dashicons-rest-api' );
}

function dacli_settings_page() {
    // Limpiar Caché manual
    if ( isset( $_POST['dacli_clear_cache'] ) ) {
        check_admin_referer( 'dacli_cache_action' );
        delete_transient( 'dacli_labs_cache' );
        echo '<div class="updated"><p>Caché eliminada correctamente.</p></div>';
    }

    // Guardar Ajustes
    if ( isset( $_POST['dacli_submit'] ) ) {
        check_admin_referer( 'dacli_update_settings' );
        update_option( 'dacli_api_url', sanitize_text_field( $_POST['api_url'] ) );
        update_option( 'dacli_cache_time', absint( $_POST['cache_time'] ) );
        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }

    $api_url = get_option( 'dacli_api_url', 'https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/lista_web.php' );
    $cache_time = get_option( 'dacli_cache_time', 60 );
    
    echo '<div class="wrap"><h1>Configuración dAcli API</h1><form method="post">';
    wp_nonce_field( 'dacli_update_settings' );
    ?>
    <table class="form-table">
        <tr>
            <th><label>URL de la API</label></th>
            <td><input type="text" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label>Caché (minutos)</label></th>
            <td><input type="number" name="cache_time" value="<?php echo esc_attr($cache_time); ?>" /></td>
        </tr>
    </table>
    <?php submit_button('Guardar Cambios', 'primary', 'dacli_submit'); ?>
    </form>
    
    <hr>
    <form method="post">
        <?php wp_nonce_field( 'dacli_cache_action' ); ?>
        <button type="submit" name="dacli_clear_cache" class="button button-secondary">Limpiar Caché Ahora</button>
    </form>
    </div>
    <?php
}

// Función principal para obtener datos
function dacli_get_labs_data() {
    $labs = get_transient( 'dacli_labs_cache' );

    if ( false === $labs ) {
        $url = get_option( 'dacli_api_url' );
        if ( empty($url) ) return [];

        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) return [];

        $body = wp_remote_retrieve_body( $response );
        $labs = json_decode( $body, true );

        if ( ! is_array( $labs ) ) return [];

        $cache_min = get_option( 'dacli_cache_time', 60 );
        set_transient( 'dacli_labs_cache', $labs, $cache_min * MINUTE_IN_SECONDS );
    }
    return $labs;
}

// Renderizado de la lista
function dacli_render_labs_html() {
    $labs = dacli_get_labs_data();
    if ( empty( $labs ) ) return "<p>No se pudieron cargar los laboratorios.</p>";

    $output = '<style>
        .dacli-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 20px 0; }
        .dacli-card { border: 1px solid #eee; padding: 20px; border-radius: 10px; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.3s; }
        .dacli-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .dacli-logo { max-height: 60px; margin-bottom: 15px; display: block; }
        .dacli-name { font-size: 1.25rem; margin-bottom: 10px; color: #0d6efd; }
        .dacli-testimonio { font-style: italic; color: #555; font-size: 0.9rem; margin: 10px 0; }
    </style>';

    $output .= '<div class="dacli-grid">';
    foreach ( $labs as $lab ) {
        // Construir URL base del logo
        $logo_path = !empty($lab['logo']) ? 'https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/' . $lab['logo'] : '';
        
        $output .= '<div class="dacli-card">';
        if ($logo_path) {
            $output .= '<img src="'. esc_url( $logo_path ) .'" class="dacli-logo" alt="Logo '. esc_attr($lab['nombre_lab_corto']) .'" />';
        }
        $output .= '<h3 class="dacli-name">'. esc_html( $lab['nombre_lab'] ) .'</h3>';
        $output .= '<p><strong>Ubicación:</strong> '. esc_html( $lab['direccion_lab'] ) .'</p>';
        if (!empty($lab['testimonio'])) {
            $output .= '<p class="dacli-testimonio">"'. esc_html( $lab['testimonio'] ) .'"</p>';
        }
        if (!empty($lab['link_laboratorio'])) {
            $output .= '<a href="'. esc_url( $lab['link_laboratorio'] ) .'" target="_blank" class="button">Visitar Sitio</a>';
        }
        $output .= '</div>'; 
    }
    $output .= '</div>';
    
    return $output;
}

// Shortcode: [dacli_lista]
add_shortcode( 'dacli_lista', 'dacli_render_labs_html' );

// NOTA: He eliminado el add_filter('the_content') porque inyectaría la lista en todas 
// tus páginas/entradas automáticamente, lo cual suele ser molesto. 
// Ahora solo aparecerá donde pongas el shortcode [dacli_lista].

// Renderizado de la lista
function dacli_render_labs_html() {
    $labs = dacli_get_labs_data();
    if ( empty( $labs ) ) return "<p>No se pudieron cargar los laboratorios.</p>";

    $output = '<style>
        .dacli-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 20px 0; }
        .dacli-card { border: 1px solid #eee; padding: 20px; border-radius: 10px; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: 0.3s; }
        .dacli-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .dacli-logo { max-height: 60px; margin-bottom: 15px; display: block; }
        .dacli-name { font-size: 1.25rem; margin-bottom: 10px; color: #0d6efd; }
        .dacli-testimonio { font-style: italic; color: #555; font-size: 0.9rem; margin: 10px 0; }
    </style>';

    $output .= '<div class="dacli-grid">';
    foreach ( $labs as $lab ) {
        // Construir URL base del logo
        $logo_path = !empty($lab['logo']) ? 'https://dacli.net/2c55947a-ca26-4fbc-b4c8-01cb7deb1c7f/' . $lab['logo'] : '';
        
        $output .= '<div class="dacli-card">';
        if ($logo_path) {
            $output .= '<img src="'. esc_url( $logo_path ) .'" class="dacli-logo" alt="Logo '. esc_attr($lab['nombre_lab_corto']) .'" />';
        }
        $output .= '<h3 class="dacli-name">'. esc_html( $lab['nombre_lab'] ) .'</h3>';
        $output .= '<p><strong>Ubicación:</strong> '. esc_html( $lab['direccion_lab'] ) .'</p>';
        if (!empty($lab['testimonio'])) {
            $output .= '<p class="dacli-testimonio">"'. esc_html( $lab['testimonio'] ) .'"</p>';
        }
        if (!empty($lab['link_laboratorio'])) {
            $output .= '<a href="'. esc_url( $lab['link_laboratorio'] ) .'" target="_blank" class="button">Visitar Sitio</a>';
        }
        $output .= '</div>'; 
    }
    $output .= '</div>';
    
    return $output;
}

// Shortcode: [dacli_lista]
add_shortcode( 'dacli_lista', 'dacli_render_labs_html' );

// NOTA: He eliminado el add_filter('the_content') porque inyectaría la lista en todas 
// tus páginas/entradas automáticamente, lo cual suele ser molesto. 
// Ahora solo aparecerá donde pongas el shortcode [dacli_lista].
