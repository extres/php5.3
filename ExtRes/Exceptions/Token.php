<?php

    namespace ExtRes\Exceptions;

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
	class Token extends Exception
	{
		const LIFETIME_EXPIRED = 16;
		const UNDEFINED_TOKEN  = 17;

		public static function lifeTimeExpired($message = null)
		{
			return new self($message, self::LIFETIME_EXPIRED);
		}

		public static function undefinedToken($message = null)
		{
			return new self($message, self::UNDEFINED_TOKEN);
		}
	}
