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
						$name = substr($name, 0, -(strlen((string)($i - 1)) + 1))."_".$i++;
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
						$name = substr($name, 0, -(strlen((string)($i - 1)) + 1))."_".$i++;
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
		$this->evData[$name]['logEndTime'] = date('Y-m-d H:i:s');


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
		echo str_pad("Function Finish Time", 20, " ").
		"\t".str_pad("Time Spent", 13, " ").
		"\t".str_pad("Start and End Time", 41, " ").
		"\t".str_pad("[Type] Evalutate Name", 30, " ").
		"\t".str_pad("Call Deep", 10, " ").
		"\t".str_pad("- Traceback", 10, " ").
		"\n";
		$durations = array();
		$iterations = array();
		foreach ($this->evData as $evName => $evType) {
			$report_content = "";
			switch ($evType['type']) {
				case EV_START:
				case EV_END:
					$evDuration = $evType[EV_END] - $evType[EV_START];
					$report_content = number_format($evDuration, 5, '.', '')." secs\t(from ".number_format($evType[EV_START], 4, '.', '')." to ".number_format($evType[EV_END], 3, '.', '').")";
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

				$report_timestamp = "[".$evType['logEndTime']."]\t";
				$report_title = "\t".str_pad("[".$evTypeDisplay[$evType['type']]."] ".$evName, 30, " ");
				if (empty($evType['parent']))
				{
					$evType['parent'] = "MAIN";
				}
				$report_backtrace = "\tDEEP: ".$evType['deep']." \t- MAIN => ".implode($evType['backtrace'], " => ");

				echo $report_timestamp.$report_content.$report_title.$report_backtrace."\n";

				$function_name = array_slice($evType['backtrace'], -2, 1)[0];
				$durations[$function_name] += $evDuration;
				if (EV_END == $evType['type'])
				{
					$iterations[$function_name]++;
				}
			}
		}
		$iterations['MAIN'] = 1;
		echo "\n";
		arsort($durations);
		echo str_pad("Function", 20, " ").
		"\t".str_pad("Total Time Spent", 16, " ").
		"\t".str_pad("Iteration", 10, " ").
		"\t".str_pad("Average Time", 20, " ").
		"\n";
		foreach ($durations as $func => $time)
		{
			if ('ev' == $func)
			{
				$func = 'MAIN';
			}
			$average_time = $time / $iterations[$func];
			echo str_pad($func, 20, " ").
			"\t".str_pad(number_format($time, 5, '.', '')." secs", 16, " ").
			"\t".str_pad($iterations[$func], 10, " ").
			"\t".str_pad(number_format($average_time, 5, '.', '')." secs", 20, " ").
			"\n";
		}
		echo "\n";
		return;
	}
}
/**
 * End of Performance Evaluation class
 */
?>