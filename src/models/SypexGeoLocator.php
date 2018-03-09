<?php
namespace Ignet\Wc\Fastery\Plugin;

/**
 * Class SypexGeoLocator
 * @package Ignet\Wc\Fastery\Plugin
 */
class SypexGeoLocator {
	
	/**
	 * @var \WP_Http
	 */
	private $wp_http;
	
	/**
	 * @var string
	 */
	private $api_url = 'https://api.sypexgeo.net/';
	
	/**
	 * @var string
	 */
	private $response_format = 'json';
	
	/**
	 * SypexGeoLocator constructor.
	 *
	 * @param \WP_Http $wp_http
	 */
	public function __construct(\WP_Http $wp_http) {
		
		$this->wp_http = $wp_http;
	}
	
	/**
	 * Возвращает информацию о местонахождении
	 *
	 * @return array|null
	 */
	public function get_location() {
		
		$user_ip = $this->get_user_ip();
		
		if (false == $this->is_bot() and false != $user_ip) {
			
			$response = $this->wp_http->request($this->api_url . $this->response_format . '/' . $user_ip, [
				'method' => 'POST',
			]);
			
			$location = (array) json_decode($response['body']);
			
			return $location;
		}
	}
	
	/**
	 * Проверяет является ли текущий пользователь ботом
	 *
	 * @return int
	 */
	private function is_bot() {
		
		$result = preg_match(
			"~(Google|Yahoo|Rambler|Bot|Yandex|Spider|Snoopy|Crawler|Finder|Mail|curl)~i",
			$_SERVER['HTTP_USER_AGENT']
		);
		
		return $result;
	}
	
	/**
	 * Возвращает ip адрес пользователя
	 */
	private function get_user_ip() {
		
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		return $ip ? $ip : false;
	}
}