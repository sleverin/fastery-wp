<?php

namespace Ignet\Wc\Fastery\Plugin;

use Ignet\WP\Dev_Toolkit\Plugin;
use Ignet\WP\Dev_Toolkit\ViewHelper;

/**
 * Class CartDesigner
 * @package Ignet\Wc\Fastery\Plugin
 */
class CartDesigner
{

    /**
     * @var Plugin
     */
    public $plugin;

    /**
     * CartDesigner constructor.
     *
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {

        $this->plugin = $plugin;
    }

    /**
     * Добавление скриптов
     */
    public function load_scripts()
    {

        wp_enqueue_style('multirange-style', $this->plugin->get('url') . 'assets/libs/multirange/multirange.css');
        wp_enqueue_script('multirange-script', $this->plugin->get('url') . 'assets/libs/multirange/multirange.js');
        wp_enqueue_script('jquery-ui-script', $this->plugin->get('url') . 'assets/libs/jquery-ui/jquery-ui.js');
        wp_enqueue_script('jquery-ui-script', $this->plugin->get('url') . 'assets/libs/js.cookie.js');
    }

    /**
     * Смена шаблона
     *
     * @param $located
     * @param $template_name
     *
     * @return string
     */
    public function get_template($located, $template_name)
    {

        if ('cart/shipping-calculator.php' == $template_name) {
            $located = $this->plugin->get('dir') . '/src/templates/template-shipping-calculator.php';
        }

        return $located;
    }

    /**
     * Вывод карты в модальном окне
     *
     * @param $content
     *
     * @return string
     */
    public function display_map($content)
    {
        $content .= '<div id="ignet-map-inner" class="ignet-modal">' .
            '<div  class="ignet-modal-content">' .
            '<p class="ignet-close-modal-inner"><span class="ignet-close-modal">&times;</span></p>' .
            '<div id="ignet-yandex-map" style="width: 100%; height: 450px"></div>' .
            '<div class="ignet-range-block">' .
            '<div class="range-min">233 р.</div>' .
            '<div id="ignet-map-slider" class="range-input">' .
            // '<input type="range" value="300,750" multiple min="158" max="2000"/>' .
            '</div>' .
            '<div class="range-max">1231 р.</div>' .
            '</div>' .
            '</div>' .
            '</div>';
        return $content;
    }

    /**
     * Добавление скрытых полей в заказ
     *
     * @param $checkout
     */
    public function add_checkout_field($checkout)
    {
        woocommerce_form_field('ignet_fastery_uid', [
            'type' => 'text',
            'class' => ['my-field-class form-row-wide ignet_fastery_uid_hidden'],
            'label' => 'ignet_fastery_uid',
            'placeholder' => '',
        ], WC()->session->get('ignet_fastery_uid'));
    }

    /**
     * Вывод интерфейса выбора ПВЗ на станице Checkout
     */
    public function add_block_select_pvz()
    {
        $weight = 0;
        $cost = 0;
        $cart_data = WC()->cart->get_cart();
        $fastery_settings = get_option('woocommerce_fastery_settings');
        $default_weight = $fastery_settings['default_weight'];
        foreach ($cart_data as $item_id => $values) {

            $_product = $values['data'];
            $product_weight = $_product->get_weight();
            $product_weight = (is_numeric($product_weight)) ? $product_weight : $default_weight;
            $weight = $weight + $product_weight * $values['quantity'];
            $cost = $cost + $values['line_total'];
        }

        echo '<tr>' .
        '<th>Пункт выдачи</th>' .
        '<td>';
        // '<span>' . WC()->customer->get_billing_address_1() .'</span><br>' 

        echo '<span id="ignet-open-map" class="ignet-open-modal">Выбрать на карте</span>' .
            '<input id="calc_shipping_city" name="calc_shipping_city" type="hidden" value="' . WC()->customer->get_billing_city() . '">' .
            '<input id="ignet_cart_cost" name="ignet_cart_cost" type="hidden" value="' . $cost . '">' .
            '<input id="ignet_cart_weight" name="ignet_cart_weight" type="hidden" value="' . WC()->session->get('ignet_cart_weight') . '">' .
            '<input id="ignet_fastery_cost" name="ignet_fastery_cost" type="hidden" value="' . WC()->session->get('ignet_fastery_cost') . '">' .
            '<input id="ignet_fastery_delivery_term" name="ignet_fastery_delivery_term" type="hidden" value="' . WC()->session->get('ignet_fastery_delivery_term') . '">' .
            '<input id="ignet_fastery_checkout" name="ignet_fastery_checkout" type="hidden" value="1">' .
            '</td>' .
            '</tr>';
    }

    /**
     * Добавляем метабокс
     */
    public function add_meta_box()
    {
        add_meta_box('fastery_meta_box', 'Fastery Shipping', [$this, 'render_metabox'], 'shop_order', 'side', 'high');
    }

    /**
     * Разметка метабокса
     */
    public function render_metabox()
    {
        global $post;
        $post_id = $post->ID;
        echo ViewHelper::get_file_output($this->plugin->get('dir') . '/src/templates/fastery-metabox.php', [
            'post_id' => $post_id
        ]);
    }

    /**
     * Добавляем скрытое поле в форму
     *
     * @param $checkout
     */
    public function add_billing_data($checkout)
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];
        $ignet_fastery_uid = '';
        if ('fastery' == $chosen_shipping) {
            $ignet_fastery_uid = WC()->session->get('ignet_fastery_uid');
        }
        echo '<input type="hidden" class="input-text " name="ignet_fastery_uid" id="ignet_fastery_uid" value="' . $ignet_fastery_uid . '">';
    }
}
