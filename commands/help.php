<?php
class HelpCommand extends Command {
	protected static $name = "help";
	protected static $helpMessage = "Shows this help text.";
	private $padding = 80;
	private $minSep = 3;
	public function execute() {
		$classes = array_filter(
			get_declared_classes(),
			function($className) {
				return is_subclass_of($className, "Command");
			}
		);
		echo str_pad("HELP", $this->padding, "=", STR_PAD_BOTH) . "\n\n";
		foreach($classes as $class) {
			echo "  ";
			$sep = 1;
			if($class::getName() != "") {
				echo $class::getName();
				$sep += 0;
			}
			$val = intval($sep / 8);
			$sep = $val > 1? 1 - $val : ($val < 1? $val + 1 : $val - 1);
			echo str_pad("", $this->minSep + $sep, "\t");
			$maxLength = $this->padding - ($this->minSep + $sep + 0) * 8;
			$message = wordwrap($class::getHelpMessage(), $maxLength, "\n", true);
			if(strpos($message, "\n") > 0) {
				$parts = explode("\n", $message);
				do {
					$part = array_shift($parts);
					echo $part."\n";
					echo str_pad("", $this->minSep + 1, "\t");
				} while(count($parts) > 1);
				echo $parts[0];
			}
			else
				echo $message;
			echo "\n\n";
		}
		echo str_pad("HELP", $this->padding, "=", STR_PAD_BOTH) . "\n";
	}
}
?>