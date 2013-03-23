<?php

    namespace ExtRes\Drivers;

    use \ExtRes\Drivers\Base;
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
	class YandexUnify extends Base\Unify
	{

		protected function formatBase(array $data)
		{
			if (isset($data['id'])) {
				$result['id'] = $data['id'];
			}
			if (isset($data['real_name'])) {
				$name = explode(' ', $data['real_name'], 2);
				$result['last_name']  = array_shift($name);
				$result['first_name'] = array_shift($name);
			}
			if (isset($data['default_email'])) {
				$result['email'] = $data['default_email'];
			}
			$result['profile'] = 'http://www.yandex.ru/';
			return $result;
		}

		protected function formatPhoneList(array $data)
		{
			return array();
		}

		protected function formatIsMale(array $data)
		{
			if (isset($data['sex'])) {
				if ($data['sex'] == 'male') {
					return true;
				} elseif ($data['sex'] == 'female') {
					return false;
				}
			}
			return null;
		}

		protected function formatBirthDay(array $data)
		{
			$result = array(
				'day'   => null,
				'month' => null,
				'year'  => null,
			);
			if (isset($data['birthday'])) {
				$date = explode('-', $data['birthday']);
				if (isset($date[0])) {
					$result['year']   = (integer) $date[0];
				}
				if (isset($date[1])) {
					$result['month'] = (integer) $date[1];
				}
				if (isset($date[2])) {
					$result['day']  = (integer) $date[2];
				}
			}
			return $result;
		}

		protected function formatPhoto(array $data)
		{
			return array();
		}

		protected function formatCity(array $data)
		{
			return null;
		}

		protected function formatUniversitiesList(array $data)
		{
			return array();
		}

		protected function formatUniversity(array $data)
		{
			return array();
		}
	}