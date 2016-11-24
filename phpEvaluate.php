<?
/**
 * Beginning of Performance Evaluation class
 */
define('EV_START', 'start');
define('EV_END', 'end');
define('EV_LOG', 'log');

class phpEvaluate
{
	private $evData = array();

	public function ev($name, $type, $description = '')
	{
		$return = array('success' => false, 'error' => '');
		$errMsg = '';

		switch ($type) {
			case EV_START:
			case EV_END:
				$i = 2;
				while (!empty($this->evData[$name][EV_START]) && !empty($this->evData[$name][EV_END]))
				{
					if (2 < $i)
					{
						$name = substr($name, 0, -(strlen((string)$i) + 1))."_".$i++;
					}
					else
					{
						$name = $name."_".$i++;
					}
				}
				$this->evData[$name][$type] = microtime(true);
				break;

			case EV_LOG:
				$i = 2;
				while (!empty($this->evData[$name][EV_LOG]))
				{
					if (2 < $i)
					{
						$name = substr($name, 0, -(strlen((string)$i) + 1))."_".$i++;
					}
					else
					{
						$name = $name."_".$i++;
					}
				}
				$this->evData[$name][$type] = $description;
				break;

			default:
				$errMsg = 'ev type error';
				goto ERROR;
				break;
		}
		$this->evData[$name]['type'] = $type;
		$i = 0;
		$backtrace = array();
		do {
			array_push($backtrace, debug_backtrace()[$i]['function']);
		} while(debug_backtrace()[++$i]['function']);
		$this->evData[$name]['backtrace'] = array_reverse($backtrace);
		$this->evData[$name]['deep'] = $i;


	ERROR:
		$return['success'] = empty($errMsg) ? true : false;
		$return['error'] = $errMsg;
		return $return;
	}

	public function report($showLog = false)
	{
		$evTypeDisplay = array(
			EV_START => 'Time',
			EV_END => 'Time',
			EV_LOG => 'Log'
		);

		echo "\n";
		foreach ($this->evData as $evName => $evType) {
			$report_content = "";
			switch ($evType['type']) {
				case EV_START:
				case EV_END:
					$evDuration = number_format(($evType[EV_END] - $evType[EV_START]), 5, '.', '');
					$report_content = $evDuration." secs\t(from ".number_format($evType[EV_START], 4, '.', '')." to ".number_format($evType[EV_END], 3, '.', '').")";
					break;

				case EV_LOG:
					if ($showLog)
					{
						$report_content = $evType[EV_LOG];
					}
					break;

				default:
					break;
			}
			if (!empty($report_content))
			{
				$i = 0;
				$indent = '';
				while (++$i < $evType['deep'])
				{
					$indent .= "\t";
				}

				$report_timestamp = "[".date('Y-m-d H:i:s')."]\t";
				$report_title = str_pad("\t[".$evTypeDisplay[$evType['type']]."] ".$evName, 40, " ");
				if (empty($evType['parent']))
				{
					$evType['parent'] = "MAIN";
				}
				$report_backtrace = "\tDEEP: ".$evType['deep']." \t- MAIN => ".implode($evType['backtrace'], " => ");

				echo $report_timestamp.$report_content.$report_title.$report_backtrace."\n";
			}
		}
		echo "\n";
		return;
	}
}
/**
 * End of Performance Evaluation class
 */
?>