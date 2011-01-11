<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo isset($title) ? $title : 'Am I A Fuck-Tard?'; ?></title>

	<?php echo Asset::css('main.css'); ?>

	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>

	<?php if(isset($css)) echo Asset::css($css); ?>
	<?php if(isset($js)) echo Asset::js($js); ?>
	
</head>
<body>
	<div id="wrapper">

		<h1><a href="/">Am I a Fuck-Tard?</a></h1>

		<?php if (isset($title)): ?>
		<h2><?php echo $title; ?></h2>
		<?php endif; ?>

		<div id="content">

			<?php if (Session::get_flash('message')): ?>
			<p><?php echo Session::get_flash('message'); ?></p>
			<?php endif; ?>

			<?php echo $content; ?>
		</div>

		<p id="footer">
      <span>This site is in no way affiliated or associated with and/or or it's respective companies.</span> &middot; 
      <a href="https://github.com/philsturgeon/amiafucktard.com">About</a>
    </p>
	</div>
</body>
</html>
