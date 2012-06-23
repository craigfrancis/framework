<?php

require_once('phpcoord-2.3.php');

$os1 = new OSRef(394251, 806376);
echo $os1->toString() . "<br />\n";
echo $os1->toSixFigureString() . "<br />\n";

$ll1 = $os1->toLatLng();
echo $ll1->toString() . "<br />\n";

?>