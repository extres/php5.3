<?php

    namespace ExtRes\Drivers\Facebook;
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

    use \ExtRes\Drivers\Base;

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
			if (isset($data['id'])) {
				$result['id'] = $data['id'];
			}
			if (isset($data['first_name'])) {
				$result['first_name'] = $data['first_name'];
			}
			if (isset($data['last_name'])) {
				$result['last_name'] = $data['last_name'];
			}
			if (isset($data['email'])) {
				$result['email'] = $data['email'];
			}
			if (isset($data['link'])) {
				$result['profile'] = $data['link'];
			}
			return $result;
		}

		protected function formatPhoneList(array $data)
		{
			return array();
		}

		protected function formatIsMale(array $data)
		{
			if (isset($data['gender'])) {
				if ($data['gender'] == 'мужской') {
					return true;
				} elseif ($data['gender'] == 'женский') {
					return false;
				}
			}
			return null;
		}

		protected function formatBirthday(array $data)
		{
			$result = array(
				'day'   => null,
				'month' => null,
				'year'  => null,
			);
			if (isset($data['birthday'])) {
				$date = explode('/', $data['birthday']);
				$result['month'] = array_shift($date);
				$result['day']   = array_shift($date);
				$result['year']  = array_shift($date);
			}
			return $result;
		}

		protected function formatPhoto(array $data)
		{
			$photo = null;
			if (
				isset(
				$data['id'],
				$data['picture'],
				$data['picture']['data'],
				$data['picture']['data']['url']
				)
				&& strpos($data['picture']['data']['url'], $data['id'])
			) {
				$photo = 'http://graph.facebook.com/'.$data['id'].'/picture?type=large';
			}
			return $photo;
		}

		protected function formatCity(array $data)
		{
			$city = null;
			if (isset(
				$data['location'],
				$data['location']['name']
			)) {
				$city = $this->findCity($data['location']['name']);
			}

			if(
				   $city === null
				&& isset(
					$data['hometown'],
					$data['hometown']['name']
				)
			) {
				$city = $this->findCity($data['hometown']['name']);
			}
			return $city;
		}

		protected function formatUniversitiesList(array $data)
		{
			$result = array();
			if (
				isset($data['education'])
				&& is_array($data['education'])
			) {
				foreach ($data['education'] as $education) {
					$result[] = $this->formatUniversity($education);
				}
			}
			return $result;
		}

		protected function formatUniversity(array $data)
		{
			$result = array(
				'name'       => null,
				'chair'      => null,
				'graduation' => array(
					'form' => null,
					'to'   => null,
				),
			);
			if (isset(
			$data['school'],
			$data['school']['name']
			)) {
				$result['name'] = $data['school']['name'];
			}
			if (isset(
			$data['concentration'],
			$data['concentration'][0],
			$data['concentration'][0]['name']
			)) {
				$result['chair'] = $data['concentration'][0]['name'];
			}
			if (isset(
			$data['year'],
			$data['year']['name']
			)) {
				$result['graduation']['to'] = $data['year']['name'];
			}
			return $result;
		}
	}