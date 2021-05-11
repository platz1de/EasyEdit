<?php

namespace platz1de\EasyEdit\utils;

use Exception;
use UnexpectedValueException;

class AdditionalDataManager
{
	/**
	 * @var array
	 */
	private $data;

	/**
	 * AdditionalDataManager constructor.
	 * @param array $data
	 */
	public function __construct(array $data = [])
	{
		$this->data = $data;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getDataKeyed(string $key, $default = null)
	{
		try {
			return $this->data[$key];
		} catch (Exception $e) {
			if ($default !== null) {
				return $default;
			}
			throw new UnexpectedValueException("Additional data with key " . $key . " does not exist");
		}
	}

	/**
	 * @param string     $key
	 * @param mixed|null $data
	 */
	public function setDataKeyed(string $key, $data = null): void
	{
		$this->data[$key] = $data;
	}
}