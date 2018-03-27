<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Class Plugin
 * @package Ignet\WP\Dev_Toolkit
 */
class Plugin {
	
	/**
	 * @var string
	 */
	protected $dir;
	
	/**
	 * @var string
	 */
	protected $url;
	
	/**
	 * @var null|string
	 */
	protected $prefix;
	
	/**
	 * WP_Plugin constructor.
	 * Стратовая точка, инициализация свойств
	 *
	 * @param string $init_path Путь к файлу запускающему плагин __FILE__
	 * @param null|string $prefix (optional) Префикс для избежания конфликтов при использовании имён
	 */
	public function __construct($init_path, $prefix = null) {
		
		$this->dir    = dirname($init_path) . '/';
		$this->url    = plugin_dir_url($init_path);
		$this->prefix = $prefix;
	}
	
	/**
	 * Возвращает закрытые свойства
	 *
	 * @param string $name
	 *
	 * @return null
	 */
	public function get($name) {
		
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		
		return null;
	}
}