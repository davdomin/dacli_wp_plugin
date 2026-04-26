<?php
/**
 * Plugin Name: DACLI WP Plugin
 * Description: A professional plugin for managing laboratories.
 * Version: 1.0.0
 * Author: Your Name
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Create admin menu
add_action( 'admin_menu', 'dacli_add_admin_menu' );
function dacli_add_admin_menu() {
    add_menu_page( 'DACLI Settings', 'Ajustes', 'manage_options', 'dacli_settings', 'dacli_settings_page' );
}

// Admin settings page
function dacli_settings_page() {
    // Handle form submission
    if ( isset( $_POST['dacli_submit'] ) ) {
        check_admin_referer( 'dacli_update_settings' );
        $api_url = sanitize_text_field( $_POST['api_url'] );
        $cache_time = absint( $_POST['cache_time'] );
        update_option( 'dacli_api_url', $api_url );
        update_option( 'dacli_cache_time', $cache_time );
        add_settings_error( 'dacli_messages', 'dacli_message', 'Settings saved.', 'updated' );
    }

    // Fetch existing settings
    $api_url = get_option( 'dacli_api_url', '' );
    $cache_time = get_option( 'dacli_cache_time', 60 );
    
    // Render the form
    echo '<div class="wrap">';
    echo '<h1>DACLI Settings</h1>';
    settings_errors( 'dacli_messages' );
    echo '<form method="post">';
    wp_nonce_field( 'dacli_update_settings' );
    echo '<label for="api_url">URL de la API:</label>';
    echo '<input type="text" id="api_url" name="api_url" value="'. esc_attr( $api_url ) .'" required />';
    echo '<label for="cache_time">Tiempo de Caché:</label>';
    echo '<input type="number" id="cache_time" name="cache_time" value="'. esc_attr( $cache_time ) .'" min="1" required />';
    echo '<button type="submit" name="dacli_submit">Save</button>';
    echo '</form>';
    echo '<form method="post" action="">';
    echo '<button type="submit" name="dacli_clear_cache" class="button">Botón Limpiar Caché</button>';
    echo '</form>';
    echo '</div>';
}

// Handle cache clearing
if ( isset( $_POST['dacli_clear_cache'] ) ) {
    check_admin_referer( 'dacli_clear_cache' );
    delete_transient( 'dacli_labs' );
    add_settings_error( 'dacli_messages', 'dacli_message', 'Cache cleared.', 'updated' );
}

// Caching system with transients
gadd_filter( 'the_content', 'dacli_show_labs' );
function dacli_show_labs( $content ) {
    $labs = get_transient( 'dacli_labs' );
    if ( false === $labs ) {
        // Fetch labs from the API
        $labs = wp_remote_get( get_option( 'dacli_api_url' ) );
        set_transient( 'dacli_labs', $labs, get_option( 'dacli_cache_time' ) * MINUTE_IN_SECONDS );
    }

    // Display labs in a responsive grid
    $output = '<div class="responsive-grid">';
    foreach ( $labs as $lab ) {
        $output .= '<div class="lab">';
        $output .= '<img src="'. esc_url( $lab['logo'] ) .'" alt="Logo" />';
        $output .= '<h2>'. esc_html( $lab['name'] ) .'</h2>';
        $output .= '<p>'. esc_html( $lab['address'] ) .'</p>';
        $output .= '<p>'. esc_html( $lab['testimony'] ) .'</p>';
        $output .= '<a href="'. esc_url( $lab['link'] ) .'"></a>';
        $output .= '</div>'; 
    }
    $output .= '</div>';
    return $content . $output;
}

// Register shortcode
add_shortcode( 'dacli_lista', 'dacli_lista_shortcode' );
function dacli_lista_shortcode() {
    return dacli_show_labs( '' );
}
?>