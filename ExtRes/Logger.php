<?php

    namespace ExtRes;
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
	class Logger
	{
		private $logger;

		public function __construct(ExternalResourceEnumeration $enumeration, $operation)
		{
			Assert::isString($operation);
			$abbreviation = $enumeration->getAbbreviation();
			$this->logger = new UniversalLogger('extres', $abbreviation);
		}

		/**
		 * @param string $request       запрос используемый для авторизации
		 * @param string $code          код ошибки
		 * @param string $description   описание ошибки
		 */
		public function invalidResponse($request, $code, $description)
		{
			$this->logger->setError(true);
			$this->logger->write(implode("\t", array(
				$request,
				$code,
				$description,
			)));
		}
	}
