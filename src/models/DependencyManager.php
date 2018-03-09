<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Менеджер зависимостей позволяет избежать ошибок при активации плагина или темы,
 * если отсутствуют плагины-зависимости обязательные для работы
 *
 * Подключение и запуск:
 *
 *   require_once 'src/models/DependencyManager.php';
 *
 *   $dependency_manager = new \Ignet\WP\Dev_Toolkit\DependencyManager();
 *   $dependency_manager->add('advanced-custom-fields-pro/acf.php', '- ACF Pro');
 *   $dependency_manager->add('ignet-dev-toolkit/init.php', '- Ignet Dev Toolkit');
 *
 * По-умолчанию встроена защита от активации с версиями PHP ниже 5.5.0
 * Чтобы изменить необходимую версию, установить следующее свойство:
 *
 *   $dependency_manager->required_php_version = '7.0.0';
 *
 * Использование в плагинах:
 *
 *   if ($dependency_manager->is_plugin_deactivated(__FILE__)) {
 *     return;
 *   }
 *
 * Использование в темах:
 *
 *   $dependency_manager->protect_theme();
 *
 * Class DependencyManager
 * @package Ignet\WP\Dev_Toolkit
 */
if ( ! class_exists(__NAMESPACE__ . "\\" . basename(__FILE__, '.php'))) :
	class DependencyManager {
		
		/**
		 * Сообщения об ошибке
		 * @var string
		 */
		public $notice_messages = [
			'plugin_dependencies' => 'Плагин не был активирован. Для его работы нужны следующие плагины:',
			'theme_dependencies'  => 'Тема не активирована. Для её работы нужны следующие плагины:',
			'plugin_php_version'  => 'Плагин не был активирован. Для его работы нужна версия PHP не ниже %1$s, текущая версия %2$s.',
			'theme_php_version'   => 'Тема не активирована. Для её работы нужна версия PHP не ниже %1$s, текущая версия %2$s.',
		];
		
		/**
		 * CSS классы уведомления
		 * @var string
		 */
		public $notice_classes = 'notice notice-error is-dismissible';
		
		/**
		 * Сообщение добавляемое вместо ссылки деактивации плагинов-зависимостей
		 * @var string
		 */
		public $disable_deactivation_message = '<div style="color:#a00">Зависимые плагины или тема активированы.</div>Деактивировать';
		
		/**
		 * @var string
		 */
		public $required_php_version = '5.5.0';
		
		/**
		 * Массив плагинов-зависимостей
		 * @var array
		 */
		private $_dependencies = [];
		
		/**
		 * @var array
		 */
		private $_missing_dependencies = [];
		
		/**
		 * Добавляет зависимость
		 *
		 * @param string $init_file_path Путь к главному фалу плагина относительно /wp-content/plugins/
		 * @param string $name Имя которое будет показано в сообщении об ошибке
		 */
		public function add($init_file_path, $name) {
			
			$this->_dependencies[ $init_file_path ] = $name;
		}
		
		/**
		 * Проверяет при активации наличие необходимых плагинов
		 * Деактивирует при их отсутствии
		 *
		 * @param string $init_file_path Путь к главному файлу плагина-клиента
		 *
		 * @return bool
		 */
		public function is_plugin_deactivated($init_file_path) {
			
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			if ((isset($_GET['action']) and $_GET['action'] == 'activate') or (isset($_GET['activate']) and $_GET['activate'] == true)) {
				
				// Проверка версии PHP
				if (false == $this->is_true_php_version()) {
					
					deactivate_plugins(plugin_basename($init_file_path));
					$this->print_notice($this->notice_messages['plugin_php_version'], [$this->required_php_version, PHP_VERSION]);
					
					return true;
				}
				
				// Проверка зависимостей
				if (false == $this->is_required_plugins_active()) {
					
					deactivate_plugins(plugin_basename($init_file_path));
					$this->print_notice($this->notice_messages['plugin_dependencies']);
					
					return true;
				}
			}
			
			// Отключение возможности деактивации зависимостей
			$this->disable_dependencies_deactivation();
			
			return false;
		}
		
		/**
		 * Проверяет при активации наличие необходимых плагинов
		 * Активирует прошлую тему при их отсутствии
		 */
		public function protect_theme() {
			
			if (isset($_GET['activated']) and $_GET['activated'] == true) {
				
				// Проверка версии PHP
				if (false == $this->is_true_php_version()) {
					
					add_action('after_switch_theme', function($old_theme_name, $old_theme) {
						switch_theme($old_theme->stylesheet);
					}, 10, 2);
					
					$this->print_notice($this->notice_messages['theme_php_version'], [$this->required_php_version, PHP_VERSION]);
					return;
				}
				
				// Проверка зависимостей
				if (false == $this->is_required_plugins_active()) {
					
					add_action('after_switch_theme', function($old_theme_name, $old_theme) {
						switch_theme($old_theme->stylesheet);
					}, 10, 2);
					
					$this->print_notice($this->notice_messages['theme_dependencies']);
					return;
				}
			}
			
			// Отключение возможности деактивации зависимостей
			$this->disable_dependencies_deactivation();
		}
		
		/**
		 * Проверяет правильная ли версия PHP
		 *
		 * @return bool
		 */
		private function is_true_php_version() {
			
			$is_true = version_compare(PHP_VERSION, $this->required_php_version, '>=');
			return $is_true;
		}
		
		/**
		 * Отключает возможность деактивации плагинов-зависимостей
		 */
		private function disable_dependencies_deactivation() {
			
			add_filter('plugin_action_links', function($actions, $plugin_file, $plugin_data, $context) {
				
				if (in_array($plugin_file, array_keys($this->_dependencies)) and ! in_array($this->disable_deactivation_message, $actions)) {
					
					unset($actions['deactivate']);
					array_unshift($actions, $this->disable_deactivation_message);
				}
				
				return $actions;
			}, 10, 4);
		}
		
		/**
		 * Проверяет активированы ли необходимые плагины
		 * Заполняет список имён отсутствующих плагинов
		 *
		 * @return bool
		 */
		public function is_required_plugins_active() {
			
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			foreach ($this->_dependencies as $path => $name) {
				
				$result = is_plugin_active($path);
				
				if ($result == false) {
					$this->_missing_dependencies[] = $name;
				}
			}
			
			return empty($this->_missing_dependencies) ? true : false;
		}
		
		/**
		 * Отображает сообщение об ошибке активации
		 *
		 * @param string $notice_message Сообщение об ошибке
		 * @param array $args Аргументы для установки в строку по шаблонам
		 */
		public function print_notice($notice_message, $args = []) {
			
			if (false == empty($args)) {
				
				$notice_message = vsprintf($notice_message, $args);
			}
			
			add_action('admin_notices', function() use ($notice_message) {
				
				// Формирование и отображение ошибки
				$message = '<p>' . $notice_message . '</p>';
				
				if (false == empty($this->_missing_dependencies)) {
					
					$message .= '<ul>';
					
					foreach ($this->_missing_dependencies as $name) {
						$message .= '<li>' . $name . '</li>';
					}
					
					$message .= '</ul>';
				}
				
				echo
					'<div class="' . $this->notice_classes . '">' .
					$message .
					'<button type="button" class="notice-dismiss"><span class="screen-reader-text">Скрыть это уведомление.</span></button>' .
					'</div>';
				
				// Сокрытие сообщения об успешной активации
				echo
					'<style>' .
					'#message, #message2 {display: none;}' .
					'</style>';
			});
		}
		
	}
endif;