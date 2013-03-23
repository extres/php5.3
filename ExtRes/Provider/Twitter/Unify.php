<?php

    namespace ExtRes\Drivers\Twitter;

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
			if (isset($data['name'])) {
				$name = explode(' ', $data['name']);
				$result['first_name'] = trim(array_shift($name));
				$result['last_name'] = trim(array_shift($name));
			}
			if (isset($data['screen_name'])) {
				$result['profile'] = 'https://twitter.com/'.$data['screen_name'];
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
			if (isset($data['profile_image_url'])) {
				$result = str_replace('_normal', '', $data['profile_image_url']);
			}
			return $result;
		}

		protected function formatBirthDay(array $data)
		{
			return array(
				'day'   => null,
				'month' => null,
				'year'  => null,
			);
		}

		protected function formatIsMale(array $data)
		{
			return null;
		}

		protected function formatCity(array $data)
		{
			$city = null;
			if (isset($data['location'])) {
				$city = $this->findCity($data['location']);
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