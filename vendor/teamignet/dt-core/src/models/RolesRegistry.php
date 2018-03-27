<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Реестр ролей автоматически регистрирующий их в WP
 *
 * Использование:
 *
 *   RolesRegistry::getInstance()->attach('dude', include $plugin->get('dir') . 'config/role-dude.php');
 *
 * Массив конфигурации должен содержать:
 *
 *   'name'         => 'Чувак', // Отображаемое имя роли
 *   'capabilities' => []       // Массив с правами
 *
 * Class RolesRegistry
 * @package Ignet\WP\Dev_Toolkit
 */
class RolesRegistry extends Registry {
	
	/**
	 * @var Registry $instance
	 */
	private static $instance = null;
	
	/**
	 * Возвращает экземпляр
	 *
	 * @return RolesRegistry
	 */
	public static function getInstance() {
		
		if (self::$instance === null) {
			
			self::$instance = new self();
			add_action('init', [self::$instance, 'register']);
		}
		
		return self::$instance;
	}
	
	/**
	 * RoleRegistry constructor.
	 */
	private function __construct() {}
	
	/**
	 * Закрыт как Одиночка
	 */
	private function __clone() {}
	
	/**
	 * Регистрирует роли
	 *
	 * @uses add_role
	 * @see https://wp-kama.ru/function/add_rol
	 */
	public function register() {
		
		foreach ($this->_registry as $slug => $args) {
			if ( ! get_role($slug)) {
				
				add_role($slug, $args['name'], $args['capabilities']);
			}
		}
	}
}