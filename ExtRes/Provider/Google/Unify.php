<?php

    namespace ExtRes\Drivers\Google;

    use \ExtRes\Drivers\Base;

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
			if (isset($data['id'])) {
				$result['id'] = $data['id'];
			}
			if (isset($data['given_name'])) {
				$result['first_name'] = $data['given_name'];
			}
			if (isset($data['family_name'])) {
				$result['last_name'] = $data['family_name'];
			}
			if (isset($data['link'])) {
				$result['profile'] = $data['link'];
			}
			if (isset($data['email'])) {
				$result['email'] = $data['email'];
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
			if (isset($data['picture'])) {
				$result = $data['picture'];
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
				$birthday = explode('-', $data['birthday']);
				$result['year']  = (integer) array_shift($birthday);
				if ($result['year'] == 0) {
					$result['year'] = null;
				}
				$result['month'] = (integer) array_shift($birthday);
				$result['day']   = (integer) array_shift($birthday);
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