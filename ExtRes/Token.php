<?php

    namespace ExtRes;
	/**
	 * Маркер внешнего ресурса
	 *
	 * Используется для хранения в сессии и при экспорте из базы
	 *
	 * Ссылки где можно запретить приложению доступ (сброс маркера)
	 *
	 *   Google          https://accounts.google.com/b/0/IssuedAuthSubTokens
	 *   Mail.ru         http://my.mail.ru/cgi-bin/connect/view                нажать кнопку удалить
	 *   Яндекс          https://oauth.yandex.ru/list_tokens                   нажать на кнопку запретить
	 *   Facebook        https://www.facebook.com/appcenter/my                 навести на приложение и нажать на крестик
	 *   Одноклассники   TODO: Не смог найти, но где то есть точно
	 *   Twitter         TODO
	 *   ВКонтакте       http://vk.com/apps?act=settings                       списоки с заголовком "внешние сайты", заходим в настройку приложения, в открывшемся окне слева "удалить приложение"
	 *
	 * @version   1.0
	 * @copyright
	 * @see       http://www.rabota.ru/
	 * @author    Oleg Suvorkov <ovsu@rdw.ru>
	 * @issue     #4300
	 */
	class Token implements \Serializable
	{
		/**
		 * Был выдан offline token
		 *
		 * Он будет действителен до тех пор пока пользователь,
		 * не сменит пароль или не запретит доступ к приложению
		 * из личного кабинет внешнего ресурса
		 */
		const EXPIRES_FOREVER = 0;

		/**
		 * Маркер доступа
		 *
		 * @var string
		 */
		private $access         = null;

		/**
		 * Время истечения срока службы маркера доступа
		 *
		 * @var integer
		 */
		private $accessExpires  = null;

		/**
		 * @var string
		 */
		private $temporary        = null;

		/**
		 * Время истечения срока службы маркера обновления
		 *
		 * @var integer
		 */
		private $temporaryExpires = null;

		/**
		 * Абревиатура внешнего ресурса
		 *
		 * @var string
		 */
		private $abbreviation   = null;

		/**
		 * @var  string
		 */
		private $userKey        = null;

		/**
		 * Задать маркер доступа
		 *
		 * @param   string  $access
		 *
		 * @return  Token
		 */
		public function setAccess($access)
		{
			$this->access = $access;
			return $this;
		}

		/**
		 * Получить маркер доступа
		 *
		 * @return  string
		 */
		public function getAccess()
		{
			return $this->access;
		}

		/**
		 * Задать время истечения срока службы маркера доступа
		 *
		 * @param   integer $accessExpires
		 *
		 * @return  Token
		 */
		public function setAccessExpires($accessExpires)
		{
			$this->accessExpires = $accessExpires;
			return $this;
		}

		/**
		 * Полчить время истечения срока службы маркера доступа
		 *
		 * @return  integer
		 */
		public function getAccessExpires()
		{
			return $this->accessExpires;
		}

		/**
		 * Задать маркер обновления
		 *
		 * @param   string  $temporary
		 *
		 * @return  Token
		 */
		public function setTemporary($temporary)
		{
			$this->temporary = $temporary;
			return $this;
		}

		/**
		 * Получить маркер обновления
		 *
		 * @return  string
		 */
		public function getTemporary()
		{
			return $this->temporary;
		}

		/**
		 * Задать время истечения срока службы маркера обновления
		 *
		 * @param   integer $temporaryExpires
		 *
		 * @return  Token
		 */
		public function setTemporaryExpires($temporaryExpires)
		{
			$this->temporaryExpires = (integer) $temporaryExpires;
			return $this;
		}

		/**
		 * Полчить время истечения срока службы маркера обновления
		 *
		 * @return  integer
		 */
		public function getTemporaryExpires()
		{
			return $this->temporaryExpires;
		}


		/**
		 * Задать аббревиатуру внешнего ресурса
		 *
		 * @param   string  $abbreviation
		 *
		 * @return  Token
		 */
		public function setAbbreviation($abbreviation)
		{
			$this->abbreviation = $abbreviation;
			return $this;
		}

		/**
		 * Получить аббревиатуру на внешнем ресурсе
		 *
		 * @return string
		 */
		public function getAbbreviation()
		{
			return $this->abbreviation;
		}

		/**
		 * @param   string  $userKey
		 *
		 * @return  Token
		 */
		public function setUserKey($userKey)
		{
			$this->userKey = $userKey;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getUserKey()
		{
			return $this->userKey;
		}

		/**
		 * Истекло ли время жизни данного маркера
		 *
		 * @return  boolean
		 */
		public function isExpired()
		{
			return $this->isAccessExpired()
				|| $this->isTemporaryExpired();
		}

		/**
		 * Истекло ли время жизни токена доступа и задан ли он вообще
		 *
		 * @return  boolean
		 */
		public function isAccessExpired()
		{
//			DebugUtils::dump($this->accessExpires < (microtime() - 60));die;
			return $this->access === null
				|| (
					   $this->accessExpires !== self::EXPIRES_FOREVER
					&& $this->accessExpires < (microtime() - 60)
				);
		}

		/**
		 * Истекло ли время жизни маркера обновления и задан ли он вообще
		 *
		 * @return  boolean
		 */
		public function isTemporaryExpired()
		{
			return $this->temporary === null
				|| $this->temporaryExpires < (microtime() - 60);
		}

		/**
		 * Сериализация, для уменьшения объема данных в храниящихся в сессии
		 *
		 * @return  string
		 */
		public function serialize()
		{
			$data = array();
			$data[0] = $this->abbreviation;
			$data[1] = $this->access;
			$data[2] = $this->accessExpires;
			if ($this->temporary) {
				$data[3] = $this->temporary;
				$data[4] = $this->temporaryExpires;
			}
			if ($this->userKey) {
				$data[5] = $this->userKey;
			}
//			DebugUtils::dump($data);die;
			return serialize($data);
		}

		/**
		 * Сериализация, для уменьшения объема данных в храниящихся в сессии
		 *
		 * @param   string  $serialized
		 *
		 * @return  Token
		 */
		public function unserialize($serialized)
		{
			$data = unserialize($serialized);
			if (array_key_exists(0, $data)) {
				$this->abbreviation  = $data[0];
			}
			if (
				   array_key_exists(1, $data)
				&& array_key_exists(2, $data)
			) {
				$this->access        = $data[1];
				$this->accessExpires = $data[2];
			}
			if (
				   array_key_exists(3, $data)
				&& array_key_exists(4, $data)
			) {
				$this->temporary        = $data[3];
				$this->temporaryExpires = $data[4];
			}
			if (array_key_exists(5, $data)) {
				$this->userKey = $data[5];
			}
			return $this;
		}
	}
