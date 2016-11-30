<?
/**
 * Beginning of Performance Evaluation class
 */
define('EV_START', 'start');		// Main log type, mark as evaluation start point
define('EV_END',   'end');			// Main log type, mark as evaluation end point
define('EV_CHECK', 'checkpoint');	// Mark as checkpoint for loop time evaluation
define('EV_COUNT', 'count');		// Add a counter and add up everytime called
define('EV_LOG',   'log');			// Log info at this point

class phpEvaluate
{
	private $evData = array();
	private $evCheckPoint = array();

	public function ev($name, $type, $description = '')
	{
		$return = array('success' => false, 'error' => '');
		$errMsg = '';

		if (!isset($this->evData[$name][$type]))
		{
			$this->evData[$name][$type] = array();
		}
		switch ($type) {
			case EV_END:
			case EV_START:
			case EV_CHECK:
				array_push($this->evData[$name][$type], microtime(true));
				break;

			case EV_COUNT:
				if (is_array($this->evData[$name][$type]))
				{
					unset($this->evData[$name][$type]);
					$this->evData[$name][$type] = 0;
				}
				$this->evData[$name][$type]++;
				break;

			case EV_LOG:
				array_push($this->evData[$name][$type], $description);
				break;

			default:
				unset($this->evData[$name][$type]);
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
		if (EV_START != $type)
		{
			if (!isset($this->evData[$name]['logUpdateTime']))
			{
				$this->evData[$name]['logUpdateTime'] = array();
			}
			array_push($this->evData[$name]['logUpdateTime'], date('Y-m-d H:i:s'));
		}

	ERROR:
		$return['success'] = empty($errMsg) ? true : false;
		$return['error'] = $errMsg;
		return $return;
	}

	public function getLastCheckPoint($name)
	{
		$cp = $this->evData[$name][EV_CHECK];
		$cpLast1 = $cp[count($cp) - 1];
		$cpLast2 = $cp[count($cp) - 2];
		return $cpLast2 ? ($cpLast1 - $cpLast2) : 0;
	}

	public function getCount($name)
	{
		$ct = $this->evData[$name][EV_COUNT];
		return $ct ? $ct : 0;
	}

	public function report($show = array(EV_START, EV_END))
	{
		$evReport = array();
		$durations = array();
		$iterations = array();

		// Display name setup
		$evContentDisplay = array(
			EV_START => 'Time',
			EV_END => 'Time',
			EV_CHECK => 'CheckPoint',
			EV_COUNT => 'Count',
			EV_LOG => 'Log'
		);

		// Push report title
		array_push($evReport, array(
			'timestamp' => "Function Finish Time",
			'content' => "Log Content",
			'name' => "[Type] Evaluation Name",
			'deep' => "Call Deep",
			'backtrace' => "Traceback"
		));

		// Generate evaluation report from data
		foreach ($this->evData as $evName => $evContent) {
			foreach ($evContent['logUpdateTime'] as $key => $logUpdateTime) {
				$reportContent = "";
				$evDuration = 0;
				switch ($evContent['type']) {
					case EV_START:
					case EV_END:
						if ((in_array(EV_START, $show)) || (in_array(EV_END, $show)))
						{
							$evDuration = $evContent[EV_END][$key] - $evContent[EV_START][$key];
							$reportContent = number_format($evDuration, 5, '.', '')." secs  (from ".number_format($evContent[EV_START][$key], 4, '.', '')." to ".number_format($evContent[EV_END][$key], 3, '.', '').")";
						}
						break;

					case EV_CHECK:
						if (in_array(EV_CHECK, $show))
						{
							$lastCheck = $evContent[EV_CHECK][$key - 1];
							$nowCheck = $evContent[EV_CHECK][$key];
							$evDuration = $lastCheck ? ($nowCheck - $lastCheck) : 0;
							$reportContent = number_format($evDuration, 5, '.', '')." secs  (from ".number_format($lastCheck, 4, '.', '')." to ".number_format($nowCheck, 3, '.', '').")";
						}
						break;

					case EV_COUNT:
						if (in_array(EV_COUNT, $show))
						{
							$reportContent = "Count +1";
						}
						break;

					case EV_LOG:
						if (in_array(EV_LOG, $show))
						{
							$reportContent = $evContent[EV_LOG][$key];
						}
						break;

					default:
						break;
				}
				$reportTimestamp = "[".$evContent['logUpdateTime'][$key]."]";
				$reportName = "[".$evContentDisplay[$evContent['type']]."] ".$evName;

				if (empty($evContent['parent']))
				{
					$evContent['parent'] = "MAIN";
				}
				$reportDeep = "DEEP: ".$evContent['deep'];
				$reportBacktrace = "MAIN => ".implode($evContent['backtrace'], " => ");

				$functionName = array_slice($evContent['backtrace'], -2, 1)[0];
				if (EV_END == $evContent['type'])
				{
					$durations[$functionName] += $evDuration;
					$iterations[$functionName]++;
				}

				array_push($evReport, array(
					'timestamp' => $reportTimestamp,
					'content' => $reportContent,
					'name' => $reportName,
					'deep' => $reportDeep,
					'backtrace' => $reportBacktrace
				));
			}
		}

		// Print out report
		echo "\n";
		foreach ($evReport as $report) {
			if (!empty($report['content']))
			{
				echo
				str_pad($report['timestamp'], 25, " ").
				str_pad($report['content'], 56, " ").
				str_pad($report['name'], 34, " ").
				str_pad($report['deep'], 13, " ").
				str_pad($report['backtrace'], 10, " ")
				."\n";
			}
		}

		// Print out summary
		$iterations['MAIN'] = 1;
		echo "\n";
		arsort($durations);
		echo str_pad("Function", 25, " ").
		str_pad("Total Time Spent", 20, " ").
		str_pad("Iteration", 13, " ").
		str_pad("Average Time Spent", 12, " ").
		"\n";
		foreach ($durations as $func => $time)
		{
			if ('ev' == $func)
			{
				$func = 'MAIN';
			}
			$averageTime = $time / $iterations[$func];
			echo str_pad($func, 25, " ").
			str_pad(number_format($time, 5, '.', '')." secs", 20, " ").
			str_pad($iterations[$func], 13, " ").
			str_pad(number_format($averageTime, 5, '.', '')." secs", 12, " ").
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