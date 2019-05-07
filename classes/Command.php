<?php
abstract class Command {
	protected static $name = "command_base";
	protected static $helpMessage = "";
	abstract public function execute();
	public static function getName() : string {
		return static::$name;
	}
	public static function getHelpMessage() : string {
		return static::$helpMessage;
	}
}
?>