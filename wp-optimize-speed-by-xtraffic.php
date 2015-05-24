<?php
/*
Plugin Name: WP Optimize Speed By xTraffic
Version: 1.0.1
Plugin URI: http://blog-xtraffic.pep.vn/
Author: xTraffic
Author URI: http://blog-xtraffic.pep.vn/
Description: Plugin speeds up your website runs faster and better performance.
*/

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_INIT' ) ) : 

define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_INIT', 1 );

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_VERSION' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_VERSION', '1.0.1' );
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_TIMESTART' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_TIMESTART', microtime(true));
}

global $wpOptimizeSpeedByxTraffic;

if(isset($wpOptimizeSpeedByxTraffic) && $wpOptimizeSpeedByxTraffic) {
	
} else {
	$wpOptimizeSpeedByxTraffic = false;
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_FILE' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_FILE', __FILE__ );
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PATH' ) ) { 
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PATH', plugin_dir_path( WPOPTIMIZESPEEDBYXTRAFFIC_FILE ) );
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG' ) ) {
	include_once(WPOPTIMIZESPEEDBYXTRAFFIC_PATH . 'inc/init-constant.php');
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_URL' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_URL', plugins_url( WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG ).'/' ); 
}

if ( ! defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_ADMIN_AJAX_URL' ) ) {
	define( 'WPOPTIMIZESPEEDBYXTRAFFIC_ADMIN_AJAX_URL', admin_url('admin-ajax.php')); 
}

require_once(WPOPTIMIZESPEEDBYXTRAFFIC_PATH.'inc/class/WPOptimizeSpeedByxTraffic_OptimizeSpeed.php');

if ( !class_exists('WPOptimizeSpeedByxTraffic') ) :

class WPOptimizeSpeedByxTraffic extends WPOptimizeSpeedByxTraffic_OptimizeSpeed
{
	public function __construct() 
	{
		parent::__construct();
	}
	
	// Set up everything when plugin active
	public function activation()
	{
		$_SESSION[WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG.'-activation'] = time();
		$this->base_activate();
	}
	
	//when plugin deactivate
	public function deactivate() 
	{
		$this->base_deactivate();
	}

}//class WPOptimizeSpeedByxTraffic

endif; //if ( !class_exists('WPOptimizeSpeedByxTraffic') )

if ( class_exists('WPOptimizeSpeedByxTraffic') ) :
	
	global $wpOptimizeSpeedByxTraffic;
	$wpOptimizeSpeedByxTraffic = new WPOptimizeSpeedByxTraffic();
	
	if (isset($wpOptimizeSpeedByxTraffic) && $wpOptimizeSpeedByxTraffic) : 
		
		register_activation_hook( __FILE__, array(&$wpOptimizeSpeedByxTraffic, 'activation') );
		register_deactivation_hook( __FILE__, array(&$wpOptimizeSpeedByxTraffic, 'deactivate') );
		
		//Action when wordpress init : 1
		function wpOptimizeSpeedByxTraffic_init_first()
		{
			global $wpOptimizeByxTraffic_AdvancedCache;
			
			if(isset($wpOptimizeByxTraffic_AdvancedCache) && $wpOptimizeByxTraffic_AdvancedCache) {
				//$wpOptimizeByxTraffic_AdvancedCache->checkAndInitWpdbCache();
			}
		}


		//Action when wordpress init
		function wpOptimizeSpeedByxTraffic_init_last()
		{
			
		}
		
		function wpOptimizeSpeedByxTraffic_wp_loaded_first() 
		{
			
		}
		
		function wpOptimizeSpeedByxTraffic_wp_loaded_last() 
		{
			global $wpOptimizeSpeedByxTraffic;
			
			if(isset($_SESSION[WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG.'-activation'])) {
				unset($_SESSION[WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG.'-activation']);
				$wpOptimizeSpeedByxTraffic->migrationOptions();
			}
			
			$wpOptimizeSpeedByxTraffic->setDefaultParams();
			if(is_admin()) {
				$wpOptimizeSpeedByxTraffic->notice_required_others_plugins();
				add_action('admin_notices', array(&$wpOptimizeSpeedByxTraffic,'admin_notices'));
			}
		}



		function wpOptimizeSpeedByxTraffic_wp_init_first() 
		{
			
		}


		
		function wpOptimizeSpeedByxTraffic_wp_init_last() 
		{
			
			
		}

		


		
		function wpOptimizeSpeedByxTraffic_wp_shutdown_first() 
		{
			
		}
		
		function wpOptimizeSpeedByxTraffic_wp_shutdown_last() 
		{
			
		}
		
		
		
		
		

		/*
		* @init 1
		* Runs after WordPress has finished loading but before any headers are sent. Useful for intercepting $_GET or $_POST triggers.
		*/
		add_action('init', 'wpOptimizeSpeedByxTraffic_init_first', 0);

		add_action('init', 'wpOptimizeSpeedByxTraffic_init_last', 999999999.0000000001);
		
		
		/*
		* @wp_loaded 2
		* After WordPress is fully loaded. This action hook is fired once WordPress, all plugins, and the theme are fully loaded and instantiated.
		*/
		add_action('wp_loaded','wpOptimizeSpeedByxTraffic_wp_loaded_first', 0.0000000001);
		
		add_action('wp_loaded','wpOptimizeSpeedByxTraffic_wp_loaded_last', 999999999.0000000001);
		
		
		
		/*
		* @wp 3
		* This action hook runs immediately after the global WP class object is set up. The $wp object is passed to the hooked function as a reference (no return is necessary).
		* This hook is one effective place to perform any high-level filtering or validation, following queries, but before WordPress does any routing, processing, or handling.
		*/
		add_action( 'wp', 'wpOptimizeSpeedByxTraffic_wp_init_first', 0.0000000001 );
		
		add_action( 'wp', 'wpOptimizeSpeedByxTraffic_wp_init_last', 999999999.0000000001 );
		
		
		/*
		* @shutdown 4
		* 
		*/
		add_action( 'shutdown', 'wpOptimizeSpeedByxTraffic_wp_shutdown_first', 0.0000000001 );
		
		add_action( 'shutdown', 'wpOptimizeSpeedByxTraffic_wp_shutdown_last', 999999999.0000000001 );
		
		
		
		
	endif;
	
endif;






endif;

