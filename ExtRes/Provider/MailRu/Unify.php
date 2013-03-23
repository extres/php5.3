<?php

    namespace ExtRes\Drivers\MailRu;

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
			$result = array(
				'id'         => null,
				'first_name' => null,
				'last_name'  => null,
				'email'      => null,
				'profile'    => null,
			);
			if (isset($data['uid'])) {
				$result['id'] = $data['uid'];
			}
			if (isset($data['first_name'])) {
				$result['first_name'] = $data['first_name'];
			}
			if (isset($data['last_name'])) {
				$result['last_name'] = $data['last_name'];
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
			if (
				  isset(
					$data['pic_190'],
					$data['has_pic']
				)
				&& $data['has_pic']
			) {
				$result = $data['pic_190'];
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
				$date = explode('.', $data['birthday']);
				$result['day']   = (integer) array_shift($date);
				$result['month'] = (integer) array_shift($date);
				$result['year']  = (integer) array_shift($date);
			}
			return $result;
		}

		protected function formatIsMale(array $data)
		{
			if (isset($data['sex'])) {
				return $data['sex'] == 0;
			}
			return null;
		}

		protected function formatCity(array $data)
		{
			$city = null;
			if (isset(
				$data['location'],
				$data['location']['city'],
				$data['location']['city']['name']
			)) {
				$city = $this->findCity($data['location']['city']['name']);
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