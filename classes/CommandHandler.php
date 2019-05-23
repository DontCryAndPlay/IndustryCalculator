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
		debug("Executing command %s", $command);
		if(!isset(self::$registeredCommands[$command])) {
			throw new CommandException("Unrecognized command");
			return false;
		}
		self::$registeredCommands[$command]->execute();
		return true;
	}
	public static function discover() {
		include("classes/Command.php");

		$files = scandir("commands");
		foreach($files as $file) {
			if($file == "." || $file == "..")
				continue;
			debug("Discovered new command file: %s", $file);
			include("commands/" . $file);
		}

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
		debug("Registering command: %s", $name);
		self::$registeredCommands[$name] = $command;
	}

	public static function exit() {
		exit(0);
	}
}