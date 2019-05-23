#!/usr/bin/env php
<?php
#Code derived from Monkey Manager
define("DEBUG", true);
require("classes/System.php");
if(!version_compare(PHP_VERSION, '7.2.0', '>=')) {
	echo "This PHP script requires PHP_VERSION 7.2.0 or greater.\nCurrent PHP_VERSION: ". PHP_VERSION . "\n";
	exit(1);
}
if(php_sapi_name() != "cli") {
	echo "This PHP script must be run on command-line.\nCurrently running from: " . php_sapi_name() . "\n";
	exit(1);
}

require("exceptions/base.php");
importExceptions();
require("classes/CommandHandler.php");
require("classes/SQLite.php");

//non-interactive cli
if(count($argv) > 1) {
	switch($argv[1]) {
		case '--cron':
			exit;
			echo "[" . date("d/m/Y H:i:s") . "]\n";
			echo "Executing cron...\n";
			chdir(dirname(__FILE__));
			system("notify-send Monkey \"Starting cron...\"");
			define("ISCRON", true);
			CommandHandler::execCron();
			echo "Cron finished.\n";
			break;
		case '--help':
		default:
			echo "Showing help: \n";
			break;
	}
	exit(0);
}
define("ISCRON", false);

$runningMonkeys = 0;

//TODO: check if there're monkeys running as cron or other instances.
//      - Shared memory?
//      - Temp file with PCNTL PIDs?
//      - IPC?
//      - RPC?

# Shared memory attempt, not working yet...
$shm_key = ftok(__FILE__, 't');
$shm_id = shmop_open($shm_key, "c", 0666, 1000);
shmop_write($shm_id, str_repeat("\00", 1000), 0);

//interactive cli

$prompt = "monkey_master";

CommandHandler::discover();

readline_completion_function("CommandHandler::autocomplete");

$s = new SQLite();
try {
	$s->open("asd");
} catch(FileException $e) {
	debug("Got file exception: %s", $e->getMessage());
	exit;
}
$s->query("CREATE TABLE IF NOT EXISTS status ( id int primary key not null)");
$s->query("SELECT * FROM status");
$s->close();

//starting user prompt
while(true) {
	$command = trim(readline($prompt . "[" . $runningMonkeys . "] > "));
	if($command == "") continue;
	readline_add_history($command);
	try {
		CommandHandler::tryExecute($command);
	} catch(CommandException $e) {
		echo $e->getMessage();
	}
}

?>