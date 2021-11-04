<?php

namespace platz1de\EasyEdit\task\schematic;

use Closure;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\SchematicLoadDummy;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\thread\output\TaskResultData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\SchematicFileAdapter;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class SchematicLoadTask extends EditTask
{
	/**
	 * @param string       $player
	 * @param string       $schematicName
	 * @param Closure|null $finish
	 */
	public static function queue(string $player, string $schematicName, ?Closure $finish): void
	{
		if ($finish === null) {
			$finish = static function (TaskResultData $result) use ($player): void {
				ClipBoardManager::setForPlayer($player, $result->getChangeId());
			};
		}
		EditAdapter::queue(new QueuedEditTask(new SchematicLoadDummy($player, "", EasyEdit::getSchematicPath() . $schematicName), new Pattern([]), "", new Vector3(0, World::Y_MIN, 0), self::class, new AdditionalDataManager(false, true), new Vector3(0, 0, 0)), $finish);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_load";
	}

	/**
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void
	{
		/** @var SchematicLoadDummy $selection */
		Selection::validate($selection, SchematicLoadDummy::class);
		SchematicFileAdapter::readIntoSelection($selection->getPath(), $handler->getChanges());
	}

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @return DynamicBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $world, AdditionalDataManager $data): BlockListSelection
	{
		return new DynamicBlockListSelection($selection->getPlayer(), $place, $selection->getCubicStart(), $selection->getCubicEnd());
	}

	/**
	 * @param string                $player
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(string $player, float $time, string $changed, AdditionalDataManager $data): void
	{
		Messages::send($player, "blocks-copied", ["{time}" => (string) $time, "{changed}" => $changed]);
	}
}