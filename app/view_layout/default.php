<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en-GB" xml:lang="en-GB" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<title>Website</title>

	<meta http-equiv="content-type" content="<?= html($GLOBALS['pageMimeType']) ?>; charset=<?= html($GLOBALS['pageCharset']) ?>" />

	<link rel="shortcut icon" type="image/x-icon" href="<?= html($GLOBALS['webAddress']) ?>/a/img/global/favicon.ico" />

	<?= $GLOBALS['tplCssLinksHtml'] ?>

	<?= $GLOBALS['tplJavaScriptHtml'] ?>

	<?= $GLOBALS['tplExtraHeadHtml'] ?>

</head>
<body id="<?= html($GLOBALS['tplPageId']) ?>">

	<div id="pageWrapper">

		<div id="pageTitle">
			<h1>Website Title</h1>
		</div>

		<div id="pageContainer">

			<div id="pageNavigation">

				<h2>Site Navigation</h2>

			</div>

			<div id="pageContent">









<!-- END OF PAGE TOP -->
<!-- START OF PAGE BOTTOM -->









			</div>

		</div>

		<div id="pageFooter">
			<h2>Footer</h2>
			<ul>

				<li class="copyright">&copy; Company <?= html(date('Y')) ?></li>

			</ul>
		</div>

	</div>

	<?= $GLOBALS['tplTrackingHtml'] ?>

</body>
</html>