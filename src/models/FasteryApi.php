<?php

namespace Ignet\Wc\Fastery\Plugin;

class FasteryApi
{
    /**
     * @var string
     */
    public $demo_url = 'http://demo.fastery.ru/api/';

    /**
     * @var string
     */
    public $live_url = 'http://lk.fastery.ru/api/';

    /**
     * @var \WP_HTTP
     */
    private $wp_http;

    /**
     * @var string
     */
    private $access_token;

    /**
     * @var string
     */
    private $shop_id;

    /**
     * @var string
     */
    private $api_url;

    /**
     * FasteryApi constructor.
     *
     * @param string $access_token
     */
    public function __construct($access_token = '', $shop_id = '', $demo_mode = '')
    {
        $this->access_token = $access_token;
        $this->shop_id = $shop_id;
        $this->wp_http = new \WP_HTTP();
        $this->api_url = ($demo_mode == 'yes') ? $this->demo_url : $this->live_url;
    }

    /**
     * Расчитать стоимость доставки
     */
    public function calculate($city, $cost, $weight)
    {
        $weight = $weight * 1000;
        $data = [
            'access-token' => $this->access_token,
            'shop_id' => $this->shop_id,
            'city' => $city,
            'cost' => $cost,
            'assessed_cost' => $cost,
            'weight' => ceil($weight),
            'sort' => 'cost',
        ];

        $url = $this->api_url . 'delivery/calculate?' . http_build_query($data);

        if ($tuCurl = curl_init()) {
            curl_setopt($tuCurl, CURLOPT_URL, $url);
            curl_setopt($tuCurl, CURLOPT_VERBOSE, 0);
            curl_setopt($tuCurl, CURLOPT_HEADER, 0);
            curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($tuCurl, CURLOPT_HTTPHEADER, array('Accept' => 'application/json'));
            $tuData = curl_exec($tuCurl);
            if (!curl_errno($tuCurl)) {
                $info = curl_getinfo($tuCurl);
            } else {
                $error = 'Curl error: ' . curl_error($tuCurl);
            }
            curl_close($tuCurl);

            $body = json_decode($tuData);
        }

//        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/fastery.log', $url . "\n\n", FILE_APPEND | LOCK_EX);
//        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/fastery.log', $tuData . "\n\n", FILE_APPEND | LOCK_EX);

        if (isset($body->items)) {
            $items = $body->items;
            return $items;
        } else {
            $message = $body->message;
            return new \WP_Error('error', $message);
        }
    }

    /**
     * Запрос на создание заказа
     *
     * @param $post_data
     *
     * @return array|\WP_Error
     */
    public function create_order($post_data)
    {
        $post_data['shop_id'] = $this->shop_id;

        $url = $this->api_url . 'order/create?access-token=' . $this->access_token;
        $result = $this->wp_http->request($url, [
            'method' => 'POST',
            'timeout' => 380,
            'body' => $post_data
        ]);

        if (is_wp_error($result)) {
            return new \WP_Error('error', 'При отправке запроса произошла ошибка');
        }

        $body = json_decode($result['body']);

        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/fastery.log', $url . "\n\n", FILE_APPEND | LOCK_EX);
        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/fastery.log', $post_data . "\n\n", FILE_APPEND | LOCK_EX); 
        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/fastery.log', $result['body'] . "\n\n", FILE_APPEND | LOCK_EX);

        if (isset($body->id)) {
            $result = [
                'id' => $body->id,
                'payment_method' => $body->payment_method
            ];
            return $result;
        } else {
            $errors = [];
            foreach ($body as $error) {
                $errors[$error->field] = $error->message;
            }
            $result = [
                'errors' => $errors,
            ];
            return $result;
        }
    }

    /**
     * Запрос на обновление заказа
     *
     * @param $order_id
     * @param $post_data
     *
     * @return array|\WP_Error
     */
    public function update_order($order_id, $post_data)
    {

        $post_data['shop_id'] = $this->shop_id;

        $url = $this->api_url . 'order/update/' . $order_id . '?access-token=' . $this->access_token;
        $result = $this->wp_http->request($url, [
            'method' => 'POST',
            'timeout' => 380,
            'body' => $post_data
        ]);

        if (is_wp_error($result)) {
            return new \WP_Error('error', 'При отправке запроса произошла ошибка');
        }

        $body = json_decode($result['body']);
        if (isset($body->id)) {

            $body = json_decode($result['body']);
            $result = [
                'id' => $body->id,
                'payment_method' => $body->payment_method
            ];

            return $result;
        } else {
            $errors = [];
            foreach ($body as $error) {
                $errors[$error->field] = $error->message;
            }

            $result = [
                'errors' => $errors,
            ];
            return $result;
        }
    }
}