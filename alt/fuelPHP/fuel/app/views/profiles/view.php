<div class="vcard">

	<a href="http://twitter.com/<?php echo $twit->screen_name; ?>" class="url"><img src="http://img.tweetimag.es/i/<?php echo $twit->screen_name; ?>_b" class="photo" alt="Photo of <?php echo $twit->screen_name; ?>" /></a>
	<h2 class="fn nickname"><a href="http://twitter.com/<?php echo $twit->screen_name; ?>">@<?php echo $twit->screen_name; ?></a></h2>

	<p style="float:right">
		Followers: <?php echo $twit->followers_count; ?><br/>
		Following: <?php echo $twit->friends_count; ?>
	</p>

	<div class="clear">&nbsp;</div>

	<?php if ($rating > 0): ?>
		<div class="rating"><?php echo round($rating); ?>%</div>
		<div class="votes">For: <?php echo $agree; ?> Against: <?php echo $disagree; ?></div>
	<?php endif; ?>

		<div class="clear">&nbsp;</div>
		<div class="last_tweet">
			<h3>Recent tweets</h3>

		<?php foreach ($tweets as $tweet): ?>
			<p><?php echo $tweet->text; ?></p>
		<?php endforeach; ?>
	</div>

	<p style="clear:both"><?php echo $twit->description; ?></p>

	<?php echo Form::open('profiles/vote', array('id' => $twit->id, 'screen_name' => $is_random ? NULL : $twit->screen_name)); ?>

		<?php if( ! in_array($twit->screen_name, array('philsturgeon', 'davestone'))): ?>
		<button type="submit" name="yes" id="yes" class="greenbutton" value="1">Yes</button>
		<?php endif; ?>

		<button type="submit" name="no" id="no" class="redbutton" value="1">No</button>

	<?php echo Form::close(); ?>

</div>