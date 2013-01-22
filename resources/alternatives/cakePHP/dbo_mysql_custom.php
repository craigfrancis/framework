<?php

/*--------------------------------------------------

	class AppController extends Controller {

		function beforeRender() {

			if (class_exists('ConnectionManager') && Configure::read('debug') > 1) {
				$db = ConnectionManager::getDataSource('default');
				$this->set('cakeSql', $db->getLog());
			} else {
				$this->set('cakeSql', '');
			}

		}

	}

//--------------------------------------------------*/

	require(LIBS . 'model' . DS . 'datasources' . DS . 'dbo' . DS . 'dbo_mysql.php');

	class DboMysqlCustom extends DboMysql {

		function getLog($sorted = false) {

			if ($sorted) {
				$log = sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC);
			} else {
				$log = $this->_queriesLog;
			}

			if ($this->_queriesCnt == 0) {
				return;
			} else if ($this->_queriesCnt > 1) {
				$text = 'queries';
			} else {
				$text = 'query';
			}

			$output = '';

			if (PHP_SAPI != 'cli') {

				if (isset($GLOBALS['debugTimeStart'])) {

					$timeEnd = explode(' ', microtime());
					$timeEnd = ((float)$timeEnd[0] + (float)$timeEnd[1]);
					$timeTotal = round(($timeEnd - $GLOBALS['debugTimeStart']), 3);

					$output = '<p style="text-align: left; padding: 0; margin: 0;">Time elapsed: ' . h($timeTotal) . 's</p>';

				}

				$output .= "<table class=\"cake-sql-log\" id=\"cakeSqlLog_" . preg_replace('/[^A-Za-z0-9_]/', '_', uniqid(time(), true)) . "\">\n<caption>SQL for {$this->configKeyName} connection - {$this->_queriesCnt} {$text} took {$this->_queriesTime} ms</caption>\n";
				$output .= "<thead>\n<tr><th>Nr</th><th>Query</th><th>Error</th><th>Affected</th><th>Num. rows</th><th>Took (ms)</th></tr>\n</thead>\n<tbody>\n";

				foreach ($log as $k => $i) {

					$queryHtml = nl2br(ltrim(preg_replace('/(SELECT|UPDATE|DELETE|INSERT|FROM|LEFT JOIN|SET|WHERE|GROUP BY|ORDER BY|LIMIT|DESCRIBE)/m', "</span><span class=\"command\">$1</span><span>", h($i['query']))));

					if (preg_match('/SELECT(.*)FROM/ms', $queryHtml, $matches)) {
						$fieldsHtml = str_replace(',', ",<br />", $matches[0]);
						$queryHtml = str_replace($matches[0], $fieldsHtml, $queryHtml);
					}

					$output .= "<tr><td>" . ($k + 1) . "</td><td><span>" . $queryHtml . "</span></td><td>{$i['error']}</td><td style = \"text-align: right\">{$i['affected']}</td><td style = \"text-align: right\">{$i['numRows']}</td><td style = \"text-align: right\">{$i['took']}</td></tr>\n";

				}

				$output .= "</tbody></table>\n";

			} else {

				foreach ($log as $k => $i) {
					$output .= ($k + 1) . ". {$i['query']} {$i['error']}\n";
				}

			}

			$this->_queriesCnt = 0;
			$this->_queriesTime = null;
			$this->_queriesLog = array();

			return $output;

		}

		function showLog($sorted = false) {
			echo $this->getLog($sorted);
		}

	}

?>