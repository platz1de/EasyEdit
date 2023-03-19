<?php

namespace platz1de\EasyEdit\session;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class SessionIdentifier
{
	/**
	 * @param bool   $player
	 * @param string $name
	 */
	public function __construct(private bool $player, private string $name) {}

	public static function internal(string $name): SessionIdentifier
	{
		return new self(false, $name);
	}

	/**
	 * @return bool
	 */
	public function isPlayer(): bool
	{
		return $this->player;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putBool($this->player);
		$stream->putString($this->name);
		return $stream->getBuffer();
	}

	public static function fastDeserialize(string $data): SessionIdentifier
	{
		$stream = new ExtendedBinaryStream($data);
		return new SessionIdentifier($stream->getBool(), $stream->getString());
	}
}