<?php
namespace Ignet\Wc\Fastery\Plugin;

use Ignet\WP\Dev_Toolkit\Plugin;

class EventsHandler
{

    /**
     * @var Plugin
     */
    public $plugin;

    /**
     * @var EventsManager
     */
    public $eventsManager;

    /**
     * @var CartManager
     */
    public $cartManager;

    /**
     * @var CartDesigner
     */
    public $cartDesigner;

    public function __construct(Plugin $plugin, EventsManager $eventsManager, CartManager $cartManager, CartDesigner $cartDesigner)
    {
        $this->plugin = $plugin;
        $this->eventsManager = $eventsManager;
        $this->cartManager = $cartManager;
        $this->cartDesigner = $cartDesigner;
    }

    /**
     * Инициализация
     */
    public function init()
    {
        // Добавление скриптов
        add_action('wp_enqueue_scripts', [$this->eventsManager, 'load_scripts']);
        add_action('admin_enqueue_scripts', [$this->eventsManager, 'load_admin_scripts']);

        // Обработка расчета стоимости доставки
        add_action('wp_ajax_' . $this->eventsManager->calculate_action, [$this->eventsManager, 'calculate_deliveries']);
        add_action('wp_ajax_nopriv_' . $this->eventsManager->calculate_action, [$this->eventsManager, 'calculate_deliveries']);

        // Обработка создания/обновления заказа из админки
        add_action('wp_ajax_' . $this->eventsManager->fastery_handle, [$this->eventsManager, 'handle_fastery_orders']);
        add_action('wp_ajax_nopriv_' . $this->eventsManager->fastery_handle, [$this->eventsManager, 'handle_fastery_orders']);

        // Обработка создания заказа
        add_action('woocommerce_checkout_order_processed', [$this->eventsManager, 'order_create_handle'], 10, 2);
        add_action('woocommerce_order_status_processing', [$this->eventsManager, 'order_paid_handle'], 10, 1);

        // Добавление метода доставки
        add_action('woocommerce_shipping_init', [$this->eventsManager, 'shipping_method_init']);
        add_filter('woocommerce_shipping_methods', [$this->eventsManager, 'add_shipping_method']);

        // Включение поля Город в форму
        add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');

        // Сохранение кастомных полей в калькуляторе
        add_action('woocommerce_calculated_shipping', [$this->cartManager, 'save_shipping_calculator_field']);
        // Изменение суммы доставки при обновлении итога
        add_filter('woocommerce_shipping_packages', [$this->cartManager, 'change_shipping_packages'], 10, 1);
        // Обновление кастомных данных заказа
        add_action('woocommerce_checkout_update_order_meta', [$this->cartManager, 'checkout_update_order_meta']);
        // Валидация перед офорлением заказа
        //remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
        //add_action('woocommerce_proceed_to_checkout',         [$this->cartManager, 'validate_proceed_to_checkout'], 10);
        // Валидация перед созданием заказа
        add_action('woocommerce_checkout_process', [$this->cartManager, 'validate_checkout_fields'], 10, 1);


        // Добавление скриптов
        add_action('wp_enqueue_scripts', [$this->cartDesigner, 'load_scripts']);
        // Замена шаблона калькулятора в корзине
        add_filter('wc_get_template', [$this->cartDesigner, 'get_template'], 10, 2);
        // Добавление модального окна с картой
        add_filter('the_content', [$this->cartDesigner, 'display_map'], 10, 1);
        // Добавление кастомных полей на странице Checkout
        //	add_action('woocommerce_after_order_notes',                       [$this->cartDesigner, 'add_checkout_field']);
        add_action('woocommerce_after_checkout_billing_form', [$this->cartDesigner, 'add_billing_data'], 10, 1);
        // Выводим поле выбора ПВЗ на странице Checkout
        add_action('woocommerce_review_order_after_shipping', [$this->cartDesigner, 'add_block_select_pvz']);
        // Добавляем метабокс к заказеу
        add_action('add_meta_boxes', [$this->cartDesigner, 'add_meta_box']);

        // Сохранение стоимости
        add_action('wp_ajax_save_cost', [$this->cartManager, 'save_cost']);
        add_action('wp_ajax_nopriv_save_cost', [$this->cartManager, 'save_cost']);

        // Удаление кеша
        add_action('wp_ajax_clear_cashe', [$this->eventsManager, 'clear_cashe']);
        add_action('wp_ajax_nopriv_clear_cashe', [$this->eventsManager, 'clear_cashe']);
    }
}