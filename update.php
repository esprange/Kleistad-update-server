<?php
/**
 * The remote host file to process update requests
 *
 * @author   Eric Sprangers <eric.sprangers@gmail.com>
 * @license  GPL3.0
 * @package  Kleistad
 */

$path = pathinfo( realpath( __FILE__ ), PATHINFO_DIRNAME ) . '/';

ini_set( 'log_errors', E_ALL );
ini_set( 'error_log', $path . '/error.log' );

$zipfiles = glob( '*.zip' );
if ( empty( $zipfiles ) ) {
	error_log( 'No zip file found' );
	exit;
}
$action          = filter_input( INPUT_GET, 'action' );// ?? filter_input( INPUT_POST, 'action' );
$zipfile         = $zipfiles[0];
$slug            = basename( $zipfile, '.zip' );
$pluginfile      = "$slug.php";
$base_url        = ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . dirname( $_SERVER['PHP_SELF'] );
$counterfile     = 'counter/counter.txt';
$count           = intval( file_get_contents( $base_url . $counterfile ) );
$data_pluginfile = file_get_contents( "zip://$path/$zipfile#$slug/$pluginfile", false, null, 0, 8192 );
$data_readmefile = file_get_contents( "zip://$path/$zipfile#$slug/README.txt" );
$headers = [
	'Name'        => 'Plugin Name',
	'PluginURI'   => 'Plugin URI',
	'Version'     => 'Version',
	'Description' => 'Description',
	'Author'      => 'Author',
	'AuthorURI'   => 'Author URI',
	'Requires'    => 'Requires at least',
	'Tested'      => 'Tested up to',
	'License'     => 'License',
	'RequiresPHP' => 'Requires at least PHP version',
];

$data = str_replace( "\r", "\n", $data_pluginfile . $data_readmefile );
foreach ( $headers as $field => $regex ) {
	if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $data, $match ) && $match[1] ) {
		$headers[ $field ] = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $match[1] ) );
	} else {
		$headers[ $field ] = '';
	}
}

$sections = [
	'Description'   => 'Description',
	'Changelog'     => 'Changelog',
	'UpgradeNotice' => 'Upgrade Notice',
];

$data = str_replace( "\r", "\n", $data_readmefile );
foreach ( $sections as $field => $regex ) {
	$needle = "== $regex ==";
	$start  = strpos( $data, $needle ) + strlen( $needle );
	if ( false !== $start ) {
		$chapter = substr( $data, $start, strpos( $data, '==', $start ) - $start );
		$text    = '';
		$list    = false;
		foreach ( preg_split( "/((\r?\n)|(\r\n?))/", $chapter ) as $line ) {
			if ( 0 === strpos( trim( $line ), '=' ) ) {
				$line = ( $list ? '</ul>' : '' ) . preg_replace( [ '/= /', '/ =/' ], [ '<h4>', '</h4>' ], $line );
				$list = false;
			}
			if ( 0 === strpos( trim( $line ), '*' ) ) {
				$line = ( ! $list ? '<ul>' : '' ) . str_replace( '*', '<li>', $line ) . '</li>';
				$list = true;
			}
			if ( 0 === strpos( trim( $line ), '#' ) ) {
				$line = str_replace( '#', '<h4>', $line ) . '</h4>';
			}
			$text .= $line;
		}
		$sections[ $field ] = $text . ( $list ? '</ul>' : '' );
	} else {
		$sections[ $field ] = '';
	}
}

// Set up the properties common to both requests.
$obj_info = (object) [
	'id'             => $slug,
	'slug'           => $slug,
	'plugin'         => "$slug/$slug.php",
	'name'           => $headers['Name'],
	'author'         => $headers['Author'],
	'plugin_name'    => $headers['Name'],
	'description'    => $headers['Description'],
	'version'        => $headers['Version'],
	'tested'         => $headers['Tested'],
	'requires_php'   => $headers['RequiresPHP'],
	'url'            => $headers['PluginURI'],
	'icons'          => [
		'default' => $base_url . "images/logo-$slug.png",
	],
	'banners'        => [
		'low' => $base_url . "images/banner-$slug-772x250.png",
	],
	'banners_rtl'    => [],
	'upgrade_notice' => $sections['UpgradeNotice'],
	'homepage'       => $headers['PluginURI'],
	'package'        => $base_url . $zipfile,
	'requires'       => $headers['Requires'],
	'downloaded'     => $count,
	'last_updated'   => strftime( '%Y-%m-%d %R', filemtime( $zipfile ) ),
	'download_link'  => $base_url . $zipfile,
	'license'        => $headers['License'],
	'sections'       => [
		'description' => $sections['Description'],
		'changelog'   => $sections['Changelog'],
	],
	'fields'         => [
		'short_description' => true,
		'sections'          => true,
		'rating'            => false,
		'ratings'           => false,
		'added'             => false,
		'tags'              => true,
		'donate_link'       => false,
		'reviews'           => false,
		'versions'          => false,
		'compatibility'     => false,
		'banners'           => true,
		'icons'             => true,
	],
];

$obj_version = (object) [
	'id'           => $slug,
	'slug'         => $slug,
	'plugin'       => "$slug/$slug.php",
	'new_version'  => $headers['Version'],
	'tested'       => $headers['Tested'],
	'requires_php' => $headers['RequiresPHP'],
	'url'          => $headers['PluginURI'],
	'package'      => $base_url . $zipfile,
	'icons'        => [
		'default' => $base_url . "images/logo-$slug.png",
	],
	'banners'      => [
		'low' => $base_url . "images/banner-$slug-772x250.png",
	],
	'banners_rtl'  => [],
];

switch ( $action ) {
	case 'version':
		echo serialize( $obj_version );
		break;
	case 'info':
		echo serialize( $obj_info );
		break;
	default:
		header( 'Cache-Control: public' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/zip' );
		readfile( $zipfile );
		file_put_contents( $path . $counterfile, ++$count . "\n", LOCK_EX );
		break;
}
