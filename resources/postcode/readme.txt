
https://www.ordnancesurvey.co.uk/opendatadownload/products.html

	Code-Point® Open
	Tick download checkbox (one on the right), next, next, etc

Postcode data in the form of:

	"AB101AA",10,394251,806376,"S92000003","","S08000006","","S12000033","S13002483"
	"AB101AB",10,394232,806470,"S92000003","","S08000006","","S12000033","S13002483"
	"AB101AF",10,394181,806429,"S92000003","","S08000006","","S12000033","S13002483"
	"AB101AG",10,394251,806376,"S92000003","","S08000006","","S12000033","S13002483"

To convert:

	http://www.jstott.me.uk/phpcoord/#download

	<?php

	require_once('phpcoord-2.3.php');

	// AB101AA = 394251, 806376

	$os1 = new OSRef(394251, 806376);
	$ll1 = $os1->toLatLng();
	echo $ll1->toString() . "<br />\n";

	?>

Converts to 57.14841674745, -2.0950248639316...

	http://local.google.co.uk/?q=AB10%201AA

	http://local.google.co.uk/?q=57.14841674745,%20-2.0950248639316
