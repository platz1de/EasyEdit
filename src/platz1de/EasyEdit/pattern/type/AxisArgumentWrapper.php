<?php

namespace platz1de\EasyEdit\pattern\type;

class AxisArgumentWrapper
{
	private bool $xAxis = false;
	private bool $yAxis = false;
	private bool $zAxis = false;

	/**
	 * @param string[] $args
	 * @return AxisArgumentWrapper
	 */
	public static function parse(array &$args): AxisArgumentWrapper
	{
		$class = new self();
		foreach ($args as $i => $arg) {
			if (!(bool) preg_match('/[^xyz]/', $arg)) {
				if (str_contains($arg, "x")) {
					$class->xAxis = true;
				}
				if (str_contains($arg, "y")) {
					$class->yAxis = true;
				}
				if (str_contains($arg, "z")) {
					$class->zAxis = true;
				}
				unset($args[$i]);
			}
		}
		$args = array_values($args);
		return $class;
	}

	/**
	 * @return bool
	 */
	public function getXAxis(): bool
	{
		return $this->xAxis;
	}

	/**
	 * @return bool
	 */
	public function getYAxis(): bool
	{
		return $this->yAxis;
	}

	/**
	 * @return bool
	 */
	public function getZAxis(): bool
	{
		return $this->zAxis;
	}
}