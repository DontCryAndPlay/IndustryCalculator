<?php
class StatusCommand extends Command {
	protected static $name = "status";
	protected static $helpMessage = "Shows the status of currently running monkeys, if they are...";
	public function execute() {
		global $runningMonkeys, $shm_id;

		$runningMonkeys = 0;
		$start=0;
		$bytes=100;
		$monkeys = array();
		for($i=0;$i<900;$i+=100) {
			$data = shmop_read($shm_id, $start, $bytes);
			if(md5($data) != "6d0bb00954ceb7fbee436bb55a8397a9") {
				$monkeys[] = $data;
				$runningMonkeys++;
			}
			$start+=$bytes;
		}
		$i=0;
		if(count($monkeys) > 0) {
			foreach($monkeys as $monkey) {
				$i++;
				echo "Monkey $i:\n$monkey\n\n";
			}
		}

		if($runningMonkeys == 0) {
			echo "There're no monkeys running at this moment.\n";
			return false;
		}
	}
}