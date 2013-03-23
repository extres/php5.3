<?php

    namespace ExtRes;

	/**
	 * Краткое описание
	 *
	 * Полное описание
	 *
	 * @version   1.0
	 * @copyright
	 * @see       http://www.rabota.ru/
	 * @author    Oleg Suvorkov <ovsu@rdw.ru>
	 * @issue     #4300
	 */
	class Enumeration implements \Serializable
	{
		// @{ НЕ МЕНЯТЬ ЗНАЧЕНИЯ!!! ОНИ ИСПОЛЬЗУЮТСЯ В БАЗЕ.
		const VK            = 1;
		const FACEBOOK      = 2;
		const ODNOKLASSNIKI = 3;
		const YANDEX        = 4;
		const MAIL          = 5;
		const GOOGLE        = 6;
		const TWITTER       = 7;
		const LINKEDIN      = 8;
		// }@

		/**
		 * @var array
		 */
		private $config = null;

		/**
		 * @var Enumeration[]
		 */
		private static $stack = array();

		/**
		 * @var array
		 */
		public static $list  = array(
			self::VK            => array(
				'name'          => 'ВКонтакте',
				'auth_url'      => 'https://oauth.vk.com/authorize?client_id={{app_id}}&scope={{scope}}&redirect_uri={{redirect_uri|urlencode}}&response_type=code&state={{state}}',
			),
			self::FACEBOOK      => array(
				'name'          => 'Facebook',
				'auth_url'      => 'http://www.facebook.com/dialog/oauth?client_id={{app_id}}&redirect_uri={{redirect_uri|urlencode}}&scope={{scope}}&state={{state}}',
			),
			self::YANDEX        => array(
				'name'          => 'Yandex',
				'auth_url'      => 'https://oauth.yandex.ru/authorize?response_type=code&client_id={{app_id}}&state={{state}}',
			),
			self::ODNOKLASSNIKI => array(
				'name'          => 'Одноклассники',
				'auth_url'      => 'http://www.odnoklassniki.ru/oauth/authorize?client_id={{app_id}}&scope={{scope}}&response_type=code&redirect_uri={{redirect_uri|urlencode}}&state={{state}}',
			),
			self::MAIL          => array(
				'name'          => 'Mail.ru',
				'auth_url'      => 'https://connect.mail.ru/oauth/authorize?client_id={{app_id}}&response_type=code&redirect_uri={{redirect_uri|urlencode}}&state={{state}}',
			),
			self::GOOGLE        => array(
				'name'          => 'Google',
				'auth_url'      => 'https://accounts.google.com/o/oauth2/auth?client_id={{app_id}}&response_type=code&scope={{scope}}&access_type=offline&redirect_uri={{redirect_uri|urlencode}}',
			),
			self::TWITTER       => array(
				'name'          => 'Twitter',
				'auth_url'      => '{{auth_url}}state={{state}}',
			)
		);

		private static $relIdToAbbreviation = array(
			self::VK            => 'vkontakte',
			self::FACEBOOK      => 'facebook',
			self::YANDEX        => 'yandex',
			self::ODNOKLASSNIKI => 'odnoklassniki',
			self::MAIL          => 'mailru',
			self::GOOGLE        => 'google',
			self::TWITTER       => 'twitter',
		);

		public function __construct($id)
		{
			if (isset(self::$list[$id])) {
				$this->id = (integer) $id;
			} else {
				throw new MissingElementException("knows nothing about such id == {$id}");
			}
		}

		public function getName()
		{
			return self::$list[$this->id]['name'];
		}

		public function getNameList()
		{
			return self::$list;
		}

		/**
		 * Получить класс с средним изображение социальной сети
		 *
		 * @return string
		 */
		public function getStandardImageClass()
		{
			return $this->getAbbreviation();
		}

		public function getAbbreviation()
		{
			if (isset(self::$relIdToAbbreviation[$this->id])) {
				return self::$relIdToAbbreviation[$this->id];
			}
			throw new ObjectNotFoundException;
		}

		/**
		 * Получить URL для авторизации
		 *
		 * @return  string
		 */
		public function makeAuthorizationUrl()
		{
			if (isset(self::$list[$this->id]['auth_url'])) {
				$authUrlTemplate = self::$list[$this->id]['auth_url'];
				$markerList      = $this->getMarkerList();
				return str_replace(array_keys($markerList), array_values($markerList), $authUrlTemplate);
			} else {
				return '#undefined';
			}
		}

		private function getMarkerList()
		{
			$config = $this->config;
			unset($config['secret']);
			$markerList = array();
			foreach ($config as $key => $value) {
				$marker = '{{'.$key.'}}';
				$markerList[$marker] = $value;
				$marker = '{{'.$key.'|urlencode}}';
				$markerList[$marker] = urlencode($value);
			}
			return $markerList;
		}

		public function setConfig(array $config)
		{
			$this->config = $config;
			return $this;
		}

		public function serialize()
		{
			return serialize($this->id);
		}

		public function unserialize($serialized)
		{
			$id = unserialize($serialized);
			return self::getById($id);
		}

		public static function getRelAbbreviationToIdList()
		{
			return array_flip(self::$relIdToAbbreviation);
		}

		/**
		 * Получить сущность социальной сети по её `id`
		 *
		 * @param  integer  $id
		 *
		 * @return ExternalResourceEnumeration
		 *
		 * @throws ObjectNotFoundException
		 */
		public static function getById($id)
		{
			if (isset(self::$list[$id])) {
				if (self::$list[$id] instanceof static == false) {
					self::$stack[$id] = new static($id);
				}
				return self::$stack[$id];
			}
			throw new ObjectNotFoundException();
		}

		/**
		 * Получить сущность социальной сети по её аббревиатруре
		 *
		 * @param  string  $abbreviation
		 *
		 * @return ExternalResourceEnumeration
		 *
		 * @throws ObjectNotFoundException
		 */
		public static function getByAbbreviation($abbreviation)
		{
			$id = array_search($abbreviation, self::$relIdToAbbreviation);
			if ($id !== false) {
				return self::getById($id);
			}
			throw new ObjectNotFoundException();
		}

		/**
		 * Получить список сущностей социальных сетей
		 *
		 * @return ExternalResourceEnumeration[]
		 */
		public static function getList()
		{
			$keys = array_keys(self::$list);
			foreach ($keys as $id) {
				if (isset(self::$stack[$id]) === false) {
					self::$stack[$id] = new static($id);
				}
			}
			return self::$stack;
		}
	}
