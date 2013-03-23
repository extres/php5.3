<?php

    namespace ExtRes\Drivers\Base;
    use \ExtRes\Token;

	/**
	 * Базовый класс интерфейсов низкого уровня для доступа к внешним ресурсам
	 *
	 * @version   1.0
	 * @copyright
	 * @see       http://www.rabota.ru/
	 * @author    Oleg Suvorkov <ovsu@rdw.ru>
	 * @issue     #4300
	 */
	abstract class LowAbstract implements Low
	{
		/**
		 * Время жизни code в сек.
		 * Стандартом рекомендовано 10 минут
		 */
		const CODE_LIFETIME = 600; // 10 * 60
		/**
		 * @var string
		 */
		protected $applicationId = null;

		/**
		 * @var string
		 */
		protected $secretKey = null;

		/**
		 * @var array
		 */
		protected $configuration;
		protected $redirectUrl = null;

		public function __construct(array $configuration)
		{
			$this->configuration = $configuration;
			$this->initializeConfiguration();
		}

		/**
		 * Проверка и инициализация конфигураций
		 */
		protected function initializeConfiguration()
		{
			Assert::isIndexExists($this->configuration, 'app_id', 'Задайте `app_id`');
			Assert::isString($this->configuration['app_id'],      '`app_id` только строка');
			$this->applicationId = $this->configuration['app_id'];

			Assert::isIndexExists($this->configuration, 'secret', 'Задайте `secret`');
			Assert::isString($this->configuration['secret'],      '`secret` только строка');
			$this->secretKey     = $this->configuration['secret'];

			Assert::isIndexExists($this->configuration, 'redirect_uri', 'Задайте `secret`');
			Assert::isString($this->configuration['redirect_uri'],      '`secret` только строка');
			$this->redirectUrl   = $this->configuration['redirect_uri'];
		}

		public function getToken($code)
		{
			$curl = $this->getTokenCurl($code);
			$curl->exec();
			$data = $curl->getResult();
			$this->parseErrorAndThrowException($data);
			$token = $this->getTokenResultParse($data);
			// TODO: Тут нажна дополнительная обработка исключения
			return $token;
		}

		/**
		 * @param   string  $code
		 *
		 * @return  CurlUtils
		 */
		protected abstract function getTokenCurl($code);

		/**
		 * @param   array $data
		 *
		 * @return  Token
		 */
		protected abstract function getTokenResultParse($data);

		protected abstract function refreshToken(Token $token);

		/**
		 * @param   Token   $token
		 * @param   string                  $method
		 * @param   array                   $parameters
		 * @param   integer                 $type
		 *
		 * @return  CurlUtils
		 */
		protected abstract function getRequestAPICurl(Token $token, $method, array $parameters, $type);

		protected abstract function getRequestAPIResultParse($data);

		public function getRequestAPI(Token $token, $method, array $parameters = array(), $type = self::API_METHOD_TYPE_READ)
		{
			$this->checkTokenAndRefreshedIfExpired($token);
			$curl = $this->getRequestAPICurl($token, $method, $parameters, $type);
			$curl->exec();
			$result = $curl->getResult();
			return $this->getRequestAPIResultParse($result);
		}

		/**
		 * @return  CurlUtils
		 */
		protected function makeCurl()
		{
			$curl = new CurlUtils();
			$curl->
				setIncludeHeadersInOutput(false)->
				addOption(CURLOPT_SSL_VERIFYPEER, 0)->
				addOption(CURLOPT_SSL_VERIFYHOST, 0)->
				setProxy(CURL_PROXY, CURL_PROXY_USER_PASSWORD)->
//				setHeader('Accept','application/json')->
				setProxyType(CURL_PROXY_TYPE)->
				setHeader('Content-Type', 'application/x-www-form-urlencoded')
			;

			return $curl;
		}

		protected abstract function parseErrorAndThrowException($data);

		public function import(array $parameters)
		{
			if (isset($parameters['code'])) {
				return $parameters['code'];
			}
			// TODO: Кидать исключения
		}

		/**
		 * Проверить маркер и обновить если есть такая необходимость
		 *
		 * @param  Token $token
		 *
		 * @throws TokenException
		 */
		protected function checkTokenAndRefreshedIfExpired(Token $token)
		{
			if ($this->hasRefreshedToken()) {
				if ($token->isTemporaryExpired()) {
					if ($token->isAccessExpired()) {
						throw TokenException::lifeTimeExpired();
					}
					$this->refreshToken($token); // TODO: Тут могут быть исключения, если пользователь удалил приложение
				}
			} elseif ($token->isAccessExpired()) {
				throw TokenException::lifeTimeExpired();
			}
		}
	}
