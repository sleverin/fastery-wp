<?php

namespace Ignet\Wc\Fastery\Plugin;

use Ignet\WP\Dev_Toolkit\Plugin;

/**
 * Class CartManager
 * @package Ignet\Wc\Fastery\Plugin
 */
class CartManager
{

    /**
     * @var Plugin
     */
    public $plugin;

    /**
     * EventsManager constructor.
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {

        $this->plugin = $plugin;
    }

    /**
     * Сохранение данных по доставке
     */
    public function save_shipping_calculator_field()
    {

        $pvz = isset($_REQUEST['ignet_fastery_pvz_field']) ? $_REQUEST['ignet_fastery_pvz_field'] : '';
        if ($pvz) {
            WC()->customer->set_billing_address_1($pvz);
        }
        $cost = isset($_REQUEST['ignet_fastery_cost']) ? $_REQUEST['ignet_fastery_cost'] : '';
        if ($cost) {

            WC()->session->set('ignet_fastery_cost', $cost);
        }

        $weight = isset($_REQUEST['ignet_cart_weight']) ? $_REQUEST['ignet_cart_weight'] : '';
        if ($weight) {

            WC()->session->set('ignet_cart_weight', $weight);
        }

        $uid = isset($_REQUEST['ignet_fastery_uid']) ? $_REQUEST['ignet_fastery_uid'] : '';
        if ($uid) {

            WC()->session->set('ignet_fastery_uid', $uid);
        }

        $delivery_term = isset($_REQUEST['ignet_fastery_delivery_term']) ? $_REQUEST['ignet_fastery_delivery_term'] : '';
        if ($delivery_term) {

            WC()->session->set('ignet_fastery_delivery_term', $delivery_term);
        }
    }

    /**
     * Меняем сумму доставки для метода
     *
     * @param $packages
     * @return mixed
     * @internal param $packeges
     *
     */
    public function change_shipping_packages($packages)
    {

        $cost = WC()->session->get('ignet_fastery_cost');
        $packages[0]['rates']['fastery']->cost = $cost;

        return $packages;
    }

    /**
     * Сохранение мета данных в заказ
     *
     * @param $order_id
     */
    public function checkout_update_order_meta($order_id)
    {

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        if ('fastery' == $chosen_shipping && !empty($_POST['ignet_fastery_uid'])) {

            update_post_meta($order_id, 'ignet_fastery_uid', sanitize_text_field($_POST['ignet_fastery_uid']));
        }
        if ('fastery_courier' == $chosen_shipping || 'fastery_mail' == $chosen_shipping) {

            $ignet_fastery_uid = WC()->session->get('ignet_' . $chosen_shipping . '_uid');
            update_post_meta($order_id, 'ignet_fastery_uid', sanitize_text_field($ignet_fastery_uid));
        }
    }

    /**
     * Валидация перед оформлением заказа
     */
    public function validate_proceed_to_checkout()
    {

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];
        if ('fastery' == $chosen_shipping) {

            $ignet_fastery_cost = WC()->session->get('ignet_fastery_cost');
            $ignet_fastery_uid = WC()->session->get('ignet_fastery_uid');
            if ($ignet_fastery_cost && $ignet_fastery_uid) {

                echo '<a href="' . esc_url(wc_get_checkout_url()) . '" class="checkout-button button alt wc-forward">' .
                    __('Proceed to checkout', 'woocommerce') .
                    '</a>';
            } else {

                echo '<a href="#" class="checkout-button button alt wc-forward checkout-button-disabled">' .
                    __('Proceed to checkout', 'woocommerce') .
                    '</a>';
                echo '<p class="chekout-error-notice">Перед оформлением заказа необходимо рассчитать стоимость доставки</p>';
            }

        } else {

            echo '<a href="' . esc_url(wc_get_checkout_url()) . '" class="checkout-button button alt wc-forward">' .
                __('Proceed to checkout', 'woocommerce') .
                '</a>';
        }
    }

    /**
     * Валидация перед созданием заказа
     */
    public function validate_checkout_fields()
    {

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];
        if ('fastery_courier' == $chosen_shipping || 'fastery_mail' == $chosen_shipping) {

            $ignet_fastery_uid = WC()->session->get('ignet_' . $chosen_shipping . '_uid');
        }

        if ('fastery' == $chosen_shipping) {

            $ignet_fastery_uid = $_POST['ignet_fastery_uid'];
        }

        if (!$ignet_fastery_uid || '' == $ignet_fastery_uid) {

            wc_add_notice('Вы не рассчитали стоимость доставки для данного метода - ' . $ignet_fastery_uid, 'error');
        }
    }

    /**
     * Сохраняем данные в сиссию
     */
    public function save_cost()
    {

        $cost = $_POST['cost'];
        WC()->session->set('ignet_fastery_cost', $cost);

        wp_send_json($cost);
    }
}