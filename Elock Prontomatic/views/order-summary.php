<?php
if (!defined('ABSPATH')) {
    exit;
}


?>
<br />
<?php

if (!empty($reserva_elock)) {

            $reserva_elock = json_decode($reserva_elock[0]);
        ?>
    
    <h2>Detalles de la Reserva Elock</h2>
    <table class="shop_table order_details">
        <?php
            $TerminalID         = $reserva_elock->TerminalID;
            $StartDate          = $reserva_elock->StartDate;
            $ExpirationDate     = $reserva_elock->ExpirationDate;
            $code               = $reserva_elock->code;
            $QR                 = $reserva_elock->QR;  
        ?>

            <tfoot>

                <tr>
                    <th scope="row">Terminal:</th>
                    <td><span class="RT"><?php echo $TerminalID; ?></span></td>
                </tr>
                <tr>
                    <th scope="row">Fecha de inicio:</th>
                    <td><span class="RT"><?php echo $StartDate; ?></span></td>
                </tr>
                <tr>
                    <th scope="row">Fecha de expiración:</th>
                    <td><span class="RT"><?php echo $ExpirationDate; ?></span></td>
                </tr>
                <tr>
                    <th scope="row">Código:</th>
                    <td><span class="RT"><?php echo $code; ?></span></td>
                </tr>
                <tr>
                    <th scope="row">QR:</th>
                    <td><span class="RT"><?php echo '<iframe src=' . $QR . ' height="300" width="400" scrolling="no"></iframe>' ?></span></td>
                </tr>
                <tr>
                </tr>
            </tfoot>
        <?php
        

        ?>
    </table>
<?php
}
?>