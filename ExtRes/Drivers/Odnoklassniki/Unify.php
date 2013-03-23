<?php

    namespace ExtRes\Drivers\Odnoklassniki;

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
	class Unify extends Base\Unify
	{
		protected function formatBase(array $data)
		{
			$result = array();
			if (isset($data['uid'])) {
				$result['id'] = $data['uid'];
			}
			if (isset($data['first_name'])) {
				$result['first_name'] = $data['first_name'];
			}
			if (isset($data['last_name'])) {
				$result['last_name'] = $data['last_name'];
			}
			if (isset($data['uid'])) {
				$result['profile'] = 'http://www.odnoklassniki.ru/profile/'.$data['uid'];
			}
			return $result;
		}

		protected function formatPhoneList(array $data)
		{
			return array();
		}

		protected function formatPhoto(array $data)
		{
			$result = null;
			if (
				isset($data['pic_2'])
				&& strpos($data['pic_2'], '.odnoklassniki.ru/res/stub_') === null
			) {
				$result = $data['pic_2'];
			}
			return $result;
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
				$result['year']  = (integer) array_shift($date);
				$result['month'] = (integer) array_shift($date);
				$result['day']   = (integer) array_shift($date);
			}
			return $result;
		}

		protected function formatIsMale(array $data)
		{
			if (isset($data['gender'])) {
				if ($data['gender'] == 'male') {
					return true;
				} elseif ($data['gender'] == 'female') {
					return false;
				}
			}
			return null;
		}

		protected function formatCity(array $data)
		{
			$city = null;
			if (isset(
				$data['location'],
				$data['location']['city']
			)) {
				$city = $this->findCity($data['location']['city']);
			}
			return $city;
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