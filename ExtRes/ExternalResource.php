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
	class ExternalResource
	{
		const SESSION_KEY_STATE = 'ExternalResourceState';
		const SESSION_KEY_TOKEN = 'ExternalResourceToken';

		const REQUEST_RESOURCE_KEY = 'resource';
		const REQUEST_STATE_KEY    = 'state';

		/**
		 * Максимальное количество хранящихся в сесси связей state и редиректов
		 */
		const MAX_COUNT_STATE = 5;

		private $config;
		private $relStateToRedirect = null;

		private static $instance = null;

		private function __construct() {}

		public function import(array $request)
		{
			$abbreviation = $this->getAbbreviation($request);
			return $this->factory($abbreviation)->getLow();
		}

		public function getProfile()
		{
			$token        = $this->getToken();
			$abbreviation = $token->getAbbreviation();
			$factory      = $this->factory($abbreviation);
			$resource     = $factory->getLow();
			$data         = $resource->getProfile($token);
			$data         = $factory->getUnify($token)->getData($data);
			return $data;
		}

		public function getExternalKey()
		{
			return $this->getFactory()->getLow()->getUserId($this->getToken());
		}

		public function getInnerRedirectUrl(array $request)
		{
			$abbreviation = $this->getAbbreviation( $request );
			$resource     = $this->import( $request );
			$code         = $resource->import( $request );
			$token        = $resource->getToken( $code );
			$token->setAbbreviation( $abbreviation );
			$this->rememberToken( $token );
			if (isset($request[self::REQUEST_STATE_KEY])) {
				$state = $request[self::REQUEST_STATE_KEY];
			} else {
				$state = null;
			}
			return $this->getByState($state);
		}

		public function makeAuthorizeUrl(array $request)
		{
			$resource = $this->import($request);
			if (
				   $resource instanceof ProviderTwitterLow
				&& isset($request[self::REQUEST_STATE_KEY])
			) {
				$resource->setState($request[self::REQUEST_STATE_KEY]);
				return $resource->makeAuthorizeUrl();
			}
			throw new WrongArgumentException;
		}

		public function getResourcesList($requestUri, $refererUri)
		{
			$state = $this->makeStateBy($requestUri, $refererUri);
			$resourcesList = ExternalResourceEnumeration::getList();
			foreach ($resourcesList as $resource) {
				$abbreviation = $resource->getAbbreviation();
				$config       = $this->getConfig($abbreviation);
				$config      += array( // TODO: Может есть вариант получше
					self::REQUEST_STATE_KEY => $state,
				);
				$resource->setConfig($config);
			}
			return $resourcesList;
		}

		/**
		 * @return Token
		 * @throws ObjectNotFoundException
		 */
		public function getToken()
		{
			if (Session::isStarted() === false) {
				Session::start();
			}
			$token = Session::get(self::SESSION_KEY_TOKEN);
			if ($token) {
				return $token;
			}
			throw new ObjectNotFoundException;
		}

		public function hasAuthorizationData()
		{
			try {
				$this->getToken();
				return true;
			} catch (ObjectNotFoundException $e) {
				return false;
			}
		}

		/**
		 * @param  string $abbreviation
		 *
		 * @throws WrongArgumentException
		 *
		 * @return Factory
		 */
		public function factory($abbreviation)
		{
			$config = $this->getConfig($abbreviation);
			return new Factory($abbreviation, $config);
		}

		public function getPersonToExternalResource()
		{
			$data = $this->getProfile();
			$token = $this->getToken();
			if ($token->getUserKey() === null) {
				$token->setUserKey($data['id']);
			}
			return DAO::v3_PersonToExternalResource()->makeObjectByTokenAndUnifiedArray($data, $token);
		}


		private function getFactory()
		{
			$token        = $this->getToken();
			$abbreviation = $token->getAbbreviation();
			return $this->factory($abbreviation);
		}

		private function getAbbreviation(array $request)
		{
			if (isset($request[self::REQUEST_RESOURCE_KEY])) {
				return $request[self::REQUEST_RESOURCE_KEY];
			}
			throw new WrongArgumentException;
		}

		private function getRelState()
		{
			if ($this->relStateToRedirect === null) {
				$this->relStateToRedirect = Session::get(self::SESSION_KEY_STATE);
				if ($this->relStateToRedirect === null) {
					$this->relStateToRedirect = array();
				}
			}
			return $this->relStateToRedirect;
		}

		private function generateState()
		{
			return substr(md5(microtime().mt_rand()), 0, 5);
		}

		private function makeStateBy($redirectUrl, $refererUri)
		{
			$this->getRelState(); // TODO: КАРЯВО
			$data = array(
				$redirectUrl,
				$refererUri
			);
			$key = array_search($data, $this->relStateToRedirect);
			if ($key === false) {
				$key = $this->generateState();
				if (count($this->relStateToRedirect) >= self::MAX_COUNT_STATE) {
					array_shift($this->relStateToRedirect);
				}
				$this->relStateToRedirect[$key] = $data;
				$this->rememberState();
			}
			return $key;
		}

		private function getByState($state)
		{
			$this->getRelState(); // TODO: КАРЯВО
			if (isset($this->relStateToRedirect[$state])) {
				return $this->relStateToRedirect[$state];
			} elseif (count($this->relStateToRedirect)) {
				$state = array_shift($this->relStateToRedirect);
				$this->rememberState();
				return $state;
			} else {
				throw new ObjectNotFoundException;
			}
		}

		private function rememberToken(Token $token)
		{
			if (Session::isStarted() === false) {
				Session::start();
			}
			Session::assign(self::SESSION_KEY_TOKEN, $token);
		}

		private function dropToken()
		{
			if (Session::isStarted() === false) {
				Session::start();
			}
			Session::drop(self::SESSION_KEY_TOKEN);
		}

		private function getConfig($abbreviation)
		{
			$this->initConfig();
			if (
				   isset($this->config[$abbreviation])
				&& is_array($this->config[$abbreviation])
			) {
				$this->config[$abbreviation]+= array( // TODO: Может есть вариант получше
					'redirect_uri' => $this->getRedirectUrl($abbreviation),
				);
				return $this->config[$abbreviation];
			}
			throw new ObjectNotFoundException;
		}

		private function initConfig()
		{
			if ($this->config === null) {
				$path = EXTRES_CONFIG_PATH;
				if (is_readable($path)) {
					$configuration = @include $path;
					if (is_array($configuration)) {
						$this->config = $configuration;
						return;
					}
				}
				throw new WrongArgumentException('Файл конфигураций не существует, не доступен для чтения либо не содержит требуемые данный');
			}
		}

		private function rememberState()
		{
			if (Session::isStarted() === false) {
				Session::start();
			}
			Session::assign(self::SESSION_KEY_STATE, $this->relStateToRedirect);
		}


		private function getRedirectUrl($abbreviation)
		{
			return FrontOfficeModule::makeAbsUrl(EXTRES_ENDPOINT_MODULE, array(
				self::REQUEST_RESOURCE_KEY => $abbreviation,
			));
		}

		/**
		 * @return self
		 */
		public static function me()
		{
			if (self::$instance === null) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}
