<?php 
namespace WPOptimizeSpeedByxTraffic\Application\Service;

use WPOptimizeByxTraffic\Application\Model\WpOptions
	,WpPepVN\Utils
	,WpPepVN\System
	,WpPepVN\Hash
	,WpPepVN\DependencyInjectionInterface
	,WpPepVN\DependencyInjection
	,WPOptimizeByxTraffic\Application\Service\WpConfigs
	,WPOptimizeByxTraffic\Application\Service\PepVN_Data
	,WPOptimizeSpeedByxTraffic\Application\Service\StatisticAccess
	,WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeJS
	,WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeCSS
	,WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeCDN
	,WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeCache
;

class OptimizeSpeed
{
	const OPTION_NAME = 'optimize_speed';
	
	protected static $_tempData = array();
	
	protected static $_configs = array();
	
	protected $_optimizeCache = false;
	
	protected $_statisticAccess = false;
	
	public $di = false;
	
    public function __construct(DependencyInjection $di) 
    {
		$this->di = $di;
		
		self::$_configs = array(
			'load_css_delay' => 5	//miliseconds
			, 'load_js_delay' => 5	//miliseconds
		);
		
		$this->_statisticAccess = new StatisticAccess($this->di);
		
		$this->_optimizeCache = new OptimizeCache($this->di);
		
		$this->_optimizeCache->setStatisticAccess($this->_statisticAccess);
		
		$hook = $this->di->getShared('hook');
		
		$hook->add_action('cronjob', array($this->_optimizeCache,'prebuild_urls_cache'), WP_PEPVN_PRIORITY_LAST);
		
		$hook->add_action('clean_cache_all', array($this,'action_clean_cache_all'), WP_PEPVN_PRIORITY_LAST);
		
		
	}
    
	public function initFrontend() 
    {
		$priorityLast = WP_PEPVN_PRIORITY_LAST;
		
		$this->_optimizeCache->initFrontend();
		
		$this->_checkAndGetCacheVar();
		
		$optimizeGooglePageSpeed = $this->di->getShared('optimizeGooglePageSpeed');
		$optimizeGooglePageSpeed->initFrontend();
		
		$hook = $this->di->getShared('hook');
		
		$hook->add_filter('output_buffer_before_return', array($this, 'process_output_buffer'), $priorityLast);
		
		$this->_statisticAccess->statistic_access_urls_sites(array());
		
		
	}
	
	public function initBackend() 
    {
		
	}
	
	public static function getDefaultOption()
	{
		return array(
			
			//Optimize Cache
			'optimize_cache_enable' => ''
			,'optimize_cache_front_page_cache_enable' => ''
			
			,'optimize_cache_feed_page_cache_enable' => ''
			,'optimize_cache_browser_cache_enable' => ''
			,'optimize_cache_database_cache_enable' => ''
			,'optimize_cache_object_cache_enable' => ''
			,'optimize_cache_ssl_request_cache_enable' => ''
			,'optimize_cache_database_cache_methods' => array(
				
			)
			,'optimize_cache_mobile_device_cache_enable' => ''
			,'optimize_cache_url_get_query_cache_enable' => ''
			,'optimize_cache_logged_users_cache_enable' => ''
			,'optimize_cache_prebuild_cache_enable' => ''
			,'optimize_cache_prebuild_cache_number_pages_each_process' => 1
			,'optimize_cache_cachetimeout' => 21600
			,'optimize_cache_exclude_url' => ''
			,'optimize_cache_exclude_cookie' => ''
			
			//Optimize Javascript
			,'optimize_javascript_enable' => ''
			,'optimize_javascript_combine_javascript_enable' => ''
			,'optimize_javascript_minify_javascript_enable' => ''
			,'optimize_javascript_asynchronous_javascript_loading_enable' => ''
			,'optimize_javascript_exclude_external_javascript_enable' => ''
			,'optimize_javascript_exclude_inline_javascript_enable' => ''
			,'optimize_javascript_exclude_url' => ''
			
			
			//Optimize CSS (Style)
			,'optimize_css_enable' => ''
			,'optimize_css_combine_css_enable' => ''
			,'optimize_css_minify_css_enable' => ''
			,'optimize_css_asynchronous_css_loading_enable' => ''
			,'optimize_css_exclude_external_css_enable' => ''
			,'optimize_css_exclude_inline_css_enable' => ''
			,'optimize_css_exclude_url' => ''
			
			//Optimize HTML
			,'optimize_html_enable' => ''
			,'optimize_html_minify_html_enable' => ''
			
			//CDN (Content Delivery Network)
			,'cdn_enable' => ''
			,'cdn_domain' => ''
			,'cdn_exclude_url' => ''
			
			,'learn_improve_google_pagespeed_enable' => ''
			,'image_lazyload_enable' => ''
			
			,'memcache_servers' => '127.0.0.1:11211'
		);
	}
	
	public static function getOption($cache_status = true)
	{
	
		return WpOptions::get_option(self::OPTION_NAME,self::getDefaultOption(),array(
			'cache_status' => $cache_status
		));
		
	}
	
	public static function updateOption($data)
	{
		//$data = array_merge(self::getOption(false), $data);
		return WpOptions::update_option(self::OPTION_NAME,$data);
	}
	
	public function migrateOptions() 
	{
		
		$newOptions = array();
		
		$oldOptionID = 'WPOptimizeByxTraffic';
		$oldOptions = get_option($oldOptionID);
		
		$keyFromOldToNew = array(
			
			//optimize_cache
			'optimize_cache_enable' => 'optimize_cache_enable'
			,'optimize_cache_front_page_cache_enable' => 'optimize_cache_front_page_cache_enable'
			,'optimize_cache_feed_page_cache_enable' => 'optimize_cache_feed_page_cache_enable'
			,'optimize_cache_browser_cache_enable' => 'optimize_cache_browser_cache_enable'
			,'optimize_cache_database_cache_enable' => 'optimize_cache_database_cache_enable'
			,'optimize_cache_ssl_request_cache_enable' => 'optimize_cache_ssl_request_cache_enable'
			,'optimize_cache_mobile_device_cache_enable' => 'optimize_cache_mobile_device_cache_enable'
			,'optimize_cache_url_get_query_cache_enable' => 'optimize_cache_url_get_query_cache_enable'
			,'optimize_cache_logged_users_cache_enable' => 'optimize_cache_logged_users_cache_enable'
			,'optimize_cache_prebuild_cache_enable' => 'optimize_cache_prebuild_cache_enable'
			,'optimize_cache_prebuild_cache_number_pages_each_process' => 'optimize_cache_prebuild_cache_number_pages_each_process'
			,'optimize_cache_cachetimeout' => 'optimize_cache_cachetimeout'
			,'optimize_cache_exclude_url' => 'optimize_cache_exclude_url'
			,'optimize_cache_exclude_cookie' => 'optimize_cache_exclude_cookie'
			
			//optimize_javascript
			,'optimize_javascript_enable' => 'optimize_javascript_enable'
			,'optimize_javascript_combine_javascript_enable' => 'optimize_javascript_combine_javascript_enable'
			,'optimize_javascript_minify_javascript_enable' => 'optimize_javascript_minify_javascript_enable'
			,'optimize_javascript_asynchronous_javascript_loading_enable' => 'optimize_javascript_asynchronous_javascript_loading_enable'
			,'optimize_javascript_exclude_external_javascript_enable' => 'optimize_javascript_exclude_external_javascript_enable'
			,'optimize_javascript_exclude_inline_javascript_enable' => 'optimize_javascript_exclude_inline_javascript_enable'
			,'optimize_javascript_exclude_url' => 'optimize_javascript_exclude_url'
			
			//optimize_css
			,'optimize_css_enable' => 'optimize_css_enable'
			,'optimize_css_combine_css_enable' => 'optimize_css_combine_css_enable'
			,'optimize_css_minify_css_enable' => 'optimize_css_minify_css_enable'
			,'optimize_css_asynchronous_css_loading_enable' => 'optimize_css_asynchronous_css_loading_enable'
			,'optimize_css_exclude_external_css_enable' => 'optimize_css_exclude_external_css_enable'
			,'optimize_css_exclude_inline_css_enable' => 'optimize_css_exclude_inline_css_enable'
			,'optimize_css_exclude_url' => 'optimize_css_exclude_url'
			
			//optimize_html
			,'optimize_html_enable' => 'optimize_html_enable'
			,'optimize_html_minify_html_enable' => 'optimize_html_minify_html_enable'
			
			//cdn
			,'cdn_enable' => 'cdn_enable'
			,'cdn_domain' => 'cdn_domain'
			,'cdn_exclude_url' => 'cdn_exclude_url'
			
			//memcache
			,'memcache_servers' => 'memcache_servers'
		);
		
		if($oldOptions && is_array($oldOptions) && !empty($oldOptions)) {
			
			foreach($keyFromOldToNew as $oldKey => $newKey) {
				if(isset($oldOptions[$oldKey])) {
					$newOptions[$newKey] = $oldOptions[$oldKey];
					unset($oldOptions[$oldKey]);
				}
				
			}
		}
		
		if(!empty($newOptions)) {
			self::updateOption(array_merge(self::getOption(),$newOptions));
			self::getOption(false);
		}
		
		update_option($oldOptionID, $oldOptions);
		
	}
	
	public function getPatternsExcludeCacheUrls($excludeUrls = array(), $options = false) 
	{
		if(!$options) {
			$options = self::getOption();
		}
		
		$wpExtend = $this->di->getShared('wpExtend');
		
		$excludeUrls = array_merge($excludeUrls, PepVN_Data::$defaultParams['wp_request_uri_not_cache']);
		
		$tmp = $wpExtend->get_woocommerce_urls();
		if(!empty($tmp)) {
			foreach($tmp as $key1 => $value1) {
				$value1 = Utils::removeScheme($value1);
				$value1 = trim($value1);
				if($value1) {
					$excludeUrls[] = $value1;
				}
			}
		}
		unset($tmp);
		
		if(isset($options['optimize_cache_exclude_url']) && ($options['optimize_cache_exclude_url'])) {
			$options['optimize_cache_exclude_url'] = trim($options['optimize_cache_exclude_url']);
			$tmp = preg_replace('#[\,\;]+#',';',$options['optimize_cache_exclude_url']);
			$tmp = explode(';',$tmp);
			$tmp = PepVN_Data::cleanArray($tmp);
			if(!empty($tmp)) {
				$excludeUrls = array_merge($excludeUrls,$tmp);
			}
			unset($tmp);
		}
		
		$excludeUrls = array_values($excludeUrls);
		
		$excludeUrls = array_unique($excludeUrls);
		
		//$excludeUrls = PepVN_Data::cleanPregPatternsArray($excludeUrls);
		//$excludeUrls = implode('|',$excludeUrls);
		
		return $excludeUrls;
		
	}
	
	public function action_clean_cache_all() 
	{
		$tmp = WP_CONTENT_PEPVN_DIR . 'cache' . DIRECTORY_SEPARATOR . 'static-files' . DIRECTORY_SEPARATOR;
		System::rmdirR($tmp);
	}
	
	public function getPatternsExcludeCacheCookies($excludeCookies = array(), $options = false) 
	{
		if(!$options) {
			$options = self::getOption();
		}
		
		$excludeCookies = array_merge($excludeCookies, PepVN_Data::$defaultParams['wp_cookies_not_cache']);
		
		if(isset($options['optimize_cache_exclude_cookie']) && ($options['optimize_cache_exclude_cookie'])) {
			$options['optimize_cache_exclude_cookie'] = trim($options['optimize_cache_exclude_cookie']);
			$tmp = preg_replace('#[\,\;]+#',';',$options['optimize_cache_exclude_cookie']);
			$tmp = explode(';',$tmp);
			$tmp = PepVN_Data::cleanArray($tmp);
			if(!empty($tmp)) {
				$excludeCookies = array_merge($excludeCookies,$tmp);
			}
			unset($tmp);
		}
		
		$excludeCookies = array_values($excludeCookies);
		
		$excludeCookies = array_unique($excludeCookies);
		
		return $excludeCookies;
		
	}
	
	public function checkOptionIsRequestCacheable($options) 
	{
		$k = Utils::hashKey(array('checkOptionIsRequestCacheable'));
		
		if(isset(self::$_tempData[$k])) {
			return self::$_tempData[$k];
		}
		
		$device = $this->di->getShared('device');
		$request = $this->di->getShared('request');
		$wpExtend = $this->di->getShared('wpExtend');
		
		$isCreateCacheStatus = false;
		
		if(isset($options['optimize_cache_enable']) && ('on' === $options['optimize_cache_enable'])) {
			$isCreateCacheStatus = true;
		}
		
		if($isCreateCacheStatus) {
			if($request->isAjax() || $wpExtend->isWpAjax()) {
				$isCreateCacheStatus = false;
			}
		}
		
		if($isCreateCacheStatus) {
			if('GET' !== $request->getMethod()) {
				$isCreateCacheStatus = false;
			}
		}
		
		if($isCreateCacheStatus) {
			$httpResponseCode = http_response_code();
			if($httpResponseCode) {
				$httpResponseCode = (int)$httpResponseCode;
				if($httpResponseCode !== 200) {
					$isCreateCacheStatus = false;
				}
			}
		}
		
		if($isCreateCacheStatus) {
			if(isset($options['optimize_cache_feed_page_cache_enable']) && ('on' === $options['optimize_cache_feed_page_cache_enable'])) {
				
			} else {
				if($wpExtend->is_feed()) {
					$isCreateCacheStatus = false;
				}
			}
		}
		
		if($isCreateCacheStatus) {
			if(isset($options['optimize_cache_front_page_cache_enable']) && ('on' === $options['optimize_cache_front_page_cache_enable'])) {
				
			} else {
				if($wpExtend->is_home() || $wpExtend->is_front_page()) {
					$isCreateCacheStatus = false;
				}
			}
		}
		
		if($isCreateCacheStatus) {
			if(isset($options['optimize_cache_logged_users_cache_enable']) && ('on' === $options['optimize_cache_logged_users_cache_enable'])) {
				
			} else {
				if($wpExtend->is_user_logged_in()) {
					$isCreateCacheStatus = false;
				}
			}
		}
		
		if($isCreateCacheStatus) {	//not set cache with GET query
			
			if(isset($options['optimize_cache_url_get_query_cache_enable']) && ('on' === $options['optimize_cache_url_get_query_cache_enable'])) {
				
			} else {
				if(
					isset(PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters']) 
					&& PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters']
					&& !empty(PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters'])
				) {
					$isCreateCacheStatus = false;
				}
			}
		}
		
		if($isCreateCacheStatus) {
			if(isset($options['optimize_cache_mobile_device_cache_enable']) && ('on' === $options['optimize_cache_mobile_device_cache_enable'])) {
				
			} else {
				if ( $device->isMobile() || $device->isTablet() ) {	//no cache with mobile
					$isCreateCacheStatus = false;
				}
			}
		}
		
		if($isCreateCacheStatus) {
			
			$scheme = $request->getScheme();
			
			if(
				('http' !== $scheme)
				&& ('https' !== $scheme)
			) {
				$isCreateCacheStatus = false;
			} else {
				if(
					('https' === $scheme)
				) {
					if(isset($options['optimize_cache_ssl_request_cache_enable']) && ('on' === $options['optimize_cache_ssl_request_cache_enable'])) {
				
					} else {
						$isCreateCacheStatus = false;
					}
				}
			}
			
		}
		
		if($isCreateCacheStatus) {
			
			$fullUri = $request->getFullUri();
			
            $tmp = $this->getPatternsExcludeCacheUrls(array(), $options);
			
			$tmp = PepVN_Data::cleanPregPatternsArray($tmp);
			$tmp = implode('|',$tmp);
			if(preg_match('#('.$tmp.')#i',$fullUri)) {
				$isCreateCacheStatus = false;
			}
			unset($tmp);
		}
		
		if($isCreateCacheStatus) {
			if(
				isset($_COOKIE)
				&& ($_COOKIE)
				&& is_array($_COOKIE)
				&& !empty($_COOKIE)
			) {
				
				$tmp = $this->getPatternsExcludeCacheCookies(array(), $options);
			
				$tmp = PepVN_Data::cleanPregPatternsArray($tmp);
				$tmp = implode('|',$tmp);
				
				$tmp2 = $_COOKIE;
				$tmp2 = (array)$tmp2;
				$tmp2 = implode(';',$tmp2);
				
				if(preg_match('#\;?('.$tmp.')\;?#i',$tmp2)) {
					$isCreateCacheStatus = false;
				}
				unset($tmp,$tmp2);
			}
		}
		
		self::$_tempData[$k] = $isCreateCacheStatus;
		
		return self::$_tempData[$k];
		
	}
	
	private function _get_folder_plus_path_for_cache()
	{
		$resultData = '';
		
		if(isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT']) {
			$siteWpRootPath = ABSPATH;
			$documentRoot = $_SERVER['DOCUMENT_ROOT'];
			$resultData = preg_replace('#^'.Utils::preg_quote($documentRoot).'#','',$siteWpRootPath,1);
			$resultData = trim($resultData,DIRECTORY_SEPARATOR);
			$resultData = trim($resultData, '/');
		}
		
		
		return $resultData;
	}
	
	public function set_server_configs()
	{
		
		//register_activation_hook( WP_OPTIMIZE_SPEED_BY_XTRAFFIC_PLUGIN_ROOT_FILE, array(&$this, 'when_plugin_active') );
		
		$wpExtend = $this->di->getShared('wpExtend');
		
		$siteWpRootPath = $wpExtend->getABSPATH();
		
		$siteRootPath_PlusToCache = $this->_get_folder_plus_path_for_cache();
		
		$pluginNameVersion = WP_OPTIMIZE_BY_XTRAFFIC_PLUGIN_NAME.'/'.WP_OPTIMIZE_BY_XTRAFFIC_PLUGIN_VERSION;
		
		$options = self::getOption(false);
		
		$mimeTypesEnableGzip = array(
			'text/html', 'text/xml', 'text/css', 'text/plain', 'text/x-component', 'text/x-js', 'text/richtext', 'text/xsd', 'text/xsl'
			,'image/svg+xml', 'application/xhtml+xml', 'application/xml', 'image/x-icon'
			,'application/rdf+xml','application/xml+rdf', 'application/rss+xml', 'application/xml+rss', 'application/atom+xml', 'application/xml+atom'
			,'text/javascript', 'application/javascript', 'application/x-javascript', 'application/json'
			,'application/x-font-ttf', 'application/x-font-otf'
			,'font/truetype', 'font/opentype'
		);
		
		/*
		$arrayPatternsCookiesNotCache = PepVN_Data::$defaultParams['wp_cookies_not_cache'];
		
		$options['optimize_cache_exclude_cookie'] = trim($options['optimize_cache_exclude_cookie']);
		if($options['optimize_cache_exclude_cookie']) {
			$arrayPatternsCookiesNotCache[] = $options['optimize_cache_exclude_cookie'];
		}
		
		$arrayPatternsCookiesNotCache = PepVN_Data::cleanPregPatternsArray($arrayPatternsCookiesNotCache);
		*/
		$arrayPatternsCookiesNotCache = $this->getPatternsExcludeCacheCookies(array(), $options);
		
		/*
		$arrayPatternsRequestUriNotCache = PepVN_Data::$defaultParams['wp_request_uri_not_cache'];
		
		$options['optimize_cache_exclude_url'] = trim($options['optimize_cache_exclude_url']);
		if($options['optimize_cache_exclude_url']) {
			$arrayPatternsRequestUriNotCache[] = $options['optimize_cache_exclude_url'];
		}
		
		$arrayPatternsRequestUriNotCache = PepVN_Data::cleanPregPatternsArray($arrayPatternsRequestUriNotCache);
		*/
		
		$arrayPatternsRequestUriNotCache = $this->getPatternsExcludeCacheUrls(array(), $options);
		
		$pluginsSlugsNotAllowWebAccess = array(WP_OPTIMIZE_BY_XTRAFFIC_PLUGIN_SLUG);
		$pluginsPathNotAllowWebAccess = array('Application','includes','libs');
		
		$webServerSoftwareName = System::getWebServerSoftwareName();
		
		if('apache' === $webServerSoftwareName) {
		
			
			$myHtaccessConfig_RewriteRule_Patterns1 = '^(.*)';
			
			$myHtaccessConfig_RewriteBase_PlusToCache = '';
			$myHtaccessConfig_RewriteRule_PlusToCache = '';
			
			$myHtaccessConfig_RewriteBase_PlusToCache2 = '';
			$myHtaccessConfig_RewriteRule_PlusToCache2 = '';
			
			if(strlen($siteRootPath_PlusToCache) > 0) {
				$myHtaccessConfig_RewriteBase_PlusToCache = $siteRootPath_PlusToCache.'/';
				$myHtaccessConfig_RewriteRule_PlusToCache = '/'.$siteRootPath_PlusToCache;
				
				$myHtaccessConfig_RewriteBase_PlusToCache2 = '/'.$siteRootPath_PlusToCache;
				$myHtaccessConfig_RewriteRule_PlusToCache2 = '/'.$siteRootPath_PlusToCache;
			}
			
			$myHtaccessConfig_ForNotCacheMobile = 
PHP_EOL . 'RewriteCond %{HTTP:X-Wap-Profile} !^[a-z0-9\"]+ [NC]'
. PHP_EOL . 'RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]'
. PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT} !^.*(2.0\ MMP|240x320|400X240|AvantGo|BlackBerry|Blazer|Cellphone|Danger|DoCoMo|Elaine/3.0|EudoraWeb|Googlebot-Mobile|hiptop|IEMobile|KYOCERA/WX310K|LG/U990|MIDP-2.|MMEF20|MOT-V|NetFront|Newt|Nintendo\ Wii|Nitro|Nokia|Opera\ Mini|Palm|PlayStation\ Portable|portalmmm|Proxinet|ProxiNet|SHARP-TQ-GX10|SHG-i900|Small|SonyEricsson|Symbian\ OS|SymbianOS|TS21i-10|UP.Browser|UP.Link|webOS|Windows\ CE|WinWAP|YahooSeeker/M1A1-R2D2|iPhone|iPod|Android|BlackBerry9530|LG-TU915\ Obigo|LGE\ VX|webOS|Nokia5800).* [NC]'
. PHP_EOL . 'RewriteCond %{HTTP_user_agent} !^(w3c\ |w3c-|acs-|alav|alca|amoi|audi|avan|benq|bird|blac|blaz|brew|cell|cldc|cmd-|dang|doco|eric|hipt|htc_|inno|ipaq|ipod|jigs|kddi|keji|leno|lg-c|lg-d|lg-g|lge-|lg/u|maui|maxo|midp|mits|mmef|mobi|mot-|moto|mwbp|nec-|newt|noki|palm|pana|pant|phil|play|port|prox|qwap|sage|sams|sany|sch-|sec-|send|seri|sgh-|shar|sie-|siem|smal|smar|sony|sph-|symb|t-mo|teli|tim-|tosh|tsm-|upg1|upsi|vk-v|voda|wap-|wapa|wapi|wapp|wapr|webc|winw|winw|xda\ |xda-).* [NC]'
. PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT} !(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge\ |maemo|midp|mmp|mobile.+firefox|netfront|opera\ m(ob|in)i|palm(\ os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows\ ce|xda|xiino [NC,OR]'
. PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT} !^(1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a\ wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r\ |s\ )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1\ u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp(\ i|ip)|hs\-c|ht(c(\-|\ |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac(\ |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt(\ |\/)|klon|kpt\ |kwc\-|kyo(c|k)|le(no|xi)|lg(\ g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-|\ |o|v)|zz)|mt(50|p1|v\ )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v\ )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-|\ )|webc|whit|wi(g\ |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-) [NC]'.PHP_EOL;

			if(isset($options['optimize_cache_mobile_device_cache_enable']) && $options['optimize_cache_mobile_device_cache_enable']) {
				$myHtaccessConfig_ForNotCacheMobile = '';
			}
			
			$myHtaccessConfig_RewriteCond_QUERY_STRING = PHP_EOL . 'RewriteCond %{QUERY_STRING} !.*=.*';
			
			if(isset($options['optimize_cache_url_get_query_cache_enable']) && $options['optimize_cache_url_get_query_cache_enable']) {
				$myHtaccessConfig_RewriteRule_Patterns1 = '^([^?]*)';
				$myHtaccessConfig_RewriteCond_QUERY_STRING = '';
			}
			
			
			$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache = '';
			//$valueTemp = str_replace('#','\#',implode('|',$arrayPatternsRequestUriNotCache));
			$valueTemp = PepVN_Data::cleanPregPatternsArray($arrayPatternsRequestUriNotCache);
			$valueTemp = implode('|',$valueTemp);
			$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache .= PHP_EOL . 'RewriteCond %{QUERY_STRING} !^.*('.$valueTemp.').*$';
			$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache .= PHP_EOL . 'RewriteCond %{REQUEST_URI} !^.*('.$valueTemp.').*$';
			
			
			$myHtaccessConfig_RewriteCond_CookiesNotCache = '';
			$valueTemp = PepVN_Data::cleanPregPatternsArray($arrayPatternsCookiesNotCache);
			$valueTemp = implode('|',$valueTemp);
			$myHtaccessConfig_RewriteCond_CookiesNotCache .= PHP_EOL . 'RewriteCond %{HTTP:Cookie} !^.*('.$valueTemp.').*$';
			
			
			$myHtaccessConfig_RewriteCond_RewriteRule_AutoResizeImagesFitWidth = '';
			
			if(class_exists('\WPOptimizeByxTraffic\Application\Service\OptimizeImages')) {
				$tmp = \WPOptimizeByxTraffic\Application\Service\OptimizeImages::getOption();
				
				if(isset($tmp['optimize_images_auto_resize_images_enable']) && ('on' === $tmp['optimize_images_auto_resize_images_enable'])) {
					
					$myHtaccessConfig_RewriteCond_RewriteRule_AutoResizeImagesFitWidth = 
PHP_EOL . '#http for auto resize image#
RewriteCond %{REQUEST_URI} !^.*//.*$'.$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache.'
RewriteCond %{REQUEST_METHOD} GET'
.$myHtaccessConfig_RewriteCond_QUERY_STRING
.$myHtaccessConfig_RewriteCond_CookiesNotCache
.$myHtaccessConfig_ForNotCacheMobile.'
RewriteCond %{HTTPS} !on
RewriteCond %{HTTP_COOKIE} xtrdvscwd=([^;]+) [NC]
RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-sw_%1.html -f
RewriteRule '.$myHtaccessConfig_RewriteRule_Patterns1.' "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-sw_%1.html" [L]

#https for auto resize image#
RewriteCond %{REQUEST_URI} !^.*//.*$'.$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache.'
RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
RewriteCond %{REQUEST_METHOD} GET'
.$myHtaccessConfig_RewriteCond_QUERY_STRING
.$myHtaccessConfig_RewriteCond_CookiesNotCache
.$myHtaccessConfig_ForNotCacheMobile.'
RewriteCond %{HTTPS} on
RewriteCond %{HTTP_COOKIE} xtrdvscwd=([^;]+) [NC]
RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-sw_%1.html -f
RewriteRule '.$myHtaccessConfig_RewriteRule_Patterns1.' "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-sw_%1.html" [L]
' . PHP_EOL;
				}
				unset($tmp);
			}
			
			$myHtaccessConfig = 
'

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
	
	<filesMatch "\.(ttf|ttc|otf|eot|woff|woff2|font.css|css|xml)$">
		Header set Access-Control-Allow-Origin "*"
	</filesMatch>

	Header set X-Powered-By "'.$pluginNameVersion.'"
	Header set Server "'.$pluginNameVersion.'"
</ifModule>


<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /'.$myHtaccessConfig_RewriteBase_PlusToCache.'
AddDefaultCharset UTF-8

###### HTML ######

'.$myHtaccessConfig_RewriteCond_RewriteRule_AutoResizeImagesFitWidth.'

###### HTTP ######
RewriteCond %{REQUEST_URI} !^.*//.*$'.$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache.'
RewriteCond %{REQUEST_METHOD} GET'
.$myHtaccessConfig_RewriteCond_QUERY_STRING
.$myHtaccessConfig_RewriteCond_CookiesNotCache
.$myHtaccessConfig_ForNotCacheMobile.'
RewriteCond %{HTTPS} !on
RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-sw_.html -f
RewriteRule '.$myHtaccessConfig_RewriteRule_Patterns1.' "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-sw_.html" [L]

###### HTTPS ######
RewriteCond %{REQUEST_URI} !^.*//.*$'.$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache.'
RewriteCond %{REQUEST_METHOD} GET'
.$myHtaccessConfig_RewriteCond_QUERY_STRING
.$myHtaccessConfig_RewriteCond_CookiesNotCache
.$myHtaccessConfig_ForNotCacheMobile.'
RewriteCond %{HTTPS} on
RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https-sw_.html -f
RewriteRule '.$myHtaccessConfig_RewriteRule_Patterns1.' "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https-sw_.html" [L]

###### XML ######

###### HTTP ######
RewriteCond %{REQUEST_URI} !^.*//.*$'.$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache.'
RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
RewriteCond %{REQUEST_METHOD} GET
RewriteCond %{QUERY_STRING} !.*=.*'
.$myHtaccessConfig_RewriteCond_CookiesNotCache
.$myHtaccessConfig_ForNotCacheMobile.'
RewriteCond %{HTTPS} !on
RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.xml -f
RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index.xml" [L]

###### HTTPS ######
RewriteCond %{REQUEST_URI} !^.*//.*$'.$myHtaccessConfig_RewriteCond_RequestUriQueryNotCache.'
RewriteCond %{REQUEST_URI} !^.*(wp-includes|wp-content|wp-admin|\.php).*$
RewriteCond %{REQUEST_METHOD} GET
RewriteCond %{QUERY_STRING} !.*=.*'
.$myHtaccessConfig_RewriteCond_CookiesNotCache
.$myHtaccessConfig_ForNotCacheMobile.'
RewriteCond %{HTTP:Accept-Encoding} gzip
RewriteCond %{HTTPS} on
RewriteCond %{DOCUMENT_ROOT}'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.xml -f
RewriteRule ^(.*) "'.$myHtaccessConfig_RewriteRule_PlusToCache.'/wp-content/pep-vn/cache/request-uri/data/%{SERVER_NAME}'.$myHtaccessConfig_RewriteBase_PlusToCache2.'/$1/index-https.xml" [L]

</IfModule>


';
			
			
			System::setServerConfigs(array(
				'ROOT_PATH' => $siteWpRootPath
				,'CONFIG_KEY' => WP_PEPVN_CONFIG_KEY.'_'.WP_OPTIMIZE_SPEED_BY_XTRAFFIC_PLUGIN_SLUG
				,'htaccess' => $myHtaccessConfig
			));
			
			unset($myHtaccessConfig);
			
		} else if('nginx' === $webServerSoftwareName) {
			
			foreach($mimeTypesEnableGzip as $key1 => $val1) {
				if('text/html' === $val1) {
					unset($mimeTypesEnableGzip[$key1]);
				}
			}
			
			
			$myConfigContent_HtmlBrowserCache = '';
			/*
			if(isset($options['optimize_cache_browser_cache_enable']) && ('on' === $options['optimize_cache_browser_cache_enable'])) {
				$myConfigContent_HtmlBrowserCache = '
location ~* \.(html|htm)$ {
    expires 180s;
    add_header Pragma "public";
    add_header Cache-Control "max-age=180, public";
}
';
			}
			*/
			
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
			
			$myConfigContent_ForNotCacheQuery = '

if ($query_string != "") {
	set $cache_uri \'null cache\'; 
}

';
			if(isset($options['optimize_cache_url_get_query_cache_enable']) && $options['optimize_cache_url_get_query_cache_enable']) {
				$myConfigContent_ForNotCacheQuery = '';
			}
			
			
			$myConfigContent_RequestUriQueryNotCache = '';
			$valueTemp = PepVN_Data::cleanPregPatternsArray($arrayPatternsRequestUriNotCache);
			$valueTemp = implode('|',$valueTemp);
			$myConfigContent_RequestUriQueryNotCache .= PHP_EOL . '
if ($request_uri ~* "('.$valueTemp.')") {
	set $cache_uri \'null cache\';
}
';
			$myConfigContent_RequestUriQueryNotCache .= PHP_EOL . '
if ($query_string ~* "('.$valueTemp.')") {
	set $cache_uri \'null cache\';
}
';
			
			$myConfigContent_CookiesNotCache = '';
			$valueTemp = PepVN_Data::cleanPregPatternsArray($arrayPatternsCookiesNotCache);
			$valueTemp = implode('|',$valueTemp);
			$myConfigContent_CookiesNotCache .= PHP_EOL . '

# Don\'t use the cache for logged in users or recent commenters

if ($http_cookie ~* "(wordpress_[a-f0-9]+|'.$valueTemp.')") {
	set $cache_uri \'null cache\';
}

';

			$myConfigContent_AutoResizeImagesFitScreenWidth1 = '-sw_';
			
			if(class_exists('\WPOptimizeByxTraffic\Application\Service\OptimizeImages')) {
				$tmp = \WPOptimizeByxTraffic\Application\Service\OptimizeImages::getOption();
				
				if(isset($tmp['optimize_images_auto_resize_images_enable']) && ('on' === $tmp['optimize_images_auto_resize_images_enable'])) {
					$myConfigContent_AutoResizeImagesFitScreenWidth1 = '-sw_$cookie_xtrdvscwd'; 
				}
				unset($tmp);
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

location ~* /('.implode('|',$pluginsSlugsNotAllowWebAccess).')/('.implode('|',$pluginsPathNotAllowWebAccess).')/ {
   deny all;
   return 403;
}

add_header Connection "keep-alive";

keepalive_requests 10240;
keepalive_timeout 30;
send_timeout 60;
server_tokens off;

#open_file_cache          max=10240 inactive=60s;
#open_file_cache_valid    60s;
#open_file_cache_min_uses 1;
#open_file_cache_errors   off;

gzip on;
gzip_comp_level 2;
#gzip_min_length 1440;
gzip_min_length 256;
gzip_buffers 16 8k;
gzip_types '.implode(' ',$mimeTypesEnableGzip).';
gzip_vary on;
gzip_proxied any;
gzip_disable "MSIE [1-6]\.";

add_header X-Powered-By "'.$pluginNameVersion.'";
add_header Server "'.$pluginNameVersion.'";

location ~* \.(ttf|ttc|otf|eot|woff|woff2|font.css|css|xml) {
	add_header Access-Control-Allow-Origin "*";
	
	location ~* \.(css) {
		expires 31536000s;
		add_header Pragma "public";
		add_header Cache-Control "max-age=31536000, public";
	}
	
	access_log off; log_not_found off;
}

location ~* \.(htc|less|js|js2|js3|js4) {
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

'.$myConfigContent_HtmlBrowserCache.'

# '.WP_OPTIMIZE_BY_XTRAFFIC_PLUGIN_NAME.' rules.

set $cache_uri $uri;
set $https_plus \'\';

# not GET requests and urls with a query string should always go to PHP
if ($request_method !~ ^(GET)$) {
	set $cache_uri \'null cache\';
}

'.$myConfigContent_ForNotCacheQuery.'

'.$myConfigContent_RequestUriQueryNotCache.'

'.$myConfigContent_CookiesNotCache.'

# START MOBILE

'.$myConfigContent_ForNotCacheMobile.'

#END MOBILE

if ($scheme = "https") {
	set $https_plus \'-https\';
}

location / {
	root '.$siteWpRootPath.'; 
	index index.php index.html index.htm default.html default.htm;
	try_files '
	. '/wp-content/pep-vn/cache/request-uri/data/$host/$cache_uri/index$https_plus'.$myConfigContent_AutoResizeImagesFitScreenWidth1.'.html '
	. '/wp-content/pep-vn/cache/request-uri/data/$host/$cache_uri/index$https_plus.xml '
	. '$uri $uri/ /index.php?$args;
}


';
			
			System::setServerConfigs(array(
				'ROOT_PATH' => $siteWpRootPath
				,'CONFIG_KEY' => WP_PEPVN_CONFIG_KEY.'_'.WP_OPTIMIZE_SPEED_BY_XTRAFFIC_PLUGIN_SLUG
				,'nginx' => $myConfigContent
			));
			
			unset($myConfigContent);
			
		}
		
	}
	
	
	public static function parsePattern($input_string) 
	{
		$k = Utils::hashKey('parsePattern_'.$input_string);
		
		if(!isset(self::$_tempData[$k])) {
			$input_string = PepVN_Data::cleanPregPatternsArray($input_string);
			self::$_tempData[$k] = implode('|',$input_string);
		}
		
		return self::$_tempData[$k];
	}
	
	
	public static function checkFileProcess($file_path)
	{
		
		$isFileValidStatus = false;
		$isNeedProcessFileStatus = false;
		
		if(is_file($file_path)) {
			
			$fileSizeTmp = filesize($file_path);
			$filemtimeTmp = filemtime($file_path);
			
			if($fileSizeTmp && ($fileSizeTmp>0)) {
				if(($filemtimeTmp + (86400 * 6)) > time()) {	//is not timeout
					$isFileValidStatus = true;
					$isNeedProcessFileStatus = false;
				}
			} else {
				if(($filemtimeTmp + (3600)) > time()) {	//is not timeout
					$isFileValidStatus = false;
					$isNeedProcessFileStatus = false;
				}
			}
			
		} else {
			$isFileValidStatus = false;
			$isNeedProcessFileStatus = true;
		}
		
		if(true === $isFileValidStatus) {
			$isNeedProcessFileStatus = false;
		}
		
		return array(
			'file_valid' => $isFileValidStatus
			, 'need_process' => $isNeedProcessFileStatus
		);
	}
    
	
	public static function parse_load_html_scripts_by_tag($input_parameters) 
	{
		$resultData = '';
		
		if(isset($input_parameters['url']) || isset($input_parameters['code'])) {
			
			if(isset($input_parameters['url'])) {
				$input_parameters['url'] = PepVN_Data::removeProtocolUrl($input_parameters['url']);
				if(!isset($input_parameters['id'])) {
					$input_parameters['id'] = Hash::crc32b($input_parameters['url']);
				}
			} else if(isset($input_parameters['code'])) {
				if(!isset($input_parameters['id'])) {
					$input_parameters['id'] = Hash::crc32b(md5($input_parameters['code']));
				}
			}
			
			$loaderId = Hash::crc32b($input_parameters['id'].'_loader');
			
			$loadTimeDelay = self::$_configs['load_css_delay'];
			if('js' === $input_parameters['type']) {
				$loadTimeDelay = self::$_configs['load_js_delay'];
			} else {
				if(!isset($input_parameters['media'])) {
					$input_parameters['media'] = 'all';
				}
			}
			
			$loadTimeDelay = (int)$loadTimeDelay;
			if($loadTimeDelay < 1) {
				$loadTimeDelay = 1;
			}
			
			if('js' === $input_parameters['load_by']) {
				
				if('js' === $input_parameters['type']) {
					$resultData = ' <script data-cfasync="false" language="javascript" type="text/javascript" id="'.$loaderId.'" defer async>
/*<![CDATA[*/
setTimeout(function() {
(function(e) { var t, n, r, s, i = "'.$input_parameters['id'].'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("script"); r.setAttribute("data-cfasync","false"); r.id = i; r.setAttribute("language","javascript"); r.setAttribute("type","text/javascript"); r.async = true; r.src = n + "//'.$input_parameters['url'].'"; s = e.getElementById("'.$loaderId.'"); s.parentNode.insertBefore(r, s); s.parentNode.removeChild(s); })(document);
}, '.$loadTimeDelay.');
/*]]>*/
</script> ';
				} else if('css' === $input_parameters['type']) { 
					if(!isset($input_parameters['append_to'])) {
						$input_parameters['append_to'] = 'head';
					}
					
					if('head' === $input_parameters['append_to']) {
					
						$resultData = ' <script language="javascript" type="text/javascript" id="'.$loaderId.'" defer async>
/*<![CDATA[*/
setTimeout(function() {
(function(e) { var t, n, r, s, hd = document.getElementsByTagName("head")[0], i = "'.$input_parameters['id'].'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("link"); r.id = i; r.setAttribute("rel","stylesheet"); r.setAttribute("type","text/css"); r.setAttribute("media","'.$input_parameters['media'].'"); r.async = true; r.href = n + "//'.$input_parameters['url'].'"; hd.appendChild(r); s = e.getElementById("'.$loaderId.'"); s.parentNode.removeChild(s); })(document);
}, '.((1 * $loadTimeDelay) + 2).');
/*]]>*/
</script> ';

					} else {
						$resultData = ' <script language="javascript" type="text/javascript" id="'.$loaderId.'">
/*<![CDATA[*/
setTimeout(function() {
(function(e) { var t, n, r, s, i = "'.$input_parameters['id'].'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("link"); r.id = i; r.setAttribute("rel","stylesheet"); r.setAttribute("type","text/css"); r.setAttribute("media","'.$input_parameters['media'].'"); r.async = true; r.href = n + "//'.$input_parameters['url'].'"; s = e.getElementById("'.$loaderId.'"); s.parentNode.insertBefore(r, s); s.parentNode.removeChild(s); })(document);
}, '.$loadTimeDelay.');
/*]]>*/
</script> ';
					}
					
				}
				
				
			} else if(
				('div_tag' === $input_parameters['load_by'])
				|| ('js_data' === $input_parameters['load_by'])
			) {
				
				$configs = array(
					'delay' => $loadTimeDelay
					,'loader_id' => $loaderId
					,'id' => $input_parameters['id']
					,'type' => $input_parameters['type']
				);
				
				if(isset($input_parameters['url'])) {
					$configs['url'] = $input_parameters['url'];
				} else if(isset($input_parameters['code'])) {
					$configs['code'] = $input_parameters['code'];
				}
				
				if(isset($input_parameters['media'])) {
					$configs['media'] = $input_parameters['media'];
				}
				
				if(
					('div_tag' === $input_parameters['load_by'])
				) {
					$resultData = ' <div class="wp-optimize-speed-by-xtraffic-loader-data-'.$input_parameters['type'].'" id="'.$loaderId.'" data-pepvn-configs="'.Utils::encodeVar($configs).'" style="display:none;"></div> ';  
				} else if(
					('js_data' === $input_parameters['load_by'])
				) {
					$keyStoreJs = 'window.wppepvnloaderdata'.$input_parameters['type'];
					
					$resultData = ' <script language="javascript" type="text/javascript" id="'.$loaderId.'">
if(typeof('.$keyStoreJs.') === "undefined") { '.$keyStoreJs.' = new Array(); }
'.$keyStoreJs.'.push("'.Utils::encodeVar($configs).'");
</script> ';
				}
			}
			
		}
		
		return $resultData;
	}
	
	private function _checkAndGetSetCacheVar_GetKeyCache()
	{
		$k = crc32(__CLASS__ . __METHOD__);
		
		if(isset(self::$_tempData[$k])) {
			return self::$_tempData[$k];
		}
		
		$wpExtend = $this->di->getShared('wpExtend');
		
		$keyCache = array(
			$k
			,PepVN_Data::$defaultParams['parseedUrlFullRequest']['url_no_parameters']
			,$wpExtend->get_current_user_id()
		);
		
		if($wpExtend->is_ssl()) {
			$keyCache[] = '-https';
		}
		
		if($wpExtend->is_feed()) {
			$keyCache[] = 'xml';
		}
		
		$device = $this->di->getShared('device');
		$screenWidth = $device->get_device_screen_width();
		if($screenWidth && ($screenWidth>0)) {
			$keyCache[] = $screenWidth;
		}
		
		$keyCache = Utils::hashKey($keyCache);
		
		self::$_tempData[$k] = $keyCache;
		
		return $keyCache;
	}
	
	private function _checkAndGetCacheVar()
	{
		$isCacheableStatus = $this->checkOptionIsRequestCacheable(self::getOption());
		
		if($isCacheableStatus) {
			/*
			* Only cache when hasn't query string (GET). Anti Flood HDD
			*/
			if(
				isset(PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters']) 
				&& PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters']
				&& !empty(PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters'])
			) {	//when has GET query then not cache
				$isCacheableStatus = false;
			}
		}
		
		if($isCacheableStatus) {
			
			$wpExtend = $this->di->getShared('wpExtend');
			$keyCache = $this->_checkAndGetSetCacheVar_GetKeyCache();
			$rsCache = PepVN_Data::$cacheObject->get_cache($keyCache);
			
			if(null !== $rsCache) {
				
				$isNotModifiedStatus = false;
				
				//get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
				$etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
				
				if(false !== $etagHeader) {
					if($etagHeader === $rsCache['etag']) {
						$isNotModifiedStatus = true;
					}
				}
				
				if(false === $isNotModifiedStatus) {
					//get the HTTP_IF_MODIFIED_SINCE header if set
					$ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
					
					if(false !== $ifModifiedSince) {
						$ifModifiedSince = strtotime($ifModifiedSince);
						if($ifModifiedSince) {
							if($ifModifiedSince === $rsCache['last_modified_time']) {
								$isNotModifiedStatus = true;
							}
						}
					}
				}
				
				$options = self::getOption();
				
				if(isset($options['optimize_cache_browser_cache_enable']) && ('on' === $options['optimize_cache_browser_cache_enable'])) {
					
				} else {
					unset($rsCache['cache_timeout']);
				}
				
				if(true === $isNotModifiedStatus) {
					$rsCache['isNotModifiedStatus'] = true;
				}
				
				$cacheRequestUri = $this->di->getShared('cacheRequestUri');
				$cacheRequestUri->flush_http_headers($rsCache);
				
				if(false === $isNotModifiedStatus) {
					echo $rsCache['text'];
				}
				
				ob_end_flush();
				
				exit();
				
			}
		}
		
	}
	
	private function _checkAndSetCacheVar($text)
	{
		/*
		* Only cache when hasn't query string (GET). Anti Flood HDD
		*/
		$isCreateCacheStatus = $this->checkOptionIsRequestCacheable(self::getOption());
		
		if($isCreateCacheStatus) {
			if(
				isset(PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters']) 
				&& PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters']
				&& !empty(PepVN_Data::$defaultParams['parseedUrlFullRequest']['parameters'])
			) {
				$isCreateCacheStatus = false;
			}
		}
		
		if($isCreateCacheStatus) {
			
			$wpExtend = $this->di->getShared('wpExtend');
		
			$options = self::getOption();
			
			$keyCache = $this->_checkAndGetSetCacheVar_GetKeyCache();
			
			$setCacheConfig = array();
			
			$contentType = Utils::getContentTypeHeadersList();
			
			if(!$contentType) {
				if($wpExtend->is_feed()) {
					$contentType = 'text/xml; charset=UTF-8';
				} else {
					$contentType = 'text/html; charset=UTF-8';
				}
			}
			
			$setCacheConfig['content_type'] = $contentType;
			$setCacheConfig['cache_timeout'] = ceil($options['optimize_cache_cachetimeout']/2);
			$setCacheConfig['last_modified_time'] = PepVN_Data::$defaultParams['requestTime'];
			$setCacheConfig['etag'] = Hash::crc32b(ceil($setCacheConfig['last_modified_time'] / $setCacheConfig['cache_timeout']));
			$setCacheConfig['text'] = $text;
			unset($text);
			PepVN_Data::$cacheObject->set_cache($keyCache, $setCacheConfig);
			unset($setCacheConfig['text']);
			
			if(isset($options['optimize_cache_browser_cache_enable']) && ('on' === $options['optimize_cache_browser_cache_enable'])) {
				
			} else {
				unset($setCacheConfig['cache_timeout']);
			}
			
			$cacheRequestUri = $this->di->getShared('cacheRequestUri');
			$cacheRequestUri->flush_http_headers($setCacheConfig);
			
			unset($setCacheConfig);
		}
	}
	
	private function _process_html($text,$options)
	{
		$classMethodKey = Hash::crc32b(__CLASS__ . '_' . __METHOD__);
		
		$keyCache = Utils::hashKey(array(
			$classMethodKey
			, $text
			, $options
		));
		
		$tmp = PepVN_Data::$cacheObject->get_cache($keyCache);
		
		if(null !== $tmp) {
			return $tmp;
		}
		
		$wpExtend = $this->di->getShared('wpExtend');
		
		$isProcessDataStatus = true;
		
		if(true === $isProcessDataStatus) {
			if ( $wpExtend->is_feed() ) {
				$isProcessDataStatus = false;
			}
		}
		
		if(true === $isProcessDataStatus) {
			if ( $wpExtend->is_admin() ) {
				$isProcessDataStatus = false;
			}
		}
		
		if(false === $isProcessDataStatus) {
			return $text;
		}
		
		$optimizeCDN = $this->di->getShared('optimizeCDN');
		
		$isProcessJavascriptStatus = false;
		$isProcessCssStatus = false;
		$isProcessHtmlStatus = false;
		$isProcessCDNStatus = false;
		
		if(isset($options['optimize_javascript_enable']) && ('on' === $options['optimize_javascript_enable'])) {
			if(
				isset($options['optimize_javascript_combine_javascript_enable']) && ('on' === $options['optimize_javascript_combine_javascript_enable'])
				|| isset($options['optimize_javascript_minify_javascript_enable']) && ('on' === $options['optimize_javascript_minify_javascript_enable'])
				|| isset($options['optimize_javascript_asynchronous_javascript_loading_enable']) && ('on' === $options['optimize_javascript_asynchronous_javascript_loading_enable'])
			) {
				$isProcessJavascriptStatus = true;
			}
		}
		
		if(isset($options['optimize_css_enable']) && ('on' === $options['optimize_css_enable'])) {
			if(
				isset($options['optimize_css_combine_css_enable']) && ('on' === $options['optimize_css_combine_css_enable'])
				|| isset($options['optimize_css_minify_css_enable']) && ('on' === $options['optimize_css_minify_css_enable'])
				|| isset($options['optimize_css_asynchronous_css_loading_enable']) && ('on' === $options['optimize_css_asynchronous_css_loading_enable'])
			) {
				$isProcessCssStatus = true;
			}
		}
		
		if(isset($options['optimize_html_enable']) && ('on' === $options['optimize_html_enable'])) {
			if(
				isset($options['optimize_html_minify_html_enable']) && ('on' === $options['optimize_html_minify_html_enable'])
			) {
				$isProcessHtmlStatus = true;
			}
		}
		
		if($optimizeCDN->is_cdn_enable()) {
			$isProcessCDNStatus = true;
		}
		
		if(!$isProcessJavascriptStatus && !$isProcessCssStatus && !$isProcessHtmlStatus && !$isProcessCDNStatus) {
			return $text;
		}
		
		$textAppendToBody = '';
		
		$patternsEscaped = array();
		
		$rsOne = PepVN_Data::escapeSpecialElementsInHtmlPage($text);
		$text = $rsOne['content'];
		if(!empty($rsOne['patterns'])) {
			$patternsEscaped = array_merge($patternsEscaped, $rsOne['patterns']);
		}
		unset($rsOne);
		
		$rsOne = PepVN_Data::escapeHtmlTagsAndContents($text,'pre');
		$text = $rsOne['content'];
		if(!empty($rsOne['patterns'])) {
			$patternsEscaped = array_merge($patternsEscaped, $rsOne['patterns']);
		}
		unset($rsOne);
		
		if($isProcessJavascriptStatus) {
			$optimizeJS = new OptimizeJS($this->di);
			$text = $optimizeJS->process_html($text, $options);
			unset($optimizeJS);
		}
		
		if($isProcessCssStatus) {
			$optimizeCSS = $this->di->getShared('optimizeCSS');
			$text = $optimizeCSS->process_html($text, $options);
			unset($optimizeCSS);
		}
		
		$jsInitUrl = WP_OPTIMIZE_SPEED_BY_XTRAFFIC_PLUGIN_ROOT_URI.'public/js/wp-optimize-speed-by-xtraffic-init.'.(WP_PEPVN_DEBUG ? '' : 'min.').'js';
		
		$jsInitUrl = PepVN_Data::removeProtocolUrl($jsInitUrl);
		
		$jsInitId = 'z'.hash('crc32b', 'wp-optimize-speed-by-xtraffic-init');
		
		$jsInitLoaderId = 'z'.hash('crc32b', $jsInitId.'_loader');
		
		$textAppendToBody .= '<script data-cfasync="false" language="javascript" type="text/javascript" id="'.$jsInitLoaderId.'" defer>setTimeout(function() {(function(e) { var t, n, r, s, i = "'.$jsInitId.'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("script"); r.setAttribute("data-cfasync","false"); r.id = i; r.setAttribute("language","javascript"); r.setAttribute("type","text/javascript"); r.async = true; r.src = n + "//'.$jsInitUrl.'"; s = e.getElementById("'.$jsInitLoaderId.'"); s.parentNode.insertBefore(r, s); s.parentNode.removeChild(s); })(document);}, 1);</script>';
		
		if($isProcessHtmlStatus) {
			$text = pepvn_MinifyHtml($text);
		}
		
		if(!empty($patternsEscaped)) {
			$text = str_replace(array_values($patternsEscaped),array_keys($patternsEscaped),$text); 
		}
		unset($patternsEscaped);
		
		if($isProcessCDNStatus) {
			$text = $optimizeCDN->process($text);
		}
		
		/*
		$wpExtend = $this->di->getShared('wpExtend');
		
		$tmp = $wpExtend->getWpOptimizeByxTrafficPluginPromotionInfo();
		
		$textAppendToBody .= $tmp['html_comment_text'];
		
		unset($tmp);
		*/
		
		if($textAppendToBody) {
			$text = PepVN_Data::appendTextToTagBodyOfHtml($textAppendToBody,$text);
		}
		
		unset($textAppendToBody);
		
		PepVN_Data::$cacheObject->set_cache($keyCache, $text);
		
		return $text;
	}
	
	public function process_output_buffer($buffer)
	{
		
		$hook = $this->di->getShared('hook');
		
		if($hook->has_action('optimize_speed_before_process_html_output_buffer')) {
			$hook->do_action('optimize_speed_before_process_html_output_buffer', $buffer);
		}
		
		$wpExtend = $this->di->getShared('wpExtend');
		
		$preResolveDns = $this->di->getShared('preResolveDns');
		
		$preResolveDns->statisticsDomains($buffer,'html');
		
		$pluginPromotionInfo = $wpExtend->getWpOptimizeByxTrafficPluginPromotionInfo();
		
		$buffer = preg_replace('#<!--[^>]+'.preg_quote($pluginPromotionInfo['data']['plugin_wp_url'],'#').'[^>]+-->#is','',$buffer);
		
		$options = self::getOption();
		
		$buffer = $this->_process_html($buffer,$options);
		
		$buffer = $preResolveDns->appendDNSPrefetch($buffer);
		
		$buffer = PepVN_Data::appendTextToTagBodyOfHtml($pluginPromotionInfo['html_comment_text'],$buffer);
		
		unset($pluginPromotionInfo);
		
		if($hook->has_filter('before_set_cache_output_buffer')) {
			$buffer = $hook->apply_filters('before_set_cache_output_buffer', $buffer);
		}
		
		$this->_optimizeCache->set_cache_for_web_server($buffer);
		
		$this->_checkAndSetCacheVar($buffer);
		
		$this->_statisticAccess->statistic_access_urls_sites(array(
			'calculate_time_php_process_status' => true
		));
		
		return $buffer;
	}
	
}