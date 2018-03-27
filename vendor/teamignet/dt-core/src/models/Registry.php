<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Class Registry
 * @package Ignet\WP\Dev_Toolkit
 */
class Registry {
	
	/**
	 * @var array
	 */
	protected $_registry = [];
	
	/**
	 * Добавляет элемент
	 *
	 * @param string $name
	 * @param mixed $data
	 */
	public function attach($name, $data) {
		
		if (TRUE === isset($this->_registry[ $name ])) {
			throw new \Exception('The instance with name ' . $name . ' already exists in registry.');
		}
		
		if ( ! empty($name)) {
			$this->_registry[ $name ] = $data;
		}
	}
	
	/**
	 * Удаляет элемент
	 *
	 * @param string $name
	 */
	public function detach($name) {
		
		if (isset($this->_registry[ $name ])) {
			
			$this->_registry[ $name ] = NULL;
			unset($this->_registry[ $name ]);
		}
	}
	
	/**
	 * Возвращает элемент
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get($name) {
		
		if (FALSE === isset($this->_registry[ $name ])) {
			throw new Exception('Invalid instance requested.');
		}
		
		return $this->_registry[ $name ];
	}
}