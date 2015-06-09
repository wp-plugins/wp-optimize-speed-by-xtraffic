<?php 




if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_VERSION' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_VERSION', '1.0.2' ); 
}


if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG' ) ) {	//basic 
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG', 'wp-optimize-speed-by-xtraffic' );
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_OPTIONS_CACHE_KEY' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_OPTIONS_CACHE_KEY', WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG . '-options-cache-key' );
}


if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PATH' ) ) { 
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PATH', WP_CONTENT_DIR . '/plugins/'.WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG.'/' );
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME', 'WP Optimize Speed By xTraffic' );
}


if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NS' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NS', 'wpoptspbxtr' );
}


function wpOptimizeSpeedByxTraffic_autoloader($class) 
{
	if ( !class_exists($class) ) {
		$filePath = WPOPTIMIZESPEEDBYXTRAFFIC_PATH.'inc/class/'.$class.'.php';
		if(file_exists($filePath) && is_file($filePath)) {
			include_once($filePath);
		}
	}
}

spl_autoload_register('wpOptimizeSpeedByxTraffic_autoloader');

