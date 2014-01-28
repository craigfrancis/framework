<?php

	echo '#' . request('key');
	echo '#' . sha1(ENCRYPTION_KEY);

?>