<?php

/**
 * 
 * Author: Miguel Sanz Senior Developer GUX
 *Author URI: https:gux.tech
 *Text Domain: gux

 *<!-- gux -->
 */

/**
 * Agrega nuevas columnas en el listado de pedidos del administrador
 */

function gux_add_new_order_admin_list_column($columns)
{
    $new_columns = (is_array($columns)) ? $columns : array();
    unset($new_columns['order_actions']);
    $new_columns['_delibery_method'] = 'Metodo de Entrega';
    //stop editing
    $new_columns['order_actions'] = $columns['order_actions'];

    return $new_columns;
}
add_filter('manage_edit-shop_order_columns', 'gux_add_new_order_admin_list_column');

/**
 * Muestra la inoformacion de la columna
 */
function gux_add_new_order_admin_list_column_content($column)
{

    global $post;
    $order = wc_get_order($post->ID);
    if ('_delibery_method' === $column) {
        echo $order->get_shipping_method();
    }
}
add_action('manage_shop_order_posts_custom_column', 'gux_add_new_order_admin_list_column_content');


function show_custom_fields_in_admin($order)
{

    $reserva_elock      = get_post_meta($order->get_id(), 'reserva_elock', false);
    $reserva_elock      = json_decode($reserva_elock[0]);
    $TerminalID         = $reserva_elock->TerminalID;
    $StartDate          = $reserva_elock->StartDate;
    $ExpirationDate     = $reserva_elock->ExpirationDate;
    $code               = $reserva_elock->code;
    $QR                 = $reserva_elock->QR; 
    echo '<pre>';
    var_dump($QR);
    echo '</pre>';

    $TerminalNo = get_post_meta($order->get_id(), 'TerminalNo', true);
    $BoxNo = get_post_meta($order->get_id(), 'BoxNo', true);
    $StartDate = get_post_meta($order->get_id(), 'StartDate', true);
    $ExpirationDate = get_post_meta($order->get_id(), 'ExpirationDate', true);
    $ID = get_post_meta($order->get_id(), 'ID', true);
    $QR = get_post_meta($order->get_id(), 'QR', true);
    if (!empty($TerminalNo)) {
        echo '<p><strong>' . __('Terminal Seleccionada') . ':</strong> ' . $TerminalNo . '</p>';
        echo '<p><strong>' . __('Locker Reservado') . ':</strong> ' . $BoxNo . '</p>';
        echo '<p><strong>' . __('Fecha de la Reserva') . ':</strong> ' . $StartDate . '</p>';
        echo '<p><strong>' . __('Fecha de Expiración de la reserva') . ':</strong> ' . $ExpirationDate . '</p>';
        echo '<p><strong>' . __('ID de la reserva') . ':</strong> ' . $ID . '</p>';
        echo '<iframe src=' . $QR . ' height="300" width="400" scrolling="no"></iframe>';
    }
}

add_filter('woocommerce_admin_order_data_after_billing_address', 'show_custom_fields_in_admin', 10, 1); 


function so_payment_complete($order_id)
{
    $order = wc_get_order($order_id);

    if (isset($_SESSION['products_elock'])) {

        $products_elock = $_SESSION['products_elock'];
    


        $boxesID = array();
        $box = $products_elock['boxes'][0];
    
        foreach ($products_elock['boxes'] as $product_elock) {
            $boxesID[] = $product_elock['box_id'];
        }

        $TerminalID = $box['terminal_id'];
        $CompanyID = $box['company_id'];
        $start = $box['start'];
        $email = $order->get_billing_email();
        $fullname = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $phone = $order->get_billing_phone();
        $rut =  "11111111-1";
        $token =  $_SESSION['token_elock'];

        $data = array(
            'name' => $fullname,
            //'phone' => formatPhoneNumber('(+56)' . $phone) ,
            'phone'=> "(+56)99999-9999",
            'email' => $email,
            'rut' => $rut,
            'terminal_id' => $TerminalID,
            'company_id' => $CompanyID,
            'start' => $start,
            'boxes' => $boxesID,

        );

        $reserva = get_boxes_elock($data, $token);

        
        
        $StartDate = $reserva->reservation->start_at;
        $ExpirationDate = $reserva->reservation->end_at;
        $code = $reserva->reservation->code;
        $QR = $reserva->reservation->url_qrcode;

        $dataResponse = array(
            'TerminalID' => $TerminalID,
            'CompanyID' => $CompanyID,
            'StartDate' => $StartDate,
            'ExpirationDate' => $ExpirationDate,
            'code' => $code,
            'QR' => $QR,
        );

        update_post_meta($order->get_id(), 'reserva_elock', json_encode($dataResponse));

        $order->add_order_note(
            '<h2>' . __('Información de la Reserva') . ':</h2><br>' .
                '<strong>' . __('Terminal Seleccionada') . ':</strong> ' . $TerminalID . '<br>' .
                '<strong>' . __('Fecha de la Reserva') . ':</strong> ' . $StartDate . '<br>' .
                '<strong>' . __('Fecha de Expiración de la reserva') . ':</strong> ' . $ExpirationDate . '<br>' .
                '<strong>' . __('ID de la reserva') . ':</strong> ' . $code . '<br>'
        );
    }
}
add_action('woocommerce_payment_complete', 'so_payment_complete');



function get_boxes_elock($data, $token)
{
    #$service_url = 'https://api-staging.elock.cl/api/v2/orders/external/sameday';
    $service_url = 'https://api-prod.elock.cl/api/v2/orders/external/sameday';
    $result = wp_remote_post($service_url, [
        'method' => 'POST',
        'headers' => ['Authorization' => 'Bearer ' . $token],
        'body' => http_build_query($data),
    ]);
    $decoded = json_decode($result['body']);
    return $decoded;
}


function gux_add_content_thankyou($order_id)
{
    $order = wc_get_order($order_id);

    $reserva_elock =  get_post_meta($order->get_id(), 'reserva_elock', false);
    // unset($_SESSION['products_elock']);

    require __DIR__ . '/../views/order-summary.php';
}
add_action('woocommerce_payment_complete', 'gux_add_content_thankyou');
add_action('woocommerce_thankyou', 'gux_add_content_thankyou');



function formatPhoneNumber($phoneNumber) {
    // Extraer el código de país y el número de teléfono
    $countryCode = substr($phoneNumber, 0, 4);
    $phoneNumber = substr($phoneNumber, 4);
  
    // Insertar el guión después del quinto dígito del número de teléfono
    $formattedPhoneNumber = $countryCode . substr($phoneNumber, 0, 5) . '-' . substr($phoneNumber, 5);
  
    return $formattedPhoneNumber;
  }
  