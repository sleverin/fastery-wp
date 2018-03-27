<?php
namespace Ignet\WP\Dev_Toolkit;

/**
 * Class DataHelper
 * @package Ignet\WP\Dev_Toolkit
 */
class DataHelper {
	
	/**
	 * Фильтрация WP_Query по не пустым мета полям
	 * Для использования добавить фильтр "posts_where" перед запросом и добавить параметры:
	 *  meta_key = 'ключ'
	 *  meta_compare = 'NOT EMPTY'
	 * После запроса рекомендуется убрать фильтр через remove_filter()
	 *
	 * @param string $where
	 * @param WP_Query $query
	 *
	 * @return string $where
	 */
	public static function filter_empty_meta($where, $query) {
		
		if (strtoupper($query->get('meta_compare')) == 'NOT EMPTY') {
			
			global $wpdb;
			$where .= " AND (" . $wpdb->postmeta . ".meta_key = '" . $query->get('meta_key') . "')" . " AND ( TRIM(IFNULL(" . $wpdb->postmeta . ".meta_value, '')) <> '' )";
		}
		
		return $where;
	}
	
	/**
	 * Регистрация загруженного в uploads файла как объект Медиафайл в БД
	 *
	 * @param string $filename
	 * @param bool $parent_post_id
	 *
	 * @return int|WP_Error $attachment_id
	 */
	public static function insert_attachment($filename, $parent_post_id = false) {
		
		// Проверим тип поста, который мы будем использовать в поле 'post_mime_type'.
		$filetype = wp_check_filetype(basename($filename), null);
		
		// Получим путь до директории загрузок.
		$wp_upload_dir = wp_upload_dir();
		
		// Подготовим массив с необходимыми данными для вложения.
		$attachment = [
			'guid'           => $wp_upload_dir['url'] . '/' . basename($filename),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];
		
		// Вставляем запись в базу данных.
		$attachment_id = wp_insert_attachment($attachment, $filename, $parent_post_id);
		
		// Подключим нужный файл, если он еще не подключен
		// wp_generate_attachment_metadata() зависит от этого файла.
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		
		// Создадим метаданные для вложения и обновим запись в базе данных.
		$attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
		wp_update_attachment_metadata($attachment_id, $attach_data);
		
		return is_wp_error($attachment_id) ? false : $attachment_id;
	}
	
	/**
	 * Получение пути файла из /uploads/ по url
	 *
	 * @param string $url
	 *
	 * @return string $path
	 */
	public static function get_upload_path($url) {
		
		$path = wp_upload_dir()['basedir'] . explode('uploads', $url)[1];
		
		return $path;
	}
}