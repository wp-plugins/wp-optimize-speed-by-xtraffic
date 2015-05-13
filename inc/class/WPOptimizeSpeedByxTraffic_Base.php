<?php

if ( !class_exists('WPOptimizeSpeedByxTraffic_Base') ) :


class WPOptimizeSpeedByxTraffic_Base 
{
	
	// Name for our options in the DB
	protected $db_option_key = WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NS; 
	
	protected $baseObjects; //array(id => object)
	
	protected $baseCacheData;
	
	protected $adminNoticesData;
	
	protected $PLUGIN_NS = WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NS;
	
	protected $PLUGIN_NAME = WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME;
	
	protected $PLUGIN_SLUG = WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_SLUG;
	
	protected $KEY_CONFIG_CONTENT = 'WPOPTIMIZESPEEDBYXTRAFFIC';
	
	function __construct() 
	{
		$this->adminNoticesData = array();
		
		$this->urlProtocol = 'http:';
		if(is_ssl()) {
			$this->urlProtocol = 'https:';
		}
		
		$this->urlFullRequest = $this->urlProtocol.'//'.$_SERVER['SERVER_NAME']; //SERVER_NAME || HTTP_HOST
		if(isset($_SERVER['REQUEST_URI'])) {
			$this->urlFullRequest .= $_SERVER['REQUEST_URI'];
		}
		
		if(is_admin()) {
			
			if(defined('WPOPTIMIZEBYXTRAFFIC_PLUGIN_SLUG')) {
				add_action('admin_menu',  array(&$this, 'add_admin_menu_page'));
			}
		}
		
		
	}
	
	
	public function add_admin_menu_page()
	{
		global $wpOptimizeByxTraffic;
		
		$admin_page = add_menu_page( 
			'WP Optimize By xTraffic'	//page_title
			,'WP Optimize'	//menu_title
			, 'manage_options'	//capability
			, 'wpoptimizebyxtraffic_dashboard'	//menu_slug
			, array( $wpOptimizeByxTraffic, 'base_dashboard_handle_options' )	//function
			, plugins_url( WPOPTIMIZEBYXTRAFFIC_PLUGIN_SLUG.'/images/icon.png')	//icon_url
			, '100.236899629' //position

		);
		
		// Sub menu pages
		$submenu_pages = array(
			
			 array( 
				'wpoptimizebyxtraffic_dashboard' //parent_slug
				, 'Optimize Speed'	//page_title
				, 'Optimize Speed'	//menu_title
				, 'manage_options'	//capability
				, 'wpoptimizebyxtraffic_optimize_speed'	//menu_slug
				, array( $this, 'page_options' )	//function
				, null
			)
			
		);
		
		if ( count( $submenu_pages ) ) {
			foreach ( $submenu_pages as $submenu_page ) {
				// Add submenu page
				$admin_page = add_submenu_page( $submenu_page[0], $submenu_page[1], $submenu_page[2], $submenu_page[3], $submenu_page[4], $submenu_page[5] );
			}
		}
		
	}
	
	public function notice_required_others_plugins()
	{
		
		$checkStatus1 = false;
		if(false === stripos($this->urlFullRequest,'/wp-admin/plugin-install.php')) {
			if(is_admin()) {
				if ( is_user_logged_in() ) {
					if ( current_user_can('activate_plugins') ) {
						$checkStatus1 = true;
					}
				}
			}
		}
		
		if($checkStatus1) {
			
			if(is_ssl()) {
				$adminUrl = admin_url( '', 'https' );
			} else {
				$adminUrl = admin_url( '', 'http' );
			}
			
			$pluginPathActived = array();
			
			if(
				isset($_GET['xtr-active-plugin-key']) && $_GET['xtr-active-plugin-key']
				&& isset($_GET['xtr-active-plugin-name']) && $_GET['xtr-active-plugin-name']
			) {
				$this->run_activate_plugin($_GET['xtr-active-plugin-key']);
				$this->adminNoticesData['success'][] = 'Plugin "<u><b>'.$_GET['xtr-active-plugin-name'].'</b></u>" activated successfully!';
				$pluginPathActived[] = $_GET['xtr-active-plugin-key'];
			}
			
			$rsOne = $this->check_required_others_plugins();
			foreach($rsOne['plugins'] as $pluginKey => $pluginVal) {
				if(
					isset($pluginVal['errors']) && ($pluginVal['errors'])
					&& is_array($pluginVal['errors']) && (!empty($pluginVal['errors']))
				) {
					foreach($pluginVal['errors'] as $errorKey => $errorVal) {
						
						if(
							('not_installed' === $errorKey)
							|| ('at_least_version' === $errorKey)
						) {
							if(isset($pluginVal['wp_plugin_url'])) {
								
								$install_plugin_install_status = $this->install_plugin_install_status(array(
									'slug' => $pluginKey
									,'fields' => array()
								));
								
								
								if(isset($install_plugin_install_status['url']) && $install_plugin_install_status['url']) {
									
									if(
										('not_installed' === $errorKey)
									) {
										$this->adminNoticesData['error'][] = 'Plugin "<u><b>'.WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME.'</b></u>" requires the following plugin "<u><b>'.$pluginVal['name'].'</b></u>"!<br />Please <a href="'.$install_plugin_install_status['url'].'"><u><b><i>click here to install this plugin</i></b></u></a>!';
									} else if(
										('at_least_version' === $errorKey)
									) {
										$this->adminNoticesData['error'][] = 'Plugin "<u><b>'.WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME.'</b></u>" requires at <u><b>least version '.$errorVal.'</b></u> of plugin "<u><b>'.$pluginVal['name'].'</b></u>"!<br />Please <a href="'.$install_plugin_install_status['url'].'"><u><b><i>click here to upgrade this plugin</i></b></u></a>!';
									}
								}
							}
						} else if('not_active' === $errorKey) {
							if(!in_array($pluginVal['file_path_key'],$pluginPathActived)) {
								if(isset($pluginVal['wp_plugin_url'])) {
									$this->adminNoticesData['error'][] = 'Plugin "<u><b>'.WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME.'</b></u>" requires the following plugin "<u><b>'.$pluginVal['name'].'</b></u>"!<br />Please <a href="'.add_query_arg(array(
										'xtr-active-plugin-key' => rawurlencode($pluginVal['file_path_key'])
										,'xtr-active-plugin-name' => rawurlencode($pluginVal['name'])
									), $adminUrl.'plugins.php?').'"><u><b><i>click here to activate this plugin</i></b></u></a>!';
								}
							}
						}
						
					}
				}
			}
		}
		
	}
	
	
	public function run_activate_plugin( $plugin ) 
	{
		$current = get_option( 'active_plugins' );
		$plugin = plugin_basename( trim( $plugin ) );

		if ( !in_array( $plugin, $current ) ) {
			$current[] = $plugin;
			sort( $current );
			do_action( 'activate_plugin', trim( $plugin ) );
			update_option( 'active_plugins', $current );
			do_action( 'activate_' . trim( $plugin ) );
			do_action( 'activated_plugin', trim( $plugin) );
		}

		return null;
	}

	
	public function check_required_others_plugins() 
	{
		$keyCache = md5(__FILE__ . __METHOD__);
		if(isset($this->baseCacheData[$keyCache])) {
			return $this->baseCacheData[$keyCache];
		}
		
		$resultData = array(
			'plugins' => array(
				'wp-optimize-by-xtraffic' => array(
					'name' => 'WP Optimize By xTraffic'
					, 'wp_plugin_url' => 'https://wordpress.org/plugins/wp-optimize-by-xtraffic/'
					, 'at_least_version' => '5.0.0'
				)
			)
		);
		
		foreach($resultData['plugins'] as $keyOne => $valueOne) {
			$valueOne['file_path_key'] = $keyOne.'/'.$keyOne.'.php';
			$valueOne['file_path'] = ABSPATH . 'wp-content/plugins/'.$valueOne['file_path_key'];
			
			$resultData['plugins'][$keyOne] = $valueOne;
		}
		
		foreach($resultData['plugins'] as $key1 => $val1) {
			if('wp-optimize-by-xtraffic' === $key1) {
				global $wpOptimizeByxTraffic;
				if(
					defined('WPOPTIMIZEBYXTRAFFIC_PLUGIN_VERSION')
					&& defined('WPOPTIMIZEBYXTRAFFIC_PLUGIN_SLUG')
					&& class_exists('WPOptimizeByxTraffic')
					&& isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic
				) {
					
					if(isset($val1['at_least_version'])) {
						if (version_compare(WPOPTIMIZEBYXTRAFFIC_PLUGIN_VERSION, $val1['at_least_version']) >= 0) {
							$resultData['plugins'][$key1]['success']['all_valid'] = true;
							$resultData['plugins'][$key1]['success']['activated'] = true;
							$resultData['plugins'][$key1]['success']['installed'] = true;
						} else {
							$resultData['plugins'][$key1]['errors']['at_least_version'] = $val1['at_least_version'];
							$resultData['plugins'][$key1]['success']['activated'] = true;
							$resultData['plugins'][$key1]['success']['installed'] = true;
						}
					} else {
						$resultData['plugins'][$key1]['success']['all_valid'] = true;
						$resultData['plugins'][$key1]['success']['activated'] = true;
						$resultData['plugins'][$key1]['success']['installed'] = true;
					}
					
				} else {
					
					if(
						file_exists($resultData['plugins'][$key1]['file_path'])
						&& is_file($resultData['plugins'][$key1]['file_path'])
					) {
						$resultData['plugins'][$key1]['errors']['not_active'] = 'not_active';
						$resultData['plugins'][$key1]['success']['installed'] = true;
					} else {
						$resultData['plugins'][$key1]['errors']['not_installed'] = 'not_installed';
					}
				}
				
			}
		}
		
		$this->baseCacheData[$keyCache] = $resultData;
		
		return $resultData;
	}
	
	public function base_parse_display_notices($input_notices) 
	{
		$resultData = $this->base_parse_notices_for_display($input_notices);
		if(count($resultData) > 0) {
			echo implode('',$resultData);
			return true;
		}
		
		return false;
	}
	
	public function base_parse_notices_for_display($input_notices) 
	{
		$resultData = array();
		
		$input_notices = (array)$input_notices;
		
		if(isset($input_notices['success'])) {
			if($input_notices['success']) {
				$input_notices['success'] = (array)$input_notices['success'];
				$input_notices['success'] = array_unique($input_notices['success']);
				foreach($input_notices['success'] as $valueOne) {
					$resultData[] = '<div style="display:block;"><div class="updated fade wpoptxtr_success" style="padding: 1% 2%;"><b>'.WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME.'</b> : '.$valueOne.'</div></div>';
				}
			}
		}
		
		if(isset($input_notices['error'])) {
			if($input_notices['error']) {
				$input_notices['error'] = (array)$input_notices['error'];
				$input_notices['error'] = array_unique($input_notices['error']);
				foreach($input_notices['error'] as $valueOne) {
					$resultData[] = '<div style="display:block;"><div class="update-nag fade wpoptxtr_error" style="padding: 1% 2%;"><b>'.WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_NAME.'</b> : '.$valueOne.'</div></div>';
				}
			}
		}
		
		$resultData = array_unique($resultData);
		
		return $resultData;
	}
	
	
	public function admin_notices() 
	{
		if(is_admin()) {
			$this->adminNoticesData = (array)$this->adminNoticesData;
			if(!empty($this->adminNoticesData)) {
				$this->base_parse_display_notices($this->adminNoticesData);
			}
			$this->adminNoticesData = array();
		}
	}
	
	public function install_plugin_install_status($input_args)
	{
		if(!function_exists('install_plugin_install_status')) {
			require_once (ABSPATH . 'wp-admin/includes/plugin-install.php');
        }
		if(!function_exists('get_plugins')) {
			require_once (ABSPATH . 'wp-admin/includes/plugin.php');
        }
		return install_plugin_install_status($this->get_plugin_info($input_args), false);
	}
	
	public function get_plugin_info($input_args)
	{
        
		if(!isset($input_args['fields'])) {
			$input_args['fields'] = array();
		}
		
		$keyCache = md5(serialize($input_args));
		if(isset($this->baseCacheData[$keyCache])) {
			return $this->baseCacheData[$keyCache];
		}
		
		if(!function_exists('plugins_api')) {
            require_once (ABSPATH . 'wp-admin/includes/plugin-install.php');
        }
		if(!function_exists('get_plugins')) {
			require_once (ABSPATH . 'wp-admin/includes/plugin.php');
        }
		
        $fields = array(
            'short_description' => true,
            'screenshots' => false,
            'changelog' => false,
            'installation' => false,
            'description' => false
        );
		
		$fields = array_merge($fields, (array)$input_args['fields']);
		
        $args = array(
            'slug' => $input_args['slug'],
            'fields' => $fields
		);
		
        $this->baseCacheData[$keyCache] = plugins_api('plugin_information', $args);
		
        return $this->baseCacheData[$keyCache];
		
    }
	
	public function base_activate()
	{
		global $wpOptimizeByxTraffic;
		
		if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
			$this->base_clear_files_config_content();
			$this->base_activate_optimize_speed();
			$wpOptimizeByxTraffic->base_clear_data(',all,');
		}
	}
	
	public function base_deactivate()
	{
		global $wpOptimizeByxTraffic;
		
		if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
			$this->base_clear_files_config_content();
			$wpOptimizeByxTraffic->base_clear_data(',all,');
		}
	}
	
	public function base_clear_config_content($input_data)
	{
		
		$input_data = preg_replace('/[\s \t]+(\#\#\# BEGIN '.$this->KEY_CONFIG_CONTENT.' \#\#\#)/s', PHP_EOL . ' $1' ,$input_data);
		$input_data = preg_replace('/(\#\#\# END '.$this->KEY_CONFIG_CONTENT.' \#\#\#)[\s \t]+/s', '$1 ' . PHP_EOL ,$input_data);
		$input_data = preg_replace('/([\s \t]*?)\#\#\# BEGIN '.$this->KEY_CONFIG_CONTENT.' \#\#\#.+\#\#\# END '.$this->KEY_CONFIG_CONTENT.' \#\#\#([\s \t]*?)/s', PHP_EOL ,$input_data);
		
		return $input_data;
	}
	
	public function base_clear_files_config_content()
	{
		global $wpOptimizeByxTraffic;
		
		if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
			$rsOne = $wpOptimizeByxTraffic->base_get_all_files_config_content_available();
			foreach($rsOne as $key1 => $value1) {
				if($value1) {
					$fileConfigContent = @file_get_contents($value1);
					$fileConfigContent = $this->base_clear_config_content($fileConfigContent);
					@file_put_contents($value1,$fileConfigContent);
				}
			}
		}
	}
	
	public function base_activate_optimize_speed()
	{
		global $wpOptimizeByxTraffic;
		
		if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
			
			$options = $this->get_options();
			
			$wpOptimizeByxTraffic_options = $wpOptimizeByxTraffic->get_options(array(
				'cache_status' => 1
			));
			
			$pathRootWP = ABSPATH;
			
			if($wpOptimizeByxTraffic->is_subdirectory_install()){
				$pathRootWP = $wpOptimizeByxTraffic->base_getABSPATH();
			}
			
			$pathRootWP_PlusToCache = $wpOptimizeByxTraffic->base_get_folder_plus_path_for_cache();
			
			$pluginNameVersion = WPOPTIMIZEBYXTRAFFIC_PLUGIN_NAME.'/'.WPOPTIMIZEBYXTRAFFIC_PLUGIN_VERSION;
			
			
			$mimeTypesEnableGzip = array(
				'text/html', 'text/xml', 'text/css', 'text/plain', 'text/x-component', 'text/x-js', 'text/richtext', 'text/xsd', 'text/xsl'
				,'image/svg+xml', 'application/xhtml+xml', 'application/xml', 'image/x-icon'
				,'application/rdf+xml','application/xml+rdf', 'application/rss+xml', 'application/xml+rss', 'application/atom+xml', 'application/xml+atom'
				,'text/javascript', 'application/javascript', 'application/x-javascript', 'application/json'
				,'application/x-font-ttf', 'application/x-font-otf'
				,'font/truetype', 'font/opentype'
			);
			
			if('apache' === PepVN_Data::$defaultParams['serverSoftware']) {
				
				$pathFileHtaccess = $pathRootWP.'.htaccess';
				
				$htaccessContent = false;
				
				if(file_exists($pathFileHtaccess) && is_file($pathFileHtaccess) && is_writable($pathFileHtaccess)){
					$htaccessContent = @file_get_contents($pathFileHtaccess);
				} else if(PepVN_Data::is_writable($pathRootWP)) {
					@file_put_contents($pathFileHtaccess,'');
					if(PepVN_Data::is_writable($pathFileHtaccess)) {
						$htaccessContent = @file_get_contents($pathFileHtaccess);
					}
				}
				
				if(false !== $htaccessContent) {
					
					$htaccessContent = trim($htaccessContent); 
					
					$myHtaccessConfig_RewriteBase_PlusToCache = '';
					$myHtaccessConfig_RewriteRule_PlusToCache = '';
					
					$myHtaccessConfig_RewriteBase_PlusToCache2 = '';
					$myHtaccessConfig_RewriteRule_PlusToCache2 = '';
					
					if(strlen($pathRootWP_PlusToCache) > 0) {
						$myHtaccessConfig_RewriteBase_PlusToCache = $pathRootWP_PlusToCache.'/';
						$myHtaccessConfig_RewriteRule_PlusToCache = '/'.$pathRootWP_PlusToCache;
						
						$myHtaccessConfig_RewriteBase_PlusToCache2 = '/'.$pathRootWP_PlusToCache;
						$myHtaccessConfig_RewriteRule_PlusToCache2 = '/'.$pathRootWP_PlusToCache;
					}
					
					$myHtaccessConfig_ForNotCacheMobile = 
	PHP_EOL . 'RewriteCond %{HTTP:X-Wap-Profile} !^[a-z0-9\"]+ [NC]'
	. PHP_EOL . 'RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]'
	. PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT} !^.*(2.0\ MMP|240x320|400X240|AvantGo|BlackBerry|Blazer|Cellphone|Danger|DoCoMo|Elaine/3.0|EudoraWeb|Googlebot-Mobile|hiptop|IEMobile|KYOCERA/WX310K|LG/U990|MIDP-2.|MMEF20|MOT-V|NetFront|Newt|Nintendo\ Wii|Nitro|Nokia|Opera\ Mini|Palm|PlayStation\ Portable|portalmmm|Proxinet|ProxiNet|SHARP-TQ-GX10|SHG-i900|Small|SonyEricsson|Symbian\ OS|SymbianOS|TS21i-10|UP.Browser|UP.Link|webOS|Windows\ CE|WinWAP|YahooSeeker/M1A1-R2D2|iPhone|iPod|Android|BlackBerry9530|LG-TU915\ Obigo|LGE\ VX|webOS|Nokia5800).* [NC]'
	. PHP_EOL . 'RewriteCond %{HTTP_user_agent} !^(w3c\ |w3c-|acs-|alav|alca|amoi|audi|avan|benq|bird|blac|blaz|brew|cell|cldc|cmd-|dang|doco|eric|hipt|htc_|inno|ipaq|ipod|jigs|kddi|keji|leno|lg-c|lg-d|lg-g|lge-|lg/u|maui|maxo|midp|mits|mmef|mobi|mot-|moto|mwbp|nec-|newt|noki|palm|pana|pant|phil|play|port|prox|qwap|sage|sams|sany|sch-|sec-|send|seri|sgh-|shar|sie-|siem|smal|smar|sony|sph-|symb|t-mo|teli|tim-|tosh|tsm-|upg1|upsi|vk-v|voda|wap-|wapa|wapi|wapp|wapr|webc|winw|winw|xda\ |xda-).* [NC]'
	. PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT} !(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge\ |maemo|midp|mmp|mobile.+firefox|netfront|opera\ m(ob|in)i|palm(\ os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows\ ce|xda|xiino [NC,OR]'
	. PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT} !^(1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a\ wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r\ |s\ )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1\ u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp(\ i|ip)|hs\-c|ht(c(\-|\ |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac(\ |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt(\ |\/)|klon|kpt\ |kwc\-|kyo(c|k)|le(no|xi)|lg(\ g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-|\ |o|v)|zz)|mt(50|p1|v\ )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v\ )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-|\ )|webc|whit|wi(g\ |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-) [NC]';
					
					if(isset($options['optimize_cache_mobile_device_cache_enable']) && $options['optimize_cache_mobile_device_cache_enable']) {
						$myHtaccessConfig_ForNotCacheMobile = '';
					}
					
					$myHtaccessConfig_AutoResizeImagesFitScreenWidth1 = '';
					$myHtaccessConfig_AutoResizeImagesFitScreenWidth2 = '';
					if(isset($wpOptimizeByxTraffic_options['optimize_images_auto_resize_images_fit_screen_width_enable']) && $wpOptimizeByxTraffic_options['optimize_images_auto_resize_images_fit_screen_width_enable']) {
						$myHtaccessConfig_AutoResizeImagesFitScreenWidth1 = PHP_EOL.'RewriteCond %{HTTP_COOKIE} xtrdvscwd=([^;]+) [NC]';
						$myHtaccessConfig_AutoResizeImagesFitScreenWidth2 = 'sw_%3';
					}
					
					
					$myHtaccessConfig = 
	'

	### BEGIN '.$this->KEY_CONFIG_CONTENT.' ###

	<IfModule pagespeed_module>
	ModPagespeed on
	</IfModule>

	<ifModule mod_deflate.c>
		AddOutputFilterByType DEFLATE '.implode(' ',$mimeTypesEnableGzip).'
		<IfModule mod_headers.c>
			Header append Vary User-Agent env=!dont-vary
		</IfModule>
		<IfModule mod_mime.c>
			AddOutputFilter DEFLATE js css htm html xml
		</IfModule>
	</ifModule>


	<ifModule mod_expires.c>
	ExpiresActive On
	ExpiresDefault "access plus 10 seconds"
	ExpiresByType text/cache-manifest "access plus 0 seconds"

	# Data
	ExpiresByType text/xml "access plus 0 seconds"
	ExpiresByType application/xml "access plus 0 seconds"
	ExpiresByType application/json "access plus 0 seconds"

	# Feed
	ExpiresByType application/rss+xml "access plus 3600 seconds"
	ExpiresByType application/atom+xml "access plus 3600 seconds"

	# Favicon
	ExpiresByType image/x-icon "access plus 31536000 seconds"

	# Media: images, video, audio
	ExpiresByType image/gif "access plus 31536000 seconds"
	ExpiresByType image/png "access plus 31536000 seconds"
	ExpiresByType image/jpeg "access plus 31536000 seconds"
	ExpiresByType image/jpg "access plus 31536000 seconds"
	ExpiresByType video/ogg "access plus 31536000 seconds"
	ExpiresByType audio/ogg "access plus 31536000 seconds"
	ExpiresByType video/mp4 "access plus 31536000 seconds"
	ExpiresByType video/webm "access plus 31536000 seconds"

	# HTC files  (css3pie)
	ExpiresByType text/x-component "access plus 31536000 seconds"

	# Webfonts
	ExpiresByType application/x-font-ttf "access plus 31536000 seconds"
	ExpiresByType font/opentype "access plus 31536000 seconds"
	ExpiresByType font/woff2 "access plus 31536000 seconds"
	ExpiresByType application/x-font-woff "access plus 31536000 seconds"
	ExpiresByType image/svg+xml "access plus 31536000 seconds"
	ExpiresByType application/vnd.ms-fontobject "access plus 31536000 seconds"

	# CSS and JavaScript
	ExpiresByType text/css "access plus 31536000 seconds"
	ExpiresByType application/javascript "access plus 31536000 seconds"
	ExpiresByType text/javascript "access plus 31536000 seconds"
	ExpiresByType application/javascript "access plus 31536000 seconds"
	ExpiresByType application/x-javascript "access plus 31536000 seconds"

	# Others files
	ExpiresByType application/x-shockwave-flash "access plus 31536000 seconds"
	ExpiresByType application/octet-stream "access plus 31536000 seconds"
	</ifModule>


	<ifModule mod_headers.c>
		<filesMatch "\.(ico|jpe?g|png|gif|swf)$">
			Header set Cache-Control "public, max-age=31536000"
			Header set Pragma "public"
		</filesMatch>
		<filesMatch "\.(css)$">
			Header set Cache-Control "public, max-age=31536000"
			Header set Pragma "public"
		</filesMatch>
		<filesMatch "\.(js)$">
			Header set Cache-Control "public, max-age=31536000"
			Header set Pragma "public"
		</filesMatch>
		
		Header set X-Powered-By "'.$pluginNameVersion.'"
		Header set Server "'.$pluginNameVersion.'"
	</ifModule>


	<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase /'.$myHtaccessConfig_RewriteBase_PlusToCache.'
	AddDefaultCharset UTF-8
	
	#http + gzip#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.''.$myHtaccessConfig_AutoResizeImagesFitScreenWidth1.'
	RewriteCond %{HTTP:Accept-Encoding} gzip
	RewriteCond %{HTTPS} !on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.html.gz -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.html.gz" [L]

	#http#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.'
	RewriteCond %{HTTPS} !on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.html -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.html" [L]

	#https + gzip#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.'
	RewriteCond %{HTTP:Accept-Encoding} gzip
	RewriteCond %{HTTPS} on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.html.gz -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.html.gz" [L]

	#https#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.'
	RewriteCond %{HTTPS} on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.html -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.html" [L]

	#XML
	
	#http + gzip#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.'
	RewriteCond %{HTTP:Accept-Encoding} gzip
	RewriteCond %{HTTPS} !on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.xml.gz -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.xml.gz" [L]

	#http#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.'
	RewriteCond %{HTTPS} !on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.xml -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.xml" [L]

	#https + gzip#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.'
	RewriteCond %{HTTP:Accept-Encoding} gzip
	RewriteCond %{HTTPS} on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.xml.gz -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.xml.gz" [L]

	#https#
	RewriteCond %{REQUEST_URI} !^.*[^/]$
	RewriteCond %{REQUEST_URI} !^.*//.*$
	RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
	RewriteCond %{REQUEST_METHOD} !POST
	RewriteCond %{QUERY_STRING} !.*=.*
	RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$'.$myHtaccessConfig_ForNotCacheMobile.'
	RewriteCond %{HTTPS} on
	RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.xml -f
	RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.xml" [L]

	</IfModule>

	### END '.$this->KEY_CONFIG_CONTENT.' ###

	';

					$myHtaccessConfig = preg_replace('#\#[^\r\n]+#is','',$myHtaccessConfig);
					$myHtaccessConfig = preg_replace('#[\r\n]{2,}#is',PHP_EOL . PHP_EOL,$myHtaccessConfig);
					
					$myHtaccessConfig = trim($myHtaccessConfig);
					
					$myHtaccessConfig = PHP_EOL . '### BEGIN '.$this->KEY_CONFIG_CONTENT.' ###' . PHP_EOL . $myHtaccessConfig . PHP_EOL . '### END '.$this->KEY_CONFIG_CONTENT.' ###' . PHP_EOL;
					
					$htaccessContent = $myHtaccessConfig . $htaccessContent;
					
					@file_put_contents($pathFileHtaccess,$htaccessContent);
				}
				
				
				$folderPath = WPOPTIMIZEBYXTRAFFIC_WPCONTENT_OPTIMIZE_CACHE_PATH;
				
				$pathFileHtaccess = $folderPath.'.htaccess';
				
				$htaccessContent = false;
				
				if(file_exists($pathFileHtaccess) && is_file($pathFileHtaccess) && is_writable($pathFileHtaccess)){
					$htaccessContent = @file_get_contents($pathFileHtaccess);
				} else if(PepVN_Data::is_writable($folderPath)) {
					@file_put_contents($pathFileHtaccess,'');
					if(PepVN_Data::is_writable($pathFileHtaccess)) {
						$htaccessContent = @file_get_contents($pathFileHtaccess);
					}
				}
				
				
				if(false !== $htaccessContent) {
				
					$htaccessContent = trim($htaccessContent);
					
					$myHtaccessConfig = 
				
	'

	### BEGIN '.$this->KEY_CONFIG_CONTENT.' ###

	<IfModule mod_mime.c>
	  <FilesMatch "\.html\.gz$">
		ForceType text/html
		FileETag None
	  </FilesMatch>
	  AddEncoding gzip .gz
	  AddType text/html .html.gz
	  AddType text/xml .xml.gz
	</IfModule>
	<IfModule mod_deflate.c>
	  SetEnvIfNoCase Request_URI \.gz$ no-gzip
	</IfModule>
	<IfModule mod_headers.c>
	  Header set Vary "Accept-Encoding, Cookie"
	  Header set Cache-Control \'max-age=60, must-revalidate\'
	</IfModule>
	<IfModule mod_expires.c>
	  ExpiresActive On
	  ExpiresByType text/html A60
	  ExpiresByType text/xml A60
	</IfModule>

	### END '.$this->KEY_CONFIG_CONTENT.' ###

	';
					
					$myHtaccessConfig = preg_replace('#\#[^\r\n]+#is','',$myHtaccessConfig);
					$myHtaccessConfig = preg_replace('#[\r\n]+#is',PHP_EOL,$myHtaccessConfig);
					
					$myHtaccessConfig = trim($myHtaccessConfig);
					
					$myHtaccessConfig = PHP_EOL . '### BEGIN '.$this->KEY_CONFIG_CONTENT.' ###' . PHP_EOL . $myHtaccessConfig . PHP_EOL . '### END '.$this->KEY_CONFIG_CONTENT.' ###' . PHP_EOL;
					
					$htaccessContent = $myHtaccessConfig . $htaccessContent;
					
					@file_put_contents($pathFileHtaccess,$htaccessContent);

				}
				
			} else if('nginx' === PepVN_Data::$defaultParams['serverSoftware']) {
				
				foreach($mimeTypesEnableGzip as $key1 => $val1) {
					if('text/html' === $val1) {
						unset($mimeTypesEnableGzip[$key1]);
					}
				}
				
				$pathFileConf = $pathRootWP.'xtraffic-nginx.conf';
				
				$configContent = false;
				
				if(file_exists($pathFileConf) && is_file($pathFileConf) && is_writable($pathFileConf)){
					$configContent = @file_get_contents($pathFileConf);
				} else if(PepVN_Data::is_writable($pathRootWP)) {
					@file_put_contents($pathFileConf,'');
					if(PepVN_Data::is_writable($pathFileConf)) {
						$configContent = @file_get_contents($pathFileConf);
					}
				}
				
				
				if(false !== $configContent) {
				
					$configContent = trim($configContent); 
					
					$myConfigContent_ForNotCacheMobile = '

	# Mobile browsers section to server them non-cached version. COMMENTED by default as most modern wordpress themes including twenty-eleven are responsive. Uncomment config lines in this section if you want to use a plugin like WP-Touch

	if ($http_x_wap_profile) {
		set $cache_uri \'null cache\';
	}

	if ($http_profile) {
		set $cache_uri \'null cache\';
	}

	if ($http_user_agent ~* (2.0\ MMP|240x320|400X240|AvantGo|BlackBerry|Blazer|Cellphone|Danger|DoCoMo|Elaine/3.0|EudoraWeb|Googlebot-Mobile|hiptop|IEMobile|KYOCERA/WX310K|LG/U990|MIDP-2.|MMEF20|MOT-V|NetFront|Newt|Nintendo\ Wii|Nitro|Nokia|Opera\ Mini|Palm|PlayStation\ Portable|portalmmm|Proxinet|ProxiNet|SHARP-TQ-GX10|SHG-i900|Small|SonyEricsson|Symbian\ OS|SymbianOS|TS21i-10|UP.Browser|UP.Link|webOS|Windows\ CE|WinWAP|YahooSeeker/M1A1-R2D2|iPhone|iPod|Android|BlackBerry9530|LG-TU915\ Obigo|LGE\ VX|webOS|Nokia5800)) {
		set $cache_uri \'null cache\';
	}

	if ($http_user_agent ~* (w3c\ |w3c-|acs-|alav|alca|amoi|audi|avan|benq|bird|blac|blaz|brew|cell|cldc|cmd-|dang|doco|eric|hipt|htc_|inno|ipaq|ipod|jigs|kddi|keji|leno|lg-c|lg-d|lg-g|lge-|lg/u|maui|maxo|midp|mits|mmef|mobi|mot-|moto|mwbp|nec-|newt|noki|palm|pana|pant|phil|play|port|prox|qwap|sage|sams|sany|sch-|sec-|send|seri|sgh-|shar|sie-|siem|smal|smar|sony|sph-|symb|t-mo|teli|tim-|tosh|tsm-|upg1|upsi|vk-v|voda|wap-|wapa|wapi|wapp|wapr|webc|winw|winw|xda\ |xda-)) {
		set $cache_uri \'null cache\';
	}


	if ($http_user_agent ~* ((android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge\ |maemo|midp|mmp|mobile.+firefox|netfront|opera\ m(ob|in)i|palm(\ os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows\ ce|xda|xiino)) {
		set $cache_uri \'null cache\';
	}

	if ($http_user_agent ~* (1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a\ wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r\ |s\ )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1\ u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp(\ i|ip)|hs\-c|ht(c(\-|\ |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac(\ |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt(\ |\/)|klon|kpt\ |kwc\-|kyo(c|k)|le(no|xi)|lg(\ g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-|\ |o|v)|zz)|mt(50|p1|v\ )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v\ )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-|\ )|webc|whit|wi(g\ |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-)) {
		set $cache_uri \'null cache\';
	}

	';
					if(isset($options['optimize_cache_mobile_device_cache_enable']) && $options['optimize_cache_mobile_device_cache_enable']) {
						$myConfigContent_ForNotCacheMobile = '';
					}
					
					
					$myConfigContent_AutoResizeImagesFitScreenWidth1 = '-sw_';
					if(isset($wpOptimizeByxTraffic_options['optimize_images_auto_resize_images_fit_screen_width_enable']) && $wpOptimizeByxTraffic_options['optimize_images_auto_resize_images_fit_screen_width_enable']) {
						$myConfigContent_AutoResizeImagesFitScreenWidth1 = '-sw_$cookie_xtrdvscwd'; 
					}
					
					
					
					$myConfigContent = 
	'
	# Deny access to any files with a .php extension in the uploads directory
	# Works in sub-directory installs and also in multisite network
	# Keep logging the requests to parse later (or to pass to firewall utilities such as fail2ban)
	location ~* /(?:uploads)/.*\.php$ {
		deny all;
		return 403;
	}

	# Deny all attempts to access hidden files such as .htaccess, .htpasswd, .DS_Store (Mac).
	# Keep logging the requests to parse later (or to pass to firewall utilities such as fail2ban)
	location ~* /\. {
		deny all;
		return 403;
	}
	
	location ~* /xtraffic-nginx\.conf {
	   deny all;
	   return 403;
	}

	location ~* /('.WPOPTIMIZEBYXTRAFFIC_PLUGIN_SLUG.'/inc)/ {
	   deny all;
	   return 403;
	}
	
	add_header Connection "keep-alive";

	keepalive_requests 10240;
	keepalive_timeout 60;
	send_timeout 60;
	server_tokens off;

	open_file_cache          max=10240 inactive=60s;
	open_file_cache_valid    60s;
	open_file_cache_min_uses 1;
	open_file_cache_errors   off;

	gzip on;
	gzip_comp_level 2;
	gzip_min_length 1440;
	gzip_buffers 16 8k;
	gzip_types '.implode(' ',$mimeTypesEnableGzip).';
	gzip_vary on;
	gzip_proxied any;
	gzip_disable "MSIE [1-6]\.";
	
	add_header X-Powered-By "'.$pluginNameVersion.'";
	add_header Server "'.$pluginNameVersion.'";

	location ~* \.(ttf|ttc|otf|eot|woff|font.css|css) {
		add_header Access-Control-Allow-Origin "*";
	}

	location ~* \.(css|htc|less|js|js2|js3|js4) {
		expires 31536000s;
		add_header Pragma "public";
		add_header Cache-Control "max-age=31536000, public";
		access_log off; log_not_found off;
	}

	location ~* \.(asf|asx|wax|wmv|wmx|avi|bmp|class|divx|doc|docx|eot|exe|gif|gz|gzip|ico|jpg|jpeg|jpe|json|mdb|mid|midi|mov|qt|mp3|m4a|mp4|m4v|mpeg|mpg|mpe|mpp|otf|odb|odc|odf|odg|odp|ods|odt|ogg|pdf|png|pot|pps|ppt|pptx|ra|ram|svg|svgz|swf|tar|tif|tiff|ttf|ttc|wav|wma|wri|woff|woff2|xla|xls|xlsx|xlt|xlw|zip) {
		expires 31536000s;
		add_header Pragma "public";
		add_header Cache-Control "max-age=31536000, public";
		access_log off; log_not_found off;
	}
	
	location ~* \.(rtf|rtx|svg|svgz|txt) {
		expires 31536000s;
		add_header Pragma "public";
		add_header Cache-Control "max-age=31536000, public";
		access_log off; log_not_found off;
	}

	location ~* \.(xml|xsd|xsl) {
		expires 300s;
		add_header Pragma "public";
		add_header Cache-Control "max-age=300, public";
	}

	#Warning : html is error, must disable this
	#location ~ \.(html|htm)$ {
	#    expires 15s;
	#    add_header Pragma "public";
	#    add_header Cache-Control "max-age=15, public";
	#}




	# '.WPOPTIMIZEBYXTRAFFIC_PLUGIN_NAME.' rules.

	set $cache_uri $request_uri;
	set $https_plus \'\';

	# POST requests and urls with a query string should always go to PHP

	if ($request_method = POST) {
		set $cache_uri \'null cache\';
	}

	if ($request_method = PUT) {
		set $cache_uri \'null cache\';
	}

	if ($request_method = UPDATE) {
		set $cache_uri \'null cache\';
	}


	if ($request_method = DELETE) {
		set $cache_uri \'null cache\';
	}

	if ($query_string != "") {
		set $cache_uri \'null cache\'; 
	}   

	# Don\'t cache uris containing the following segments
	if ($request_uri ~* "(/wp-admin/|/wp-content/|/wp-includes/|/xmlrpc.php|/wp-(app|cron|login|register|mail).php|wp-.*.php|index.php|wp-comments-popup.php|wp-links-opml.php|wp-locations.php)") {
		set $cache_uri \'null cache\';
	}   

	# Don\'t use the cache for logged in users or recent commenters

	if ($http_cookie ~* "comment_author|wordpress_[a-f0-9]+|wp-postpass|wordpress_logged_in") {
		set $cache_uri \'null cache\';
	}

	# START MOBILE

	'.$myConfigContent_ForNotCacheMobile.'

	#END MOBILE

	if ($scheme = "https") {
		set $https_plus \'-https\';
	}  
	
	location / {
		index index.php index.html index.htm default.html default.htm;
		try_files '.
		'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/$host/$cache_uri/index$https_plus'.$myConfigContent_AutoResizeImagesFitScreenWidth1.'.html '.
		'/wp-content/cache/'.WPOPTIMIZEBYXTRAFFIC_OPTIMIZE_CACHE_SLUG.'/data/$host/$cache_uri/index$https_plus.xml '.
		'$uri $uri/ /index.php?$args;
	}


	';
					
					$myConfigContent = preg_replace('#\#[^\r\n]+#is','',$myConfigContent);
					$myConfigContent = preg_replace('#([\;\{\}]+)\s+#is','$1 ',$myConfigContent);
					$myConfigContent = preg_replace('#\s+([\;\{\}]+)#is',' $1',$myConfigContent);
					
					$myConfigContent = trim($myConfigContent);
					
					$myConfigContent = PHP_EOL . '### BEGIN '.$this->KEY_CONFIG_CONTENT.' ###' . PHP_EOL . $myConfigContent . PHP_EOL . '### END '.$this->KEY_CONFIG_CONTENT.' ###' . PHP_EOL;
					
					$configContent = $myConfigContent . $configContent;
					
					@file_put_contents($pathFileConf, $configContent);
				}
			}
			
			$pathFile1 = $pathRootWP.'wp-settings.php';
			
			$fileContent1 = false;
			
			if(file_exists($pathFile1) && is_file($pathFile1) && is_writable($pathFile1)){
				$fileContent1 = @file_get_contents($pathFile1);
				if($fileContent1) {
					
					$patterns = array();
					
					$arrayTargetSearchs = array(
						'wp_plugin_directory_constants();'
					);
					
					$targetSearch = false; 
					foreach($arrayTargetSearchs as $key1 => $value1) {
						if($value1) {
							if(false !== strpos($fileContent1,$value1)) {
								$targetSearch = $value1;
								break;
							}
						}
					}
					
					if($targetSearch) {
						$pathFile2 = $pathRootWP.'wp-content/plugins/wp-optimize-by-xtraffic/inc/advanced-cache.php';
						$replace1 = $targetSearch.'

### BEGIN '.$this->KEY_CONFIG_CONTENT.' ###

if(file_exists(\''.$pathFile2.'\') && is_file(\''.$pathFile2.'\')) {
	include(\''.$pathFile2.'\');
}

### END '.$this->KEY_CONFIG_CONTENT.' ###

';
						$patterns[$targetSearch] = $replace1; 
						
						$fileContent1 = str_replace(array_keys($patterns),array_values($patterns),$fileContent1);
						
						@file_put_contents($pathFile1,$fileContent1);
					}
					
				}
				
			}
			
		}
		
	}
	
	public function set_cache_options()
	{
		global $wpOptimizeByxTraffic;
		
		if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
			if ( defined( 'WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_OPTIONS_CACHE_KEY' ) ) {
				PepVN_Data::$cacheObject->set_cache(WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_OPTIONS_CACHE_KEY, $this->get_options(array(
					'cache_status' => 1
				)));
			}
		}
	}
	
}



endif;

