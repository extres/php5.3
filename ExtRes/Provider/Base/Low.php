<?php

    namespace ExtRes\Drivers\Base;
    use \ExtRes\Token;

	/**
	 * Интерфейс для доступа к данным пользователя на внешнем ресурсе
	 *
	 * Приблизительная схема работы модуля
	 * 1. При отображении viewHelper генерируется параметр state уникаьный для пары referer_uri и request_uri
	 * 2. В сессии сохраняется отношение state => {referer_uri, request_uri}
	 * 3. Параметр state передается в auth_url конкретного ресурса
	 * 4. При возврате с внешнего ресурса, получаем code и параметр state
	 * 5. По code получаем token сохраняем его в сессии и делаем redirect на uri полученному из state.
	 * 6. Далее выполняются действия соответствующие данному контроллеру
	 *
	 * @version   1.0
	 * @copyright
	 * @see       http://www.rabota.ru/
	 * @author    Oleg Suvorkov <ovsu@rdw.ru>
	 * @issue     #4300
	 */
	interface Low
	{
		const API_METHOD_TYPE_READ  = 1;
		const API_METHOD_TYPE_WRITE = 2;
		/**
		 * Был выдан offline token
		 *
		 * Он будет действителен до тех пор пока пользователь,
		 * не сменит пароль или не запретит доступ к приложению
		 * из личного кабинет внешнего ресурса
		 */
		const EXPIRES_FOREVER = 0;

		public function __construct(array $configuration);

		/**
		 * Получить token пользователя
		 *
		 * @param  string $code
		 *
		 * @return Token
		 */
		public function getToken($code);

		/**
		 * Получить массив данных из профиля пользователя
		 *
		 * @param   Token  $token
		 *
		 * @return  array
		 */
		public function getProfile(Token $token);

		/**
		 * Получить ключ пользователя во внешнем ресурсе
		 *
		 * @param   Token   $token
		 *
		 * @return  string
		 */
		public function getUserId(Token $token);

		/**
		 * @param   array   $parameters
		 *
		 * @return  string
		 */
		public function import(array $parameters);

		/**
		 * Использует ли ресурс обновляемый маркер
		 *
		 * @return boolean
		 */
		public function hasRefreshedToken();
	}
