<?php

//--------------------------------------------------
// Setup

	mime_set('text/plain');

	class html_template extends html_template_base {
	}

//--------------------------------------------------
// Tests

	//--------------------------------------------------

		echo "\n";

		echo ht('<a href="?">?</a>', [url('https://example.com/'), 'Example & Link']);

	//--------------------------------------------------

		echo "\n";

		$link = ht('<a href="?">?</a>');
		$url = url('https://example.com/');

		echo $link->html([$url->get(['id' => 1]), 'Example & Link']) . "\n";
		echo $link->html([$url->get(['id' => 2]), 'Example & Link']) . "\n";
		echo $link->html([$url->get(['id' => 3]), 'Example & Link']);

	//--------------------------------------------------

		echo "\n\n";

		echo ht('<a href="?">?</a>', [url('mailto:alert(1)'), 'Evil Link']);

	//--------------------------------------------------

		echo "\n\n";

		echo ht('<p>Example?</p>'); // No parameters, just a question mark that should be ignored.

	//--------------------------------------------------

		echo "\n\n";

		echo ht('<span>?</span> Test?', ['Example']); // Ignore last question mark

	//--------------------------------------------------

		echo "\n\n";

		$link = ht('<span class="s1a"><a href="?">?</a> - <em>?</em></span>');

		echo $link->html([
				url('/url/'),
				'Link Text 1',
				'Extra Text 1',
			]);

	//--------------------------------------------------

		echo "\n";

		echo $link->html([
				url('/url/'),
				'Link Text 2',
				'Extra Text 2',
			]);

	//--------------------------------------------------

		echo "\n";

		$link = ht('<span class="s1b"><a href="?">?</a> - <em>?</em></span>', [
				url('/url/'),
				'Link Text 3',
				'Extra Text 3',
			]);

		echo $link->html();

	//--------------------------------------------------

		echo "\n";

		echo $link; // __toString

	//--------------------------------------------------

		echo "\n";

		echo ht('<span class="s1c"><a href="?">?</a> - <em>?</em></span>', [
				url('/url/'),
				'Link Text 4',
				'Extra Text 4',
			]);

	//--------------------------------------------------

		echo "\n\n";
		echo "--------------------------------------------------";
		echo "\n\n";


		$html = [];
		$parameters = [];

		$html[] = '<h1>?</h1>';
		$parameters[] = 'Heading';

		$html[] = '<p class="?">Testing <a href="?" class="?" data-href="?">?</a> End</p>';
		$parameters[] = 'class_1';
		$parameters[] = url('mailto:alert(1)');
		$parameters[] = 'class_1a';
		$parameters[] = 'data_1b';
		$parameters[] = 'Example <script> & Link';

		$html[] = '<p class="?">Second</p>';
		$parameters[] = 'class_2';

		$html[] = '<p class="?"><img src="?" alt="?" width="?" height="?" /></p>';
		$parameters[] = 'class_3a class_3b';
		$parameters[] = url('/img/example.jpg');
		$parameters[] = 'Example Alt';
		$parameters[] = 123;
		$parameters[] = intval('321');

		$html[] = '<meta name="my_json" content="?" />';
		$parameters[] = json_encode([1, 2, 3]);

		$html[] = '<blockquote cite="?"><p>?</p></blockquote>';
		$parameters[] = url('https://example.com');
		$parameters[] = 'Some Words';

		echo ht(implode("\n", $html), $parameters);

	//--------------------------------------------------

		echo "\n\n";
		echo "--------------------------------------------------";
		echo "\n\n";

			// A reminder on how unsafe this function is!

		$template = ht('<img src="?" onerror="?" /> <script>?</script>');
		$template->unsafe_allow_node('img', ['onerror' => 'text']); // NEVER DO THIS!
		$template->unsafe_allow_node('script'); // NEVER DO THIS!

		$parameters = [];
		$parameters[] = url('/');
		$parameters[] = 'alert();';
		$parameters[] = 'alert();';

		echo $template->html($parameters);

	//--------------------------------------------------

		echo "\n\n";
		echo "--------------------------------------------------";
		echo "\n\n";

		$template = ht('<svg width="?" height="?" viewBox="?" aria-label="?" role="img" xmlns="http://www.w3.org/2000/svg"><rect x="?" y="?" width="?" height="?" fill="?" /></svg>');
		$template->unsafe_allow_node('svg',  ['width' => 'int', 'height' => 'int', 'viewBox' => 'text', 'aria-label' => 'text', 'role' => 'text']);
		$template->unsafe_allow_node('rect', ['width' => 'int', 'height' => 'int', 'x' => 'int', 'y' => 'int', 'fill' => 'text']);

		$parameters = [];
		$parameters[] = 200;
		$parameters[] = 200;
		$parameters[] = '0 0 200 200';
		$parameters[] = 'My Image';
		$parameters[] = 50;
		$parameters[] = 50;
		$parameters[] = 100;
		$parameters[] = 100;
		$parameters[] = 'red';

		echo $template->html($parameters);

//--------------------------------------------------
// Timings

	//--------------------------------------------------

		$template_html = '<p>Hi <span>?</span>, what do you think about <a href="?">?</a>? </p>';
		$parameters = ['A&B', url('mailto:alert("1&2")'), '>This Link<'];
		$iterations = 1000;
		$decimals = 4;

		echo "\n\n";
		echo "--------------------------------------------------";
		echo "\n\n";

		echo ht($template_html, $parameters)->html();

		echo "\n";

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

		$link = ht($template_html);

		$k = 0;
		while (++$k < $iterations) {
			$html = $link->html($parameters);
		}

		$time_template = (microtime(true) - $start);

		echo "\n " . number_format($time_template, $decimals) . 's +' . round(((($time_template / $time_plain) * 100) - 100)) . '%';

	//--------------------------------------------------

		$start = microtime(true);

		$k = 0;
		while (++$k < $iterations) {
			$link = ht($template_html);
			$html = $link->html($parameters);
		}

		$time_template = (microtime(true) - $start);

		echo "\n " . number_format($time_template, $decimals) . 's +' . round(((($time_template / $time_plain) * 100) - 100)) . '%';

	//--------------------------------------------------

		$start = microtime(true);

		$k = 0;
		while (++$k < $iterations) {
			$html = ht($template_html, $parameters)->html();
		}

		$time_template = (microtime(true) - $start);

		echo "\n " . number_format($time_template, $decimals) . 's +' . round(((($time_template / $time_plain) * 100) - 100)) . '%';

//--------------------------------------------------
// Parsing checks

	//--------------------------------------------------
	// All versions

		echo "\n\n";
		echo "--------------------------------------------------";
		echo "\n\n";

		echo ht('Start <a href="?" class=\'?\' data-value="?" data-static="abc">?</a> <span>?</span> End', [url('https://example.com/?a=b&c=d'), 'my-class', 123, 'Link\'s', '& Span']);

	//--------------------------------------------------

		echo "\n\n";

		$debug = config::get('debug.level');

		$link = ht(implode("\n", [
				'#0  ?',
				'#0 x?<',
				'#0 >?x',
				'#0 >?"',
				'#0 >?\'',
				'#0 >? <',
				'#0 >??<',
				'#1 <span>?</span>',
				'#0 >?"',
				'#0 "?>',
				'#2 <span class="?"></span>',
				'#0 "?>',
				'#0 "?<',
				'#0 "?\'',
				'#3 <span class=\'?\'></span>',
				'#0 \'?"',
			]));

		config::set('debug.level', 0); // Temporarily disable debugging, which checks this is valid XML, and the parameter context.

		echo $link->html([1, 2, 3]);

		config::set('debug.level', $debug);

	//--------------------------------------------------

		echo "\n\n";

		echo ht(
			'<span class="&quot;&apos;&#039;&amp;&lt;&gt;" data-ignore="? " data-value="?">?</span>' . "\n" .
			'<span>&quot;&apos;&#039;&amp;&lt;&gt;</span>' . "\n" .
			'<span class="ignore-value">? </span>', ['DataValue', 'ContentValue']); // Can parse these correctly

	//--------------------------------------------------

		echo "\n\n";

		$html  = '<div class="?">' . "\n";
		$html .= '<a href="?" class="?" data-value="?">?</a>' . "\n";
		$html .= '<span>?</span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div><pre>?</pre></div>' . "\n";
		$html .= '<div><p>?</p></div>';

		$parameters = [
			'p-class',
			url('/'),
			'a-class',
			'a'    . "\n" . 'value', // No <br />
			'a'    . "\n" . 'text', // Yes <br />
			'span' . "\n" . 'text', // Yes <br />
			'pre'  . "\n" . 'text', // No <br />
			'para' . "\n" . 'text', // Yes <br />
		];

		echo ht($html, $parameters); // Show that attributes and <pre> text does not use <br />

	//--------------------------------------------------

		echo "\n\n";

		$parsing_tests = [
				['<span>?</span>',                            []],
				['<span>?</span>',                            ['Good', 'Extra']],
				['<img src=? alt="?" />',                     ['BadImage']], // Parameters must be quoted, to avoid 'src=x onerror=evil-js'
				['<span extra="fish>?</span>',                ['MyText']],
				['[span class="?"></span>',                   ['MyClass']],
				['span>?</span>',                             ['MyText']],
				['<span class=""">?</span>',                  ['MyText']],
				['<span class=">" attr>?</span>',             ['MyText']],
				['<span class="<">?</span>',                  ['MyText']],
				['<span@abc>?</span>',                        ['MyText']],
				['<pre>?</pre x>',                            ['MyText']],
				['<script>?</script>',                        ['EvilValue']],
				['<a href="/" onclick="abc">?</a>',           ['EvilValue']],
				['<meta http-equiv="refresh" content="?" />', ['EvilValue']],
				['<meta name="referrer" content="?" />',      ['EvilValue']],
				['<img src="?" alt="?" />',                   ['https://example.com', 'My Image']],
				['<img src="?" alt="?" width="?" />',         [url('/img.jpg'), 'My Image', '123']],
				['<p class="?">Text</p>',                     ['example1 example2!']],
				['<time datetime="?">9am</time>',             ['2020-09-26T09:00:00.000-01:00x']],
			];

		foreach ($parsing_tests as $test) {
			try {
				$t = ht($test[0], $test[1]);
				echo $t;
				exit_with_error('Did not pick up bad attribute: ' . $test[0]);
			} catch (error_exception $e) {
				echo 'Correctly Failed:' . "\n";
				echo '  ' . $e->getMessage() . "\n";
				echo '  ' . str_replace("\n", "\n  ", $e->getHiddenInfo()) . "\n\n";
			}
		}

//--------------------------------------------------
// Done

	exit();

?>