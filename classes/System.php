<?php
function debug(string $message, ...$params) {
	$message = vsprintf($message, $params);
	if(!defined("DEBUG") || DEBUG == false)
		return;
	@list(,$caller)=debug_backtrace(false);
	if(isset($caller['class'])){
		if ($caller['class'] == "DummyClass"){
			list(,,$caller) = debug_backtrace(false);
		}
		printf("[%s][%s]: %s\n", $caller['class'], $caller['function'], $message);
	} else
		printf("[%s]: %s\n", $caller['function'], $message);
}
function error(string $message, ...$params) {
	$message = vsprintf($message, $params);
	printf("%s\n", $message);
}
function signalHandler($signal) {
	switch($signal) {
		case SIGINT:
			break;
	}
}
pcntl_signal(SIGINT, "signalHandler");
?>