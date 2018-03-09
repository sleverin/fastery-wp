<?php
/**
 * Shipping Calculator
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/shipping-calculator.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.woocommerce.com/document/template-structure/
 * @author        WooThemes
 * @package    WooCommerce/Templates
 * @version     2.0.8
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if ('no' === get_option('woocommerce_enable_shipping_calc') || !WC()->cart->needs_shipping()) {
    return;
}

?>

<?php do_action('woocommerce_before_shipping_calculator'); ?>

<form class="woocommerce-shipping-calculator" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

    <p><a href="#" class="shipping-calculator-button"><?php _e('Calculate shipping', 'woocommerce'); ?></a></p>

    <section class="shipping-calculator-form" style="display:none;">

        <p class="form-row form-row-wide" id="calc_shipping_country_field">
            <select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state"
                    rel="calc_shipping_state">
                <option value=""><?php _e('Select a country&hellip;', 'woocommerce'); ?></option>
                <?php
                foreach (WC()->countries->get_shipping_countries() as $key => $value) {
                    echo '<option value="' . esc_attr($key) . '"' . selected(WC()->customer->get_shipping_country(), esc_attr($key), false) . '>' . esc_html($value) . '</option>';
                }
                ?>
            </select>
        </p>

        <p class="form-row form-row-wide" id="calc_shipping_state_field">
            <?php
            $current_cc = WC()->customer->get_shipping_country();
            $current_r = WC()->customer->get_shipping_state();
            $states = WC()->countries->get_states($current_cc);

            // Hidden Input
            if (is_array($states) && empty($states)) {

                ?><input type="hidden" name="calc_shipping_state" id="calc_shipping_state"
                         placeholder="<?php esc_attr_e('State / County', 'woocommerce'); ?>" /><?php

                // Dropdown Input
            } elseif (is_array($states)) {

                ?><span>
                <select name="calc_shipping_state" id="calc_shipping_state"
                        placeholder="<?php esc_attr_e('State / County', 'woocommerce'); ?>">
							<option value=""><?php esc_html_e('Select a state&hellip;', 'woocommerce'); ?></option>
                    <?php
                    foreach ($states as $ckey => $cvalue) {
                        echo '<option value="' . esc_attr($ckey) . '" ' . selected($current_r, $ckey, false) . '>' . esc_html($cvalue) . '</option>';
                    }
                    ?>
						</select>
                </span><?php

                // Standard Input
            } else {

                ?><input type="text" class="input-text" value="<?php echo esc_attr($current_r); ?>"
                         placeholder="<?php esc_attr_e('State / County', 'woocommerce'); ?>" name="calc_shipping_state"
                         id="calc_shipping_state" /><?php

            }
            ?>
        </p>

        <?php if (apply_filters('woocommerce_shipping_calculator_enable_city', false)) : ?>

            <p class="form-row form-row-wide" id="calc_shipping_city_field">
                <input type="text" class="input-text"
                       value="<?php echo esc_attr(WC()->customer->get_shipping_city()); ?>"
                       placeholder="<?php esc_attr_e('City', 'woocommerce'); ?>" name="calc_shipping_city"
                       id="calc_shipping_city"/>
            </p>

        <?php endif; ?>

        <?php if (apply_filters('woocommerce_shipping_calculator_enable_postcode', true)) : ?>

            <p class="form-row form-row-wide" id="calc_shipping_postcode_field">
                <input type="text" class="input-text"
                       value="<?php echo esc_attr(WC()->customer->get_shipping_postcode()); ?>"
                       placeholder="<?php esc_attr_e('Postcode / ZIP', 'woocommerce'); ?>" name="calc_shipping_postcode"
                       id="calc_shipping_postcode"/>
            </p>

        <?php endif; ?>

        <?php

        $cart_data = WC()->cart->get_cart();
        $weight = 0;
        $cost = 0;
        $fastery_settings = get_option('woocommerce_fastery_settings');
        $default_weight = $fastery_settings['default_weight'];

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        foreach ($cart_data as $item_id => $values) {

            $_product = $values['data'];
            $product_weight = $_product->get_weight();
            $product_weight = (is_numeric($product_weight)) ? $product_weight : $default_weight;
            $weight = $weight + $product_weight * $values['quantity'];
            $cost = $cost + $values['line_total'];
        }

        $address_1 = WC()->customer->get_billing_address_1();
        $ignet_fastery_cost = WC()->session->get('ignet_fastery_cost');
        $ignet_fastery_uid = WC()->session->get('ignet_fastery_uid');
        $delivery_term = WC()->session->get('ignet_fastery_delivery_term');

        if ('fastery' == $chosen_shipping) {
            echo '<input type="hidden" id="ignet_cart_weight"  name="ignet_cart_weight"  value="' . $weight . '">';
            echo '<input type="hidden" id="ignet_cart_cost"    name="ignet_cart_cost"    value="' . $cost . '">';
            echo '<input type="hidden" id="ignet_fastery_uid"  name="ignet_fastery_uid"  value="' . $ignet_fastery_uid . '">';
            echo '<input type="hidden" id="ignet_fastery_cost" name="ignet_fastery_cost" value="' . $ignet_fastery_cost . '">';
            echo '<input type="hidden" id="ignet_fastery_delivery_term" name="ignet_fastery_delivery_term" value="">';
            ?>

            <p class="form-row form-row-wide validate-required" id="calc_ignet_fastery_pvz_field">
                <input type="hidden" class="input-text"
                       value="<?php echo esc_attr(WC()->customer->get_billing_address_1()); ?>"
                       placeholder="Адрес пункта выдачи" name="ignet_fastery_pvz_field" id="ignet_fastery_pvz_field">
            </p>

            <p class="form-row form-row-wide" id="calc_fastery_pvz_field">
                <?php
                if ('' != $address_1) {
                    echo '<b>Адрес пункта выдачи:<br></b>' . $address_1;
                }
                ?>
            </p>

            <p class="form-row form-row-wide" id="calc_fastery_cost">
                <?php
                if ('' != $ignet_fastery_cost) {
                    echo '<b>Стоимость: </b>' . $ignet_fastery_cost . ' руб.';
                }
                ?>
            </p>

            <p class="form-row form-row-wide" id="calc_fastery_delivery_term">
                <?php
                if ('' != $delivery_term) {
                    echo '<b>Срок доставки: </b> от ' . $delivery_term . ' д.';
                }
                ?>
            </p>

            <p><span id="ignet-open-map" class="ignet-open-modal">Выбрать пункт выдачи на карте</span></p>

            <?php

        } //  if ('fastery' == $chosen_shipping)
        ?>

        <p>
            <button type="submit" name="calc_shipping" value="1"
                    class="button"><?php _e('Update totals', 'woocommerce'); ?></button>
        </p>

        <?php wp_nonce_field('woocommerce-cart'); ?>
    </section>
</form>

<?php do_action('woocommerce_after_shipping_calculator'); ?>
