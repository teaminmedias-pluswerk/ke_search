<?php
	header("Content-Type: image/svg+xml");
	$percent = intval($_GET['p']);
?>
<svg xmlns="http://www.w3.org/2000/svg"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:ev="http://www.w3.org/2001/xml-events"
	version="1.1" baseProfile="full"
	width="50px" height="12px" viewBox="0 0 50 12">
	<rect x="0" y="0" width="<?=$percent?>%" height="100%" fill="lightgray" stroke="black" stroke-width="1px" />
	<rect x="0" y="0" width="50px" height="12px" fill="transparent" stroke="darkgray" stroke-width="1px"/>
</svg>
