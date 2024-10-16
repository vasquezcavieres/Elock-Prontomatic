<?php

/**
 * Plugin Name: Elock Connect Prontomatic
 * Plugin URI: https://gux.tech/wordpress/plugins
 * Description: Asignacion de Lockers según volumen de productos
 * Version: 0.0.3
 * Author: GUX Technology
 * Contributors: GUX Technology
 * Author URI: https://gux.tech/wordpress/plugins
 * License: MIT License
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: gux
 *
 * WC requires at least: 5.8
 * WC tested up to: 6.4
 *
 */

/**Init Scripts and Styles */

function gux_load_scripts_styles()
{


?>
    <!--- External links-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
    <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<?php
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_script('gux_load_scripts', $plugin_url . 'js/scripts.js');
    wp_enqueue_style('gux_load_styles', $plugin_url . 'styles/styles.css');
    wp_localize_script('gux_load_scripts', 'gux_vars', [
        'ajaxurl' => admin_url('admin-ajax.php'),
    ]);
}

add_action('wp_enqueue_style', 'gux_load_scripts_styles', 99);
add_action('wp_enqueue_scripts', 'gux_load_scripts_styles');

function start_my_session()
{
    echo session_id();
    if (!session_id()) {
        session_start();
    }
}

add_action('init', 'start_my_session');

require_once plugin_dir_path(__FILE__) . 'functions/init.php';
require_once plugin_dir_path(__FILE__) . 'functions/forms_checkout.php';
require_once plugin_dir_path(__FILE__) . 'layout/admin_page.php';
require_once plugin_dir_path(__FILE__) . 'functions/order_info.php';
