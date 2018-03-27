<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Реестр типов постов автоматически регистрирующий их в WP
 *
 * Использование:
 *
 *   PostTypeRegistry::getInstance()->attach('some', include $plugin->get('dir') . 'config/post-types/some.php');
 *     или
 *   PostTypeRegistry::getInstance()->attach('some', $args);
 *     или короткая форма
 *   PostTypeRegistry::getInstance()->build('some', 'имя', 'иконка', 'позиция в меню');
 *
 * Class PostTypeRegistry
 * @package Ignet\WP\Dev_Toolkit
 */
class PostTypeRegistry extends Registry {
	
	/**
	 * @var Registry $instance
	 */
	private static $instance = null;
	
	/**
	 * @var array Значения по-умолчанию
	 */
	private $defaults = [
		'label'   => 'Зайчики',
		'labels'  => [
			'name'               => 'Зайчики',
			'singular_name'      => 'Зайчик',
			'add_new'            => 'Добавить',
			'add_new_item'       => 'Добавление',
			'edit_item'          => 'Редактирование',
			'new_item'           => 'Новый',
			'view_item'          => 'Смотреть',
			'search_items'       => 'Искать',
			'not_found'          => 'Не найдено',
			'not_found_in_trash' => 'Не найдено в корзине',
			'parent_item_colon'  => '',
			'menu_name'          => 'Зайчики',
		],
		'description'         => '',
		'public'              => true,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => null,
		'show_in_menu'        => null, // показывать ли в меню консоли
		'show_in_admin_bar'   => null, // по умолчанию значение show_in_menu
		'show_in_nav_menus'   => null,
		'show_in_rest'        => null, // добавить в REST API. C WP 4.7
		'rest_base'           => null, // $post_type. C WP 4.7
		'menu_position'       => 21,
		'menu_icon'           => 'dashicons-carrot',
		'hierarchical'        => false,
		
		// 'title','editor','author','thumbnail','excerpt','trackbacks',
		// 'custom-fields','comments','revisions','page-attributes','post-formats'
		'supports'            => ['title','editor'],
		'taxonomies'          => [],
		'has_archive'         => false,
		'rewrite'             => true,
		'query_var'           => true,
	];
	
	/**
	 * Возвращает экземпляр
	 *
	 * @return PostTypeRegistry
	 */
	public static function getInstance() {
		
		if (self::$instance === null) {
			
			self::$instance = new self();
			add_action('init', [self::$instance, 'register']);
		}
		
		return self::$instance;
	}
	
	/**
	 * PostTypeRegistry constructor.
	 */
	private function __construct() {}
	
	/**
	 * Закрыт как Одиночка
	 */
	private function __clone() {}
	
	/**
	 * Собирает из набора параметров тип поста и регистрирует
	 *
	 * @param string       $slug          Уникальный ключ на кириллице
	 * @param string|array $name          Имя в единственном числе или неопределённой форме
	 * @param null|string  $menu_icon     Ссылка на иконку или название Dashicons
	 * @param null|int     $menu_position Позиция в меню консоли
	 *
	 * @uses register_post_type
	 * @see https://wp-kama.ru/function/register_post_type
	 */
	public function build($slug, $name, $menu_icon = null, $menu_position = null) {
		
		$args = [
			'labels' => array(
				'name'               => $name,
				'singular_name'      => $name,
				'menu_name'          => $name,
			),
			'menu_icon'     => $menu_icon,
			'menu_position' => $menu_position,
		];
		
		$this->attach($slug, $args);
	}
	
	/**
	 * Регистрирует типы постов
	 *
	 * @uses register_post_type
	 * @see https://wp-kama.ru/function/register_post_type
	 */
	public function register() {
		
		foreach ($this->_registry as $slug => $args) {
			
			$args = wp_parse_args($args, $this->defaults);
			register_post_type($slug, $args);
		}
	}
}