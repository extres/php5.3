<?php

    namespace ExtRes\Drivers\Base;
    use \ExtRes\Token;

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
	abstract class Unify
	{
		protected $lowInterface;
		protected $token;

		public function __construct(Low $lowInterface, Token $token)
		{
			$this->lowInterface = $lowInterface;
			$this->token        = $token;
		}

		public function getData(array $data)
		{
			$profile = array(
				'id'         => null,
				'first_name' => null,
				'last_name'  => null,
				'email'      => null,
				'profile'    => null,
			);
			$profile                 = $this->formatBase($data) + $profile;
			$profile['is_male']      = $this->formatIsMale($data);
			$profile['phones']       = $this->formatPhoneList($data);
			$profile['bdate']        = $this->formatBirthDay($data);
			$profile['photo']        = $this->formatPhoto($data);
			$profile['city']         = $this->formatCity($data);
			$profile['universities'] = $this->formatUniversitiesList($data);
			return $profile;
		}

		protected abstract function formatBase(array $data);

		protected abstract function formatPhoneList(array $data);

		protected abstract function formatPhoto(array $data);

		protected abstract function formatBirthDay(array $data);

		protected abstract function formatIsMale(array $data);

		protected abstract function formatCity(array $data);

		protected abstract function formatUniversitiesList(array $data);

		protected abstract function formatUniversity(array $data);

		protected function findCity($name)
		{
			try {
				// TODO: Может однажды, это перевести на Sphinx, Что бы более адекватно получать id региона
				$region = DAO::v3_region()->getCitiesByFirstLetters(Suggester::switchKeyboardLayout('en', 'ru', $name));
				$region = array_shift($region);
				if ($region instanceof Identifiable) {
					return $region->getId();
				}
			} catch (ObjectNotFoundException $e) {
			}
			return null;
		}
	}