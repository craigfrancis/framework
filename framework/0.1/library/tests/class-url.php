<?php

	class url extends url_base {
	}

	echo "<br />\n";
	echo "URL Testing as function:<br />\n";
	echo '&#xA0; ' . html(url()) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('#testing')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('thank-you/')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('./thank-you/')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('./')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('/')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('../news/')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('/news/')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url(array('id' => 6, 'empty' => '', 'blank' => NULL))) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('/news/', 'id', array('id' => 5, 'test' => 't=r&u e'))) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('/folder/#anchor', array('id' => 5, 'test' => 't=r&u e'))) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('/folder/', 'id', '/view/', 'detail')->get(array('id' => 54))) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('https://www.example.com')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('https://user:pass@www.example.com:80/about/folder/?id=example#anchor', array('id' => 5, 'test' => 't=r&u e'))) . '<br />' . "\n";
	echo '&#xA0; ' . html(http_url('./thank-you/')) . '<br />' . "\n";
	echo '&#xA0; ' . html(url('mailto:user@example.com', array('subject' => 'My Subject'))) . '<br />' . "\n";

	$example = new url('/news/?d=e#top', 'id', array('id' => 10, 'a' => 'b'));
	echo "<br />\n";
	echo "URL Testing as object:<br />\n";
	echo '&#xA0; ' . html($example) . '<br />' . "\n";
	echo '&#xA0; ' . html($example->get(array('id' => 15))) . '<br />' . "\n";
	echo '&#xA0; ' . html($example) . '<br />' . "\n";

	$url = url('./');
	$url->format_set('relative');
	echo "<br />\n";
	echo "URL Testing as relative:<br />\n";
	echo '&#xA0; ' . html($url) . '<br />' . "\n";

	echo "<br />\n";
	echo "URL Testing with prefix:<br />\n";
	config::set('url.prefix', '/website');
	echo '&#xA0; ' . html(url('/folder/')) . '<br />' . "\n";

?>