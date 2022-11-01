<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\pattern\block\BlockType;
use platz1de\EasyEdit\pattern\block\SolidBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use pocketmine\math\Vector3;
use UnexpectedValueException;

class CommandFlagCollection
{
	/**
	 * @var CommandFlag[]
	 */
	private array $flags;

	public function addFlag(CommandFlag $flag): void
	{
		if (isset($this->flags[$flag->getName()])) {
			throw new UnexpectedValueException("Flag " . $flag->getName() . " already exists");
		}
		$this->flags[$flag->getName()] = $flag;
	}

	public function removeFlag(string $flag): void
	{
		if (!isset($this->flags[$flag])) {
			throw new UnexpectedValueException("Flag " . $flag . " does not exists");
		}
		unset($this->flags[$flag]);
	}

	public function getStringFlag(string $name): string
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof StringCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected String");
		}
		return $flag->getArgument();
	}

	public function getIntFlag(string $name): int
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof IntegerCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected Integer");
		}
		return $flag->getArgument();
	}

	public function getFloatFlag(string $name): float
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof FloatCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected Float");
		}
		return $flag->getArgument();
	}

	public function getVectorFlag(string $name): Vector3
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof VectorCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected Vector");
		}
		return $flag->getArgument();
	}

	public function getPatternFlag(string $name): Pattern
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof PatternCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected Pattern");
		}
		return $flag->getArgument();
	}

	public function getBlockFlag(string $name): BlockType
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof BlockCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected Block");
		}
		return $flag->getArgument();
	}

	public function getStaticBlockFlag(string $name): StaticBlock
	{
		$flag = $this->getBlockFlag($name);
		if (!$flag instanceof StaticBlock) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected ordinary Block");
		}
		return $flag;
	}

	public function getSessionFlag(string $name): Session
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof SessionCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected Session");
		}
		return $flag->getArgument();
	}

	public function hasFlag(string $name): bool
	{
		return isset($this->flags[$name]);
	}
}