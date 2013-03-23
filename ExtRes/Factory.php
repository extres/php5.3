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
	class Factory
	{
		private $config;
		private $abbreviation;
		private $low = null;
		private $unify = null;

		/**
		 * @param string    $abbreviation
		 * @param array     $config
		 */
		public function __construct($abbreviation, array $config)
		{
			$this->abbreviation = $abbreviation;
			$this->config       = $config;
			// TODO: Может вынести проверку наличия необходимых классов сюда
		}

		/**
		 * @return ProviderBaseLow
		 *
		 * @throws ObjectNotFoundException
		 * @throws Exception
		 */
		public function getLow()
		{
			try {
				if ($this->low === null) {
					Assert::isIndexExists($this->config, 'provider');
					$className = $this->config['provider'];
					Assert::classExists($className);
					Assert::isInstance($className, 'ExternalResourceLow'); /* @see ExternalResourceLow */
					$this->low = new $className($this->config);
				} elseif ($this->low === false) { // TODO: А может быть и не нужно
					throw new ObjectNotFoundException();
				}
			} catch (Exception $e) {
				if (self::isDebug()) {
					throw $e;
				} else {
					throw new ObjectNotFoundException(
						$e->getMessage(),
						$e->getCode(),
						null,
						null,
						$e
					);
				}
			}
			return $this->low;
		}

		/**
		 * @param Token $token
		 *
		 * @return ProviderBaseUnify
		 * @throws ObjectNotFoundException
		 * @throws Exception
		 */
		public function getUnify(Token $token)
		{
			try {
				if ($this->unify === null) {
					Assert::isIndexExists($this->config, 'unify');
					$className = $this->config['unify'];
					Assert::classExists($className);
					Assert::isInstance($className, 'ExternalResourceUnify'); /* @see ExternalResourceUnifyProfileData */
					$this->unify = new $className($this->getLow(), $token);
				} elseif ($this->unify === false) { // TODO: А может быть и не нужно
					throw new ObjectNotFoundException();
				}
			} catch (Exception $e) {
				if (self::isDebug()) {
					throw $e;
				} else {
					throw new ObjectNotFoundException(
						$e->getMessage(),
						$e->getCode(),
						null,
						null,
						$e
					);
				}
			}
			return $this->unify;
		}

		private static function isDebug()
		{
			return defined('LOCAL_DEBUG')
				&& LOCAL_DEBUG;
		}
	}