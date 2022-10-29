<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use Generator;
use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\flags\CommandArgumentFlag;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FloatCommandFlag;
use platz1de\EasyEdit\command\flags\FloatValueCommandFlag;
use platz1de\EasyEdit\command\flags\IntCommandFlag;
use platz1de\EasyEdit\command\flags\SetValueCommandFlag;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class BrushCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/brush", [KnownPermissions::PERMISSION_BRUSH], ["/br"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$nbt = CompoundTag::create()->setString("brushType", BrushHandler::identifierToName($flags->getIntFlag("type")));
		switch ($flags->getIntFlag("type")) {
			case BrushHandler::BRUSH_SPHERE:
				$nbt->setFloat("brushSize", $flags->getFloatFlag("size"));
				$nbt->setString("brushPattern", $flags->hasFlag("gravity") ? "gravity(" . $flags->getStringFlag("pattern") . ")" : $flags->getStringFlag("pattern"));
				break;
			case BrushHandler::BRUSH_SMOOTH:
				$nbt->setFloat("brushSize", $flags->getFloatFlag("size"));
				break;
			case BrushHandler::BRUSH_NATURALIZE:
				$nbt->setFloat("brushSize", $flags->getFloatFlag("size"));
				$nbt->setString("topBlock", $flags->getStringFlag("top"));
				$nbt->setString("middleBlock", $flags->getStringFlag("middle"));
				$nbt->setString("bottomBlock", $flags->getStringFlag("bottom"));
				break;
			case BrushHandler::BRUSH_CYLINDER:
				$nbt->setFloat("brushSize", $flags->getFloatFlag("size"));
				$nbt->setShort("brushHeight", $flags->getIntFlag("height"));
				$nbt->setString("brushPattern", $flags->hasFlag("gravity") ? "gravity(" . $flags->getStringFlag("pattern") . ")" : $flags->getStringFlag("pattern"));
				break;
			case BrushHandler::BRUSH_PASTE:
				$nbt->setByte("isInsert", $flags->hasFlag("insert") ? 1 : 0);
		}
		$item = VanillaItems::WOODEN_SHOVEL()->setNamedTag($nbt);
		$lore = [];
		foreach ($nbt->getValue() as $name => $value) {
			$lore[] = $name . ": " . $value;
		}
		$item->setLore($lore);
		$item->setCustomName(TextFormat::GOLD . "Brush");
		$session->asPlayer()->getInventory()->setItem($session->asPlayer()->getInventory()->getHeldItemIndex(), $item);
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		//TODO: Find a way to tidy this up
		return [
			"sphere" => new SetValueCommandFlag("type", BrushHandler::BRUSH_SPHERE, ["sph"], "s"),
			"smooth" => new SetValueCommandFlag("type", BrushHandler::BRUSH_SMOOTH, ["smoothing"], "o"),
			"naturalize" => new SetValueCommandFlag("type", BrushHandler::BRUSH_NATURALIZE, ["nat", "naturalized"], "n"),
			"cylinder" => new SetValueCommandFlag("type", BrushHandler::BRUSH_CYLINDER, ["cyl", "cy"], "c"),
			"paste" => new SetValueCommandFlag("type", BrushHandler::BRUSH_PASTE, ["pasting"], "p"),

			"size" => new FloatCommandFlag("size", ["radius", "rad"], "r"), //sphere, smooth, naturalize, cylinder
			"pattern" => new StringCommandFlag("pattern", [], "f"), //sphere, cylinder
			"gravity" => new SingularCommandFlag("gravity", [], "g"), //sphere, cylinder
			"height" => new IntCommandFlag("height", [], "h"), //cylinder
			"top" => new StringCommandFlag("top", [], "t"), //naturalize
			"middle" => new StringCommandFlag("middle", [], "m"), //naturalize
			"bottom" => new StringCommandFlag("bottom", [], "b"), //naturalize
			"insert" => new SingularCommandFlag("insert", [], "i") //paste
		];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (!$flags->hasFlag("type")) {
			yield new SetValueCommandFlag("type", $type = BrushHandler::nameToIdentifier($args[0] ?? ""));
			array_shift($args); //This will break if the user does specify the type twice (flag and argument)
		} else {
			$type = $flags->getIntFlag("type");
		}

		if ($type === BrushHandler::BRUSH_PASTE) {
			if (!$flags->hasFlag("insert") && ArgumentParser::parseBool(false, $args[0] ?? null)) {
				yield new SingularCommandFlag("insert");
			}
			return;
		}

		if (!$flags->hasFlag("size")) {
			yield new FloatValueCommandFlag("size", (float) ($args[0] ?? 5.0));
		}
		switch ($type) {
			/** @noinspection PhpMissingBreakStatementInspection */
			case BrushHandler::BRUSH_CYLINDER:
				if (!$flags->hasFlag("height")) {
					yield new SetValueCommandFlag("height", (int) ($args[1] ?? 3));
				}
				array_shift($args);
			case BrushHandler::BRUSH_SPHERE:
				if (!$flags->hasFlag("pattern")) {
					try {
						yield new CommandArgumentFlag("pattern", PatternParser::validateInput($args[1] ?? "stone", $session->asPlayer()));
					} catch (ParseError $exception) {
						throw new PatternParseException($exception);
					}
				}
				if (!$flags->hasFlag("gravity") && ArgumentParser::parseBool(false, $args[3] ?? null)) {
					yield new SingularCommandFlag("gravity");
				}
				break;
			case BrushHandler::BRUSH_NATURALIZE:
				try {
					if (!$flags->hasFlag("top")) {
						yield new CommandArgumentFlag("top", PatternParser::validateInput($args[1] ?? "grass", $session->asPlayer()));
					}
					if (!$flags->hasFlag("middle")) {
						yield new CommandArgumentFlag("middle", PatternParser::validateInput($args[2] ?? "dirt", $session->asPlayer()));
					}
					if (!$flags->hasFlag("bottom")) {
						yield new CommandArgumentFlag("bottom", PatternParser::validateInput($args[3] ?? "stone", $session->asPlayer()));
					}
				} catch (ParseError $exception) {
					throw new PatternParseException($exception);
				}
		}
	}
}