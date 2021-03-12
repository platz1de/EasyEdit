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
	 * @return mixed
	 */
	public function getDataKeyed(string $key)
	{
		try {
			return $this->data[$key];
		} catch (Exception $e) {
			throw new UnexpectedValueException("Additional data with key " . $key . " does not exist");
		}
	}
}