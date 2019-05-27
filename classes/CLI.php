<?php
namespace CLI;
class Common {
	const CLEAR_EVERYTHING = 0x01;
	const CLEAR_RIGHT      = 0x02;
	const CLEAR_LEFT       = 0x03;

	public static function clear(int $type = self::CLEAR_EVERYTHING) {
		switch ($type) {
			case self::CLEAR_EVERYTHING:
				echo "\033[H\033[0J";
			break;
			case self::CLEAR_RIGHT:
				echo "\033[K";
			break;
		}
	}
}
class Cursor {
	const CURSOR_SAVE     = "\033[s";
	const CURSOR_RESTORE  = "\033[u";
	const CURSOR_POSITION = "\033[%u;%uf";
	const CURSOR_UP       = "\033[%uA";
	const CURSOR_DOWN     = "\033[%uB";
	const CURSOR_RIGHT    = "\033[%uC";
	const CURSOR_LEFT     = "\033[%uD";

	public static $saved = false;
	public static function save() {
		echo CURSOR_SAVE;
		self::$saved = true;
	}
	public static function restore() {
		if(self::$saved) {
			echo CURSOR_RESTORE;
			self::$saved = false;
		}
	}
	public static function goLeft(int $n = 1) {
		printf(self::CURSOR_LEFT, $n);
	}
	public static function goUp(int $n = 1) {
		printf(self::CURSOR_UP, $n);
	}
}
class ProgressBar {
	private $unfilled = "░";
	private $filled = "█";
	private $percent = 0;
	private $drawing = false;
	private $maxLength = 0;
	private $defaultLength = 20;
	function __construct(int $maxLength = 0) {
		if($maxLength == 0)
			$maxLength = $this->defaultLength;
		$this->maxLength = $maxLength;
	}
	private function draw() {
		if($this->drawing) {
			Cursor::goUp();
			Common::clear(Common::CLEAR_RIGHT);
		}
		else
			$this->drawing = true;

		$nFilled = floor($this->maxLength * $this->percent / 100);
		$nUnfilled = floor($this->maxLength * (100 - $this->percent) / 100);

		printf("[%s%s] %d%%\n", str_repeat($this->filled, $nFilled), str_repeat($this->unfilled, $nUnfilled), $this->percent);
	}
	public function updatePercent(int $percent = 0) {
		if($percent > 100)
			$percent = 100;
		if($percent < 0)
			$percent = 0;
		$this->percent = $percent;
		$this->draw();
	}
}
?>