<?php
/**
 * Script to check the remote host file to process update requests
 *
 * @author   Eric Sprangers <eric.sprangers@gmail.com>
 * @license  GPL3.0
 * @package  WP_private_update
 */

/**
 * Dump de inhoud van het opgevraagde object
 *
 * @param string $contents Het geserializeerde object.
 */
function dump( $contents ) {
	echo '<table>';
	$info = unserialize( $contents );
	foreach ( (array) $info as $key => $element ) {
		echo "<tr><th>$key</th><td>";
		if ( is_array( $element ) ) {
			echo '<table>';
			foreach ( $element as $key_1 => $sub_element ) {
				echo "<tr><th>$key_1</th><td>" . print_r( $sub_element ) . '</td></tr>';
			}
			echo '</table>';
		} else {
			print_r( $element );
		}
		echo '</td></tr>';
	}
	echo '</table>';
}
?>

<html>
<head>
<title>plugin.kleistad.nl</title>
<style>
* { font-family: verdana; font-size: 10pt; COLOR: gray; }
b { font-weight: bold; }
table { border: 1px solid gray;}
</style>
</head>
<body>
<br><br><br><br>
<h2>Kleistad zip file informatie</h2>
<?php

/**
 * Dump de inhoud van de twee informatie opvraag acties.
 */
dump( file_get_contents( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . 'update.php?action=version' ) );
dump( file_get_contents( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . 'update.php?action=info' ) );
?>

</body>
</html>
