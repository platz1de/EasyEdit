<?php

namespace platz1de\EasyEdit;

use platz1de\EasyEdit\command\CommandManager;
use platz1de\EasyEdit\command\defaults\clipboard\CopyCommand;
use platz1de\EasyEdit\command\defaults\clipboard\FlipCommand;
use platz1de\EasyEdit\command\defaults\clipboard\LoadSchematicCommand;
use platz1de\EasyEdit\command\defaults\clipboard\PasteCommand;
use platz1de\EasyEdit\command\defaults\clipboard\RotateCommand;
use platz1de\EasyEdit\command\defaults\clipboard\SaveSchematicCommand;
use platz1de\EasyEdit\command\defaults\generation\CubeCommand;
use platz1de\EasyEdit\command\defaults\generation\CylinderCommand;
use platz1de\EasyEdit\command\defaults\generation\NoiseCommand;
use platz1de\EasyEdit\command\defaults\generation\SphereCommand;
use platz1de\EasyEdit\command\defaults\history\HistoryAccessCommand;
use platz1de\EasyEdit\command\defaults\movement\ThruCommand;
use platz1de\EasyEdit\command\defaults\movement\UnstuckCommand;
use platz1de\EasyEdit\command\defaults\movement\UpCommand;
use platz1de\EasyEdit\command\defaults\selection\AliasedContextCommand;
use platz1de\EasyEdit\command\defaults\selection\CountCommand;
use platz1de\EasyEdit\command\defaults\selection\ExtendCommand;
use platz1de\EasyEdit\command\defaults\selection\ExtinguishCommand;
use platz1de\EasyEdit\command\defaults\selection\FirstPositionCommand;
use platz1de\EasyEdit\command\defaults\selection\MoveCommand;
use platz1de\EasyEdit\command\defaults\selection\NaturalizeCommand;
use platz1de\EasyEdit\command\defaults\selection\OverlayCommand;
use platz1de\EasyEdit\command\defaults\selection\ReplaceCommand;
use platz1de\EasyEdit\command\defaults\selection\SecondPositionCommand;
use platz1de\EasyEdit\command\defaults\selection\SetCommand;
use platz1de\EasyEdit\command\defaults\selection\SmoothCommand;
use platz1de\EasyEdit\command\defaults\selection\StackCommand;
use platz1de\EasyEdit\command\defaults\selection\ViewCommand;
use platz1de\EasyEdit\command\defaults\utility\BenchmarkCommand;
use platz1de\EasyEdit\command\defaults\utility\BlockInfoCommand;
use platz1de\EasyEdit\command\defaults\utility\BrushCommand;
use platz1de\EasyEdit\command\defaults\utility\BuilderRodCommand;
use platz1de\EasyEdit\command\defaults\utility\CancelCommand;
use platz1de\EasyEdit\command\defaults\utility\DrainCommand;
use platz1de\EasyEdit\command\defaults\utility\FillCommand;
use platz1de\EasyEdit\command\defaults\utility\HelpCommand;
use platz1de\EasyEdit\command\defaults\utility\LineCommand;
use platz1de\EasyEdit\command\defaults\utility\PasteStatesCommand;
use platz1de\EasyEdit\command\defaults\utility\StatusCommand;
use platz1de\EasyEdit\command\defaults\utility\WandCommand;
use platz1de\EasyEdit\command\FlagRemapAlias;
use platz1de\EasyEdit\command\flags\IntegerCommandFlag;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\listener\DefaultEventListener;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\CompoundTile;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\world\clientblock\Registry;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;

class EasyEdit extends PluginBase
{
	private static EasyEdit $instance;

	public function onEnable(): void
	{
		self::$instance = $this;

		if (!is_dir(self::getSchematicPath()) && !mkdir(self::getSchematicPath(), 0777, true) && !is_dir(self::getSchematicPath())) {
			throw new AssumptionFailedError("Failed to created schematic directory");
		}

		$thread = new EditThread(Server::getInstance()->getLogger());
		$thread->start(PTHREADS_INHERIT_INI | PTHREADS_INHERIT_CONSTANTS);

		ConfigManager::load();

		$this->getScheduler()->scheduleRepeatingTask(new EditAdapter(), 1);

		DefaultEventListener::init();

		CommandManager::registerCommands([
			//Selection
			new FirstPositionCommand(),
			new SecondPositionCommand(),
			new ExtendCommand(),
			new SetCommand(),
			new ReplaceCommand(),
			new OverlayCommand(),
			new NaturalizeCommand(),
			new SmoothCommand(),
			new AliasedContextCommand(SelectionContext::center(), "/center", ["/middle"]),
			new AliasedContextCommand(SelectionContext::walls(), "/walls", ["/wall"]),
			new AliasedContextCommand(SelectionContext::hollow(), "/sides", ["/side", "/hset", "/hollow"]),
			new MoveCommand(),
			$stack = new StackCommand(),
			new FlagRemapAlias($stack, new SingularCommandFlag("insert"), "/istack"),
			new CountCommand(),
			new ExtinguishCommand(),
			new ViewCommand(),

			//History
			new HistoryAccessCommand(true), //undo
			new HistoryAccessCommand(false), //redo

			//Clipboard
			$copy = new CopyCommand(),
			new FlagRemapAlias($copy, new SingularCommandFlag("remove"), "/cut"),
			$paste = new PasteCommand(),
			new FlagRemapAlias($paste, IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_AIR, "mode"), "/insert"),
			new FlagRemapAlias($paste, IntegerCommandFlag::with(DynamicPasteTask::MODE_ONLY_SOLID, "mode"), "/merge"),
			new FlagRemapAlias($paste, IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_SOLID, "mode"), "/rpaste"),
			new RotateCommand(),
			new FlipCommand(),
			new LoadSchematicCommand(),
			new SaveSchematicCommand(),

			//Generation
			$sphere = new SphereCommand(),
			new FlagRemapAlias($sphere, new SingularCommandFlag("hollow"), "/hsphere", ["/hsph", "/hollowsphere"]),
			$cylinder = new CylinderCommand(),
			new FlagRemapAlias($cylinder, new SingularCommandFlag("hollow"), "/hcylinder", ["/hcy", "/hcyl", "/hollowcylinder"]),
			new NoiseCommand(),
			$cube = new CubeCommand(),
			new FlagRemapAlias($cube, new SingularCommandFlag("hollow"), "/hcube", ["/hollowcube", "/hcb"]),

			//Utility
			new HelpCommand(),
			new BrushCommand(),
			new FillCommand(),
			new DrainCommand(),
			new LineCommand(),
			new BlockInfoCommand(),
			new BuilderRodCommand(),
			new StatusCommand(),
			new CancelCommand(),
			new BenchmarkCommand(),
			new PasteStatesCommand(),
			new WandCommand(),

			// Movement
			new ThruCommand(),
			new UnstuckCommand(),
			new UpCommand()
		]);

		Registry::registerToNetwork();
	}

	/**
	 * @return EasyEdit
	 */
	public static function getInstance(): self
	{
		return self::$instance;
	}

	/**
	 * @return string
	 * @internal
	 */
	public static function getResourcePath(): string
	{
		return self::getInstance()->getFile() . "resources";
	}

	/**
	 * @return string
	 */
	public static function getSchematicPath(): string
	{
		return self::getInstance()->getDataFolder() . "schematics" . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	public static function getCachePath(): string
	{
		return self::getInstance()->getDataFolder() . "cache" . DIRECTORY_SEPARATOR;
	}
}