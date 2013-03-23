<?php

    namespace ExtRes\Drivers\MailRu;

    use \ExtRes\Drivers\Base;
    use \ExtRes\Token;

	/**
	 * Низкоуровневый интерфейс для доступа к данным Facebook
	 *
	 * Время жизни access_token 60 дней
	 *
	 * Спецификацию для access_token смотри тут
	 * @see http://api.mail.ru/docs/guides/oauth/client-credentials/
	 * Нектороые данные для работы с API
	 * @see http://api.mail.ru/docs/guides/restapi/#params
	 * Коды ошибок
	 * @see http://api.mail.ru/docs/reference/rest/users-getinfo/#errors
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
			$data = array(
				'client_id'     => $this->applicationId,
				'client_secret' => $this->secretKey,
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => $this->redirectUrl,
			);
			$data = http_build_query($data, '', '&');
			return $this->makeCurl()->
				setURL('https://connect.mail.ru/oauth/token')->
				setPost($data);
		}

		protected function getTokenResultParse($data)
		{
			$result = json_decode($data, true);
			if (isset(
				$result['access_token'],
				$result['expires_in'],
				$result['x_mailru_vid'],
				$result['refresh_token']
			)) {
				$token = new Token();
				$token->
					setTemporary($result['access_token'])->
					setTemporaryExpires(mktime() + (integer) $result['expires_in'])->
					setAccess($result['refresh_token'])->
					setAccessExpires(Token::EXPIRES_FOREVER)->
					setUserKey($result['x_mailru_vid']);
				return $token;
			}
			// TODO: Кидаем исключение
		}

		public function getProfile(Token $token)
		{
			$result = $this->getRequestAPI($token, 'users.getInfo', array(
				'uids' => $token->getUserKey(),
			));
			return array_shift($result);
		}

		protected function refreshToken(Token $token) {}

		protected function parseErrorAndThrowException($data)
		{
			$result = json_decode($data, true); // TODO: Иногда удобнее массив передавать
			if (isset($result['error'])) {
				$error = $result['error'];
				if (isset(
					$error['message'],
					$error['code']
				)) {
					throw new Exception((integer) $error['code'], $error['message']);
				} else {
					throw new Exception($result['error'], (string) $result['error']);
				}
			}
		}

		public function hasRefreshedToken()
		{
			return true;
		}

		public function getUserId(Token $token)
		{
			$this->checkTokenAndRefreshedIfExpired($token);
			return $token->getUserKey();
		}

		private function buildSignature(array $data, Token $token)
		{
			$params = '';
			ksort($data);
			foreach ($data as $key => $value) {
				$params.= $key.'='.$value;
			}
			return md5($token->getUserKey().$params.$this->secretKey);
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
				'app_id'      => $this->applicationId,
				'format'      => 'json',
				'method'      => $method,
				'session_key' => $token->getTemporary(),
			);
			$parameters['sig'] = $this->buildSignature($parameters, $token);
			$curl = $this->makeCurl()->
				setURL('http://www.appsmail.ru/platform/api');
			if ($type === self::API_METHOD_TYPE_READ) {
				$curl->setGet($parameters);
			} elseif ($type === self::API_METHOD_TYPE_WRITE) {
				$curl->setPost($parameters);
			}
			return $curl;
		}

		protected function getRequestAPIResultParse($data)
		{
			return json_decode($data, true);
		}
	}
