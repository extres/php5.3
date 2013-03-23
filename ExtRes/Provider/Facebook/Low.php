<?php

    namespace ExtRes\Drivers\Facebook;

    use \ExtRes\Drivers\Base;
    use \ExtRes\Token;

	/**
	 * Низкоуровневый интерфейс для доступа к данным Facebook
	 *
	 * Время жизни access_token 60 дней
	 *
	 * @version   1.0
	 * @copyright
	 * @see       http://www.rabota.ru/
	 * @author    Oleg Suvorkov <ovsu@rdw.ru>
	 * @issue     #4300
	 */
	class Low extends Base\LowAbstract
	{
		protected function getTokenCurl($code)
		{
			return $this->makeCurl()->
				setURL('https://graph.facebook.com/oauth/access_token')->
				setGet(array(
					'client_id'     => $this->applicationId,
					'redirect_uri'  => $this->redirectUrl,
					'client_secret' => $this->secretKey,
					'code'          => $code,
				));
		}

		protected function getTokenResultParse($data)
		{
			$result = array();
			parse_str($data, $result);
			if (isset($data['access_token'], $data['expires'])) {
				$token = new Token();
				$token->
					setAccess($result['access_token'])->
					setAccessExpires(mktime() + (integer) $result['expires']);
				return $token;
			}
			// TODO: Кидать исключение
		}

		public function getProfile(Token $token)
		{
			Assert::isIndexExists($this->configuration, 'profile_fields');
			$fields = $this->configuration['profile_fields'];
			return $this->getRequestAPI($token, 'me', array(
				'fields' => $fields,
			));
		}

		public function hasRefreshedToken()
		{
			return false;
		}

		protected function refreshToken(Token $token) {}

		protected function parseErrorAndThrowException($data)
		{
			if (is_array($data) === false) {
				$result = json_decode($data, true); // TODO: Иногда удобнее массив передавать
			}
			if (isset($result['error'])) {
				$error = $result['error'];
				if (isset($error['message'], $error['code'])) {
					// TODO: Логичнее завести отдельный класс для данных исключений
					throw new Exception((integer) $error['code'], $error['message']);
				}
			}
		}

		public function getUserId(Token $token)
		{
			$data = $this->getProfile($token);
			if (isset($data['id'])) {
				return (string) $data['id'];
			} else {
				throw new Exception(Exception::UNKNOWN);
			}
		}

		/**
		 * @param   Token   $token
		 * @param   string                  $method
		 * @param   array                   $parameters
		 * @param   integer                 $type
		 *
		 * @return  CurlUtils
		 */
		protected function getRequestAPICurl(Token $token, $method, array $parameters, $type)
		{
			$parameters+= array(
				'access_token' => $token->getAccess(),
				'locale'       => 'ru_RU',
			);
			$curl = $this->makeCurl()->
				setURL('https://graph.facebook.com/'.$method);
			if ($type == self::API_METHOD_TYPE_READ) {
				$curl->setGet($parameters);
			} elseif ($curl == self::API_METHOD_TYPE_WRITE) {
				$curl->setPost($parameters);
			}
			return $curl;
		}

		protected function getRequestAPIResultParse($data)
		{
			return json_decode($data, true);
		}
	}
