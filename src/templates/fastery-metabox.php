<?php
/**
 * @uses $post_id
 */

$status = get_post_meta($post_id, 'fastery_status', 1);
$status_txt = ('send' == $status) ? 'Заказ передан в систему' : 'Заказ не передан в систему';
$payment = get_post_meta($post_id, 'ignet_fastery_payment_method', 1);
$payments = [
    'fullPay' => 'Оплата товара + доставки',
    'noPay' => 'Оплата не требуется',
    'deliveryPay' => 'Оплата только за доставку',
    'productPay' => 'Оплата только за товар',
];
$errors = get_post_meta($post_id, 'fastery_errors', 1);

echo '<div class="fastery-block-data">';

if (is_array($errors)) {
    foreach ($errors as $err_id => $err_msg) {

        echo '<p>' . $err_id . ' - ' . $err_msg . '</p>';
    }
}

echo '<p>' .
    '<strong>Статус выгрузки заказа:</strong><br>' .
    $status_txt .
    '</p>';

if ('send' == $status) {

    echo '<p>' .
        '<strong>Номер заказа:</strong> ' .
        get_post_meta($post_id, 'ignet_fastery_order_id', true) .
        '</p>';

    echo '<p>' .
        '<strong>Оплата:</strong><br>' .
        $payments[$payment] .
        '</p>';

    echo '<p>' .
        '<strong>Уникальный идентификатор доставки:</strong><br>' .
        get_post_meta($post_id, 'ignet_fastery_uid', true) .
        '</p>';

    echo '<input type="button" class="send_fastery_order" value="Обновить заказ" data-action="update" data-id="' . $post_id . '">';
} else {
    echo '<input type="button" class="send_fastery_order" value="Отправить заказ" data-action="send" data-id="' . $post_id . '">';
}

echo '</div>';
?>

<div class="ignet-loader" style="
    position: absolute;
    top: 0;
    left: 33%;
    display: none;
">
    <svg width="100px" height="100px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"
         preserveAspectRatio="xMidYMid" class="lds-ripple" style="background: none;">
        <circle cx="50" cy="50" r="30.769" fill="none" ng-attr-stroke="{{config.c1}}"
                ng-attr-stroke-width="{{config.width}}" stroke="#442317" stroke-width="2">
            <animate attributeName="r" calcMode="spline" values="0;40" keyTimes="0;1" dur="1" keySplines="0 0.2 0.8 1"
                     begin="-0.5s" repeatCount="indefinite"></animate>
            <animate attributeName="opacity" calcMode="spline" values="1;0" keyTimes="0;1" dur="1"
                     keySplines="0.2 0 0.8 1" begin="-0.5s" repeatCount="indefinite"></animate>
        </circle>
        <circle cx="50" cy="50" r="10.0086" fill="none" ng-attr-stroke="{{config.c2}}"
                ng-attr-stroke-width="{{config.width}}" stroke="#782f19" stroke-width="2">
            <animate attributeName="r" calcMode="spline" values="0;40" keyTimes="0;1" dur="1" keySplines="0 0.2 0.8 1"
                     begin="0s" repeatCount="indefinite"></animate>
            <animate attributeName="opacity" calcMode="spline" values="1;0" keyTimes="0;1" dur="1"
                     keySplines="0.2 0 0.8 1" begin="0s" repeatCount="indefinite"></animate>
        </circle>
    </svg>
</div>


