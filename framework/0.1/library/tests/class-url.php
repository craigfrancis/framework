<?php

//--------------------------------------------------
// Setup

	mime_set('text/plain');

	class url extends url_base {
	}

//--------------------------------------------------
// Tests

	echo "\n";
	echo 'Basic:' . "\n";
	echo '  ' . url() . "\n";
	echo '  ' . url('') . "\n";
	echo '  ' . url('#testing') . "\n";
	echo '  ' . url('thank-you/') . "\n";
	echo '  ' . url('./thank-you/') . "\n";
	echo '  ' . url('./') . "\n";
	echo '  ' . url('/') . "\n";
	echo '  ' . url('../news/') . "\n";
	echo '  ' . url('/news/') . "\n";
	echo '  ' . url(['id' => 6, 'empty' => '', 'blank' => NULL]) . "\n";
	echo '  ' . url('/news/', 'id', ['id' => 5, 'test' => 't=r&u e']) . "\n";
	echo '  ' . url('/folder/#anchor', ['id' => 5, 'test' => 't=r&u e']) . "\n";
	echo '  ' . url('/folder/', 'id', '/view/', 'detail')->get(['id' => 54]) . "\n";
	echo '  ' . url('https://www.example.com') . "\n";
	echo '  ' . url('https://user:pass@www.example.com:80/about/folder/?id=example#anchor', ['id' => 5, 'test' => 't=r&u e']) . "\n";
	echo '  ' . http_url('./thank-you/') . "\n";
	echo '  ' . url('mailto:user@example.com', ['subject' => 'My Subject']) . "\n";

	$example = new url('/news/?d=e#top', 'id', ['id' => 10, 'a' => 'b']);
	echo "\n";
	echo 'Object:' . "\n";
	echo '  ' . $example . "\n";
	echo '  ' . $example->get(['id' => 15]) . "\n";
	echo '  ' . $example . "\n";

	$url = url('./example/../abc/');
	$url->format_set('relative');
	echo "\n";
	echo 'Relative:' . "\n";
	echo '  ' . $url . "\n";
	$url->format_set('absolute');
	echo '  ' . $url . "\n";
	$url->format_set('full');
	echo '  ' . $url . "\n";

	echo "\n";
	echo 'Prefix:' . "\n";
	config::set('url.prefix', '/website');
	echo '  ' . url('/folder/') . "\n";

	echo "\n";
	echo 'Scheme:' . "\n";
	$url = url('app:/custom/value/');
	$url->schemes_allowed_set(['app']);
	echo '  ' . $url . "\n";

	echo "\n";
	echo 'Evil JavaScript:' . "\n";
	try {
		$url = url('JaVaScRiPt:alert(1)');
		$url->schemes_allowed_set(['javascript']);
		echo '  ' . $url . "\n";
	} catch (error_exception $e) {
		echo '  Good Rejection: ' . $e->getMessage() . "\n";
	}

	try {
		$url = url('java&#x09;script:alert(1)');
		$url->schemes_allowed_set(['java&#x09;script']);
		echo '  ' . $url . "\n";
	} catch (error_exception $e) {
		echo '  Good Rejection: ' . $e->getMessage() . "\n";
	}

//--------------------------------------------------
// Done

	exit();

?>