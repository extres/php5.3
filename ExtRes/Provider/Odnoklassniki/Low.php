<?php

    namespace ExtRes\Drivers\Odnoklassniki;

    use \ExtRes\Drivers\Base;
    use \ExtRes\Token;

	/**
	 * Низкоуровневый интерфейс для доступа к данным Одноклассников
	 *
	 * @version   1.0
	 * @copyright
	 * @see       http://www.rabota.ru/
	 * @author    Oleg Suvorkov <ovsu@rdw.ru>
	 * @issue     #4300
	 */
	class Low extends Base\LowAbstract
	{
		/**
		 * Время жизни временного token'а (30 мин.)
		 *
		 * @var integer
		 */
		const LIFE_TIME_ACCESS_TOKEN  = 1800;
		/**
		 * Время жизни обновляемого token'а (30 дней.)
		 * (в последствии может быть увеличен до до нескольких месяцев)
		 *
		 * @var integer
		 */
		const LIFE_TIME_REFRESH_TOKEN = 2592000; // 30 дней

		private $publicKey;

		protected function initializeConfiguration()
		{
			parent::initializeConfiguration();
			Assert::isIndexExists($this->configuration, 'public', 'Задайте `public`');
			Assert::isString($this->configuration['public'],      '`public` только строка');
			$this->publicKey = $this->configuration['public'];
		}


		protected function getTokenCurl($code)
		{
			$data = array(
				'code'          => $code,
				'redirect_uri'  => $this->redirectUrl,
				'grant_type'    => 'authorization_code',
				'client_id'     => $this->applicationId,
				'client_secret' => $this->secretKey,
			);
			$data = http_build_query($data, '', '&');
			return $this->makeCurl()->
				setURL('http://api.odnoklassniki.ru/oauth/token.do')->
				setPost($data);
		}

		protected function getTokenResultParse($data)
		{
			$result = json_decode($data, true);
			if (isset($result['access_token'], $result['refresh_token'])) {
				$token = new Token();
				$token->
					setTemporary($result['access_token'])->
					setTemporaryExpires(mktime() + self::LIFE_TIME_ACCESS_TOKEN)->
					setAccess($result['refresh_token'])->
					setAccessExpires(mktime() + self::LIFE_TIME_REFRESH_TOKEN);
				return $token;
			}
			// TODO: Кидать исключение
		}

		public function getProfile(Token $token)
		{
			return $this->getRequestAPI($token, 'users.getCurrentUser');
		}

		protected function refreshToken(Token $token)
		{
			// TODO: Реализовать
		}

		protected function parseErrorAndThrowException($data)
		{
			// TODO: Корректная обработка ошибок
			$result = json_decode($data, true); // TODO: Иногда удобнее массив передавать
			if (isset($result['error'], $result['error_description'])) {
				$code    = $result['error'];
				$message = $result['error_description'];
				// TODO: Логичнее завести отдельный класс для данных исключений
				throw new Exception($code, $message);
			}
		}

		public function hasRefreshedToken()
		{
			return true;
		}

		/**
		 * Получить ключ пользователя во внешнем ресурсе
		 *
		 * @param Token $token
		 *
		 * @return string
		 */
		public function getUserId(Token $token)
		{
			$data = $this->getProfile($token);
			DebugUtils::dump()
			if (isset($data['uid'])) {
				return $data['uid'];
			}
			throw new ObjectNotFoundException;
		}

		private function buildSignature($apiMethod, Token $token)
		{
			$key  = md5($token->getTemporary().$this->secretKey);
			$base = 'application_key='.$this->publicKey.'method='.$apiMethod;
			return md5($base.$key);
		}

		/**
		 * @param   Token $token
		 * @param   string                $method
		 * @param   array                 $parameters
		 * @param   integer               $type
		 *
		 * @throws  WrongArgumentException
		 * @return  CurlUtils
		 */
		protected function getRequestAPICurl(Token $token, $method, array $parameters, $type)
		{
			$parameters+= array(
				'access_token'    => $token->getTemporary(),
				'application_key' => $this->publicKey,
				'method'          => $method,
				'sig'             => $this->buildSignature($method, $token)
			);
			$curl = $this->makeCurl()->
				setURL('http://api.odnoklassniki.ru/fb.do');
			if ($type === self::API_METHOD_TYPE_READ) {
				$curl->setGet($parameters);
			} elseif ($type == self::API_METHOD_TYPE_WRITE) {
				$curl->setPost($parameters);
			} else {
				throw new WrongArgumentException;
			}
			return $curl;
		}

		protected function getRequestAPIResultParse( $data )
		{
			return json_decode($data, true);
		}
	}
