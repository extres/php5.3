<?php

    namespace ExtRes\Drivers\Twitter;

    use \ExtRes\Drivers\Base;
    use \ExtRes\Token;

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
	class Low implements Base\Low
	{
		const URL_AUTHORIZE = 'https://api.twitter.com/oauth/authorize';

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
		protected $accessToken = null;
		protected $accessTokenExpires = null;
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

		/**
		 * Получить token пользователя
		 *
		 * @param  Token $token
		 *
		 * @return string
		 */
		public function getToken($token)
		{
			Assert::isInstance($token, 'ExternalResourceToken');
			$parameters = $this->arrayExtend($token->getAccess(), array(
				'oauth_token',
				'oauth_verifier',
			));
			$response = $this->httpRequest(
				'https://api.twitter.com/oauth/access_token',
				'GET',
				$parameters
			);
			$data = array();
			parse_str($response, $data);
			$token = new Token();
			$token->setUserKey($data['user_id']);
			unset($data['user_id']);
			$token->
				setAccess($data)->
				setAccessExpires(Token::EXPIRES_FOREVER);
			return $token;
			// TODO: Кидаем исключение
		}

		/**
		 * Получить массив данных из профиля пользователя
		 *
		 * @param Token $token
		 *
		 * @return array
		 */
		public function getProfile(Token $token)
		{
			$parameters = $this->arrayExtend($token->getAccess(), array(
				'oauth_token',
				'oauth_token_secret',
			));
			$response = $this->httpRequest(
				'https://api.twitter.com/1/account/verify_credentials.json',
				'GET',
				$parameters
			);
			return json_decode($response, true);
		}

		/**
		 * TODO: Кастыль подумать как выпилить
		 *
		 * @param  string $state
		 * @return $this
		 */
		public function setState($state)
		{
			if (strrpos($this->redirectUrl, '?')) {
				$this->redirectUrl.= '&';
			} else {
				$this->redirectUrl.= '?';
			}
			$this->redirectUrl.= 'state='.$state;
			return $this;
		}

		/**
		 * Получить ключ пользователя во внешнем ресурсе
		 *
		 *
		 * @param Token $token
		 *
		 * @return string
		 */
		public function getUserId(Token $token)
		{
			return $token->getUserKey();
		}

		public function getOAuthToken()
		{
			$parameters = array(
				'oauth_callback' => $this->redirectUrl,
			);
			$response = $this->httpRequest(
				'https://api.twitter.com/oauth/request_token',
				'GET',
				$parameters
			);
			$data = array();
			parse_str($response, $data);
			$token = new Token();
			$token->setAccess($data);
			return $token;
			// TODO: Кидаем исключение
		}

		/**
		 * @param       $url
		 * @param       $method
		 * @param array $parameters
		 *
		 * @return string
		 */
		private function httpRequest($url, $method, array $parameters = array())
		{
			if (isset($parameters['oauth_token_secret'])) {
				$oAuthTokenSecret = $parameters['oauth_token_secret'];
				unset($parameters['oauth_token_secret']);
			} else {
				$oAuthTokenSecret = null;
			}
			$parameters+= array(
				'oauth_consumer_key'     => $this->applicationId,
				'oauth_nonce'            => md5(microtime().mt_rand()),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_timestamp'        => time(),
				'oauth_version'          => '1.0',
			);
			uksort($parameters, 'strcmp');
			$parameters['oauth_signature'] = $this->buildSignature($url, $method, $parameters, $oAuthTokenSecret);
			$url.= '?'.$this->httpBuildQuery($parameters);
			$curl = $this->makeCurl()->
				setURL($url);
			$curl->exec();
			return $curl->getResult();
		}

		private function buildSignature($url, $method, array $parameters, $oauthTokenSecret = null)
		{
			$base = self::urlEncodeRFC3986($method).
				'&'.self::urlEncodeRFC3986($url).
				'&'.self::urlEncodeRFC3986($this->httpBuildQuery($parameters));

			$key  = self::urlEncodeRFC3986($this->secretKey).
				'&'.self::urlEncodeRFC3986($oauthTokenSecret);

			return base64_encode(hash_hmac('sha1', $base, $key, true));
		}

		private function makeCurl()
		{
			$curl = new CurlUtils();
			$curl->
				addOption(CURLOPT_USERAGENT, 'TwitterOAuth v0.2.0-beta2')->
				addOption(CURLOPT_CONNECTTIMEOUT, 30)->
				addOption(CURLOPT_TIMEOUT, 30)->
				addHeader('Expect:')->
				addOption(CURLOPT_SSL_VERIFYPEER, false)->
				setProxy(CURL_PROXY, CURL_PROXY_USER_PASSWORD)->
				setProxyType(CURL_PROXY_TYPE)->
				addOption(CURLOPT_HEADER, false);
			return $curl;
		}

		public function import(array $parameters)
		{

			if (isset(
				$parameters['oauth_token'],
				$parameters['oauth_verifier']
			)) {
				$token = new Token();
				$token->setAccess($parameters);
				return $token;
			}
		}

		public function makeAuthorizeUrl()
		{
			$token      = $this->getOAuthToken();
			$access     = $token->getAccess();
			$oAuthToken = $access['oauth_token'];
			return self::URL_AUTHORIZE.'?oauth_token='.$oAuthToken;
		}

		public function hasRefreshedToken()
		{
			return false;
		}

		// @{ Вспомогательные функции
		/**
		 * Упрощенный аналог http_build_query, основанный на
		 * @see ExternalResourceTwitterLow::urlEncodeRFC3986
		 *
		 * @param   array   $parameters
		 *
		 * @return  string
		 */
		private static function httpBuildQuery(array $parameters)
		{
			foreach ($parameters as $field => &$value) {
				$value = self::urlEncodeRFC3986($field).'='.self::urlEncodeRFC3986($value);
			}
			return implode('&', array_values($parameters));
		}

		/**
		 * Спицифичный URL Encode основнный на RFC3986,
		 * с небольшими изменениями для Twitter
		 *
		 * @param   string  $string
		 *
		 * @return  string
		 */
		private static function urlEncodeRFC3986($string)
		{
			$data = array(
				'+'   => ' ',
				'%7E' => '~',
			);
			$string = rawurlencode($string);
			return str_replace(array_keys($data), array_values($data), $string);
		}

		/**
		 * TODO: Дать нормальное название
		 *
		 * @param array $array
		 * @param array $keys
		 *
		 * @throws WrongArgumentException
		 *
		 * @return array
		 */
		private static function arrayExtend(array $array, array $keys)
		{
			$keys   = array_fill_keys($keys, null);
			$result = array_intersect_key($array, $keys);
			$result = array_filter($result, 'strlen');
			if (count($keys) < count($result)) {
				$undefinedParameters = array_diff_key($keys, $result);
				$undefinedParameters = implode(', ', array_keys($undefinedParameters));
				// TODO: Дать ошибке код
				throw new WrongArgumentException($undefinedParameters);
			}
			return $result;
		}
		// }@
	}
