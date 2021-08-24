<?php

namespace platz1de\EasyEdit\utils;

use Throwable;
use UnexpectedValueException;

class AdditionalDataManager
{
	/**
	 * @var array<string, mixed>
	 */
	private $data;

	/**
	 * AdditionalDataManager constructor.
	 * @param array<string, mixed> $data
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
	public function getDataKeyed(string $key, mixed $default = null): mixed
	{
		try {
			return $this->data[$key];
		} catch (Throwable $e) {
			if ($default !== null) {
				return $default;
			}
			throw new UnexpectedValueException("Additional data with key " . $key . " does not exist");
		}
	}

	/**
	 * @param string $key
	 * @param bool   $default
	 * @return bool
	 */
	public function getBoolKeyed(string $key, bool $default = false): bool
	{
		return (bool) $this->getDataKeyed($key, $default);
	}

	/**
	 * @param string     $key
	 * @param mixed|null $data
	 */
	public function setDataKeyed(string $key, mixed $data = null): void
	{
		$this->data[$key] = $data;
	}
}