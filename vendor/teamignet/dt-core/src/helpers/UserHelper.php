<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Class UserHelper
 *
 * Вспомогательные методы для работы с пользователем
 *
 * @package Ignet\WP\Dev_Toolkit
 */
class UserHelper {
	
	/**
	 * Проверка наличия роли у пользователя
	 *
	 * @param array|string $roles
	 * @param \WP_User|int|bool $user
	 *
	 * @return bool
	 */
	public static function is_role_in($roles, $user = false) {
		
		if ( ! $user) {
			$user = wp_get_current_user();
		}
		
		if (is_numeric($user)) {
			$user = get_userdata($user);
		}
		
		if (empty($user->ID) or empty($roles)) {
			return false;
		}
		
		if ( ! is_array($roles)) {
			$roles = [$roles];
		}
		
		foreach ($roles as $role) {
			if (in_array($role, $user->roles)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Возможность авторизации по user_email
	 *
	 * Для использования удалить и установить следующие хуки:
	 *
	 * remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
	 * add_filter('authenticate', [UserHelper::class, 'user_email_auth'], 20, 3);
	 *
	 * @param \WP_User $user
	 * @param string $username
	 * @param string $password
	 *
	 * @return false|\WP_Error|\WP_User
	 */
	public static function user_email_auth($user, $username, $password) {
		
		if (is_a($user, 'WP_User')) {
			return $user;
		}
		
		if ( ! empty($username)) {
			
			$username = str_replace('&', '&amp;', stripslashes($username));
			$user = get_user_by('email', $username);
			
			if (isset($user, $user->user_login, $user->user_status) && 0 == (int) $user->user_status) {
				$username = $user->user_login;
			}
		}
		
		return wp_authenticate_username_password(null, $username, $password);
	}
	
	/**
	 * Изменяет user_nicename, nickname и display_name при изменении user_email
	 * Для отображения актуальной почты при использовании её в качестве логина и никнейма
	 *
	 * Для использования установить на хук:
	 *
	 * add_action('update_user_meta', [UserHelper::class, 'sync_user_email_names'], 10, 4);
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param string $_meta_value
	 */
	public static function sync_user_email_names($meta_id, $object_id, $meta_key, $_meta_value) {
		
		if ($meta_key == 'user_email') {
			
			$user = get_userdata($object_id);
			$user->user_nicename = $_meta_value;
			$user->nickname = $_meta_value;
			$user->display_name = $_meta_value;
			
			wp_update_user($user);
		}
	}
}