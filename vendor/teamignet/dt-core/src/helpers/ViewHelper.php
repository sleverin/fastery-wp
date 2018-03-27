<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Class ViewHelper
 *
 * Вспомогательные методы для работы с видом
 *
 * @package Ignet\WP\Dev_Toolkit
 */
class ViewHelper {
	
	/**
	 * Получение выводимого файлом потока при помощи буферизации
	 *
	 * @param string $path Путь к шорткоду
	 * @param array $args Дополнительные аргументы в виде ассоциативного массива, который будет распакован на переменные
	 *                    функцией extract() в область видимости функции
	 *
	 * @return string|bool
	 */
	public static function get_file_output($path, $args = []) {
		
		if (file_exists($path)) {
			
			if ( ! empty($args)) {
				extract($args);
			}
			
			ob_start();
			include $path;
			$content = ob_get_clean();
		}
		
		return isset($content) ? $content : false;
	}
}