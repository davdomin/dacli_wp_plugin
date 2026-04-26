<?php
/**
 * Plugin Name: DACLI WP Plugin
 * Description: A simple WordPress plugin with caching and admin configuration options.
 * Version: 1.0.0
 * Author: davdomin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dacli_WP_Plugin {
    public function __construct() {
        add_action('admin_menu', array( $this, 'dacli_add_admin_menu' ));
        add_action('admin_init', array( $this, 'dacli_settings_init' ));
    }

    public function dacli_add_admin_menu() {
        add_menu_page(
            'DACLI Settings',
            'DACLI',
            'manage_options',
            'dacli',
            array( $this, 'dacli_options_page' ),
            'dashicons-admin-generic'
        );
    }

    public function dacli_settings_init() {
        register_setting('pluginPage', 'dacli_settings');

        add_settings_section(
            'dacli_pluginPage_section',
            __('Your section description', 'wordpress'),
            null,
            'pluginPage'
        );

        add_settings_field(
            'dacli_text_field_0',
            __('Settings Field', 'wordpress'),
            array( $this, 'dacli_text_field_0_render' ),
            'pluginPage',
            'dacli_pluginPage_section'
        );
    }

    public function dacli_text_field_0_render() {
        $options = get_option('dacli_settings');
        ?>
        <input type='text' name='dacli_settings[dacli_text_field_0]' value='<?php echo $options['dacli_text_field_0']; ?>'>
        <?php
    }

    public function dacli_options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>DACLI WP Plugin</h2>
            <?php
            settings_fields('pluginPage');
            do_settings_sections('pluginPage');
            submit_button();
            ?>
        </form>
        <?php
    }
}

new Dacli_WP_Plugin();

// Caching functionality
function dacli_cache_data() {
    $transient_name = 'dacli_data';
    $cached_data = get_transient($transient_name);

    if ( false === $cached_data ) {
        // Fallback to fetching fresh data if cache does not exist
        $cached_data = array(); // Fetch your data here

        // Cache for 12 hours (43200 seconds)
        set_transient($transient_name, $cached_data, 43200);
    }
    return $cached_data;
}