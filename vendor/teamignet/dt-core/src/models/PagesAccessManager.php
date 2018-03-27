<?php
namespace Ignet\WP\Dev_Toolkit;

use Ignet\WP\Dev_Toolkit\UserHelper;

/**
 * Менеджер доступа к страницам
 *
 * Осуществляет переадресацию пользователя с запрещённых страниц по ролям или авторизации.
 * Для использования установить в свойства pages_by_* и запустить метод init()
 *
 * Class PagesAccessManager
 * @package Ignet\WP\Dev_Toolkit\Components\Restrictions
 */
class PagesAccessManager {
	
	/**
	 * @var string
	 */
	public $redirect = '404';
	
	/**
	 * Массив с элементами вида 'role' => 'slug'
	 *
	 * @var array $pages_by_roles
	 */
	public $pages_by_roles;
	
	/**
	 * Массив слагов
	 *
	 * @var array $pages_by_auth
	 */
	public $pages_by_auth;
	
	/**
	 * Запуск ограничений
	 */
	public function init() {
		
		if (is_array($this->pages_by_roles)) {
			add_action('wp', [$this, 'deny_by_roles']);
		}
		
		if (is_array($this->pages_by_auth)) {
			add_action('wp', [$this, 'deny_by_auth']);
		}
	}
	
	/**
	 * Ограничение по ролям
	 */
	public function deny_by_roles() {
		
		foreach ($this->pages_by_roles as $role => $page_slug) {
			if (is_page() and UserHelper::is_role_in([$role])) {
				
				$page = get_page_by_path($page_slug);
				$page_childs = $this->get_page_childs($page->ID);
				
				if ($page and is_array($page_childs) and (in_array(get_the_ID(), $page_childs) or is_page($page->ID))) {
					
					wp_redirect(home_url($this->redirect));
					exit;
				}
			}
		}
	}
	
	/**
	 * Ограничение по ролям
	 */
	public function deny_by_auth() {
		
		foreach ($this->pages_by_auth as $page_slug) {
			if (is_page() and ! is_user_logged_in()) {
				
				$page = get_page_by_path($page_slug);
				
				if (is_object($page)) {
					
					$page_childs = $this->get_page_childs($page->ID);
					
					if (is_page($page->ID) or (in_array(get_the_ID(), $page_childs))) {
						
						wp_redirect(home_url($this->redirect));
						exit;
					}
				}
			}
		}
	}
	
	/**
	 * Получение дочерних страниц страницы
	 *
	 * @param int $parent_page_id
	 *
	 * @return array
	 */
	public static function get_page_childs($parent_page_id) {
		
		if (is_numeric($parent_page_id)) {
			
			$child_query = new \WP_Query([
				'fields'         => 'ids',
				'post_type'      => 'page',
				'posts_per_page' => - 1,
				'post_parent'    => $parent_page_id,
				'tax_query'      => [],
			]);
			
			$childs = $child_query->posts;
			
			if ($child_query->found_posts > 0) {
				
				$grandchild_query = new \WP_Query([
					'fields'          => 'ids',
					'post_type'       => 'page',
					'posts_per_page'  => - 1,
					'post_parent__in' => $child_query->posts,
				]);
				
				$childs = array_merge($childs, $grandchild_query->posts);
			}
		}
		
		return (isset($childs) and ! empty($childs)) ? $childs : [];
	}
}
