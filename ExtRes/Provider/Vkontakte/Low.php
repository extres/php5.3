<?php

    namespace ExtRes\Drivers\Vkontakte;

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
				setURL('https://oauth.vk.com/access_token')->
				setGet(array(
					'client_id'     => $this->applicationId,
					'redirect_uri'  => $this->redirectUrl,
					'client_secret' => $this->secretKey,
					'code'          => $code,
				));
		}

		protected function getTokenResultParse($data)
		{
			$result = json_decode($data, true);
			$this->parseErrorAndThrowException($result);
			if (isset(
					$result['access_token'],
					$result['user_id']
			)) {
				$token = new Token();
				if (isset($result['expires_in'])) {
					if ($result['expires_in']) {
						$expires = mktime() + (integer) $result['expires_in'];
					} else {
						$expires = Token::EXPIRES_FOREVER;
					}
					$token->
						setAccess($result['access_token'])->
						setAccessExpires($expires)->
						setUserKey((string) $result['user_id']);
				}
				return $token;
			}
			// TODO: Кидать исключения
		}

		protected function refreshToken(Token $token) {}

		public function getProfile(Token $token)
		{
			Assert::isIndexExists($this->configuration, 'profile_fields');
			$fields = $this->configuration['profile_fields'];
			$result = $this->getRequestAPI($token, 'users.get', array(
				'uids'   => $token->getUserKey(),
				'fields' => $fields,
			));
			return array_shift($result);
		}

		public function getCityName(Token $token, $cityId)
		{
			Assert::isInteger($cityId);
			$response = $this->getRequestAPI($token, 'places.getCityById', array(
				'cids' => $cityId,
			));
			if (is_array($response)) {
				$response = array_shift($response);
				if (isset($response['name'])) {
					return $response['name'];
				}
			}
			throw new Exception(Exception::UNKNOWN);
		}

		protected function parseErrorAndThrowException($data)
		{
			if (is_array($data) === false) {
				$result = json_decode($data, true); // TODO: Иногда удобнее массив передавать
			} else {
				$result = $data;
			}
			if (isset(
				$result['error'],
				$result['error_description']
			)) {
				$code    = $result['error'];
				$message = $result['error_description'];
				// TODO: Логичнее завести отдельный класс для данных исключений
				throw new Exception($code, $message);
			}
		}

		public function hasRefreshedToken()
		{
			return false;
		}

		public function getUserId(Token $token)
		{
			$this->checkTokenAndRefreshedIfExpired($token);
			return $token->getUserKey();
		}

		/**
		 * @param   Token $token
		 * @param   string                $method
		 * @param   array                 $parameters
		 * @param   integer               $type
		 *
		 * @throws WrongArgumentException
		 * @return  CurlUtils
		 */
		protected function getRequestAPICurl(Token $token, $method, array $parameters, $type)
		{
			$parameters+= array(
				'access_token' => $token->getAccess(),
			);
			$curl = $this->makeCurl()->
				setURL('https://api.vk.com/method/'.$method);
			if ($type === self::API_METHOD_TYPE_READ) {
				$curl->setGet($parameters);
			} elseif ($type === self::API_METHOD_TYPE_WRITE) {
				$curl->setPost($parameters);
			} else {
				throw new WrongArgumentException;
			}
			return $curl;
		}

		protected function getRequestAPIResultParse($data)
		{
			$result = json_decode($data, true);
			if (
				   is_array($result)
				&& isset($result['response'])
			) {
				$result = $result['response'];
			} else {
				$this->parseErrorAndThrowException($result);
				$result = null;
			}
			if ($result === null) {
				throw new Exception(Exception::UNKNOWN);
			}
			return $result;
		}
	}
