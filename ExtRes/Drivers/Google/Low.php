<?php

    namespace ExtRes\Drivers\Google;

    use \ExtRes\Drivers\Base;
    use \ExtRes\Token;

	/**
	 * Краткое описание
	 *
	 * Временный токен выдается на 1 час.
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
		 * @param $code
		 *
		 * @return CurlUtils
		 */
		protected function getTokenCurl($code)
		{
			return $this->makeCurl()->
				setURL('https://accounts.google.com/o/oauth2/token')->
				setPost(array(
					'code'          => $code,
					'client_id'     => $this->applicationId,
					'client_secret' => $this->secretKey,
					'redirect_uri'  => $this->redirectUrl,
					'grant_type'    => 'authorization_code',
				));
		}

		public function getProfile(Token $token)
		{
			return $this->getRequestAPI($token, 'userinfo');
		}

		/**
		 * @param   array $data
		 *
		 * @return  Token
		 */
		protected function getTokenResultParse($data)
		{
			$result = json_decode($data, true);
			if (isset(
				$result['access_token'],
				$result['expires_in']
			)) {
//				DebugUtils::dump($result);die;
				$token = new Token();
				$token->
					setTemporary($result['access_token'])->
					setTemporaryExpires(mktime() + (integer) $result['expires_in']);
				if (isset($result['id_token'])) { // По моему он выдается условно
					$token->
						setAccess($result['id_token'])->
						setAccessExpires(Token::EXPIRES_FOREVER); // TODO: ЭТО НЕ ТОЧНО! Узнать
				}
//				DebugUtils::dump($token);die;
				return $token;
			}
			// Кидать исключение подумать какое
		}

		public function hasRefreshedToken()
		{
			return true;
		}

		protected function refreshToken(Token $token) {}

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

		public function getUserId(Token $token)
		{
			$data = $this->getProfile($token);
			if (isset($data['id'])) {
				return $data['id'];
			}
			throw new ObjectNotFoundException;
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
			$curl = $this->makeCurl()->
				setURL('https://www.googleapis.com/oauth2/v1/'.$method);
			$parameters+= array(
				'access_token' => $token->getTemporary(),
			);
			if ($type == self::API_METHOD_TYPE_READ) {
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
