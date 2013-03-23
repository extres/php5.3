<?php

    namespace ExtRes\Drivers\Yandex;

    use \ExtRes\Drivers\Base;
    use \ExtRes\Token;

	/**
	 * Краткое описание
	 *
	 * Срок службы token зависит оп запрошенных прав
	 *
	 * @version   1.0
	 * @copyright
	 * @see       http://www.rabota.ru/
	 * @author    Oleg Suvorkov <ovsu@rdw.ru>
	 * @issue     #4300
	 */
	class Low extends Base\LowAbstract
	{
		public function getProfile(Token $token)
		{
			return $this->getRequestAPI($token, 'info');
		}

		/**
		 * @param $code
		 *
		 * @return CurlUtils
		 */
		protected function getTokenCurl($code)
		{
			return $this->makeCurl()->
				setURL('https://oauth.yandex.ru/token')->
				setPost(array(
					'grant_type'    => 'authorization_code',
					'code'          => $code,
					'client_id'     => $this->applicationId,
					'client_secret' => $this->secretKey,
				));
		}

		/**
		 * Коды ошибок при запросе токена
		 *
		 * @see http://api.yandex.ru/oauth/doc/dg/reference/obtain-access-token.xml#access-token-response
		 *
		 * invalid_request        ― неверный формат запроса.
		 * invalid_grant          ― неверный (пльзователь запретил доступ к своим данным из кабинета)
		 * или просроченный код подтверждения.
		 * unsupported_grant_type ― неверное значение параметра grant_type.
		 *
		 * @param $data
		 *
		 * @return null
		 */
		protected function getTokenResultParse($data)
		{
			$result = json_decode($data, true);
			if (isset($result['access_token'])) {
				if (
					isset($result['expires_in'])
					&& $result['expires_in']
				) {
					$expires = mktime() + (integer) $result['expires_in'];
				} else {
					$expires = Token::EXPIRES_FOREVER;
				}
				$token = new Token();
				$token->
					setAccess($result['access_token'])->
					setAccessExpires($expires);
				return $token;
			}
			// TODO: Кидать исключения
		}

		public function hasRefreshedToken()
		{
			return false;
		}

		protected function refreshToken(Token $token) {}

		public function getUserId(Token $token)
		{
			$data = $this->getProfile($token);
			if (isset($data['id'])) {
				return $data['id'];
			}
			throw new Exception(Exception::UNKNOWN);
		}

		protected function parseErrorAndThrowException($data)
		{
			$data = json_decode($data, true);
			if (isset($data['error'])) {
				throw new Exception($data['error']);
			}
		}

		protected function makeCurl()
		{
			return parent::makeCurl()->
				setProtocol(CurlUtils::PROTOCOL_HTTPS);
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
		protected function getRequestAPICurl(Token $token, $method, array $parameters, $type)		{
			$parameters+= array(
				'format'      => 'json',
				'oauth_token' => $token->getAccess(),
			);
			$curl = $this->makeCurl()->
				setURL('https://login.yandex.ru/'.$method);
			if ($type === self::API_METHOD_TYPE_READ) {
				$curl->setGet($parameters);
			} elseif ($type === self::API_METHOD_TYPE_WRITE) {
				$curl->setPost($parameters);
			} else {
				throw new WrongArgumentException; // TODO: Может ввести тип ошибки
			}
			return $curl;
		}

		protected function getRequestAPIResultParse( $data )
		{
			return json_decode($data, true);
		}
	}
