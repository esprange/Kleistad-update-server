<?php
/**
 * The remote host file to process update requests
 *
 * @author   Eric Sprangers <eric.sprangers@gmail.com>
 * @license  GPL3.0
 * @package  Kleistad
 */

declare( strict_types = 1 );

define( 'COUNTERFILE', 'counter.txt' );
define( 'LOGFILE', 'error.log' );

ini_set( 'log_errors', '1' );
ini_set( 'error_log', LOGFILE );
error_reporting( E_ALL );

$action   = filter_input( INPUT_GET, 'action' );
$base_url = ( ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . dirname( $_SERVER['PHP_SELF'] ) . '/';
error_log( $_SERVER['REMOTE_ADDR'] . " : $action" );

try {
	$zipfiles = glob( '*.zip' );
	if ( empty( $zipfiles ) ) {
		throw new Exception( 'No zip file found' );
	} else {
		$zipfile = $zipfiles[0];
	}

	$slug = basename( $zipfile, '.zip' );
	$zip  = new ZipArchive();
	if ( true === $zip->open( $zipfile ) ) {
		$data_pluginfile = $zip->getFromName( "$slug/$slug.php" );
		if ( false === $data_pluginfile ) {
			throw new Exception( "Missing $slug.php file" );
		}
		$data_readmefile = $zip->getFromName( "$slug/README.txt" );
		if ( false === $data_readmefile ) {
			throw new Exception( 'Missing README.txt file' );
		};
		$zip->close();
	} else {
		throw new Exception( "Zip file $zipfile cannot be openend" );
	}
} catch ( Exception $exception ) {
	error_log( $exception->getMessage() );
	die;
}

$headers = array(
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
);

$data = str_replace( "\r", "\n", $data_pluginfile . $data_readmefile );
foreach ( $headers as $field => $regex ) {
	if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $data, $match ) && $match[1] ) {
		$headers[ $field ] = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $match[1] ) );
	} else {
		$headers[ $field ] = '';
	}
}

$sections = array(
	'Description'   => 'Description',
	'Changelog'     => 'Changelog',
	'UpgradeNotice' => 'Upgrade Notice',
);

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
				$line = ( $list ? '</ul>' : '' ) . preg_replace( array( '/= /', '/ =/' ), array( '<h4>', '</h4>' ), $line );
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
$obj_info = (object) array(
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
	'icons'          => array(
		'default' => $base_url . "images/logo-$slug.png",
	),
	'banners'        => array(
		'low' => $base_url . "images/banner-$slug-772x250.png",
	),
	'banners_rtl'    => array(),
	'upgrade_notice' => $sections['UpgradeNotice'],
	'homepage'       => $headers['PluginURI'],
	'package'        => $base_url . $zipfile,
	'requires'       => $headers['Requires'],
	'downloaded'     => @filesize( COUNTERFILE ) ?: 0, // phpcs:ignore
	'last_updated'   => strftime( '%Y-%m-%d %R', filemtime( $zipfile ) ),
	'download_link'  => $base_url . $zipfile,
	'license'        => $headers['License'],
	'sections'       => array(
		'description' => $sections['Description'],
		'changelog'   => $sections['Changelog'],
	),
	'fields'         => array(
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
	),
);

$obj_version = (object) array(
	'id'           => $slug,
	'slug'         => $slug,
	'plugin'       => "$slug/$slug.php",
	'new_version'  => $headers['Version'],
	'tested'       => $headers['Tested'],
	'requires_php' => $headers['RequiresPHP'],
	'url'          => $headers['PluginURI'],
	'package'      => $base_url . $zipfile,
	'icons'        => array(
		'default' => $base_url . "images/logo-$slug.png",
	),
	'banners'      => array(
		'low' => $base_url . "images/banner-$slug-772x250.png",
	),
	'banners_rtl'  => array(),
);

switch ( $action ) {
	case 'version':
		echo serialize( $obj_version );
		break;
	case 'info':
		echo serialize( $obj_info );
		break;
	default:
		file_put_contents( COUNTERFILE, '1', FILE_APPEND ); // phpcs:ignore
		header( 'Cache-Control: public' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $slug . '.zip"' );
		readfile( $zipfile );
}
