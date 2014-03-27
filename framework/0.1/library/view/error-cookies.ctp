<?php
	$response->set('title_html', '<h1>Your browser does not accept cookies!</h1>');
?>

	<p>To enable cookies, follow the instructions below for the browser version you are using.</p>

	<h3>Internet Explorer 6.0</h3>
	<ul>
		<li>Select &quot;Internet Options&quot; from the &quot;Tools&quot; menu.</li>
		<li>Click on the &quot;Privacy&quot; tab.</li>
		<li>Click the &quot;Default&quot; button (or manually slide the bar down to &quot;Medium&quot;) under &quot;Settings&quot;.</li>
		<li>Click &quot;OK&quot;.</li>
	</ul>
	<div class="notice">
		<h4>Notice</h4>
		<p>Internet Explorer has difficultly working out when cookies expire... to avoid this issue, please check your computers clock is correct.</p>
		<p>When this page was loaded, the time was <?= html(date('g:ia \o\n l jS F Y')) ?>.</p>
	</div>

	<h3>Internet Explorer 5.x</h3>
	<ul>
		<li>Select &quot;Internet Options&quot; from the &quot;Tools&quot; menu.</li>
		<li>Click on the &quot;Security&quot; tab.</li>
		<li>Click the &quot;Custom Level&quot; button.</li>
		<li>Scroll down to the &quot;Cookies&quot; section.</li>
		<li>Set &quot;Allow cookies that are stored on your computer&quot; to &quot;Enable&quot;.</li>
		<li>Set &quot;Allow per-session cookies&quot; to &quot;Enable&quot;.</li>
		<li>Click &quot;OK&quot;.</li>
	</ul>

	<h3>Firefox 1.5 (<a href="http://www.getfirefox.com/">download</a>)</h3>
	<ul>
		<li>Select &quot;Options&quot; from the &quot;Tools&quot; menu.</li>
		<li>Select the &quot;Privacy&quot; icon in the top panel.</li>
		<li>Open the &quot;Cookies&quot; tab.</li>
		<li>Check the box corresponding to &quot;Allow sites to set cookies&quot;.</li>
		<li>Click &quot;OK&quot;.</li>
	</ul>

	<h3>Firefox 1.0 (and earlier)</h3>
	<ul>
		<li>Select &quot;Options&quot; from the &quot;Tools&quot; menu.</li>
		<li>Select the &quot;Privacy&quot; icon in the left panel.</li>
		<li>Check the box corresponding to &quot;Allow sites to set cookies&quot;.</li>
		<li>Click &quot;OK&quot;.</li>
	</ul>

	<h3>Opera 8</h3>
	<ul>
		<li>Select &quot;Quick Preferences&quot; from the &quot;Tools&quot; menu.</li>
		<li>Select &quot;Enable cookies&quot;.</li>
		<li>Click &quot;OK&quot;.</li>
	</ul>

	<h3>Safari</h3>
	<ul>
		<li>Select &quot;Preferences&quot; from the &quot;Safari&quot; main menu.</li>
		<li>Select the &quot;Security&quot; icon in the top panel.</li>
		<li>Select &quot;Only from sites you navigate to&quot;.</li>
		<li>Close the &quot;Preferences&quot; window.</li>
	</ul>

	<h3>Netscape 7.1/Mozilla 5.0</h3>
	<ul>
		<li>Select &quot;Preferences&quot; from the &quot;Edit&quot; menu.</li>
		<li>Click on the arrow next to &quot;Privacy &amp; Security&quot; in the scrolling window to expand.</li>
		<li>Under &quot;Privacy &amp; Security&quot;, select &quot;Cookies&quot;.</li>
		<li>Select &quot;Enable all cookies&quot;.</li>
		<li>Click &quot;OK&quot;.</li>
	</ul>
