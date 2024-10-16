<?php

if (!defined('WPINC')) {
    die;
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    if (function_exists('acf_add_options_page')) {

        acf_add_options_page(array(
            'page_title'     => 'Aqui puedes personalizar las reglas para tus envíos',
            'menu_title'    => 'Opciones Adicionales',
            'menu_slug'     => 'rules-delivery-settings',
            'redirect'        => false
        ));
        acf_add_options_sub_page(array(
            'page_title'     => 'Opciones Popup Promo',
            'menu_title'    => 'Popup',
            'parent_slug'    => 'rules-delivery-settings',
        ));
    }
}
