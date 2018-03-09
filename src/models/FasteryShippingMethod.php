<?php

use WC_Shipping_Method;

class FasteryShippingMethod extends WC_Shipping_Method
{
    /**
     * FasteryShippingMethod constructor.
     */

    public function __construct()
    {
        $this->id = 'fastery';
        $this->method_title = 'Fastery Shipping';
        $this->method_description = 'Custom Shipping Method for Fastery';
        $this->init();
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : 'Fastery Shipping';
    }

    /**
     * Инициализация настроек
     */
    public function init()
    {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();
        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }


    /**
     * Поля для страницы настроек
     */
    public function init_form_fields()
    {


        $this->form_fields = [


            'enabled' => [

                'title' => 'Enable',

                'type' => 'checkbox',

                'description' => 'Включить этот метод доставки',

                'default' => 'yes'

            ],


            'demo_mode' => [

                'title' => 'Демо режим',

                'type' => 'checkbox',

                'description' => 'Включить демо режим',

                'default' => 'yes'

            ],


            'title' => [

                'title' => 'Заголовок',

                'type' => 'text',

                'description' => 'Заголовок, который выводится на сайте',

                'default' => 'Fastery Shipping'

            ],


            'shop_id' => [

                'title' => 'Fatery Shop Id',

                'type' => 'text',

                'description' => 'Shop Id Вы можете скопировать в своем личном кабинете Fastery',

                'default' => ''

            ],


            'access_token' => [

                'title' => 'Fatery Access Token',

                'type' => 'text',

                'description' => 'Access Token Вы можете скопировать в своем личном кабинете Fastery',

                'default' => ''

            ],


            'delayed_order_send' => [

                'title' => 'Отложенная выгрузка заказов',

                'type' => 'checkbox',

                'description' => '',

                'default' => 'no'

            ],
            'default_weight' => [

                'title' => 'Вес товара по умолчанию (кг)',

                'type' => 'text',

                'description' => 'Этот вес используется в случае, если у товара отсутствует вес',

                'default' => ''

            ],


            'clear_fastery_cashe' => [

                'title' => 'Очистить кеш',

                'type' => 'button',

                'description' => 'Очистка всех сохраненных данных о городах',

                'default' => 'Очистить кеш',

                'class' => 'clear_fastery_cashe-btn'

            ]

        ];

    }


    /**
     * Расчет стоимости доставки
     *
     * @param $package
     */

    public function calculate_shipping($package)
    {


        $time = current_time('mysql');

        // Самовывоз

        $rate = [

            'id' => $this->id,

            'label' => 'Самовывоз'

        ];


        // Курьерская доставка

        $courier_rate = [

            'id' => $this->id . '_courier',

            'label' => 'Курьерская доставка',

        ];


        // Почта России

        $mail_rate = [

            'id' => $this->id . '_mail',

            'label' => 'Почта России'

        ];


        $weight = 0;

        $order_cost = $package['contents_cost'];

        $city = $package['destination']['city'];

        foreach ($package['contents'] as $item_id => $values) {


            $_product = $values['data'];

            $product_weight = $_product->get_weight();

            $product_weight = (is_numeric($product_weight) && '' != $product_weight) ? $product_weight : $this->settings['default_weight'];

            $weight = $weight + $product_weight * $values['quantity'];

        }


        $items = [];

        $courier_flag = false;


        // Отправим запрос в Fastery

        if ($city != '') {


            $post_data = [

                'city' => $city,

                'cost' => $order_cost,

                'weight' => $weight,

                'action' => 'calculate_deliveries',

            ];

            $url = admin_url('admin-ajax.php');

            $wp_http = new \WP_HTTP();

            $result = $wp_http->request($url, [

                'method' => 'POST',

                'timeout' => 380,

                'body' => $post_data

            ]);

            $items = is_wp_error($result) ? [] : json_decode($result['body']);

        }


        // Почта России

        if (isset($items->mail)) {


            $mail_rate['cost'] = $items->mail->cost;

            WC()->session->set('ignet_fastery_mail_uid', $items->mail->uid);

        }


        // Курьерская доставка

        if (isset($items->courier)) {


            $courier_rate['cost'] = $items->courier->cost;

            WC()->session->set('ignet_fastery_courier_uid', $items->courier->uid);

        }


        // Самовывоз

        $ignet_fastery_cost = WC()->session->get('ignet_fastery_cost');

        if ($ignet_fastery_cost) {

            $rate['cost'] = $ignet_fastery_cost;

        }

        if (isset($_POST['ignet_fastery_cost'])) {

            $rate['cost'] = $_POST['ignet_fastery_cost'];

        }


        if (isset($items->points)) {


            $rate['cost'] = '';


            $uid = WC()->session->get('ignet_fastery_uid');

            foreach ($items->points as $item) {


                if ($uid == $item->uid) {


                    $rate['cost'] = $item->cost;

                    WC()->session->set('ignet_fastery_cost', $item->cost);

                }

            }

        }

        if (isset($_POST['post_data'])) {

            $post_data = explode('&', $_POST['post_data']);

            foreach ($post_data as $data) {
                $item = explode('=', $data);
                if ('ignet_fastery_cost' == $item[0]) {
                    $rate['cost'] = $item[1];
                    WC()->session->set('ignet_fastery_cost', $item[1]);
                }
                if ('ignet_fastery_uid' == $item[0]) {
                    WC()->session->set('ignet_fastery_uid', $item[1]);
                }
            }
        }

        // Добавим методы
        $this->add_rate($mail_rate);
        $this->add_rate($courier_rate);
        $this->add_rate($rate);
    }
}