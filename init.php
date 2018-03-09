<?php
namespace Ignet\Wc\Fastery\Plugin;

use Composer\Autoload\ClassLoader;
use Ignet\WP\Dev_Toolkit\Plugin;

/*
Plugin Name: Fastery - модуль доставки
Description: Модуль доставки от Fastery для WordPress.
Author: Fastery
Version: 1.0.0
*/

require_once 'src/models/DependencyManager.php';
$dependency_manager = new \Ignet\WP\Dev_Toolkit\DependencyManager();
$dependency_manager->add('woocommerce/woocommerce.php', '- Woocommerce');

// Автозагрузка
$loader = require_once 'vendor/autoload.php';
$loader->addPsr4(__NAMESPACE__ . '\\', __DIR__ . '/src/controllers');
$loader->addPsr4(__NAMESPACE__ . '\\', __DIR__ . '/src/models');
$loader->addPsr4(__NAMESPACE__ . '\\', __DIR__ . '/src/views');
$loader->addPsr4(__NAMESPACE__ . '\\', __DIR__ . '/src/helpers');

add_action('plugins_loaded', function() {
	
	// Запуск плагина
	$plugin = new Plugin(__FILE__, 'i_wc_fastery_');
	
	// Запуск компонентов плагина
	$eventsManager = new EventsManager($plugin);
	$cartManager   = new CartManager($plugin);
	$cartDesigner  = new CartDesigner($plugin);
	$eventsHandler = new EventsHandler($plugin, $eventsManager, $cartManager, $cartDesigner);
	$eventsHandler->init();
});