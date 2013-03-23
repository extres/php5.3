<?php

    namespace ExtRes\Drivers\Vkontakte;

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
	 *
	 * @property VkontakteLow $lowInterface
	 */
	class Unify extends Base\Unify
	{
		protected function formatBase(array $data)
		{
			if (isset($data['uid'])) {
				$result['id'] = $data['uid'];
			}
			if (isset($data['first_name'])) {
				$result['first_name'] = $data['first_name'];
			}
			if (isset($data['last_name'])) {
				$result['last_name'] = $data['last_name'];
			}
			if (isset($result['screen_name'])) {
				$result['profile'] = 'https://vk.com/'.$result['screen_name'];
			} elseif (isset($data['uid'])) {
				$result['profile'] = 'https://vk.com/id'.$data['uid'];
			}
			return $result;
		}

		protected function formatPhoneList(array $data)
		{
			$result = array();
			if (isset($data['mobile_phone'])) {
				try {
					$result[] = PhoneUtils::parse($data['mobile_phone']);
				} catch (WrongArgumentException $e) {}
			}
			if (isset($data['home_phone'])) {
				try {
					$result[] = PhoneUtils::parse($data['home_phone']);
				} catch (WrongArgumentException $e) {}
			}
			return $result;
		}

		protected function formatPhoto(array $data)
		{
			$result = null;
			if (
				   isset($data['photo_max_orig'])
				&& $data['photo_max_orig'] != 'https://vk.com/images/camera_a.gif'
			) {
				$result = $data['photo_max_orig'];
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
			if (isset($data['bdate'])) {
				$date = explode('.', $data['bdate']);
				$result['day']   = (integer) array_shift($date);
				$result['month'] = (integer) array_shift($date);
				$result['year']  = (integer) array_shift($date);
			}
			return $result;
		}

		protected function formatIsMale(array $data)
		{
			if (isset($data['sex'])) {
				if ($data['sex'] == 2) {
					return true;
				} elseif ($data['sex'] == 1) {
					return false;
				}
			}
			return null;
		}

		protected function formatCity(array $data)
		{
			$city = null;
			if (isset($data['city'])) {
				try {
					$city = $this->lowInterface->getCityName($this->token, $data['city']);
					$city = $this->findCity($city);
				} catch (ObjectNotFoundException $e) {
					$city = null;
				} catch (Exception $e) {
					$city = null;
				} catch (TokenException $e) {
					$city = null;
				}
			}
			return $city;
		}

		protected function formatUniversitiesList(array $data)
		{
			$result = array();
			if (
				isset($data['universities'])
				&& is_array($data['universities'])
			) {
				foreach ($data['universities'] as $university) {
					$result[] = $this->formatUniversity($university);
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
			if (isset($data['name'])) {
				$result['name'] = $data['name'];
			}
			if (isset($data['chair_name'])) {
				$result['chair'] = $data['chair_name'];
			}
			if (isset($data['graduation'])) {
				$result['graduation']['to'] = $data['graduation'];
			}
			return $result;
		}
	}