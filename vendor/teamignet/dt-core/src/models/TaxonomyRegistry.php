<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Реестр таксономий автоматически регистрирующий их в WP
 *
 * Использование:
 *
 *   TaxonomyRegistry::getInstance()->attach('слаг_таксономии', $массив_аргументов);
 *
 * Примечание:
 *
 *   WP функция register_taxonomy($slug, $post_types, $args) принимает вторым аргуметом массив типов постов
 *   Чтобы сохранять возможность добавления элеметов через родительский метод attach(),
 *   типы постов нужно указывать в $массив_аргументов['post_types'],
 *   и функция получит типы постов следующим образом - register_taxonomy($slug, $args['post_types'], $args);
 *
 * Class TaxonomyRegistry
 * @package Ignet\WP\Dev_Toolkit
 */
class TaxonomyRegistry extends Registry {
	
	/**
	 * @var Registry $instance
	 */
	private static $instance = null;
	
	/**
	 * @var array Значения по-умолчанию
	 */
	private $defaults = [
		'label'                 => 'Жанры', // определяется параметром $labels->name
		'labels'                => [
			'name'              => 'Жанры',
			'singular_name'     => 'Жанр',
			'search_items'      => 'Искать',
			'all_items'         => 'Все',
			'view_item '        => 'Смотреть',
			'parent_item'       => 'Родитель',
			'parent_item_colon' => 'Родитель:',
			'edit_item'         => 'Редактировать',
			'update_item'       => 'Обновить',
			'add_new_item'      => 'Добавить',
			'new_item_name'     => 'Новый',
			'menu_name'         => 'Жанры',
		],
		'description'           => '', // описание таксономии
		'public'                => true,
		'publicly_queryable'    => null, // равен аргументу public
		'show_in_nav_menus'     => true, // равен аргументу public
		'show_ui'               => true, // равен аргументу public
		'show_tagcloud'         => true, // равен аргументу show_ui
		'show_in_rest'          => null, // добавить в REST API
		'rest_base'             => null, // $taxonomy
		'hierarchical'          => false,
		'update_count_callback' => '',
		'rewrite'               => true, //'query_var' => $taxonomy, // название параметра запроса
		'capabilities'          => [],
		// callback функция. Отвечает за html код метабокса (с версии 3.8): post_categories_meta_box или post_tags_meta_box.
		// Если указать false, то метабокс будет отключен вообще
		'meta_box_cb'           => null,
		// Позволить или нет авто-создание колонки таксономии в таблице ассоциированного типа записи. (с версии 3.5)
		'show_admin_column'     => false,
		'_builtin'              => false,
		'show_in_quick_edit'    => null,    // по умолчанию значение show_ui
		'post_types'            => ['post'] // типы постов, для второго аргумента функции register_taxonomy()
	];
	
	/**
	 * Возвращает экземпляр
	 *
	 * @return TaxonomyRegistry
	 */
	public static function getInstance() {
		
		if (self::$instance === null) {
			
			self::$instance = new self();
			add_action('init', [self::$instance, 'register']);
		}
		
		return self::$instance;
	}
	
	/**
	 * TaxonomyRegistry constructor.
	 */
	private function __construct() {}
	
	/**
	 * Закрыт как Одиночка
	 */
	private function __clone() {}
	
	/**
	 * Регистрирует таксономии
	 *
	 * @uses register_taxonomy
	 * @see https://wp-kama.ru/function/register_taxonomy
	 */
	public function register() {
		
		foreach ($this->_registry as $slug => $args) {
			
			$args = wp_parse_args($args, $this->defaults);
			register_taxonomy($slug, $args['post_types'], $args);
		}
	}
}