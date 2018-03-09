<?php

namespace Ignet\Wc\Fastery\Plugin;

use Ignet\WP\Dev_Toolkit\Plugin;
use Ignet\WP\Dev_Toolkit\ViewHelper;

class EventsManager
{

    /**
     * @var Plugin
     */
    public $plugin;

    /**
     * @var string
     */
    public $calculate_action = 'calculate_deliveries';

    /**
     * @var string
     */
    public $fastery_handle = 'fastery_orders_handle';

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
     * Добавление скриптов на фронт
     */
    public function load_scripts()
    {

        wp_enqueue_style($this->plugin->get('prefix') . 'style', $this->plugin->get('url') . '/assets/css/main.css');

        wp_enqueue_script('api-maps-yandex', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU');
        wp_enqueue_script('events-handle-scripts', $this->plugin->get('url') . '/assets/js/events-handle-scripts.js', [
            'jquery',
            'api-maps-yandex'
        ]);
        wp_localize_script('events-handle-scripts', 'eventsHandle', [
            'url' => admin_url('admin-ajax.php'),
            'action' => $this->calculate_action,
        ]);
    }

    /**
     * Добавления скриптов в админку
     */
    public function load_admin_scripts()
    {

        wp_enqueue_script('admin-handle-scripts', $this->plugin->get('url') . '/assets/js/admin-handle-scripts.js', [
            'jquery'
        ]);
        wp_localize_script('admin-handle-scripts', 'adminHandle', [
            'url' => admin_url('admin-ajax.php'),
            'action' => $this->fastery_handle,
        ]);
    }

    /**
     * Инициализация метода доставки
     */
    public function shipping_method_init()
    {

        ViewHelper::get_file_output($this->plugin->get('dir') . '/src/models/FasteryShippingMethod.php', []);
    }

    /**
     * Добавление метода
     *
     * @param $methods
     *
     * @return array
     */
    public function add_shipping_method($methods)
    {

        $methods[] = 'FasteryShippingMethod';

        return $methods;
    }

    /**
     * Обработка запроса на рассчет стоимости доставки
     */
    public function calculate_deliveries()
    {

        $result = [];
        $city = $_POST['city'];
        $cost = $_POST['cost'];
        $weight = $_POST['weight'];
        $points_cache = get_option('fastery_pvz_points');
        $key = $city . $cost . $weight;

        // Есть ли в кеше данные
        if (isset($points_cache[$key])) {

            $result = $points_cache[$key];
        } else {

            // Отправим запрос в Fastery
            $fastery_settings = get_option('woocommerce_fastery_settings');
            $access_token = $fastery_settings['access_token'];
            $shop_id = $fastery_settings['shop_id'];
            $demo_mode = $fastery_settings['demo_mode'];

            $fasteryApi = new FasteryApi($access_token, $shop_id, $demo_mode);
            $items = $fasteryApi->calculate($city, $cost, $weight);
            $res_items = [];
            $mail_item = [];
            $cour_item = [];
            $addresses = [];
            $cour_flag = false;

            foreach ($items as $item) {

                if ('point' == $item->type) {
                    $latlng = $item->lat . $item->lng;
                    if (isset($addresses[$latlng])) {
                        $carriers = $addresses[$latlng];
                        if (!in_array($item->carrier_key, $carriers)) {
                            $addresses[$latlng][] = $item->carrier_key;
                            $res_items[] = $item;
                        }
                    } else {
                        $addresses[$latlng][] = $item->carrier_key;
                        $res_items[] = $item;
                    }
                }
                if ('mail' == $item->type) {
                    $mail_item = $item;
                }

                if ('courier' == $item->type) {
                    if (!$cour_flag) {

                        $cour_item = $item;
                        $cour_flag = true;
                    }
                }
            }

            $points_cache[$key] = [
                'points' => $res_items,
                'mail' => $mail_item,
                'courier' => $cour_item,
            ];
            $result = $points_cache[$key];
            update_option('fastery_pvz_points', $points_cache);
        }

        wp_send_json($result);
    }

    /**
     * Обработка создания/обновления заказа
     */
    public function handle_fastery_orders()
    {

        $action = $_POST['fastery_action'];
        $order_id = $_POST['order_id'];

        $order = new \WC_Order($order_id);
        $shipping_method = array_shift($order->get_shipping_methods());
        $shipping_method_id = $shipping_method['method_id'];
        $shipping_methods = ['fastery', 'fastery_mail', 'fastery_courier'];
        if (!in_array($shipping_method_id, $shipping_methods)) {

            return;
        }

        if ('update' == $action) {

            $result = $this->update_order($order_id);
        } else {

            $result = $this->create_order($order_id);
        }

        $html = ViewHelper::get_file_output($this->plugin->get('dir') . '/src/templates/fastery-metabox.php', [
            'post_id' => $order_id
        ]);

        wp_send_json($html);
    }

    /**
     * Создание заказа в Личном кабинете Fastery
     *
     * @param $order_id
     * @param $posted
     */
    public function order_create_handle($order_id, $posted)
    {

        $order = new \WC_Order($order_id);
        $shipping_method = array_shift($order->get_shipping_methods());
        $shipping_method_id = $shipping_method['method_id'];
        $shipping_methods = ['fastery', 'fastery_mail', 'fastery_courier'];
        if (!in_array($shipping_method_id, $shipping_methods)) {

            return;
        }

        $fastery_settings = get_option('woocommerce_fastery_settings');
        if ($fastery_settings['delayed_order_send'] != 'yes') {

            $this->create_order($order_id);
        }
    }

    /**
     * Обработчик смены статуса заказа на оплачен
     *
     * @param $order_id
     */
    public function order_paid_handle($order_id)
    {

        $order = new \WC_Order($order_id);
        $shipping_method = array_shift($order->get_shipping_methods());
        $shipping_method_id = $shipping_method['method_id'];
        $shipping_methods = ['fastery', 'fastery_mail', 'fastery_courier'];
        if (!in_array($shipping_method_id, $shipping_methods)) {

            return;
        }

        $fastery_settings = get_option('woocommerce_fastery_settings');
        $status = get_post_meta($order_id, 'fastery_status', 1);

        if ($fastery_settings['delayed_order_send'] == 'yes' && 'send' != $status) {

            $this->create_order($order_id);
        } else {

            $this->update_order($order_id);
        }
    }

    /**
     * Создание заказа в Fastery и обновление мета данных
     *
     * @param $order_id
     *
     * @return array|string|\WP_Error
     */
    public function create_order($order_id)
    {

        $fastery_settings = get_option('woocommerce_fastery_settings');
        $fasteryApi = new FasteryApi($fastery_settings['access_token'], $fastery_settings['shop_id'], $fastery_settings['demo_mode']);
        $request_body = $this->generate_request_body($order_id);

        $result = $fasteryApi->create_order($request_body);
        if (!is_wp_error($result)) {

            if (isset($result['id'])) {

                update_post_meta($order_id, 'fastery_status', 'send');
                update_post_meta($order_id, 'ignet_fastery_order_id', $result['id']);
                update_post_meta($order_id, 'ignet_fastery_payment_method', $result['payment_method']);
                delete_post_meta($order_id, 'fastery_errors');
            }

            if (isset($result['errors'])) {

                update_post_meta($order_id, 'fastery_errors', $result['errors']);
            }

            return $result;
        } else {

            return $result->get_error_message();
        }
    }

    /**
     * Обновление заказа в Fastery и обновление мета данных
     *
     * @param $order_id
     *
     * @return array|string|\WP_Error
     */
    public function update_order($order_id)
    {

        $fastery_settings = get_option('woocommerce_fastery_settings');
        $fasteryApi = new FasteryApi($fastery_settings['access_token'], $fastery_settings['shop_id'], $fastery_settings['demo_mode']);
        $fastery_id = get_post_meta($order_id, 'ignet_fastery_order_id', 1);
        $request_body = $this->generate_request_body($order_id);

        $result = $fasteryApi->update_order($fastery_id, $request_body);
        if (!is_wp_error($result)) {

            if (isset($result['id'])) {

                update_post_meta($order_id, 'ignet_fastery_payment_method', $result['payment_method']);
                delete_post_meta($order_id, 'fastery_errors');
            }

            if (isset($result['errors'])) {

                update_post_meta($order_id, 'fastery_errors', $result['errors']);
            }
            return $result;
        } else {

            return $result->get_error_message();
        }
    }

    /**
     * Генерируем тело запроса
     *
     * @param $order_id
     */
    public function generate_request_body($order_id)
    {

        $products = [];
        $order = new \WC_Order($order_id);
        $items = $order->get_items();
        $fastery_settings = get_option('woocommerce_fastery_settings');

        $address = $order->get_billing_address_1();
        $address_data = explode(', ', $address);
        $street = isset($address_data[0]) ? $address_data[0] : '';
        $house = isset($address_data[1]) ? $address_data[1] : '';

        foreach ($items as $item) {

            $product = $item->get_product();
            $name = $item->get_name();
            $weight = $product->get_weight();
            $weight = ('' != $weight || $weight) ? $weight : $fastery_settings['default_weight'];
            $quantity = $item->get_quantity();
            $price = $product->get_price();
            $product = [
                'name' => $item->get_name(),
                'barcode' => $product->get_id(),
                'quantity' => $item->get_quantity(),
                'price' => $price,
                'weight' => $weight * 100
            ];

            array_push($products, $product);
        }

        $data = [
            'shop_order_number' => $order_id,
            'phone' => $order->get_billing_phone(),
            'email' => $order->get_billing_email(),
            'fio' => $order->get_billing_last_name() . ' ' . $order->get_billing_first_name(),
            'products' => $products,
            'address' => [
                'region' => $order->get_billing_state(),
                'city' => $order->get_billing_city(),
                'postcode' => $order->get_billing_postcode(),
                'street' => $street,
                'house' => $house,
            ],
            'delivery' => [
                'cost' => $order->get_shipping_total(),
                'pickup_date' => $order->get_date_created()->date('d.m.Y'),
                'uid' => get_post_meta($order_id, 'ignet_fastery_uid', 1)
            ]
        ];

        $payment_method = $order->get_payment_method();
        $order_status = $order->get_status();
        if (in_array($order_status, ['processing']) && 'cod' != $payment_method) {

            $data['payment_method'] = 'noPay';
        }

        return $data;
    }

    /**
     * Удаление кеша
     */
    public function clear_cashe()
    {

        delete_option('fastery_pvz_points');

        wp_send_json('ok');
    }
}