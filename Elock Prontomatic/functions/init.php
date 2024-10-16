<?php

/**
 * Initialize the plugin config for woocommerce section
 */

if (!defined('WPINC')) {
    die();
    // If this file is called directly, abort.
}

if (
    in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    )
) {
    function gux_shipping_method()
    {
        if (!class_exists('Gux_Shipping_Method')) {
            class Gux_Shipping_Method extends WC_Shipping_Method
            {
                public function __construct()
                {

                    $this->id = 'prontomatic_elock';
                    $this->method_title = __('Elock Prontomatic', 'gux');
                    $this->method_description = __('Elock Prontomatic', 'gux');
                    $this->availability = 'including';
                    $this->countries = [
                        'CL', // Sólo para Chile
                    ];
                    $this->init();
                    $this->enabled = isset($this->settings['enabled'])
                        ? $this->settings['enabled']
                        : 'yes';
                    $this->title = isset($this->settings['title'])
                        ? $this->settings['title']
                        : __('Elock Prontomatic', 'gux');
                    $this->api_key = isset($this->settings['api_key'])
                        ? $this->settings['api_key']
                        : __('', 'gux');
                    $this->token_key = isset($this->settings['token_key'])
                        ? $this->settings['token_key']
                        : __('', 'gux');
                    $this->uri_elock = isset($this->settings['uri_elock'])
                        ? $this->settings['uri_elock']
                        : __('', 'gux');
                    $this->enable_default = isset(
                        $this->settings['enable_default']
                    )
                        ? $this->settings['enable_default']
                        : 'yes';
                }

                function init()
                {
                    $this->init_form_fields();
                    $this->init_settings();

                    add_action(
                        'woocommerce_update_options_shipping_' . $this->id,
                        [$this, 'process_admin_options']
                    );
                }

                function init_form_fields()
                {
                    $this->form_fields = [
                        'enabled' => [
                            'title' => __('Enable', 'gux'), // Titulo del campo
                            'type' => 'checkbox', // Tipo de campo
                            'label' => __('Enable', 'gux'), // Etiqueta del campo
                            'default' => 'yes', // Valor por defecto
                        ],
                        'title' => [
                            'title' => __('Title', 'gux'), // Titulo del campo
                            'type' => 'text', // Tipo de campo
                            'description' => __(
                                'Title to display on site',
                                'gux'
                            ), // Descripción del campo
                            'default' => __('Elock Prontomatic', 'gux'), // Valor por defecto
                        ],
                        'api_key' => [
                            'title' => __('API KEY', 'gux'), // Titulo del campo
                            'type' => 'text', // Tipo de campo
                            'description' => __('API key', 'gux'), // Descripción del campo
                            'default' => __('', 'gux'), // Valor por defecto
                        ],
                        'token_key' => [
                            'title' => __('Token KEY', 'gux'), // Titulo del campo
                            'type' => 'text', // Tipo de campo
                            'description' => __('Token key', 'gux'), // Descripción del campo
                            'default' => __('', 'gux'), // Valor por defecto
                        ],
                        'uri_elock' => [
                            'title' => __('URI Elock', 'gux'), // Titulo del campo
                            'type' => 'text', // Tipo de campo
                            'description' => __('Url Api Elock', 'gux'), // Descripción del campo
                            'default' => __('', 'gux'), // Valor por defecto
                        ],


                    ];
                }

                public function calculate_shipping($package = [])
                {
                    $api_key = $this->api_key;
                    $token_key = $this->token_key;
                    [$token_auth, $terminal_id] = $this->get_token_elock($api_key, $token_key);
                    $boxes = $this->get_boxes_elock($token_auth, $terminal_id);

                    $products = []; // Array de productos
                    $total_volume = 0; // Volumen total de los productos
                    $finish_products = []; // Array de productos final
                    $lockers_elock = [];
                    $boxSelected = [];
                    $height = 0;
                    $width = 0;
                    $length = 0;

                    $con = 0;

                    // Obtengo los datos del producto y calculo el volumen
                    foreach ($package['contents'] as $item_id => $values) {
                        $_product = $values['data'];
                        $height = $_product->get_height();
                        $width = $_product->get_width();
                        $length = $_product->get_length();
                        $id = $_product->get_id();
                        $product_volumen = $height * $width * $length;
                        $total_volume += $product_volumen;
                        $newProduct = [
                            'height' => $height,
                            'width' => $width,
                            'length' => $length,
                            'price' => $_product->get_price(),
                            'name' => $_product->get_name(),
                            'quantity' => $values['quantity'],
                            'id' => $id,
                            'volumen' => $product_volumen,
                        ];
                        array_push($products, $newProduct);
                    }

                    // Obtengo los datos Locker y filtro solo los necesarios
                    foreach ($boxes as $item_id => $values) {

                        $box = $values;
                        $terminalId =  $_SESSION['terminal_id'];
                        $companyId =  $_SESSION['company_id'];
                        $Boxes = $box;


                        $new_box = [
                            'terminal_id' => $terminalId,
                            'company_id' => $companyId,
                            'Boxes' => $Boxes,
                        ];

                        array_push($lockers_elock, $new_box);
                    }


                    $boxSelect = $lockers_elock;
                    $totalVolumen = 0;

                    // Filtro los lockers y calculo el volumen
                    foreach ($boxSelect as $item_id => $values) {
                        $box = $values['Boxes'];
                        $terminalId =  $_SESSION['terminal_id'];
                        $companyId =  $_SESSION['company_id'];
                        $box_name = $box->box_type->name;
                        $box_length = $box->box_type->depht / 10;
                        $box_height = $box->box_type->height / 10;
                        $box_width = $box->box_type->width / 10;
                        $box_volumen = $box_length * $box_height * $box_width;
                        if ($box->is_enabled) {
                            $totalVolumen += $box_volumen;
                            $new_box = [
                                'box_id' => $box->id,
                                'box_name' => $box_name,
                                'box_length' => $box_length,
                                'box_height' => $box_height,
                                'box_width' => $box_width,
                                'box_volumen' => $box_volumen,
                                'terminal_id' => $terminalId,
                                'company_id' => $companyId,
                                'start' => ($date = date('Y-m-d')),
                            ];

                            array_push($boxSelected, $new_box);
                        }
                    }

                    $totalProductsVolumen = 0;


                    $boxes = filterBox($boxSelected, $products);

                    if (is_array($boxes) && count($boxes) > 0) {
                        $products[0]['boxes'] = $boxes;
                        $finish_products = $products;
                        $newTitle = $this->title;
                        $result = array();
                        $boxesSelected = array();
                        $boxesSelected = $finish_products[0]['boxes'];
                        $boxesSelected = array_unique($boxesSelected, SORT_REGULAR);

                        // Crear un array para almacenar los nombres de las cajas
                        $box_names = array();

                        // Contar las cajas seleccionadas
                        $box_counts = array_count_values(array_column($boxes, 'box_name'));

                        // Iterar sobre las cajas seleccionadas y agregar sus nombres y cantidades al array
                        foreach ($box_counts as $box_name => $count) {
                            if ($count > 1) {
                                $box_names[] = $count . ': Cajas ' . $box_name;
                            } else {
                                $box_names[] = $count . ': Caja ' . $box_name;
                            }
                        }

                        // Unir los nombres de las cajas en una sola cadena
                        $box_name_str = implode(' - ', $box_names);

                        // Agregar los nombres de las cajas al título
                        $newTitle .= ' - ' . $box_name_str;

                        $_SESSION['products_elock'] = $finish_products[0];

                        #Use the woocommece function info to show messages
                        $terminal_address = $_SESSION['terminal_address'];
                        //TODO habilitar wc_add_notice('El locker Elok esta úbicado en:  ' . $terminal_address, 'notice');
                        # add terminal address to the title
                        $newTitle .= ' - ' . $terminal_address;

                        $rate = [
                            'id' => $this->id,
                            'label' => $newTitle,
                            'cost' => 0,
                            'calc_tax' => 'per_item',
                        ];

                        if ($totalVolumen >= $totalProductsVolumen) {
                            $this->add_rate($rate);
                        }
                    }
                }

                public function get_token_elock($api_key, $token_key)
                {

                    //flat_rate:4 es el de 3990
                    // creamos la ruta
                    $service_url = $this->uri_elock . '/auth/external/access_token';

                    // Creamos un array con los parámetros que queremos enviar en el cuerpo de la solicitud
                    $body_params = array(
                        'api_key' => $api_key,
                        'secret_key' => $token_key
                    );

                    // Convertimos el array de parámetros en una cadena de consulta
                    $body = http_build_query($body_params);

                    // Realizamos la solicitud POST enviando los parámetros en el cuerpo de la solicitud
                    $result = wp_remote_post($service_url, array(
                        'method' => 'POST',
                        'body' => $body
                    ));

                    // Procesamos el resultado de la solicitud
                    $decoded = json_decode($result['body']);
                    $token_auth = $decoded->security_token;
                    $_SESSION['token_elock'] = $token_auth;
                    $company_id = $this->get_companies($token_auth);



                    $terminal_id = $this->get_terminals($token_auth, $company_id);
                    // return $token_auth and terminal_id
                    return array($token_auth, $terminal_id);
                }

                public function get_companies($token_auth)
                {
                    $service_url = $this->uri_elock . '/companies/external/list/list-by-user';
                    $result = wp_remote_get($service_url, [
                        'timeout' => 10,
                        'headers' => ['Authorization' => 'Bearer ' . $token_auth],
                    ]);
                    $decoded = json_decode($result['body']);

                    // COMPANY_TYPE
                    $company_type = $decoded[0]->company_type->name;
                    $_SESSION['company_type'] = $company_type;
                    // COMPANY_ID
                    $company_id = $decoded[0]->id;

                    $_SESSION['company_id'] = $company_id;
                    return $company_id;
                }

                public function get_terminals($token_auth, $company_id)
                {
                    $service_url = $this->uri_elock . '/companies/external/terminals/list/' . $company_id;
                    $result = wp_remote_get($service_url, [
                        'timeout' => 10,
                        'headers' => ['Authorization' => 'Bearer ' . $token_auth],
                    ]);
                    $decoded = json_decode($result['body']);

                    $terminal_address = $decoded[0]->address;
                    $_SESSION['terminal_address'] = $terminal_address;
                    $terminal_id = $decoded[0]->id;
                    $_SESSION['terminal_id'] = $terminal_id;
                    // TERMINAL NAME 
                    $terminal_name = $decoded[0]->name;
                    $_SESSION['terminal_name'] = $terminal_name;

                    return $terminal_id;
                }

                public function get_boxes_elock($token_auth, $terminal_id)
                {
                    $date = date('Y-m-d');
                    // TODO: buscar que variable es la que se envia day o start_date?
                    $service_url = $this->uri_elock . '/boxes/external/availables/sameday/' . $terminal_id . '?start=' . $date;
                    $result = wp_remote_get($service_url, [
                        'timeout' => 10,
                        'headers' => ['Authorization' => 'Bearer ' . $token_auth],
                    ]);
                    $decoded = json_decode($result['body']);
                    return $decoded;
                }
            }
        }
    }

    add_action('woocommerce_shipping_init', 'gux_shipping_method');

    function filterBox($boxes, $products)
    {
        /*echo '<pre>';
        print_r($products);
        echo '</pre>';  */
        $responsenoBoxes = 'No hay cajas disponibles para este pedido';
        // Ordenar las cajas de menor a mayor según el volumen calculado
        usort($boxes, function ($a, $b) {
            return $a['box_volumen'] <=> $b['box_volumen'];
        });

        // Calcular el volumen total del pedido
        $total_volume = array_reduce($products, function ($carry, $item) {
            return $carry + ($item['volumen'] * $item['quantity']);
        }, 0);

        // Primera etapa intentar colocar el volumen total del pedido en una caja

        $selected_boxes = array();
        $remaining_boxes = $boxes;

        foreach ($remaining_boxes as $key => $box) {
            if ($total_volume <= $box['box_volumen']) {
                $selected_boxes[] = $box;
                unset($remaining_boxes[$key]);
                return $selected_boxes;
            }
        }

        // Si Cumple la primera etapa, se retorna las cajas seleccionadas

        // Segunda Etapa: Separar el volumen del carrito en grupos de productos iguales

        // Crear grupos de productos
        $product_groups = array();
        foreach ($products as $key => $product) {
            $product_groups[$product['id']][] = $product;
        }

        // Calcular el volumen de cada grupo de productos
        foreach ($product_groups as $group_key => $group) {
            foreach ($group as $product_key => $product) {
                $product_groups[$group_key][$product_key]['volumen'] = $product['volumen'] * $product['quantity'];
            }
        }

        // Evaluar el volumen de cada grupo con respecto a las cajas
        $rejected = false;
        $selected_boxes = array();

        foreach ($product_groups as $group) {
            $group_fits = false;

            // Calcular el volumen total del grupo
            $group_volume = 0;
            foreach ($group as $product) {
                $group_volume += $product['volumen'];
            }

            // Crear una copia de las cajas restantes para no afectar las cajas originales
            $temp_remaining_boxes = $remaining_boxes;
            $temp_selected_boxes = array();

            foreach ($temp_remaining_boxes as $key => $box) {
                if ($group_volume <= $box['box_volumen']) {
                    $temp_selected_boxes[] = $box;
                    unset($temp_remaining_boxes[$key]);
                    $group_fits = true;
                    break;
                }
            }

            // Si algún grupo de productos no cabe en ninguna caja, rechazar todo el pedido y pasar a la Etapa 3
            if (!$group_fits) {
                $rejected = true;
                break;
            } else {
                // Si el grupo cabe en una caja, actualizar las cajas restantes y agregar la caja seleccionada
                $remaining_boxes = $temp_remaining_boxes;
                $selected_boxes = array_merge($selected_boxes, $temp_selected_boxes);
            }
        }

        if (!$rejected) {
            return $selected_boxes;
        } else {

            // Rechazar todo el proceso y pasar a la Etapa 3
            // Separar los productos en subgrupos de cantidades iguales
            $product_subgroups = array();
            foreach ($products as $product) {
                while ($product['quantity'] > 0) {
                    $subgroup_quantity = ceil($product['quantity'] / 2);
                    $product_subgroups[] = array(
                        'id' => $product['id'],
                        'volumen' => $product['volumen'],
                        'quantity' => $subgroup_quantity,
                    );
                    $product['quantity'] -= $subgroup_quantity;
                }
            }

            $selected_boxes = array();
            $remaining_boxes = $boxes;

            // Iterar sobre cada subgrupo de productos
            foreach ($product_subgroups as $subgroup) {
                $subgroup_volume = $subgroup['volumen'] * $subgroup['quantity'];
                $subgroup_fits = false;


                // Crear una copia de las cajas restantes para no afectar las cajas originales
                $temp_remaining_boxes = $remaining_boxes;
                $temp_selected_boxes = array();


                // Evaluar si el subgrupo cabe en alguna de las cajas restantes
                foreach ($temp_remaining_boxes as $key => $box) {
                    if ($subgroup_volume <= $box['box_volumen']) {
                        $temp_selected_boxes[] = $box;
                        unset($temp_remaining_boxes[$key]);
                        $subgroup_fits = true;
                        break;
                    }
                }



                // Si el subgrupo no cabe en ninguna caja o se terminan las cajas disponibles, rechazar todo el pedido
                if (!$subgroup_fits || empty($temp_remaining_boxes)) {
                    return $responsenoBoxes;
                } else {
                    // Si el subgrupo cabe en una caja, actualizar las cajas restantes y agregar la caja seleccionada
                    $remaining_boxes = $temp_remaining_boxes;
                    $selected_boxes = array_merge($selected_boxes, $temp_selected_boxes);
                }
            }

            return $selected_boxes;
        }

        return $responsenoBoxes;
    }

    function add_starken_shipping_method($methods)
    {
        $methods['prontomatic_elock'] = 'Gux_Shipping_Method';
        return $methods;
    }

    function gux_comunas_de_chile($states)
    {
        $new_comunas = get_field('comunas_para_despacho', 'options');
        $comunas = [];
        foreach ($new_comunas as $key => $val) {
            $comunas += [$val['comuna'] => $val['comuna']];
        }

        $states['CL'] = $comunas;
        return $states;
    }

    function log_me($message)
    {
        if (WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }

    function getClosest($val1, $val2, $target)
    {
        if ($target - $val1['box_volumen'] >= $val2['box_volumen'] - $target)
            return $val2;
        else
            return $val1;
    }

    add_filter('woocommerce_shipping_methods', 'add_starken_shipping_method');
    add_filter('woocommerce_states', 'gux_comunas_de_chile');

   /* function custom_minimum_order_amount()
    {
        $minimum = 15000;  // Cambia 15000 al importe mínimo deseado
        $target_shipping_method = 'prontomatic_elock'; // Cambia esto al ID del método de envío específico

        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping_method = $chosen_shipping_methods[0];

        if ($chosen_shipping_method != $target_shipping_method && WC()->cart->total < $minimum) {
            if (is_cart()) {
                wc_print_notice(
                    sprintf(
                        'Debes realizar un pedido mínimo de %s o selecciona Elock si esta disponible en tu zona para finalizar tu compra.',
                        wc_price($minimum)
                    ),
                    'error'
                );
            } else {
                wc_add_notice(
                    sprintf(
                        'Debes realizar un pedido mínimo de %s o selecciona Elock si esta disponible en tu zona para finalizar tu compra.',
                        wc_price($minimum)
                    ),
                    'error'
                );
            }
        }
    }

    add_action('woocommerce_checkout_process', 'custom_minimum_order_amount');
    add_action('woocommerce_before_cart', 'custom_minimum_order_amount');


    function custom_checkout_field_update_order_meta($order_id)
    {
        if (!empty($_POST['gux_locker'])) {
            update_post_meta($order_id, 'gux_locker', sanitize_text_field($_POST['gux_locker']));
        }
    }
}



// ocultamos el metodo de envio cuando el subtotal es igual o mayor a 15000
add_filter( 'woocommerce_package_rates', 'ocultar_metodo_envio', 10, 2 );
function ocultar_metodo_envio( $rates, $package ) {
    $subtotal = WC()->cart->subtotal; // Obtenemos el subtotal del carrito

    if ( $subtotal >= 15000 ) {
        // Iteramos a través de los métodos de envío
        foreach ( $rates as $rate_key => $rate ) {
            
            if ( 'flat_rate:4' === $rate->id ) {
                unset( $rates[ $rate_key ] ); 
            }
        }
    }

    return $rates;

}*/
}



