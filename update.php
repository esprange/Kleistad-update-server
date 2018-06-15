<?php
/**
 * The remote host file to process update requests
 *
 */

$path    = pathinfo( realpath( __FILE__ ), PATHINFO_DIRNAME ) . '/';
 
ini_set( 'log_errors', E_ALL );
ini_set( 'error_log', $path . '/error.log' );

$slug        = 'kleistad';
$zipfile     = "$slug.zip";
$pluginfile  = "$slug.php";
$counterfile = 'counter.txt';

if ( !isset( $_POST['action'] ) ) {
	// echo '0';
	header( 'Cache-Control: public' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-Type: application/zip' );
	readfile( $zipfile );
	
	$fp = @fopen( $counterfile, 'r+' );
	if ( FALSE === $fp ) {
		$fp = fopen( $counterfile, 'w+' );
	}	
	while ( ! flock( $fp, LOCK_EX ) ) {
	}
	$count = intval( fread( $fp, filesize( $counterfile ) ) );
	$count++;
	ftruncate( $fp, 0 );
	fwrite( $fp, $count );
	fflush( $fp );
	flock( $fp, LOCK_UN );
	fclose( $fp );
	exit;
}

$fp    = fopen( "zip://$path/$zipfile#$slug/$pluginfile", 'r' );
$data  = fread( $fp, 8192 );
fclose ( $fp );

$fp    = fopen( "zip://$path/$zipfile#$slug/README.txt", 'r' );
$data .= fread( $fp, 8192 );
fclose ( $fp );

$fp    = @fopen( $counterfile, 'r' );
if ( FALSE === $fp ) {
	$count = 0;
	$fp = fopen( $counterfile, 'w+' );
	ftruncate( $fp, 0 );
	fwrite( $fp, $count );
	fflush( $fp );
	flock( $fp, LOCK_UN );
} else {
	$count = intval( fread( $fp, filesize( $counterfile ) ) );
}
fclose ( $fp ); 

$headers = 	[
	'Name'        => 'Plugin Name',
	'PluginURI'   => 'Plugin URI',
	'Version'     => 'Version',
	'Description' => 'Description',
	'Author'      => 'Author',
	'AuthorURI'   => 'Author URI',
	'Requires'    => 'Requires at least',
	'Tested'      => 'Tested up to',
	'License'     => 'License',
];

$data = str_replace( "\r", "\n", $data );
foreach ( $headers as $field => $regex ) {
	if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $data, $match ) && $match[1] )
		$headers[ $field ] = trim( preg_replace( "/\s*(?:\*\/|\?>).*/", '', $match[1] ) );
	else
		$headers[ $field ] = '';
}

//set up the properties common to both requests 
$obj                  = new \stdClass();
$obj->slug            = $slug;
$obj->name            = $headers['Name'];
$obj->author          = $headers['Author'];
$obj->plugin_name     = $headers['Name'];
$obj->description     = $headers['Description'];
$obj->new_version     = $headers['Version'];
$obj->tested          = $headers['Tested'];
$obj->required_php    = '5.6';  
$obj->url             = $headers['PluginURI'];
$obj->homepage        = $obj->url;
$obj->package         = 'http://' . $_SERVER['HTTP_HOST'] . dirname( $_SERVER['PHP_SELF'] ) . '/' . $zipfile;
$obj->requires        = $headers['Requires'];  
$obj->downloaded      = $count;
//$obj->active_installs = 1;
$obj->last_updated    = strftime( '%Y-%m-%d', filemtime( $zipfile ) );  
$obj->download_link   = $obj->package;
$obj->license         = $headers['License'];
// $obj->sections        = [  
// 	'description'     => 'De nieuwe versie van de Kleistad plugin',  
// 	'another_section' => 'This is another section',  
// 	'changelog'       => 'Some new features'  
// ];
// $obj->fields       = [
// 	'banners' => [],
// 	'reviews' => false,
// ];
// $obj->banners = [
//  			'low' => 'https://ps.w.org/wp-mybackup/assets/banner-772x250.png?rev=1244519',
//  			'high' => '',
// ];

switch ( $_POST['action'] ) {

case 'version':  
	echo serialize( $obj );
	break;  
case 'info':   
	echo serialize( $obj );  
}  

?>
