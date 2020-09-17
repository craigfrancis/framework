<?php

//--------------------------------------------------
// Setup

	mime_set('text/plain');

	class html_template extends html_template_base {
	}

//--------------------------------------------------
// Tests

	//--------------------------------------------------

		$link = h("\n" . implode("\n", [
				'#0  ?',
				'#0 x?<',
				'#0 >?x',
				'#0 >?"',
				'#0 >?\'',
				'#0 >? <',
				'#0 >??<',
				'#1 >?<',
				'#0 >?"',
				'#0 "?>',
				'#2 "?"',
				'#0 "?>',
				'#0 "?<',
				'#0 "?\'',
				'#3 \'?\'',
				'#0 \'?"',
			]) . "\n");

		echo $link->html([1, 2, 3]);

	//--------------------------------------------------

		echo "\n";

		echo h('<a href="?">?</a>', ['https://example.com/?a=b&c=d', 'Example & Link']);

	//--------------------------------------------------

		echo "\n\n";

		echo h('<a href="?">?</a>', ['javascript:alert(1)', 'Evil Link']); // This isn't safe.

	//--------------------------------------------------

		echo "\n\n";

		echo h('<p>Example?</p>'); // No parameters, just a question mark that should be ignored.

	//--------------------------------------------------

		echo "\n\n";

	//--------------------------------------------------

		echo h('<img src=? alt="?" />', ['Bad Image']); // Should ignore the src parameter, as the lack of quotes is an issue, e.g. 'x onerror=evil-js'

	//--------------------------------------------------

		echo "\n\n";

		$link = h('<span class="s1a"><a href="?">?</a> - <em>?</em></span>');

		echo $link->html([
				'/url/',
				'Link Text 1',
				'Extra Text 1',
			]);

	//--------------------------------------------------

		echo "\n";

		echo $link->html([
				'/url/',
				'Link Text 2',
				'Extra Text 2',
			]);

	//--------------------------------------------------

		echo "\n";

		$link = h('<span class="s1b"><a href="?">?</a> - <em>?</em></span>', [
				'/url/',
				'Link Text 3',
				'Extra Text 3',
			]);

		echo $link->html();

	//--------------------------------------------------

		echo "\n";

		echo $link;

	//--------------------------------------------------

		echo "\n";

		echo h('<span class="s1c"><a href="?">?</a> - <em>?</em></span>', [
				'/url/',
				'Link Text 4',
				'Extra Text 4',
			]);

//--------------------------------------------------
// Timings

	//--------------------------------------------------

		$template_html = '<p>Hi <span>?</span>, what do you think about <a href="?">?</a>? </p>';
		$parameters = ['A&B', 'javascript:alert("1&2")', '>This Link<'];
		$iterations = 10000;
		$decimals = 4;

		echo "\n\n";
		echo h($template_html, $parameters)->html();

	//--------------------------------------------------

		$start = microtime(true);

		$k = 0;
		while (++$k < $iterations) {
			$html = '<p>Hi <span>' . html($parameters[0]) . '</span>, what do you think about <a href="' . html($parameters[1]) . '">' . html($parameters[2]) . '</a>?</p>';
		}

		$time_plain = (microtime(true) - $start);

		echo "\n " . number_format($time_plain, $decimals) . 's';

	//--------------------------------------------------

		$start = microtime(true);

		$link = h($template_html);

		$k = 0;
		while (++$k < $iterations) {
			$html = $link->html($parameters);
		}

		$time_template = (microtime(true) - $start);

		echo "\n " . number_format($time_template, $decimals) . 's +' . round(((($time_template / $time_plain) * 100) - 100), 1) . '%';

	//--------------------------------------------------

		$start = microtime(true);

		$k = 0;
		while (++$k < $iterations) {
			$link = h($template_html);
			$html = $link->html($parameters);
		}

		$time_template = (microtime(true) - $start);

		echo "\n " . number_format($time_template, $decimals) . 's +' . round(((($time_template / $time_plain) * 100) - 100), 1) . '%';

	//--------------------------------------------------

		$start = microtime(true);

		$k = 0;
		while (++$k < $iterations) {
			$html = h($template_html, $parameters)->html();
		}

		$time_template = (microtime(true) - $start);

		echo "\n " . number_format($time_template, $decimals) . 's +' . round(((($time_template / $time_plain) * 100) - 100), 1) . '%';

//--------------------------------------------------
// Possible version 2, using XPath

	// $link = h('<span class="s2a"><a href=""></a> - <em></em></span>');
	//
	// echo $link->html([
	// 		'//a' => [
	// 			'href' => '/url/',
	// 			NULL => 'Link Text 1',
	// 		],
	// 		'//em' => [
	// 			NULL   => 'Extra Text 1',
	// 		],
	// 	]);

		//--------------------------------------------------
		//
		// 	$this->template = new DomDocument();
		// 	$this->template->loadHTML('<?xml encoding="UTF-8">' . $template_html);
		// 	$this->xpath = new DOMXPath($this->template); // Was in __construct()
		//
		// 	foreach ($values as $query => $attributes) {
		//
		// 		if (!is_literal($query)) {
		// 			throw new Exception('Invalid Template XPath.');
		// 		}
		//
		// 		foreach ($xpath->query($query) as $element) {
		// 			foreach ($attributes as $attribute => $value) {
		//
		// 				if (!is_literal($attribute)) {
		// 					throw new Exception('Invalid Template Attribute.');
		// 				}
		//
		// 				if ($attribute) {
		// 					$safe = false;
		// 					if ($attribute == 'href') {
		// 						if (preg_match('/^https?:\/\//', $value)) {
		// 							$safe = true; // Not "javascript:..."
		// 						}
		// 					} else if ($attribute == 'class') {
		// 						if (in_array($value, ['admin', 'important'])) {
		// 							$safe = true; // Only allow specific classes?
		// 						}
		// 					} else if (preg_match('/^data-[a-z]+$/', $attribute)) {
		// 						if (preg_match('/^[a-z0-9 ]+$/i', $value)) {
		// 							$safe = true;
		// 						}
		// 					}
		// 					if ($safe) {
		// 						$element->setAttribute($attribute, $value);
		// 					}
		// 				} else {
		// 					$element->textContent = $value;
		// 				}
		//
		// 			}
		// 		}
		//
		// 	}
		//
		// 	$html = '';
		//
		// 	$body = $this->template->documentElement->firstChild;
		// 	if ($body->hasChildNodes()) {
		// 		foreach ($body->childNodes as $node) {
		// 			$html .= $this->template->saveXML($node);
		// 		}
		// 	}

//--------------------------------------------------
// Done

	exit();

?>