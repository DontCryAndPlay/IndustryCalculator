<?php
class CommandHandler {
	public static $registeredCommands = array();
	public static function autocomplete($string, $index) {
		$matches = array();

		foreach(array_keys(self::$registeredCommands) as $command)
			if(stripos($command, $string) === 0)
				$matches[] = $command;

		if($matches == false);
			$matches[] = '';

		return $matches;
	}
	public static function tryExecute(string $command) : bool {
		if(!isset(self::$registeredCommands[$command])) {
			throw new CommandException("Unrecognized command");
			return false;
		}
		self::$registeredCommands[$command]->execute();
		return true;
	}
	public static function discover() {
		include("classes/Command.php");

		// TODO: proper command discovery
		include("commands/help.php");
		include("commands/status.php");

		$commands = array_filter(
			get_declared_classes(),
			function($className) {
				return is_subclass_of($className, "Command");
			}
		);

		foreach($commands as $command)
			self::registerCommand(new $command);
	}

	public static function registerCommand(Command $command) {
		$name = $command->getName();
		self::$registeredCommands[$name] = $command;
	}

	public static function exit() {
		exit(0);
	}
}