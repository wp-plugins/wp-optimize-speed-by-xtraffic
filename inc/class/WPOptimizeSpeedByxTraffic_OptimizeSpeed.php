<?php




if ( !class_exists('WPOptimizeSpeedByxTraffic_OptimizeSpeed') ) :

class WPOptimizeSpeedByxTraffic_OptimizeSpeed extends WPOptimizeSpeedByxTraffic_Base
{
	
	private $getUrlContentCacheTimeout = 86400; 
	
	private $loadJsTimeDelay = 1000;//miliseconds
	private $loadCssTimeDelay = 10;//miliseconds
	
	private $numberLoadCssAsync = 0;
	
	private $patternsIgnoredJavascript_Uri = '#(adsbygoogle)#is'; 
	private $patternsIgnoredJavascript_Code = '#(document\.write|_ase\.push)#is';
	
	public $cdn_patternFilesTypeAllow = '';
	
	private $defaultParams = false;
	
	public function __construct() 
	{
		parent::__construct();
	}
	
	public function setDefaultParams()
	{
		if(false === $this->defaultParams) {
			
			$this->defaultParams['status'] = 1;
			
			global $wpOptimizeByxTraffic;
			
			if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
				$this->fullDomainName = $wpOptimizeByxTraffic->fullDomainName;
			}
			
			if(defined('WPOPTIMIZEBYXTRAFFIC_CONTENT_FOLDER_PATH_CACHE_PEPVN')) {
				$plusPathAndUrl1 = 'static-files/';
				$this->UploadsStaticFilesFolderPath = WPOPTIMIZEBYXTRAFFIC_CONTENT_FOLDER_PATH_CACHE_PEPVN . $plusPathAndUrl1;
				$this->UploadsStaticFilesFolderUrl = WPOPTIMIZEBYXTRAFFIC_CONTENT_FOLDER_URL_CACHE_PEPVN . $plusPathAndUrl1;
				if(!file_exists($this->UploadsStaticFilesFolderPath)) {
					PepVN_Data::createFolder($this->UploadsStaticFilesFolderPath, WPOPTIMIZEBYXTRAFFIC_CHMOD);
				}
			}
			
			$this->cdn_patternFilesTypeAllow = array(
				//img
				'jpg'
				,'jpeg'
				,'gif'
				,'png'
				,'ico'
				,'svg'
				
				//js & css
				,'css'
				,'js'
				
				//font
				,'ttf'
				,'woff2'
				
				//audio
				,'wav'
				,'ogg'
				,'mp3'
				,'wma'
				,'mid'
				,'midi'
				,'rm'
				,'ram'
				,'aac'
				,'mp4'
							
				
				//video
				,'mpg'
				,'mpeg'
				,'avi'
				,'wmv'
				,'mov'
				,'rm'
				,'ram'
				,'ogg'
				,'webm'
				,'mp4'
				
				//flash
				,'swf'
				,'flv'
				
				//document
				,'pdf'
				
			);
			
			$this->cdn_patternFilesTypeAllow = array_unique($this->cdn_patternFilesTypeAllow);
			$this->cdn_patternFilesTypeAllow = implode('|',$this->cdn_patternFilesTypeAllow);
			
		}
		
	}
	
	
	public function migrationOptions()
	{
		$rsOne = $this->check_required_others_plugins();
		if(isset($rsOne['plugins']['wp-optimize-by-xtraffic']['success']['all_valid'])) {
			global $wpOptimizeByxTraffic;
			$options1 = $this->get_options();
			$options2 = $wpOptimizeByxTraffic->get_options();
			foreach($options1 as $key1 => $value1) {
				foreach($options2 as $key2 => $value2) {
					if(false !== strpos($key2,$key1)) {
						$options1[$key1] = $value2;
						unset($options2[$key1]);
						break 1;
					}
				}
			}
			
			$wpOptimizeByxTraffic->update_options(WPOPTIMIZEBYXTRAFFIC_PLUGIN_NS, $options2);
			$this->update_options($this->db_option_key, $options1);
		}
		
	}
	
	
	
	public function check_system_ready() 
	{
		global $wpOptimizeByxTraffic;
		
		$resultData = array();
		$resultData['notice']['error'] = array();
		$resultData['notice']['error_no'] = array();
		
		
		$rsTemp = $wpOptimizeByxTraffic->base_check_system_ready();
		$resultData = PepVN_Data::mergeArrays(array(
			$resultData
			,$rsTemp
		));
		
		
		$folderPath = WP_CONTENT_DIR . '/cache/';
		
		if(!file_exists($folderPath)) {
			PepVN_Data::createFolder($folderPath);
		}
		
		if(!PepVN_Data::isAllowReadAndWrite($folderPath)) {
			$resultData['notice']['error'][] = '<div class="update-nag fade"><b>'.$this->PLUGIN_NAME.'</b> : '.__('Your server must set',$this->PLUGIN_SLUG).' <u>'.__('readable',$this->PLUGIN_SLUG).'</u> & <u>'.__('writable',$this->PLUGIN_SLUG).'</u> '.__('folder',$this->PLUGIN_SLUG).' "<b>'.$folderPath.'</b>" '.__('to use',$this->PLUGIN_SLUG).' "<b>Optimize Speed</b>"</div>';
			$resultData['notice']['error_no'][] = 30;
		}
		
		
		if(function_exists('ob_start')) {
		} else {
			$resultData['notice']['error'][] = '<div class="update-nag fade"><b>'.$this->PLUGIN_NAME.'</b> : '.__('Your server must support',$this->PLUGIN_SLUG).' "<a href="http://php.net/manual/en/function.ob-start.php" target="_blank"><b>ob_start</b></a>" '.__('to use',$this->PLUGIN_SLUG).' "<b>Optimize Speed</b>"</div>';
			$resultData['notice']['error_no'][] = 30;
		}
		
		if(
			isset($this->UploadsStaticFilesFolderPath)
			&& $this->UploadsStaticFilesFolderPath
		) {
			if(!file_exists($this->UploadsStaticFilesFolderPath)) {
				PepVN_Data::createFolder($this->UploadsStaticFilesFolderPath);
			}
			
			if(
				file_exists($this->UploadsStaticFilesFolderPath)
				&& PepVN_Data::isAllowReadAndWrite($this->UploadsStaticFilesFolderPath)
			) {
				
			} else {
				$resultData['notice']['error'][] = '<div class="update-nag fade"><b>'.$this->PLUGIN_NAME.'</b> : '.__('Your server must set',$this->PLUGIN_SLUG).' <u>'.__('readable',$this->PLUGIN_SLUG).'</u> & <u>'.__('writable',$this->PLUGIN_SLUG).'</u> '.__('folder',$this->PLUGIN_SLUG).' "<b>'.$this->UploadsStaticFilesFolderPath.'</b>" '.__('to use',$this->PLUGIN_SLUG).' "<b>Optimize Speed</b>"</div>';
				$resultData['notice']['error_no'][] = 30;
			}
		}
		
		$path = ABSPATH;
		
		if($this->is_subdirectory_install()){
			$path = $this->base_getABSPATH();
		}
		
		if('apache' === PepVN_Data::$defaultParams['serverSoftware']) {
			
			$pathFileHtaccess = $path.'.htaccess';
			
			$checkStatus1 = false;
			
			if(file_exists($pathFileHtaccess) && is_file($pathFileHtaccess) && is_writable($pathFileHtaccess)){
				$checkStatus1 = true;
			} else if(PepVN_Data::is_writable($path)) {
				$checkStatus1 = true;
			}
			
			if(!$checkStatus1) {
				$resultData['notice']['error'][] = '<div class="update-nag fade"><b>'.$this->PLUGIN_NAME.'</b> : '.__('Your server is using Apache. You should create file ',$this->PLUGIN_SLUG).'"'.$pathFileHtaccess.'" and make it <u>'.__('readable',$this->PLUGIN_SLUG).'</u> & <u>'.__('writable',$this->PLUGIN_SLUG).'</u> '.__('to achieve the highest performance with ',$this->PLUGIN_SLUG).' "<b>Optimize Speed</b>". '.__('After change, please deactivate & reactivate this plugin for the changes to be updated',$this->PLUGIN_SLUG).'!</div>';
			}
			
		} else if('nginx' === PepVN_Data::$defaultParams['serverSoftware']) {
			$pathFileConfig = $path.'xtraffic-nginx.conf';
			
			$checkStatus1 = false;
			
			if(file_exists($pathFileConfig) && is_file($pathFileConfig) && is_writable($pathFileConfig)){
				$checkStatus1 = true;
			} else if(PepVN_Data::is_writable($path)) {
				$checkStatus1 = true; 
			}
			
			if(!$checkStatus1) {
				$resultData['notice']['error'][] = '<div class="update-nag fade"><b>'.$this->PLUGIN_NAME.'</b> : '.__('Your server is using Nginx. You should create file ',$this->PLUGIN_SLUG).'"'.$pathFileConfig.'" and make it <u>'.__('readable',$this->PLUGIN_SLUG).'</u> & <u>'.__('writable',$this->PLUGIN_SLUG).'</u> '.__('to achieve the highest performance with ',$this->PLUGIN_SLUG).' "<b>Optimize Speed</b>". '.__('After change, please deactivate & reactivate this plugin for the changes to be updated',$this->PLUGIN_SLUG).'!</div>';
			} else {
				$resultData['notice']['error'][] = '
				<div class="update-nag fade">
					<p><b>'.$this->PLUGIN_NAME.'</b> : '.__('To achieve the highest performance with the plugin on your Nginx server, you should follow the instructions below (if you have not already done) : ',$this->PLUGIN_SLUG).' <input type="button" value="Show me" class="button-primary wpoptimizebyxtraffic_show_hide_trigger" data-target="#optimize_nginx_config_server_guide_container" /></p>
					<div id="optimize_nginx_config_server_guide_container" class="wpoptimizebyxtraffic_show_hide_container" style="display:none;">
						<ul>
						
							<li>
								<h6 style="font-weight: 900;font-size: 100%;margin-bottom: 6px;"><b><u>'.__('Step 1',$this->PLUGIN_SLUG).'</u></b> : '.__('Find and remove <i style="color: red;font-weight: 900;">red block below</i> (if exists) in your config "<i>server {...}</i>" block (at file .conf)',$this->PLUGIN_SLUG).' :</h6>
<pre style="background-color: #eee;padding: 20px 20px;margin-left: 2%;">server {
	listen   80; 
	## Your website name goes here.
	server_name '.$this->fullDomainName.';
	root '.$path.';
	index index.php;
	...
	<b style="color: red;font-weight: 900;"><i>location / {
		...
	}</i></b>
	...
}</pre>
							</li>
							
							<li>
								<h6 style="font-weight: 900;font-size: 100%;margin-bottom: 6px;"><b><u>'.__('Step 2',$this->PLUGIN_SLUG).'</u></b> : '.__('Add <i style="color: blue;font-weight: 900;">blue line below</i> into your config "<i>server {...}</i>" block (at file .conf)',$this->PLUGIN_SLUG).' :</h6>
<pre style="background-color: #eee;padding: 20px 20px;margin-left: 2%;">server {
	listen   80; 
	## Your website name goes here.
	server_name '.$this->fullDomainName.';
	root '.$path.';
	index index.php;
	...
	<b style="color: blue;font-weight: 900;"><i>include '.$pathFileConfig.';</i></b>
	...
}</pre>
							</li>
							
							<li>
								<h6 style="font-weight: 900;font-size: 100%;margin-bottom: 6px;"><b><u>'.__('Step 3',$this->PLUGIN_SLUG).'</u></b> : '.__('Restart your Nginx through SSH command',$this->PLUGIN_SLUG).' : </h6>
<pre style="background-color: #eee;padding: 20px 20px;margin-left: 2%;"># sudo service nginx restart</pre>
							</li>
						</ul>
					</div>
				</div>
				';
			}
			
		}
		
		$resultData['notice']['error'] = array_unique($resultData['notice']['error']);
		$resultData['notice']['error_no'] = array_unique($resultData['notice']['error_no']);
		
		return $resultData;
		
	}
	
	public function fix_javascript_code($input_data) 
	{
		$patterns = array(
			'#document.write\((\'|\")(.+)\1\)#is' => 'wpOptimizeByxtraffic_appendHtml(document.getElementsByTagName("body")[0],$1$2$1)' 
		);
		
		return preg_replace(array_keys($patterns), array_values($patterns), $input_data);
	}
	
	private function _get_all_javascripts_for_process($text) 
	{
		$keyCache = PepVN_Data::fKey(array(
			__METHOD__
			, $text
		));
		
		$resultData = PepVN_Data::$cacheObject->get_cache($keyCache);
		
		if(null === $resultData) {
			$resultData = $this->get_all_javascripts($text);
			
			foreach($resultData as $key1 => $value1) {
				
				$checkStatus1 = false;
				
				if($value1) {
					if(!preg_match('#<(script)[^><]*data-xtraffic-optimize-js-exclude[^><]*/?>.*?</script>#is',$value1,$matched2)) {
						$checkStatus1 = true;
					}
					$matched2 = 0;
				}
				
				if(true !== $checkStatus1) {
					unset($resultData[$key1]);
				}
				$value1 = 0;
			}
			
			PepVN_Data::$cacheObject->set_cache($keyCache, $resultData);
		}
		
		return $resultData;
		
	}
	
	public function get_all_javascripts($text) 
	{
		$keyCache = PepVN_Data::fKey(array(
			__METHOD__
			, $text
		));
		
		$resultData = PepVN_Data::$cacheObject->get_cache($keyCache);
		
		if(null === $resultData) {
			$resultData = array();
			
			preg_match_all('#<script[^><]*>.*?</script>#is',$text,$matched1);
			
			if(isset($matched1[0]) && !PepVN_Data::isEmptyArray($matched1[0])) {
				$matched1 = $matched1[0];
				foreach($matched1 as $key1 => $value1) {
					unset($matched1[$key1]);
					
					if($value1) {
						$resultData[] = $value1;
					}
					
					$value1 = 0;
				}
			}
			
			PepVN_Data::$cacheObject->set_cache($keyCache, $resultData);
		}
		
		return $resultData;
		
	}
	
	
	public function get_all_css($text) 
	{
		
		$keyCache = PepVN_Data::fKey(array(
			__METHOD__
			, $text
		));
		
		$resultData = PepVN_Data::$cacheObject->get_cache($keyCache);
		
		if(null === $resultData) {
			
			$resultData = array();
			
			preg_match_all('#<(link)[^><]*/?>.*?(</\1>)?#is',$text,$matched1);
			
			if(isset($matched1[0]) && !PepVN_Data::isEmptyArray($matched1[0])) {
				$matched1 = $matched1[0];
				foreach($matched1 as $key1 => $value1) {
					unset($matched1[$key1]);
					
					if($value1) {
						if(preg_match('#type=(\'|")text/css\1#i',$value1,$matched2)) {
							if(!preg_match('#<(link)[^><]*data-xtraffic-optimize-css-exclude[^><]*/?>.*?(</\1>)?#is',$value1,$matched3)) {
								$positions1 = strpos($text,$value1);
								if(false !== $positions1) {
									$resultData[$value1] = (int)$positions1;
								}
							}
							$matched3 = 0;
						}
						$matched2 = 0;
					}
					
					$value1 = 0;
				}
			}
			
			$matched1 = 0;
			
			preg_match_all('#<(style)[^><]*>.*?</\1>#is',$text,$matched1);
			
			if(isset($matched1[0]) && !PepVN_Data::isEmptyArray($matched1[0])) {
				$matched1 = $matched1[0];
				foreach($matched1 as $key1 => $value1) {
					unset($matched1[$key1]);
					
					if($value1) {
						if(!preg_match('#<(style)[^><]*data-xtraffic-optimize-css-exclude[^><]*/?>.*?(</\1>)?#is',$value1,$matched2)) {
							$positions1 = strpos($text,$value1);
							if(false !== $positions1) {
								$resultData[$value1] = (int)$positions1;
							}
						}
						$matched2 = 0;
					}
					$value1 = 0;
				}
			}
			
			$matched1 = 0;
			
			asort($resultData);
			$resultData = array_keys($resultData);
			PepVN_Data::$cacheObject->set_cache($keyCache, $resultData);
			
		}
		
		return $resultData;
	}
	
	
	public function parse_load_html_scripts_by_tag($input_parameters) 
	{
		$resultData = '';
		if(isset($input_parameters['url'])) {
			
			$input_parameters['url'] = PepVN_Data::removeProtocolUrl($input_parameters['url']);
			
			if(!isset($input_parameters['id'])) {
				$input_parameters['id'] = PepVN_Data::mcrc32($input_parameters['url']);
			}
			
			$jsLoaderId = PepVN_Data::mcrc32($input_parameters['id'].'_js_loader');
			
			if(!isset($input_parameters['media'])) {
				$input_parameters['media'] = 'all';
			}
			
			if(!isset($input_parameters['append_to'])) {
				$input_parameters['append_to'] = '';
			}
			
			$loadTimeDelay = $this->loadCssTimeDelay;
			if('js' === $input_parameters['file_type']) {
				$loadTimeDelay = $this->loadJsTimeDelay;
			}
			
			$loadTimeDelay = (int)$loadTimeDelay;
			if($loadTimeDelay<10) {
				$loadTimeDelay = 10;
			}
			
			if('js' === $input_parameters['load_by']) {
				
				if('js' === $input_parameters['file_type']) {
					$resultData = ' <script data-cfasync="false" language="javascript" type="text/javascript" id="'.$jsLoaderId.'">
/*<![CDATA[*/
setTimeout(function() {
(function(e) { var t, n, r, s, i = "'.$input_parameters['id'].'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("script"); r.setAttribute("data-cfasync","false"); r.id = i; r.setAttribute("language","javascript"); r.setAttribute("type","text/javascript"); r.async = true; r.src = n + "//'.$input_parameters['url'].'"; s = e.getElementById("'.$jsLoaderId.'"); s.parentNode.insertBefore(r, s); s.parentNode.removeChild(s); })(document);
}, '.$loadTimeDelay.');
/*]]>*/
</script> ';
				} else if('css' === $input_parameters['file_type']) { 
					
					if('head' === $input_parameters['append_to']) {
					
						$resultData = ' <script language="javascript" type="text/javascript" id="'.$jsLoaderId.'">
/*<![CDATA[*/
setTimeout(function() {
(function(e) { var t, n, r, s, hd = document.getElementsByTagName("head")[0], i = "'.$input_parameters['id'].'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("link"); r.id = i; r.setAttribute("rel","stylesheet"); r.setAttribute("type","text/css"); r.setAttribute("media","'.$input_parameters['media'].'"); r.async = true; r.href = n + "//'.$input_parameters['url'].'"; hd.appendChild(r); s = e.getElementById("'.$jsLoaderId.'"); s.parentNode.removeChild(s); })(document);
}, '.(($this->numberLoadCssAsync * $loadTimeDelay) + 2).');
/*]]>*/
</script> ';
						$this->numberLoadCssAsync++;

					} else {
						$resultData = ' <script language="javascript" type="text/javascript" id="'.$jsLoaderId.'">
/*<![CDATA[*/
setTimeout(function() {
(function(e) { var t, n, r, s, i = "'.$input_parameters['id'].'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("link"); r.id = i; r.setAttribute("rel","stylesheet"); r.setAttribute("type","text/css"); r.setAttribute("media","'.$input_parameters['media'].'"); r.async = true; r.href = n + "//'.$input_parameters['url'].'"; s = e.getElementById("'.$jsLoaderId.'"); s.parentNode.insertBefore(r, s); s.parentNode.removeChild(s); })(document);
}, '.$loadTimeDelay.');
/*]]>*/
</script> ';
					}
					
				}
				
				
			} else if('div_tag' === $input_parameters['load_by']) {
				
				$resultData = ' <div class="wp-optimize-by-xtraffic-js-loader-data" id="'.$jsLoaderId.'" pepvn_data_loader_id="'.$jsLoaderId.'" pepvn_data_append_to="'.$input_parameters['append_to'].'" pepvn_data_src="'.($input_parameters['url']).'" pepvn_data_id="'.$input_parameters['id'].'" pepvn_data_file_type="'.$input_parameters['file_type'].'" pepvn_data_media="'.$input_parameters['media'].'" pepvn_data_time_delay="'.$loadTimeDelay.'" style="display:none;" ></div> ';  
				
			} else if('js_data' === $input_parameters['load_by']) {
				
				$resultData = ' <script language="javascript" type="text/javascript" id="'.$jsLoaderId.'" > (function(e) { if(typeof(e.wpOptimizeByxTraffic_JsLoaderData) === "undefined") { e.wpOptimizeByxTraffic_JsLoaderData = []; } e.wpOptimizeByxTraffic_JsLoaderData.push({ 
"pepvn_data_loader_id" : "'.$jsLoaderId.'"
,"pepvn_data_append_to" : "'.$input_parameters['append_to'].'"
,"pepvn_data_src" : "'.($input_parameters['url']).'"
,"pepvn_data_id" : "'.$input_parameters['id'].'"
,"pepvn_data_file_type" : "'.$input_parameters['file_type'].'"
,"pepvn_data_media" : "'.$input_parameters['media'].'"
,"pepvn_data_time_delay" : "'.$loadTimeDelay.'"
}); })(window); </script> ';
			}
			
		}
		
		return $resultData;
	}
	
	
	
	public function process_html_pages(&$text) 
	{
		global $wpOptimizeByxTraffic;
		
		$checkStatus1 = true;
		
		if($checkStatus1) {
			if ( PepVN_Data::wp_is_feed() ) {
				$checkStatus1 = false;
			}
		}
		
		if($checkStatus1) {
			if ( PepVN_Data::wp_is_admin() ) {
				$checkStatus1 = false;
			}
		}
		
		if($checkStatus1) {
			$rsTemp = $this->check_system_ready();
			if(in_array(30,$rsTemp['notice']['error_no'])) {
				$checkStatus1 = false; 
			}
		}
		
		if(!$checkStatus1) {
			return $text;
		}
		
		$options = $this->get_options(array(
			'cache_status' => 1
		));
		
		//Check can process
		
		$processJavascriptStatus = false;
		$processCssStatus = false;
		$processHtmlStatus = false;
		
		if('on' == $options['optimize_javascript_enable']) {
			
			if(
				('on' == $options['optimize_javascript_combine_javascript_enable'])
				|| ('on' == $options['optimize_javascript_minify_javascript_enable'])
				|| ('on' == $options['optimize_javascript_asynchronous_javascript_loading_enable'])
			) {
				$processJavascriptStatus = true;
			}
		}
		
		
		if('on' == $options['optimize_css_enable']) {
			if(
				('on' == $options['optimize_css_combine_css_enable'])
				|| ('on' == $options['optimize_css_minify_css_enable'])
				|| ('on' == $options['optimize_css_asynchronous_css_loading_enable'])
			) {
				$processCssStatus = true;
			}
		}
		
		if('on' == $options['optimize_html_enable']) {
			if('on' == $options['optimize_html_minify_html_enable']) {
				$processHtmlStatus = true;
			}
		}
		
		if(
			(!$processJavascriptStatus)
			&& (!$processCssStatus)
			&& (!$processHtmlStatus)
		) {
			return $text;
		}
		
		$keyCacheProcessMain = PepVN_Data::fKey(array(
			'keyCache1' => array(
				__METHOD__
				,$text
				,'process_main'
			)
			,'options' => $options
		));
		
		$valueTemp = PepVN_Data::$cachePermanentObject->get_cache($keyCacheProcessMain);
		
		if(null !== $valueTemp) {
			$text = $valueTemp;
			$valueTemp = 0;
			return $text;
		}
		
		$patternsEscaped = array();
		
		$rsOne = PepVN_Data::escapeSpecialElementsInHtmlPage($text);
		$text = $rsOne['content'];
		if(count($rsOne['patterns'])>0) {
			$patternsEscaped = array_merge($patternsEscaped, $rsOne['patterns']);
		}
		$rsOne = false;
		
		$rsOne = PepVN_Data::escapeHtmlTagsAndContents($text,'pre');
		
		$text = $rsOne['content'];
		if(count($rsOne['patterns'])>0) {
			$patternsEscaped = array_merge($patternsEscaped, $rsOne['patterns']);
		}
		$rsOne = false;
		
		$textAppendToBody = '';
		
		$textAppendToHead = '';
		
		$fullDomainName = $this->fullDomainName;
		$fullDomainNamePregQuote = PepVN_Data::preg_quote($fullDomainName); 
		
		if($processJavascriptStatus) {
			$arrayDataTextNeedReplace = array();
			
			$patternJavascriptExcludeUrl = array(
				'wp\-admin'
				,'stats.wp.com'
			);
			//cleanPregPatternsArray
			if($options['optimize_javascript_exclude_url']) {
				$valueTemp1 = $options['optimize_javascript_exclude_url'];
				$valueTemp1 = PepVN_Data::cleanPregPatternsArray($valueTemp1);
				if(!PepVN_Data::isEmptyArray($valueTemp1)) {
					$patternJavascriptExcludeUrl = array_merge($patternJavascriptExcludeUrl, $valueTemp1);
				}
			}
			
			$patternJavascriptExcludeUrl = implode('|',$patternJavascriptExcludeUrl);
			$patternJavascriptExcludeUrl = trim($patternJavascriptExcludeUrl);
			
			$combineJavascriptsStatus = false;
			if(isset($options['optimize_javascript_combine_javascript_enable']) && ($options['optimize_javascript_combine_javascript_enable'])) {	
				$combineJavascriptsStatus = true;
			}
			
			$rsGetAllJavascripts = $this->_get_all_javascripts_for_process($text);
			
			if(!PepVN_Data::isEmptyArray($rsGetAllJavascripts)) {
				
				$rsGetAllJavascripts1 = array();
				
				foreach($rsGetAllJavascripts as $key1 => $value1) {
					unset($rsGetAllJavascripts[$key1]);
					
					$checkStatus2 = false;
					if($value1) {
						$checkStatus2 = true;
						if(preg_match('#type=(\'|")([^"\']+)\1#i',$value1,$matched2)) {
							if(isset($matched2[2]) && $matched2[2]) {
								$matched2[2] = trim($matched2[2]);
								if($matched2[2]) {
									$checkStatus2 = false;
									if(false !== stripos($matched2[2],'javascript')) {
										$checkStatus2 = true;
									}
								}
							}
						}
						$matched2 = 0;
					}
					
					
					if($checkStatus2) {
						
						if(preg_match('#<script[^><]*?src=(\'|")((https?:)?//[^"\']+)\1#i',$value1,$matched2)) {
						
							if(isset($matched2[2]) && $matched2[2]) {
								
								$matched2 = trim($matched2[2]);
								
								$isProcessStatus1 = true;
								
								if($patternJavascriptExcludeUrl) {
									if(preg_match('#('.$patternJavascriptExcludeUrl.')#i',$matched2)) {
										$isProcessStatus1 = false;
									}
								}
								
								if($isProcessStatus1) {
									if(preg_match($this->patternsIgnoredJavascript_Uri,$matched2)) {
										$isProcessStatus1 = false;
									}
								}
								
								if($isProcessStatus1) {
									if(isset($options['optimize_javascript_exclude_external_javascript_enable']) && $options['optimize_javascript_exclude_external_javascript_enable']) {
										if(!preg_match('#^(https?)?:?(//)?'.$fullDomainNamePregQuote.'#i',$matched2)) {
											
											$isProcessStatus1 = false;
										}
									}
								}
								
								
								if($isProcessStatus1) {
									$rsGetAllJavascripts1[$key1] = $value1;
								}
							}
						} else if(preg_match('/<script[^><]*>(.*?)<\/script>/is',$value1,$matched2)) {
						
							if(isset($matched2[1]) && $matched2[1]) {
								$matched2 = trim($matched2[1]);
								if($matched2) {
									if(preg_match('#\s*?st_go\(\{.+#is',$matched2)) { 
									
									} else if(!preg_match($this->patternsIgnoredJavascript_Code,$matched2)) {
										
										if(isset($options['optimize_javascript_combine_javascript_enable']) && ($options['optimize_javascript_combine_javascript_enable'])) {	
											if(isset($options['optimize_javascript_exclude_inline_javascript_enable']) && ($options['optimize_javascript_exclude_inline_javascript_enable'])) {
												$matched2 = pepvn_MinifyJavascript($this->fix_javascript_code($matched2));
												$arrayDataTextNeedReplace[$value1] = $this->parse_load_html_scripts_by_tag(array(
													'url' => '|__ecv__|'.PepVN_Data::encodeVar($matched2)//base64_encode($matched2)
													,'load_by' => 'js_data'//js_data,div_tag
													,'file_type' => 'js'
												));
											} else {
												$rsGetAllJavascripts1[$key1] = $value1;
											}
											
										} else {
											if(isset($options['optimize_javascript_exclude_inline_javascript_enable']) && ($options['optimize_javascript_exclude_inline_javascript_enable'])) {
											} else {
												$rsGetAllJavascripts1[$key1] = $value1;
											}
										}
									}
								
								}
							}
						}
						
						$matched2 = 0;
					}
					
					$value1 = 0;
				}
				
				$rsGetAllJavascripts = $rsGetAllJavascripts1; $rsGetAllJavascripts1 = 0;
				
				if(!$combineJavascriptsStatus) {
					
					$iNumberScript1 = 1;
					
					foreach($rsGetAllJavascripts as $key1 => $value1) {
						if($value1) {
							
							$jsLink1 = false;
							
							if(preg_match('#src=(\'|")((https?:)?//[^"\']+)\1#i',$value1,$matched2)) {
								if(isset($matched2[2]) && $matched2[2]) {
									$matched2[2] = trim($matched2[2]);
									
									$jsLink1 = $matched2[2];
								}
							}
							
							if($jsLink1) { 
							
								if(isset($options['optimize_javascript_minify_javascript_enable']) && $options['optimize_javascript_minify_javascript_enable']) {
									
									$keyCacheJsLink1 = PepVN_Data::fKey($jsLink1);
									
									$jsLink1FilesPath = false;
									
									$jsLink1FilesPath1 = $this->UploadsStaticFilesFolderPath . $keyCacheJsLink1.'.js';
									
									if(file_exists($jsLink1FilesPath1)) {
										if(filesize($jsLink1FilesPath1)>0) {
											$jsLink1FilesPath = $jsLink1FilesPath1;
										} else {
											$filemtimeTemp1 = filemtime($jsLink1FilesPath1);
											if($filemtimeTemp1) {
												$filemtimeTemp1 = (int)$filemtimeTemp1;
												if((PepVN_Data::$defaultParams['requestTime'] - $filemtimeTemp1) <= (86400 * 1)) {
													$jsLink1FilesPath1 = false;
												} else {
													unlink($jsLink1FilesPath1);
												}
											}
										}
									}
									
									if($jsLink1FilesPath1 && !$jsLink1FilesPath) {
										if(PepVN_Data::is_writable($this->UploadsStaticFilesFolderPath)) {
											
											@file_put_contents($jsLink1FilesPath1,'');
											
											$jsLink1Temp = $jsLink1;
											$protocol1 = 'http://';
											$jsLink1Temp = PepVN_Data::removeProtocolUrl($jsLink1Temp);
											
											if(preg_match('#^https\://#i', $jsLink1)) {
												$protocol1 = 'https://';
											}
											
											$jsContent1 = $wpOptimizeByxTraffic->quickGetUrlContent($protocol1.$jsLink1Temp, array(
												'cache_timeout' => $this->getUrlContentCacheTimeout
											));
											
											
											if($jsContent1) {
												$jsContent1 = trim($jsContent1);
												if($jsContent1) {
													
													$jsContent1 = $this->fix_javascript_code($jsContent1);
													
													pepvn_MinifyJavascript_Ref($jsContent1);
													
													$jsContent1 = ' try { '.$jsContent1.' } catch(err) { } ';
													
													
													@file_put_contents($jsLink1FilesPath1, $jsContent1);
													
													$jsLink1FilesPath = $jsLink1FilesPath1;
													
												}
											}
											
										}
									}
									
									if($jsLink1FilesPath) {
										$jsLink1 = str_replace($this->UploadsStaticFilesFolderPath,$this->UploadsStaticFilesFolderUrl,$jsLink1FilesPath);
									}
									
								}//optimize_javascript_minify_javascript_enable
							}
							
							if($jsLink1) {
								$jsLink1 = $this->cdn_get_cdn_link($jsLink1);
								$jsLink1 = PepVN_Data::removeProtocolUrl($jsLink1);
								
								$jsLink1 = trim($jsLink1);
								
								if($jsLink1) {
								
									if(isset($options['optimize_javascript_asynchronous_javascript_loading_enable']) && ($options['optimize_javascript_asynchronous_javascript_loading_enable'])) {
									
										$valueTemp = $this->parse_load_html_scripts_by_tag(array(
											'url' => $jsLink1
											,'load_by' => 'js_data'//js,js_data,div_tag
											,'file_type' => 'js'
										));
										if($valueTemp) {
											$arrayDataTextNeedReplace[$value1] = $valueTemp;
										}
										
									} else {
										$arrayDataTextNeedReplace[$value1] = ' <script language="javascript" type="text/javascript" src="//'.$jsLink1.'" ></script> ';
									}
								}
								
							}
							
						}
						$value1 = 0;
					}
					
					
				} else {//enable combine js
			
					
					$keyCacheAllJavascripts = PepVN_Data::fKey($rsGetAllJavascripts);
					
					$combinedAllJavascriptsFilesPath = false;
					
					$combinedAllJavascriptsFilesPath1 = $this->UploadsStaticFilesFolderPath . $keyCacheAllJavascripts.'.js';
					
					if(file_exists($combinedAllJavascriptsFilesPath1)) {
						if(filesize($combinedAllJavascriptsFilesPath1)>0) {
							$combinedAllJavascriptsFilesPath = $combinedAllJavascriptsFilesPath1;
						} else {
							$filemtimeTemp1 = filemtime($combinedAllJavascriptsFilesPath1);
							if($filemtimeTemp1) {
								$filemtimeTemp1 = (int)$filemtimeTemp1;
								if((PepVN_Data::$defaultParams['requestTime'] - $filemtimeTemp1) <= (3600 * 12)) {
									$combinedAllJavascriptsFilesPath1 = false;
								} else {
									unlink($combinedAllJavascriptsFilesPath1);
								}
							}
						}
					}
					
					if($combinedAllJavascriptsFilesPath1 && !$combinedAllJavascriptsFilesPath) {
						if(PepVN_Data::is_writable($this->UploadsStaticFilesFolderPath)) {
							
							@file_put_contents($combinedAllJavascriptsFilesPath1,'');
							
							$combinedAllJavascriptsFilesContents = '';
							
							$breakProcessStatus1 = false;
							
							foreach($rsGetAllJavascripts as $key1 => $value1) {
								
								$jsContent1 = '';
								$value1 = trim($value1);
								if($value1) {
									
									if(preg_match('#<script[^><]*?src=(\'|")((https?:)?//[^"\']+)\1#i',$value1,$matched2)) {
										
										if(isset($matched2[2]) && $matched2[2]) {
											
											$matched2 = trim($matched2[2]);
										
											$protocol1 = 'http';
											
											if(false !== strpos($matched2,'https')) {
												$protocol1 .= 's';
											}
											
											$protocol1 .= ':';
											
											$matched2 = preg_replace('#^https?:#i','',$matched2);
											
											$jsContent2 = $wpOptimizeByxTraffic->quickGetUrlContent($protocol1.$matched2, array(
												'cache_timeout' => $this->getUrlContentCacheTimeout
											));
											
											if($jsContent2) {
												$jsContent1 = $jsContent2;
											}
											
											if(!$jsContent2) {
												$breakProcessStatus1 = true;
												break;
											}
											
											$jsContent2 = 0;
										}
										
									} else if(preg_match('/<script[^><]*>(.*?)<\/script>/is',$value1,$matched2)) {
										
										if(isset($matched2[1]) && $matched2[1]) {
											$matched2 = $matched2[1];
											if(preg_match('#\s*?st_go\(\{.+#is',$matched2,$matched3)) {
												
											} else {
												$jsContent1 = $matched2;
											}
											$matched3 = 0;
										}
									}
									
									$matched2 = 0;
								}
								
								
								if('' !== $jsContent1) {
									
									$jsContent1 = $this->fix_javascript_code($jsContent1);
									
									$arrayDataTextNeedReplace[$value1] = '';
									
									if(isset($options['optimize_javascript_minify_javascript_enable']) && $options['optimize_javascript_minify_javascript_enable']) {
										pepvn_MinifyJavascript_Ref($jsContent1);
									}
									
									$combinedAllJavascriptsFilesContents .= ' try { '.$jsContent1.' } catch(err) { } ';
									$jsContent1 = 0;
									
								}
								
								$value1 = 0;
							}
							
							if(!$breakProcessStatus1) {
								@file_put_contents($combinedAllJavascriptsFilesPath1, trim($combinedAllJavascriptsFilesContents));
								$combinedAllJavascriptsFilesContents = 0;
								$combinedAllJavascriptsFilesPath = $combinedAllJavascriptsFilesPath1;
							}
							
						}
					}
					
					
					if($combinedAllJavascriptsFilesPath) {
						
						foreach($rsGetAllJavascripts as $key1 => $value1) {
							unset($rsGetAllJavascripts[$key1]);
							$arrayDataTextNeedReplace[$value1] = '';
							$value1 = 0;
						}
						
						$combinedAllJavascriptsFilesUrl = str_replace($this->UploadsStaticFilesFolderPath,$this->UploadsStaticFilesFolderUrl,$combinedAllJavascriptsFilesPath);
						
						$combinedAllJavascriptsFilesUrl = $this->cdn_get_cdn_link($combinedAllJavascriptsFilesUrl);
						
						$combinedAllJavascriptsFilesUrl = PepVN_Data::removeProtocolUrl($combinedAllJavascriptsFilesUrl);
						
						$combinedAllJavascriptsFilesUrl = trim($combinedAllJavascriptsFilesUrl);
												
						if(isset($options['optimize_javascript_asynchronous_javascript_loading_enable']) && ($options['optimize_javascript_asynchronous_javascript_loading_enable'])) {
							
							$valueTemp = $this->parse_load_html_scripts_by_tag(array(
								'url' => $combinedAllJavascriptsFilesUrl
								,'load_by' => 'js_data'//js_data,div_tag
								,'file_type' => 'js'
							));
							if($valueTemp) {
								$textAppendToBody .= $valueTemp;
							}
							
						} else {
							$textAppendToBody .= ' <script language="javascript" type="text/javascript" src="//'.$combinedAllJavascriptsFilesUrl.'" ></script> ';
						}
						
					}
				}
				
				if(!PepVN_Data::isEmptyArray($arrayDataTextNeedReplace)) {
					$text = str_replace(array_keys($arrayDataTextNeedReplace),array_values($arrayDataTextNeedReplace),$text);
				}
				$arrayDataTextNeedReplace = array();
				
			}
			
			$rsGetAllJavascripts = 0;
			$arrayDataTextNeedReplace = array();
			
		}
		
		
		if($processCssStatus) {
			$arrayDataTextNeedReplace = array();
			
			$patternCssExcludeUrl = array(
				'wp\-admin'
			);
			//cleanPregPatternsArray
			if($options['optimize_css_exclude_url']) {
				$valueTemp1 = $options['optimize_css_exclude_url'];
				$valueTemp1 = PepVN_Data::cleanPregPatternsArray($valueTemp1);
				if(!PepVN_Data::isEmptyArray($valueTemp1)) {
					$patternCssExcludeUrl = array_merge($patternCssExcludeUrl, $valueTemp1);
				}
			}
			
			$patternCssExcludeUrl = implode('|',$patternCssExcludeUrl);
			$patternCssExcludeUrl = trim($patternCssExcludeUrl);
			
			
			$combineCssStatus = true;
			if(!$options['optimize_css_combine_css_enable']) {
				$combineCssStatus = false;
			}
			
			$rsGetAllCss = $this->get_all_css($text); 
			
			if(!PepVN_Data::isEmptyArray($rsGetAllCss)) {
				
				if(!$combineCssStatus) {	//combineCssStatus:false
					
					foreach($rsGetAllCss as $key1 => $value1) {
						unset($rsGetAllCss[$key1]);
						
						if($value1) {
							
							$cssLink1 = false;
							
							if(preg_match('#href=(\'|")((https?:)?//[^"\']+)\1#is',$value1,$matched2)) {
								if(isset($matched2[2]) && $matched2[2]) {
									
									$matched2 = trim($matched2[2]);
									
									$isProcessStatus1 = true;
									
									if($patternCssExcludeUrl) {
										if(preg_match('#('.$patternCssExcludeUrl.')#i',$matched2,$matched3)) {
											$isProcessStatus1 = false;
										}
										$matched3 = 0;
									}
									
									if(isset($options['optimize_css_exclude_external_css_enable']) && ($options['optimize_css_exclude_external_css_enable'])) {
										
										if(!preg_match('#^(https?)?:?(//)?'.$fullDomainNamePregQuote.'#i',$matched2,$matched3)) {
											$isProcessStatus1 = false;
										}
										$matched3 = 0;
									}
									
									if($isProcessStatus1) {
										$cssLink1 = $matched2;
									}
									
									
								}
							}
							$matched2 = 0;
							
							if($cssLink1) {
								
								$mediaType1 = 'all';
								if(preg_match('#media=(\'|")([^"\']+)\1#is',$value1,$matched2)) {
									if(isset($matched2[2]) && $matched2[2]) {
										$matched2 = trim($matched2[2]);
										if($matched2) {
											$mediaType1 = $matched2;
										}
									}
								}
								
								if(isset($options['optimize_css_minify_css_enable']) && ($options['optimize_css_minify_css_enable'])) {
									
									$keyCacheCssFile1 = PepVN_Data::fKey(array(
										__METHOD__
										,$cssLink1
									));
									
									$cssFilePath1 = false;
									
									$cssFilePath2 = $this->UploadsStaticFilesFolderPath . $keyCacheCssFile1.'.css';
									
									if(file_exists($cssFilePath2)) {
										
										if(filesize($cssFilePath2) > 0) {
											$cssFilePath1 = $cssFilePath2;
										} else {
											$filemtimeTemp1 = filemtime($cssFilePath2);
											if($filemtimeTemp1) {
												$filemtimeTemp1 = (int)$filemtimeTemp1;
												if((PepVN_Data::$defaultParams['requestTime'] - $filemtimeTemp1) <= (3600 * 6)) {
													$cssFilePath2 = false;
												} else {
													@unlink($cssFilePath2);
												}
											}
										}
										
									}
									
									if($cssFilePath2 && !$cssFilePath1) {
										if(PepVN_Data::is_writable($this->UploadsStaticFilesFolderPath)) {
											@file_put_contents($cssFilePath2,'');
											
											
											$cssLinkTemp1 = $cssLink1;
											if(preg_match('#^//#i',$cssLinkTemp1,$matched3)) {
												$cssLinkTemp1 = 'http:'.$cssLinkTemp1;
											}
											
											if(PepVN_Data::isUrl($cssLinkTemp1)) {
												$cssContent1 = $wpOptimizeByxTraffic->quickGetUrlContent($cssLinkTemp1, array(
													'cache_timeout' => $this->getUrlContentCacheTimeout
												));
												
												if($cssContent1) {
													
													$pepVN_CSSFixer = 0;
													$pepVN_CSSFixer = new PepVN_CSSFixer();
													
													$valueTemp = false;
													
													if(isset($options['optimize_css_minify_css_enable']) && ($options['optimize_css_minify_css_enable'])) {
														$valueTemp = $pepVN_CSSFixer->fix(array(
															'css_content' => $cssContent1
															,'css_url' => $cssLinkTemp1
															,'minify_status' => true
														));
													} else {
														$valueTemp = $pepVN_CSSFixer->fix(array(
															'css_content' => $cssContent1
															,'css_url' => $cssLinkTemp1
															,'minify_status' => false
														));
													}
													
													if($valueTemp) {
														$cssContent1 = $valueTemp;
													}
													
													$valueTemp = 0;
													
													$cssContent1 = $this->cdn_process_text($cssContent1,'css');
													
													@file_put_contents($cssFilePath2,$cssContent1);
													$cssContent1 = 0;
													
													$cssFilePath1 = $cssFilePath2;
													
												}
											}											
										}
									}
									
									if($cssFilePath1) {
										
										$cssLink1 = str_replace($this->UploadsStaticFilesFolderPath,$this->UploadsStaticFilesFolderUrl,$cssFilePath1);
										
									}
								}
								
								if($cssLink1) {
									$cssLink1 = $this->cdn_get_cdn_link($cssLink1);
									$cssLink1 = PepVN_Data::removeProtocolUrl($cssLink1);
								}
								
								if(isset($options['optimize_css_asynchronous_css_loading_enable']) && ($options['optimize_css_asynchronous_css_loading_enable'])) {
									$valueTemp = $this->parse_load_html_scripts_by_tag(array(
										'url' => $cssLink1
										,'load_by' => 'js_data'//js_data,div_tag
										,'file_type' => 'css'
										,'media' => $mediaType1
									));
									
									if($valueTemp) {
										$arrayDataTextNeedReplace[$value1] = $valueTemp;
									}
								} else {
									$arrayDataTextNeedReplace[$value1] = ' <link href="//'.$cssLink1.'" media="'.$mediaType1.'" rel="stylesheet" type="text/css" /> ';
								}
								
							}
							
						}
						$value1 = 0;
					}
					
					if(!PepVN_Data::isEmptyArray($arrayDataTextNeedReplace)) {
						$text = str_replace(array_keys($arrayDataTextNeedReplace),array_values($arrayDataTextNeedReplace),$text);
					}
					$arrayDataTextNeedReplace = array();
				
				} else {//combineCssStatus:true
					
					$breakProcessStatus1 = false;
					
					$rsGetAllCssGroup = array();
					
					$lastMediaType = false;
					$lastCssGroup = array(
						'media' => ''
						,'css' => array()
						,'original_full_css' => array()
					);
					
					foreach($rsGetAllCss as $key1 => $value1) {
						
						if($value1) {
							$cssContent1 = '';
							if(preg_match('#href=(\'|")((https?)?:?//[^"\']+)\1#i',$value1,$matched2)) {
								
								if(isset($matched2[2]) && $matched2[2]) {
									$matched2 = trim($matched2[2]);
									
									$protocol1 = 'http';
									
									if(false !== strpos($matched2,'https')) {
										$protocol1 .= 's';
									}
									
									$protocol1 .= ':';
									
									$matched2 = preg_replace('#^https?:#i','',$matched2);
									
									$cssContent1 = $protocol1.$matched2;
									
									
									$isProcessStatus1 = true;
									
									if($patternCssExcludeUrl) {
										if(preg_match('#('.$patternCssExcludeUrl.')#i',$cssContent1,$matched3)) {
											$isProcessStatus1 = false;
										}
										$matched3 = 0;
									}
									
									if(isset($options['optimize_css_exclude_external_css_enable']) && ($options['optimize_css_exclude_external_css_enable'])) {
										if(!preg_match('#^(https?)?:?(//)?'.$fullDomainNamePregQuote.'#i',$cssContent1,$matched3)) {
											$isProcessStatus1 = false;
										}
										$matched3 = 0;
									}
									
									if(!$isProcessStatus1) {
										$cssContent1 = '';
									}
									
								}
								
							} else if(preg_match('/<style[^><]*?>(.*?)<\/style>/is',$value1,$matched2)) {
								if(isset($matched2[1]) && $matched2[1]) {
									$matched2 = $matched2[1];
									if(!$options['optimize_css_exclude_inline_css_enable']) {
										$cssContent1 .= PHP_EOL . ' ' .$matched2;
									}
								}
							}
							$matched2 = 0;
							
							if($cssContent1) {
								
								$mediaType1 = 'all';
								if(preg_match('#media=(\'|")([^"\']+)\1#is',$value1,$matched2)) {
									if(isset($matched2[2]) && $matched2[2]) {
										$matched2 = trim($matched2[2]);
										if($matched2) {
											$mediaType1 = $matched2;
										}
									}
								}
								
								if(false === $lastMediaType) {
									$lastMediaType = $mediaType1;
								}
								
								if($lastMediaType !== $mediaType1) {
									$rsGetAllCssGroup[] = $lastCssGroup;
									$lastMediaType = $mediaType1;
									$lastCssGroup = array(
										'media' => ''
										,'css' => array()
										,'original_full_css' => array()
									);
								}
								
								$lastCssGroup['media'] = $lastMediaType;
								$lastCssGroup['css'][] = $cssContent1;
								$lastCssGroup['original_full_css'][] = $value1;
								
							}
							$cssContent1 = 0;
						}
						
						$value1 = 0;
					}
					
					$cssLoadByJSString = '';
					
					$breakProcessStatus = false;
					
					$arrayDataTextNeedRemove = array();
					
					$rsGetAllCssGroup[] = $lastCssGroup;
					$lastCssGroup = 0;
					
					$iNumberCssFiles = 1;
					
					$appendCssToHead = '';
					
					$keyCacheAllCss = PepVN_Data::fKey($rsGetAllCssGroup);
					
					$combinedAllCssFilesPath = false;
					
					$combinedAllCssFilesPath1 = $this->UploadsStaticFilesFolderPath . $keyCacheAllCss.'.css';
					
					if(file_exists($combinedAllCssFilesPath1) && is_file($combinedAllCssFilesPath1)) {
						
						if(filesize($combinedAllCssFilesPath1) > 0) {
							$combinedAllCssFilesPath = $combinedAllCssFilesPath1;
						} else {
							$filemtimeTemp1 = filemtime($combinedAllCssFilesPath1);
							if($filemtimeTemp1) {
								$filemtimeTemp1 = (int)$filemtimeTemp1;
								if((PepVN_Data::$defaultParams['requestTime'] - $filemtimeTemp1) <= (3600 * 6)) {
									$combinedAllCssFilesPath1 = false;
								} else {
									@unlink($combinedAllCssFilesPath1);
								}
							}
						}
						
					}
					
					if($combinedAllCssFilesPath1 && !$combinedAllCssFilesPath) {
						
						if(PepVN_Data::is_writable($this->UploadsStaticFilesFolderPath)) {
							@file_put_contents($combinedAllCssFilesPath1,'');
							
							$combinedAllCssFilesHandle = fopen($combinedAllCssFilesPath1, "wb");
							
							if($combinedAllCssFilesHandle) {
								
								foreach($rsGetAllCssGroup as $key1 => $value1) {
								
									if(isset($value1['css']) && !PepVN_Data::isEmptyArray($value1['css'])) {
										
										$combinedAllCssFilesContents = '';
										
										$breakProcessStatus1 = false;
										
										foreach($value1['css'] as $key2 => $value2) {
											unset($value1['css'][$key2]);
											
											$value2 = trim($value2);
											if($value2) {
												$cssContent2 = '';
												
												if(PepVN_Data::isUrl($value2)) {
													$cssContent3 = $wpOptimizeByxTraffic->quickGetUrlContent($value2, array(
														'cache_timeout' => $this->getUrlContentCacheTimeout
													));
													
													if($cssContent3) {
														
														$pepVN_CSSFixer = 0;
														$pepVN_CSSFixer = new PepVN_CSSFixer();
														$valueTemp = $pepVN_CSSFixer->fix(array(
															'css_content' => $cssContent3
															,'css_url' => $value2
															,'minify_status' => false
														));
														$pepVN_CSSFixer = 0;
														
														if($valueTemp) {
															$cssContent3 = $valueTemp;
														}
														
														$cssContent2 .= PHP_EOL . ' ' .$cssContent3 . ' '.PHP_EOL;
														$valueTemp = 0; $cssContent3 = 0;
													} else {
														$breakProcessStatus1 = true;
														break;
													}
												} else {
													$cssContent2 .= PHP_EOL . ' ' . $value2 . ' '.PHP_EOL;
												}
												
												$cssContent2 = trim($cssContent2);
												if($cssContent2) {
													$combinedAllCssFilesContents .= PHP_EOL . ' ' . $cssContent2 . ' '.PHP_EOL;
												}
												$cssContent2 = 0;
											}
											$value2 = 0;
										}
										
										if(!$breakProcessStatus1) {
											
											$combinedAllCssFilesContents = $this->cdn_process_text($combinedAllCssFilesContents,'css');
											
											if(isset($options['optimize_css_minify_css_enable']) && ($options['optimize_css_minify_css_enable'])) {
												pepvn_MinifyCss_Ref($combinedAllCssFilesContents);
											}
											$combinedAllCssFilesContents = trim($combinedAllCssFilesContents);
											
											if(isset($value1['media']) && $value1['media'] && ('all' !== $value1['media'])) {
												$combinedAllCssFilesContents = ' @media '.$value1['media'].' {'.$combinedAllCssFilesContents.'}';
											}
											
											fwrite($combinedAllCssFilesHandle,$combinedAllCssFilesContents);
											
											$combinedAllCssFilesContents = 0;
											
										} else {
											$breakProcessStatus = true;
											break;
										}
									}
									
									$value1 = 0;
								}
								
								fclose($combinedAllCssFilesHandle);
								
								if(!$breakProcessStatus) {
									$combinedAllCssFilesPath = $combinedAllCssFilesPath1;
								}
								
							}
							
							$combinedAllCssFilesHandle = null; unset($combinedAllCssFilesHandle);
							
						}
					}
					
					if(!$breakProcessStatus) {
						
						if($combinedAllCssFilesPath) {
							
							foreach($rsGetAllCssGroup as $key1 => $value1) {
								unset($rsGetAllCssGroup[$key1]);
								if(isset($value1['original_full_css']) && !PepVN_Data::isEmptyArray($value1['original_full_css'])) {
									foreach($value1['original_full_css'] as $key2 => $value2) {
										unset($value1['original_full_css'][$key2]);
										$arrayDataTextNeedRemove[$value2] = '';
										$value2 = 0;
									}
								}
								
							}
							
							$rsGetAllCssGroup = 0;
							
							$combinedAllCssFilesUrl = str_replace($this->UploadsStaticFilesFolderPath,$this->UploadsStaticFilesFolderUrl,$combinedAllCssFilesPath);
							
							$combinedAllCssFilesUrl = $this->cdn_get_cdn_link($combinedAllCssFilesUrl);
							
							$combinedAllCssFilesUrl = PepVN_Data::removeProtocolUrl($combinedAllCssFilesUrl);
							$combinedAllCssFilesUrl = trim($combinedAllCssFilesUrl);
							
							if(isset($options['optimize_css_asynchronous_css_loading_enable']) && ($options['optimize_css_asynchronous_css_loading_enable'])) {
								$valueTemp = $this->parse_load_html_scripts_by_tag(array(
									'url' => $combinedAllCssFilesUrl
									,'load_by' => 'js_data'//js_data,div_tag
									,'append_to' => 'head'
									,'file_type' => 'css'
									,'media' => 'all'
								));
								if($valueTemp) {
									$textAppendToBody .= ' '.$valueTemp;
								}
							} else {
								$appendCssToHead .= ' <link href="//'.$combinedAllCssFilesUrl.'" media="'.$value1['media'].'" rel="stylesheet" type="text/css" /> ';
							}
						}
						
					}
					
					if(!$breakProcessStatus) { 
						
						$appendCssToHead = trim($appendCssToHead);
						if($appendCssToHead) {
							$textAppendToHead .= ' '.$appendCssToHead.' ';
						}
						
						if(!PepVN_Data::isEmptyArray($arrayDataTextNeedRemove)) {
							$text = str_replace(array_keys($arrayDataTextNeedRemove),array_values($arrayDataTextNeedRemove),$text);
						}
						
						$arrayDataTextNeedRemove = array();
						
						
					}
					
				}
			}
			
			$rsGetAllCss = 0;
			$arrayDataTextNeedReplace = array();
		}
		
		
		$jsUrl = WPOPTIMIZEBYXTRAFFIC_PLUGIN_URL;
		$jsUrl .= 'js/optimize_speed_by_xtraffic.min.js?v=' . WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_VERSION; 
		//$jsUrl .= 'js/optimize_speed_by_xtraffic.js?v=' . WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_VERSION . PepVN_Data::$defaultParams['requestTime'];//test

		$jsUrl = $this->cdn_get_cdn_link($jsUrl);
		$jsUrl = PepVN_Data::removeProtocolUrl($jsUrl);
		
		$jsId = $this->PLUGIN_SLUG.'-optimize-speed';
		$jsLoaderId = $jsId.'-js-loader'; 
		
		$jsLoadString = '<script data-cfasync="false" language="javascript" type="text/javascript" id="'.$jsLoaderId.'">
/*<![CDATA[*/
setTimeout(function() {
(function(e) { var t, n, r, s, i = "'.$jsId.'"; if(e.getElementById(i)) { return 0; } t = document.location.protocol; if(-1 !== t.indexOf("https")) { n = "https:"; } else { n = "http:"; } r = e.createElement("script"); r.setAttribute("data-cfasync","false"); r.id = i; r.setAttribute("language","javascript"); r.setAttribute("type","text/javascript"); r.async = true; r.src = n + "//'.$jsUrl.'"; s = e.getElementById("'.$jsLoaderId.'"); s.parentNode.insertBefore(r, s); s.parentNode.removeChild(s); })(document);
}, 10);
/*]]>*/
</script>';
		$textAppendToBody .= ' '.$jsLoadString;
		
		$text = PepVN_Data::appendTextToTagBodyOfHtml($textAppendToBody,$text);
		$textAppendToBody = 0;
		
		$textAppendToHead = trim($textAppendToHead);
		if($textAppendToHead) {
			
			$text = PepVN_Data::appendTextToTagHeadOfHtml($textAppendToHead,$text);
			
		}
		$textAppendToHead = 0;
		
		if($processHtmlStatus) {
			if(isset($options['optimize_html_minify_html_enable']) && ($options['optimize_html_minify_html_enable'])) {
				pepvn_MinifyHtml_Ref($text);
			}
			
		}
		
		if(count($patternsEscaped)>0) {
			$text = str_replace(array_values($patternsEscaped),array_keys($patternsEscaped),$text);
		}
		$patternsEscaped = 0;
		
		if($processHtmlStatus) {
			if(isset($options['optimize_html_minify_html_enable']) && ($options['optimize_html_minify_html_enable'])) {
				$this->_reduce_space_in_html_ref($text);
			}
		}
		
		$text = trim($text); 
		
		PepVN_Data::$cachePermanentObject->set_cache($keyCacheProcessMain,$text);
		
		return $text;
		
	}
	
	private function _reduce_space_in_html_ref(&$text) 
	{
		
		$keyCache1 = PepVN_Data::fKey(array(
			__METHOD__
			,$text
		));

		$resultData = PepVN_Data::$cachePermanentObject->get_cache($keyCache1);

		if(null !== $resultData) {
			$text = $resultData;
			return $text;
		}
		
		$patternsEscaped = array();
		
		preg_match_all('#<(script|pre)[^><]*>.*?</\1>#is',$text,$matched1);
		
		if(isset($matched1[0]) && !PepVN_Data::isEmptyArray($matched1[0])) {
			$matched1 = $matched1[0];
			foreach($matched1 as $key1 => $value1) {
				unset($matched1[$key1]);
				if($value1) {
					$patternsEscaped[$value1] = '_'.md5($value1).'_';
				}
				$value1 = 0;
			}
		}
		$matched1 = 0;
		
		if(!empty($patternsEscaped)) {
			$text = str_replace(array_keys($patternsEscaped),array_values($patternsEscaped),$text);
		}
		
		$text = preg_replace('#[\s \t]+#is',' ',$text);
		
		if(!empty($patternsEscaped)) {
			$text = str_replace(array_values($patternsEscaped),array_keys($patternsEscaped),$text);
		}
		
		$patternsEscaped = 0;
		
		$text = trim($text);
		
		PepVN_Data::$cachePermanentObject->set_cache($keyCache1, $text);
		
		return $text;
	}
	
	
	public function cdn_get_cdn_link($input_link) 
	{
		global $wpOptimizeByxTraffic;
		
		$options = $this->get_options(array(
			'cache_status' => 1
		));
		
		$checkStatus1 = false;
		
		if(isset($options['cdn_enable']) && ($options['cdn_enable'])) {
			if(isset($options['cdn_domain']) && ($options['cdn_domain'])) {
				$options['cdn_domain'] = trim($options['cdn_domain']);
				if($options['cdn_domain']) {
					$checkStatus1 = true;
				}
			}
		}
		
		
		if($checkStatus1) {
			
			$keyCache1 = 'cdn_get_cdn_link_cdn_exclude_url_data';
		
			if(!isset($this->baseCacheData[$keyCache1])) {
					
				$cdn_exclude_url = array();
				
				if(isset($options['cdn_exclude_url']) && ($options['cdn_exclude_url'])) {
					$valueTemp = trim($options['cdn_exclude_url']);
					if($valueTemp) {
						$valueTemp = PepVN_Data::explode(',',$valueTemp);
						$valueTemp = PepVN_Data::cleanArray($valueTemp);
						if(!PepVN_Data::isEmptyArray($valueTemp)) {
							$cdn_exclude_url = $valueTemp;
							
						}
					}
				}
				
				if(!PepVN_Data::isEmptyArray($cdn_exclude_url)) {
					$cdn_exclude_url = array_unique($cdn_exclude_url);
					foreach($cdn_exclude_url as $key1 => $value1) {
						$cdn_exclude_url[$key1] = PepVN_Data::preg_quote($value1);
					}
				} else {
					$cdn_exclude_url = false;
				}
				
				$this->baseCacheData[$keyCache1] = $cdn_exclude_url;
			}
			
			$cdn_exclude_url = $this->baseCacheData[$keyCache1];
			
			
			$currentProtocol = 'http://';
			if(PepVN_Data::is_ssl()) {
				$currentProtocol = 'https://';
			}
			
			$input_link1 = PepVN_Data::removeProtocolUrl($input_link);
			if(preg_match('#^'.PepVN_Data::preg_quote($this->fullDomainName).'.+$#i',$input_link1,$matched3)) {
				
				$checkStatus2 = true; 
	
				if($cdn_exclude_url) {
					if(preg_match('#('.implode('|',$cdn_exclude_url).')#i',$input_link1,$matched4)) {
						$checkStatus2 = false;
					}
				}
				
				if($checkStatus2) {
					return $currentProtocol.preg_replace('#^'.PepVN_Data::preg_quote($this->fullDomainName).'#i',$options['cdn_domain'],$input_link1,1);
				}
				
			}
			
		}
		
		return $input_link;
	}
	
	
	
	/*
	*	input_type (string) : html | css | js
	*/
	public function cdn_process_text(&$text, $input_type) 
	{
		
		$options = $this->get_options(array(
			'cache_status' => 1
		));
		
		$checkStatus1 = true; 
				
		if($checkStatus1) {
			if ( PepVN_Data::wp_is_feed() ) {
				$checkStatus1 = false;
			}
		}
		
		if($checkStatus1) {
			if ( PepVN_Data::wp_is_admin() ) {
				$checkStatus1 = false;
			}
		}
		
		if($checkStatus1) {
			$checkStatus1 = false;
			
			if(isset($options['cdn_enable']) && ($options['cdn_enable'])) {
				if(isset($options['cdn_domain']) && ($options['cdn_domain'])) {
					$options['cdn_domain'] = trim($options['cdn_domain']);
					if($options['cdn_domain']) {
						if($this->fullDomainName) {
							$checkStatus1 = true;
						}
					}
				}
			}
		}
		
		if(!$checkStatus1) {
			return $text;
		}
		
		$keyCacheProcessMain = array(
			__METHOD__
			,$text
			,'process_main'
		);
		
		$arrayOptionsFactorsAffectingKeyCache = array(
			'cdn_enable'
			,'cdn_domain'
			,'cdn_exclude_url'
		);
		
		foreach($arrayOptionsFactorsAffectingKeyCache as $value1) {
			if(isset($options[$value1])) {
				$keyCacheProcessMain[$value1] = $options[$value1];
			} else {
				$keyCacheProcessMain[$value1] = 0;
			}
		}
		
		$arrayOptionsFactorsAffectingKeyCache = 0;
		
		$keyCacheProcessMain = PepVN_Data::fKey($keyCacheProcessMain);
		
		$valueTemp = PepVN_Data::$cachePermanentObject->get_cache($keyCacheProcessMain); 
		
		if(null !== $valueTemp) {
			$text = $valueTemp; 
			$valueTemp = 0;
			return $text;
		}
		
		global $wpOptimizeByxTraffic;
		
		$allTargetElements = array();
		$arrayDataTextNeedReplace = array();
		
		preg_match_all('#(\'|\"|\(|\))?(https?:)?//'.PepVN_Data::preg_quote($this->fullDomainName).'[^\'\"\(\)]+\.('.$this->cdn_patternFilesTypeAllow.')\??[^\'\"\(\)]*?(\'|\"|\(|\))?#is',$text,$matched1);
		
		if(isset($matched1[0]) && $matched1[0] && (!PepVN_Data::isEmptyArray($matched1[0]))) {
			$allTargetElements = array_merge($allTargetElements, $matched1[0]);
		}
		
		$allTargetElements = array_unique($allTargetElements);
		
		if(count($allTargetElements)>0) {
			
			foreach($allTargetElements as $key1 => $value1) {
				
				$checkStatus2 = false;
				
				if($value1) {
					
					$matched2 = 0;
					
					preg_match('#(\'|\"|\(|\))?((https?:)?//[^\'|\"|\(|\)]+)(\'|\"|\(|\))?#i',$value1,$matched2);
					
					if(isset($matched2[2]) && $matched2[2]) {
						
						$matched2[2] = trim($matched2[2]);
						if($matched2[2]) {
							$valueTemp1 = $matched2[2];
							$valueTemp2 = $this->cdn_get_cdn_link($valueTemp1);
							$valueTemp1 = PepVN_Data::removeProtocolUrl($valueTemp1);
							$valueTemp2 = PepVN_Data::removeProtocolUrl($valueTemp2);
							if($valueTemp1 !== $valueTemp2) {
								
								$valueTemp1 = '//'.$valueTemp1;
								$valueTemp2 = '//'.$valueTemp2;
								$arrayDataTextNeedReplace[$valueTemp1] = $valueTemp2;
								
							}
							
						}
					}
					
				}
				
			}
		}
		
		if(count($arrayDataTextNeedReplace)>0) {
			$text = str_replace(array_keys($arrayDataTextNeedReplace),array_values($arrayDataTextNeedReplace),$text);
		}
		
		$arrayDataTextNeedReplace = 0; 
		
		$text = trim($text); 
		
		PepVN_Data::$cachePermanentObject->set_cache($keyCacheProcessMain,$text);
		
		return $text;
		
	}
	
	
	
	
	public function optimize_cache_get_filenamecache_current_request()
	{
		global $wpOptimizeByxTraffic_AdvancedCache;
		
		return $wpOptimizeByxTraffic_AdvancedCache->optimize_cache_get_filenamecache_current_request();
		
	}
	
	
	public function optimize_cache_get_hash_current_request()
	{
		$resultData = array();
		
		$queried_object = get_queried_object();
		
		$resultData['fullDomainName'] = array(
			$this->fullDomainName => PepVN_Data::mcrc32($this->fullDomainName)
		);
		
		$resultData['urlFullRequest'] = array(
			$this->urlFullRequest => PepVN_Data::mcrc32($this->urlFullRequest)
		);
		
		
		
		$author = get_the_author();
		$authorObjId = 'author-'.$author;
		
		$resultData['authorObjId'] = array(
			$authorObjId => PepVN_Data::mcrc32($authorObjId)
		);
		
		$taxObjId = 'tax-';
		
		if($queried_object) {
			$term_id = $queried_object->term_id;
			if(PepVN_Data::wp_is_category()) {
				$taxObjId = 'cat-'.get_cat_ID();
			} else if(PepVN_Data::wp_is_tag()) {
				$taxObjId = 'tag-'.$term_id;
			} else if(PepVN_Data::wp_is_tax()) {
				$taxObjId = 'tax-'.$term_id;
			}
		}
		
		$resultData['taxObjId'] = array(
			$taxObjId => PepVN_Data::mcrc32($taxObjId)
		);
		
	}
	
	public function optimize_cache_flush_http_headers($input_parameters)
	{
		global $wpOptimizeByxTraffic_AdvancedCache;
		$wpOptimizeByxTraffic_AdvancedCache->optimize_cache_flush_http_headers($input_parameters);
	}
	
	public function optimize_cache_isCacheable($options)
	{
		
		$isCacheStatus = false;
		
		if(isset($options['optimize_cache_enable']) && $options['optimize_cache_enable']) {
			$isCacheStatus = true;
		}
		
		if($isCacheStatus) {
			global $wpOptimizeByxTraffic_AdvancedCache;
			if(!$wpOptimizeByxTraffic_AdvancedCache->optimize_cache_isCacheable($options)) {
				$isCacheStatus = false;
			}
		}
		
		if($isCacheStatus) {
			global $wpOptimizeByxTraffic;
			$rs_get_woocommerce_urls = $wpOptimizeByxTraffic->base_get_woocommerce_urls();
			if(
				isset($rs_get_woocommerce_urls['urls_paths'])
				&& ($rs_get_woocommerce_urls['urls_paths'])
				&& is_array($rs_get_woocommerce_urls['urls_paths'])
			) {
			
				$valueTemp = $rs_get_woocommerce_urls['urls_paths'];
				$valueTemp = PepVN_Data::cleanPregPatternsArray($valueTemp);
				if($valueTemp) {
					if(count($valueTemp)>0) {
						if(preg_match('#('.implode('|',$valueTemp).')#i',$this->urlRequestNoParameters)) {
							$isCacheStatus = false; 
						}
					}
				}
			}
		}
		
		
		return $isCacheStatus; 
	}
	
	
	public function optimize_cache_check_and_flush_http_browser_cache()
	{
		$options = $this->get_options(array( 
			'cache_status' => 1
		));
		
		$isCacheStatus = $this->optimize_cache_isCacheable($options);
		
		if($isCacheStatus) {
			$isBrowserCacheStatus = false;
			if(isset($options['optimize_cache_browser_cache_enable']) && $options['optimize_cache_browser_cache_enable']) {
				$isBrowserCacheStatus = true;
			}
			
			if($isBrowserCacheStatus) {
					
				$rsOne = $this->optimize_cache_get_info_current_request();
				
				$filenamecache = $rsOne['filenamecache'];
				$rsGetFilemtime = $rsOne['filemtime'];
				$etag = $rsOne['etag'];
				$contentType = $rsOne['content_type'];
				
				$parametersTemp = array();
				$parametersTemp['content_type'] = $contentType; 
				$parametersTemp['etag'] = $etag;
				$parametersTemp['last_modified_time'] = $rsGetFilemtime;
				$parametersTemp['cache_timeout'] = 0;
				if($isBrowserCacheStatus) {
					$parametersTemp['cache_timeout'] = $options['optimize_cache_cachetimeout'];
				}
				
				$this->optimize_cache_flush_http_headers($parametersTemp);
				$parametersTemp = 0;
				
			}
			
		}
	}
	
	public function optimize_cache_get_info_current_request($input_mode = 1)
	{
		global $wpOptimizeByxTraffic_AdvancedCache;
		return $wpOptimizeByxTraffic_AdvancedCache->optimize_cache_get_info_current_request($input_mode);
	}
	
	public function optimize_cache_check_and_get_page_cache()
	{
		global $wpOptimizeByxTraffic_AdvancedCache;
		$wpOptimizeByxTraffic_AdvancedCache->optimize_cache_check_and_get_page_cache();
	}
	
	public function optimize_cache_check_and_create_page_cache($input_parameters = false)
	{
		
		$input_parameters['content'] = trim($input_parameters['content']);
		if($input_parameters['content']) {
			
			$options = $this->get_options(array(
				'cache_status' => 1
			));
			
			$isCacheStatus = $this->optimize_cache_isCacheable($options);
			
			if($isCacheStatus) {
				
				$filenamecache = $this->optimize_cache_get_filenamecache_current_request();
				
				PepVN_Data::$cacheObject->set_cache($filenamecache, $input_parameters['content']);
				
				
				global $wpOptimizeByxTraffic_AdvancedCache;
				
				$wpOptimizeByxTraffic_AdvancedCache->optimize_cache_check_and_create_static_page_cache_for_server_software(array(
					'content' => $input_parameters['content']
					,'force_write_status' => 1
				));
				
			}
		}
		
		
	}
	
	
	public function optimize_cache_prebuild_urls_cache()
	{
		
		sleep( 1 );
		
		$resultData = array();
		
		global $wpOptimizeByxTraffic;
		
		if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
		} else {
			return $resultData;
		}
		
		$options = $this->get_options(array(
			'cache_status' => 1
		));
		
		$checkStatus1 = false;
		
		if(isset($options['optimize_cache_enable']) && $options['optimize_cache_enable']) {
			$checkStatus1 = true;
		}
		
		if($checkStatus1) {
			if(isset($options['optimize_cache_prebuild_cache_enable']) && $options['optimize_cache_prebuild_cache_enable']) {
			} else {
				$checkStatus1 = false;
			}
		}
		
		if(!$checkStatus1) {
			return $resultData;
		}
		
		if(!isset($options['optimize_cache_prebuild_cache_number_pages_each_process'])) {
			$options['optimize_cache_prebuild_cache_number_pages_each_process'] = 1;
		}
		$options['optimize_cache_prebuild_cache_number_pages_each_process'] = (int)$options['optimize_cache_prebuild_cache_number_pages_each_process'];
		if($options['optimize_cache_prebuild_cache_number_pages_each_process'] < 1) {
			$options['optimize_cache_prebuild_cache_number_pages_each_process'] = 1;
		}
		
		if(!isset($options['optimize_cache_cachetimeout'])) {
			$options['optimize_cache_cachetimeout'] = 3600;
		}
		
		$timeoutRequest = 60;//seconds
		
		$maxTimePrebuild = 180;//seconds
		
		$staticVarData = PepVN_Data::staticVar_GetData(WPOPTIMIZEBYXTRAFFIC_KEY_STATIC_VAR_BASE_CRONJOBS, false);
		
		$groupUrlsStatistics = array();
		
		$groupUrlsNeedPrebuild = array();
		
		$groupScreenWidth = array();
		
		if(isset($staticVarData['statistics']['group_urls']) && is_array($staticVarData['statistics']['group_urls'])) {
			$groupUrlsStatistics = $staticVarData['statistics']['group_urls'];
		}
		
		if(isset($staticVarData['statistics']['group_screen_width']) && is_array($staticVarData['statistics']['group_screen_width'])) {
			$groupScreenWidth = $staticVarData['statistics']['group_screen_width'];
		}
		
		$maxNumberUrlsPrebuild = 999999;
		
		if(
			isset($staticVarData['total_hits_microtime_process']) && $staticVarData['total_hits_microtime_process']
			&& isset($staticVarData['total_microtime_process']) && $staticVarData['total_microtime_process']
		) {
			$microtimeProcessPerRequest = $staticVarData['total_microtime_process'] / $staticVarData['total_hits_microtime_process'];
			$microtimeProcessPerRequest = (float)$microtimeProcessPerRequest;
			
			$maxNumberUrlsPrebuild1 = ceil($maxTimePrebuild / $microtimeProcessPerRequest);
			if($maxNumberUrlsPrebuild > $maxNumberUrlsPrebuild1) {
				$maxNumberUrlsPrebuild = $maxNumberUrlsPrebuild1;
			}
			
		}
		
		$maxNumberUrlsPrebuild = (int)$maxNumberUrlsPrebuild;
	
		if($maxNumberUrlsPrebuild > $options['optimize_cache_prebuild_cache_number_pages_each_process']) {
			$maxNumberUrlsPrebuild = $options['optimize_cache_prebuild_cache_number_pages_each_process'];
		}
		
		if($maxNumberUrlsPrebuild < 1) {
			$maxNumberUrlsPrebuild = 1;
		}
		
		$maxNumberUrlsPrebuild = (int)$maxNumberUrlsPrebuild;
		
		if(count($groupUrlsStatistics)>0) {
			PepVN_Data::ref_sort_array_by_key($groupUrlsStatistics, 'r', 'desc');
			
			$iNumber1 = 0;
			
			foreach($groupUrlsStatistics as $key1 => $value1) {
				unset($groupUrlsStatistics[$key1]);
				if($key1) {
					
					$checkStatus2 = true;
					
					if(isset($staticVarData['group_urls_prebuild_cache'][$key1]) && $staticVarData['group_urls_prebuild_cache'][$key1]) {
						$checkStatus2 = false;
						if(($staticVarData['group_urls_prebuild_cache'][$key1] + $options['optimize_cache_cachetimeout']) < PepVN_Data::$defaultParams['requestTime']) {
							$checkStatus2 = true;
						}
					}
					
					if($checkStatus2) {
						$groupUrlsNeedPrebuild[] = $key1;
						$iNumber1++;
						if($iNumber1 >= $maxNumberUrlsPrebuild) {
							break;
						}
					}
				}
				
			}
			$groupUrlsStatistics = 0;
		}
		
		if(!empty($groupUrlsNeedPrebuild)) {
			$groupUrlsNeedPrebuild = array_unique($groupUrlsNeedPrebuild);
			
			$optimize_images_auto_resize_images_enable = false;
			
			$wpOptimizeByxTraffic_options = $wpOptimizeByxTraffic->get_options(array(
				'cache_status' => 1
			));
			
			if(isset($wpOptimizeByxTraffic_options['optimize_images_auto_resize_images_enable']) && $wpOptimizeByxTraffic_options['optimize_images_auto_resize_images_enable']) {
				$optimize_images_auto_resize_images_enable = true;
				if(!empty($groupScreenWidth)) {
					arsort($groupScreenWidth);
					reset($groupScreenWidth);
					$iNumber1 = 0;
					foreach($groupScreenWidth as $key1 => $value1) {
						unset($groupScreenWidth[$key1]);
						$iNumber1++;
						if($iNumber1 < 10) {
							$key1 = preg_replace('#[^0-9]+#is','',$key1);
							$key1 = (int)$key1;
							$groupScreenWidth[$key1]  = $value1;
						}
					}
				}
			}
			
			foreach($groupUrlsNeedPrebuild as $key1 => $value1) {
				unset($groupUrlsNeedPrebuild[$key1]);
				$wpOptimizeByxTraffic->quickGetUrlContent($value1, array(
					'request_timeout' => $timeoutRequest
					,'redirection' => 1
				));
				$staticVarData['group_urls_prebuild_cache'][$value1] = PepVN_Data::$defaultParams['requestTime'];
				PepVN_Data::staticVar_SetData(WPOPTIMIZEBYXTRAFFIC_KEY_STATIC_VAR_BASE_CRONJOBS, $staticVarData, 'm');
				$resultData['optimize_cache_prebuild_urls'][$value1] = 1;
				sleep( 1 );
				if($optimize_images_auto_resize_images_enable) {
					if(!empty($groupScreenWidth)) {
						reset($groupScreenWidth);
						foreach($groupScreenWidth as $key2 => $value2) {
							$wpOptimizeByxTraffic->quickGetUrlContent($value1, array(
								'request_timeout' => $timeoutRequest
								,'redirection' => 1
								,'headers' => array(
									'Cookie:xtrdvscwd='.$key2
								)
								,'cookies' => array(
									'xtrdvscwd' => $key2
								)
							));
							sleep(1);
						}
					}
				}
				
			}
		}
		
		PepVN_Data::staticVar_SetData(WPOPTIMIZEBYXTRAFFIC_KEY_STATIC_VAR_BASE_CRONJOBS, $staticVarData, 'm'); 
		
		return $resultData;
	}
	

	
	
	public function is_subdirectory_install()
	{
		if(strlen(site_url()) > strlen(home_url())){
			return true;
		}
		return false;
	}
	
	public function page_options()
	{
		global $wpOptimizeByxTraffic;
		
		$options = $this->handle_options();
		
		$action_url = $_SERVER['REQUEST_URI'];
		
		//Optimize Cache Options
		$optimize_cache_enable = $options['optimize_cache_enable'] == 'on' ? 'checked':'';
		
		$optimize_cache_browser_cache_enable = $options['optimize_cache_browser_cache_enable'] == 'on' ? 'checked':'';
		$optimize_cache_front_page_cache_enable = $options['optimize_cache_front_page_cache_enable'] == 'on' ? 'checked':'';
		
		$optimize_cache_database_cache_enable = $options['optimize_cache_database_cache_enable'] == 'on' ? 'checked':'';
		$optimize_cache_database_cache_methods = $options['optimize_cache_database_cache_methods'] ? (array)$options['optimize_cache_database_cache_methods'] : array();
		
		$optimize_cache_feed_page_cache_enable = $options['optimize_cache_feed_page_cache_enable'] == 'on' ? 'checked':'';
		$optimize_cache_ssl_request_cache_enable = $options['optimize_cache_ssl_request_cache_enable'] == 'on' ? 'checked':'';
		$optimize_cache_mobile_device_cache_enable = $options['optimize_cache_mobile_device_cache_enable'] == 'on' ? 'checked':'';
		$optimize_cache_url_get_query_cache_enable = $options['optimize_cache_url_get_query_cache_enable'] == 'on' ? 'checked':'';
		$optimize_cache_logged_users_cache_enable = $options['optimize_cache_logged_users_cache_enable'] == 'on' ? 'checked':'';
		
		$optimize_cache_prebuild_cache_enable = $options['optimize_cache_prebuild_cache_enable'] == 'on' ? 'checked':'';
		$optimize_cache_prebuild_cache_number_pages_each_process = abs((int)$options['optimize_cache_prebuild_cache_number_pages_each_process']);
		
		$optimize_cache_cachetimeout = abs((int)$options['optimize_cache_cachetimeout']);
		
		$optimize_cache_exclude_url = trim((string)$options['optimize_cache_exclude_url']);
		$optimize_cache_exclude_cookie = trim((string)$options['optimize_cache_exclude_cookie']);
		
		//Optimize Javascript Options
		$optimize_javascript_enable = $options['optimize_javascript_enable'] == 'on' ? 'checked':'';
		
		$optimize_javascript_combine_javascript_enable = $options['optimize_javascript_combine_javascript_enable'] == 'on' ? 'checked':'';
		$optimize_javascript_minify_javascript_enable = $options['optimize_javascript_minify_javascript_enable'] == 'on' ? 'checked':'';
		$optimize_javascript_asynchronous_javascript_loading_enable = $options['optimize_javascript_asynchronous_javascript_loading_enable'] == 'on' ? 'checked':'';
		$optimize_javascript_exclude_external_javascript_enable = $options['optimize_javascript_exclude_external_javascript_enable'] == 'on' ? 'checked':'';
		$optimize_javascript_exclude_inline_javascript_enable = $options['optimize_javascript_exclude_inline_javascript_enable'] == 'on' ? 'checked':'';
		$optimize_javascript_exclude_url = $options['optimize_javascript_exclude_url'];
		
		//Optimize CSS Options
		$optimize_css_enable = $options['optimize_css_enable'] == 'on' ? 'checked':'';
		
		$optimize_css_combine_css_enable = $options['optimize_css_combine_css_enable'] == 'on' ? 'checked':'';
		$optimize_css_minify_css_enable = $options['optimize_css_minify_css_enable'] == 'on' ? 'checked':'';
		$optimize_css_asynchronous_css_loading_enable = $options['optimize_css_asynchronous_css_loading_enable'] == 'on' ? 'checked':'';
		$optimize_css_exclude_external_css_enable = $options['optimize_css_exclude_external_css_enable'] == 'on' ? 'checked':'';
		$optimize_css_exclude_inline_css_enable = $options['optimize_css_exclude_inline_css_enable'] == 'on' ? 'checked':'';
		$optimize_css_exclude_url = $options['optimize_css_exclude_url'];
		
		$optimize_cache_dir_path_cache = $options['optimize_cache_dir_path_cache'];
		
		//Optimize HTML Options
		$optimize_html_enable = $options['optimize_html_enable'] == 'on' ? 'checked':'';
		
		$optimize_html_minify_html_enable = $options['optimize_html_minify_html_enable'] == 'on' ? 'checked':'';
		
		//CDN Options
		$cdn_enable = $options['cdn_enable'] == 'on' ? 'checked':''; 
		$cdn_domain = $options['cdn_domain'];
		$cdn_exclude_url = $options['cdn_exclude_url'];
		
		$isHostSupportApcCacheStatus = $wpOptimizeByxTraffic->base_is_has_apc_cache();
		
		$isHostSupportMemcacheStatus = $wpOptimizeByxTraffic->base_is_has_memcache();
		
		$nonce = wp_create_nonce( $this->PLUGIN_SLUG);
		
		$classSystemReady = '';
		$rsTemp = $this->check_system_ready();
		if(!PepVN_Data::isEmptyArray($rsTemp['notice']['error'])) {
			echo implode(' ',$rsTemp['notice']['error']);
		}
		if(in_array(30,$rsTemp['notice']['error_no'])) {
			$classSystemReady = 'wpoptimizebyxtraffic_disabled';
		}
		
		$wpOptimizeByxTraffic->display_admin_notices_session();
		
		echo '

<div class="wrap wpoptimizebyxtraffic_admin ',$classSystemReady,'" style="">
	
	<h2>WP Optimize By xTraffic (Optimize Speed)</h2>
				
	<div id="poststuff" style="margin-top:10px;">
		
		<div id="mainblock" style="">

			<div class="dbx-content">
			
				<form name="WPOptimizeByxTraffic" action="',$action_url,'" method="post">
					
					<input type="hidden" id="_wpnonce" name="_wpnonce" value="',$nonce,'" />
					
					
					<input type="hidden" name="optimize_speed_submitted" value="1" /> 
					
					
					<h3>',__('Overview "Optimize Speed"',$this->PLUGIN_SLUG),'</h3>
					<div class="wpoptimizebyxtraffic_green_block">
						<p>',__('Although we have tried and optimal features "Optimize Javascript" & "Optimize CSS" operate effectively on many websites, they make your website load faster and have higher scores on the measure tools.',$this->PLUGIN_SLUG),'</p>
						<p>',__('But there are some exceptions make website\'s layout is broken or not running properly like before. If your website is in the unfortunate case, you just simply turn off only 2 features "Optimize Javascript" & "Optimize CSS" and experience other features, because they operate independently of each other.',$this->PLUGIN_SLUG),'</p>
					</div>
					
					<div class="xtraffic_tabs_nav">
						<a href="#xtraffic_tabs_content1" class="active">Optimize Cache</a>
						<a href="#xtraffic_tabs_content2" class="">Optimize Javascript</a> 
						<a href="#xtraffic_tabs_content3" class="">Optimize CSS</a>
						<a href="#xtraffic_tabs_content4" class="">Optimize HTML</a>
						<a href="#xtraffic_tabs_content5" class="">CDN</a>
						<a href="#xtraffic_tabs_content6" class="optimize_cache_memcache_container wpoptimizebyxtraffic_show_hide_container" style="display:none;">Memcache</a>
					</div>
					
					<div id="xtraffic_tabs_content1" class="xtraffic_tabs_contents">

						<h3>Optimize Cache</h3>
						
						<ul>
							
							<li>
								<h4 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_cache_enable" class="wpoptimizebyxtraffic_show_hide_trigger" data-target="#optimize_cache_container"  ',$optimize_cache_enable,' /> &nbsp; ',__('Enable Optimize Cache',$this->PLUGIN_SLUG),' ( ',__('Recommended',$this->PLUGIN_SLUG),' )</h4>
							</li>
							
						</ul>
						
						<div style="margin-top: 0;" id="optimize_cache_container" class="wpoptimizebyxtraffic_show_hide_container">
							
							<ul>
								
								
								<li>
									
									<h6><input type="checkbox" name="optimize_cache_front_page_cache_enable" class="" ',$optimize_cache_front_page_cache_enable,' /> &nbsp; ',__('Enable Cache Front Page',$this->PLUGIN_SLUG),' ( ',__('Recommended',$this->PLUGIN_SLUG),' )</h6>
									<p style="margin-bottom: 3%;" class="description">',__('Front Page include : Home page, category page, tag page, author page, date page, archives page,...',$this->PLUGIN_SLUG),'</p>
									
								</li> 
								
								<li>
									
									<h6 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_cache_feed_page_cache_enable" class="" ',$optimize_cache_feed_page_cache_enable,' /> &nbsp; ',__('Enable Cache Feed (RSS/Atom) Page',$this->PLUGIN_SLUG),' ( ',__('Recommended',$this->PLUGIN_SLUG),' )</h6>
									<p class="description"></p>
									
								</li> 
								
								<li>
									
									<h6 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_cache_browser_cache_enable" class="" ',$optimize_cache_browser_cache_enable,' /> &nbsp; ',__('Enable Browser Cache',$this->PLUGIN_SLUG),' ( ',__('Recommended',$this->PLUGIN_SLUG),' )</h6>
									<p class="description"></p>
									
								</li> 
								
								<li style="margin-bottom: 3%;"> 
									<h6 style="margin-bottom: 0%;"><input type="checkbox" name="optimize_cache_database_cache_enable" data-target="#optimize_cache_database_cache_enable_container"  class="wpoptimizebyxtraffic_show_hide_trigger"  ',$optimize_cache_database_cache_enable,' /> &nbsp; ',__('Enable Database Cache',$this->PLUGIN_SLUG),' ( ',__('Recommended',$this->PLUGIN_SLUG),' )</h6>
									<p class="description">',__('This feature helps to increase website speed and reduce the query to the database',$this->PLUGIN_SLUG),'</p>
									<div style="margin-top: 0;" id="optimize_cache_database_cache_enable_container" class="wpoptimizebyxtraffic_show_hide_container">
										<ul>',( ($isHostSupportApcCacheStatus) ? '
											<li>
												<h6 style="margin-bottom: 0;"><input type="checkbox" name="optimize_cache_database_cache_methods[apc]" value="apc" class="" '.(isset($optimize_cache_database_cache_methods['apc']) ? ' checked="checked" ' : ' ').' />'.__('Use APC',$this->PLUGIN_SLUG).'&nbsp;</h6> 
											</li>' : '' ), ' '
											
											,( ($isHostSupportMemcacheStatus) ? '
											<li>
												<h6 style="margin-bottom: 0;"><input type="checkbox" name="optimize_cache_database_cache_methods[memcache]" value="memcache" class="wpoptimizebyxtraffic_show_hide_trigger" data-target=".optimize_cache_memcache_container" '.(isset($optimize_cache_database_cache_methods['memcache']) ? ' checked="checked" ' : ' ').' />'.__('Use Memcache',$this->PLUGIN_SLUG).'&nbsp;</h6> 
											</li>' : '' ), '
											
											<li>
												<h6 style="margin-bottom: 0;"><input type="checkbox" disabled class="" checked="checked" />',__('Use File (Default)',$this->PLUGIN_SLUG),'&nbsp;</h6> 
											</li>
										</ul>
										
									</div>
									
								</li> 
								
								
								<li>
									
									<h6 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_cache_ssl_request_cache_enable" class="" ',$optimize_cache_ssl_request_cache_enable,' /> &nbsp; ',__('Enable Cache SSL (https) Requests',$this->PLUGIN_SLUG),'</h6>
									<p class="description"></p>
									
								</li> 
								
								<li style="margin-bottom: 3%;">
									<h6 style="margin-bottom: 0%;"><input type="checkbox" name="optimize_cache_mobile_device_cache_enable" class="" ',$optimize_cache_mobile_device_cache_enable,' /> &nbsp; ',__('Enable Cache For Mobile Device',$this->PLUGIN_SLUG),'</h6>
									<p class="description" style="color:red;">',__('Warning',$this->PLUGIN_SLUG),': ',__('Don\'t turn on this option if you use one of these plugins: WP Touch, WP Mobile Detector, wiziApp, and WordPress Mobile Pack.',$this->PLUGIN_SLUG),'</p>
									<p class="description" style="">',__('If you use wordpress theme responsive, you should enable this feature.',$this->PLUGIN_SLUG),'</p>
								</li> 
								
								<li>
									<h6 style=""><input type="checkbox" name="optimize_cache_url_get_query_cache_enable" class="" ',$optimize_cache_url_get_query_cache_enable,' /> &nbsp; ',__('Enable Cache URIs with GET query string variables',$this->PLUGIN_SLUG),'</h6>
									<p class="description" style="margin-bottom: 3%;">',__('Ex : "/?s=query..." at the end of a url',$this->PLUGIN_SLUG),'</p>
								</li> 
								
								<li>
									<h6 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_cache_logged_users_cache_enable" class="" ',$optimize_cache_logged_users_cache_enable,' /> &nbsp; ',__('Enable Cache For Logged Users',$this->PLUGIN_SLUG),'</h6>
									<p class="description"></p>
								</li> 
								
								<li style="margin-bottom: 3%;">
									
									<h6 style="margin-bottom: 0;"><input type="checkbox" name="optimize_cache_prebuild_cache_enable" data-target="#optimize_cache_prebuild_cache_container"  class="wpoptimizebyxtraffic_show_hide_trigger" ',$optimize_cache_prebuild_cache_enable,' /> &nbsp; ',__('Enable Prebuild Cache',$this->PLUGIN_SLUG),' ( ',__('Recommended',$this->PLUGIN_SLUG),' )</h6>
									
									<p class="description">',__('Prebuild cache help your site load faster by creating cache of pages is the most visited.',$this->PLUGIN_SLUG),'</p>
									<div style="margin-top: 0;" id="optimize_cache_prebuild_cache_container" class="wpoptimizebyxtraffic_show_hide_container">
										<ul>	
											<li>
												<h6 style="margin-bottom: 0;">',__('Maximum number of pages is prebuilt each process',$this->PLUGIN_SLUG),'&nbsp;:&nbsp;<input type="text" name="optimize_cache_prebuild_cache_number_pages_each_process" class="" value="',$optimize_cache_prebuild_cache_number_pages_each_process,'" style="width: 100px;" />&nbsp;',__('pages',$this->PLUGIN_SLUG),'</h6> 
												<p class="description">',__('This number depends on the performance of the server, if your server is fast then you should set this number higher and vice versa.',$this->PLUGIN_SLUG),'</p>
											</li>
										</ul>
										
									</div>
								</li> 
								
								<li style="margin-bottom: 3%;">
									<h6> ',__('Cache Timeout',$this->PLUGIN_SLUG),'&nbsp;:&nbsp;<input type="text" name="optimize_cache_cachetimeout" class="" value="',$optimize_cache_cachetimeout,'" style="width: 100px;" />&nbsp;seconds </h6> 
									<p class="description">',__('How long should cached pages remain fresh? You should set this value from 21600 seconds (6 hours) to 86400 seconds (24 hours). Minimum value is 300 seconds (5 minutes).',$this->PLUGIN_SLUG),'</p>
								</li>
								
								<li style="margin-bottom: 3%;">
									<h6> ',__('Exclude url',$this->PLUGIN_SLUG),' (',__('Contained in url, separate them by comma',$this->PLUGIN_SLUG),')</h6> 
									<input type="text" name="optimize_cache_exclude_url" class="" value="',$optimize_cache_exclude_url,'" style="width: 50%;" /> &nbsp;  
									<p class="description">',__('Plugin will ignore these urls',$this->PLUGIN_SLUG),'</p>
								</li>
								
								<li style="margin-bottom: 3%;">
									<h6> ',__('Exclude cookie',$this->PLUGIN_SLUG),' (',__('Cookie name or combine Cookie name with cookie value, separate them by comma',$this->PLUGIN_SLUG),')</h6> 
									<input type="text" name="optimize_cache_exclude_cookie" class="" value="'.$optimize_cache_exclude_cookie.'" style="width: 50%;" /> &nbsp;  
									<p class="description">',__('Plugin will ignore these request cookie',$this->PLUGIN_SLUG),'</p>
								</li>
								
								<!--
								<li style="margin-bottom: 3%;">
									<h6> ',__('Directory\'s path contain cache data',$this->PLUGIN_SLUG),'</h6> 
									<input type="text" name="optimize_cache_dir_path_cache" class="" value="',$optimize_cache_dir_path_cache,'" style="width: 50%;" /> &nbsp;  
									<p class="description">',__('Leave blank if you want the plugin to use the default directory',$this->PLUGIN_SLUG),'</p>
								</li>
								-->
								
							</ul>						
							<br /> 
							
						</div>
						
					</div><!-- //xtraffic_tabs_contents -->  
					
					
					<div id="xtraffic_tabs_content2" class="xtraffic_tabs_contents">

						<h3>Optimize Javascript</h3>
						
						<ul>
							
							<li style="margin-bottom: 3%;">
								<h4 style="margin-bottom: 1%;"><input type="checkbox" name="optimize_javascript_enable" class="wpoptimizebyxtraffic_show_hide_trigger" data-target="#optimize_javascript_container"  ',$optimize_javascript_enable,' /> &nbsp; ',__('Enable Optimize Javascript',$this->PLUGIN_SLUG),'</h4>
								<p class="description" style="color:red;">',__('Warning',$this->PLUGIN_SLUG),': ',__('This option will help your site load faster. However, in some cases, web layout will be error. If an error occurs, you should disable this option.',$this->PLUGIN_SLUG),'</p>
							</li>
							
						</ul>
						
						<div style="margin-top: 0;" id="optimize_javascript_container" class="wpoptimizebyxtraffic_show_hide_container">
							
							<ul>
								<li>
									
									<h6 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_javascript_combine_javascript_enable" class="wpoptimizebyxtraffic_show_hide_trigger" data-target="#optimize_javascript_container2"  ',$optimize_javascript_combine_javascript_enable,' /> &nbsp; ',__('Enable Combine Javascript',$this->PLUGIN_SLUG),'</h6>
									<p class="description"></p>
									
								</li> 
								
								<li>
											
									<h6 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_javascript_minify_javascript_enable" class="" ',$optimize_javascript_minify_javascript_enable,' /> &nbsp; ',__('Enable Minify Javascript',$this->PLUGIN_SLUG),'</h6>
									<p class="description"></p>
									
								</li>
						
								<li>
								
									<h6 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_javascript_asynchronous_javascript_loading_enable" class="" ',$optimize_javascript_asynchronous_javascript_loading_enable,' /> &nbsp; ',__('Enable Asynchronous Javascript Loading',$this->PLUGIN_SLUG),'</h6>
									<p class="description"></p>
									
								</li>
								
								<li style="margin-bottom: 3%;">
									
									<h6 style="margin-bottom: 0;"><input type="checkbox" name="optimize_javascript_exclude_external_javascript_enable" class="" ',$optimize_javascript_exclude_external_javascript_enable,' /> &nbsp; ',__('Exclude External Javascript File',$this->PLUGIN_SLUG),'</h6>
									<p class="description">',__('Plugin will ignore all external javascript files',$this->PLUGIN_SLUG),' ( ',__('Which not scripts in your self-hosted',$this->PLUGIN_SLUG),' ). <i>',__('You should not enable this feature unless an error occurs',$this->PLUGIN_SLUG),'</i></p>
									
								</li>
								
								<li style="margin-bottom: 3%;">
									
									<h6 style="margin-bottom: 0%;"><input type="checkbox" name="optimize_javascript_exclude_inline_javascript_enable" class="" ',$optimize_javascript_exclude_inline_javascript_enable,' /> &nbsp; ',__('Exclude Inline Javascript Code',$this->PLUGIN_SLUG),'</h6>
									<p class="description">',__('Plugin will ignore all javascript code in your html',$this->PLUGIN_SLUG),'. <i>',__('You should enable this feature unless an error occurs',$this->PLUGIN_SLUG),'</i></p>
									
								</li>
								
								<li>
									<h6> ',__('Exclude',$this->PLUGIN_SLUG),' (',__('Contained in url, separate them by comma',$this->PLUGIN_SLUG),')</h6> 
									<input type="text" name="optimize_javascript_exclude_url" class="" value="',$optimize_javascript_exclude_url,'" style="width: 50%;" /> &nbsp;  
									<p class="description">',__('Plugin will ignore these javascript files urls',$this->PLUGIN_SLUG),'</p>
								</li>
								
								
							</ul>						
							<br />  
							
						</div>
						
					</div><!-- //xtraffic_tabs_contents -->  
					
					<div id="xtraffic_tabs_content3" class="xtraffic_tabs_contents">

						<h3>',__('Optimize CSS (Style)',$this->PLUGIN_SLUG),'</h3>
						
						<ul>
							
							<li style="margin-bottom: 3%;">
								<h4 style="margin-bottom: 1%;"><input type="checkbox" name="optimize_css_enable" class="wpoptimizebyxtraffic_show_hide_trigger" data-target="#optimize_css_container"  ',$optimize_css_enable,' /> &nbsp; ',__('Enable Optimize CSS',$this->PLUGIN_SLUG),'</h4>
								<p class="description" style="color:red;">',__('Warning',$this->PLUGIN_SLUG),': ',__('This option will help your site load faster. However, in some cases, web layout will be error. If an error occurs, you should disable this option.',$this->PLUGIN_SLUG),'</p>
							</li>
							
						</ul>
						
						<div style="margin-top: 0;" id="optimize_css_container" class="wpoptimizebyxtraffic_show_hide_container">
							<ul>
								<li>
									
									<h5 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_css_combine_css_enable" class="" ',$optimize_css_combine_css_enable,' /> &nbsp; ',__('Enable Combine CSS',$this->PLUGIN_SLUG),'</h5>
									<p class="description"></p>
									
								</li> 
								
						
								<li>
									
									<h5 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_css_minify_css_enable" class="" ',$optimize_css_minify_css_enable,' /> &nbsp; ',__('Enable Minify CSS',$this->PLUGIN_SLUG),'</h5>
									<p class="description"></p>
									
								</li>
								
								
						
								<li>
								
									<h5 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_css_asynchronous_css_loading_enable" class="" ',$optimize_css_asynchronous_css_loading_enable,' /> &nbsp; ',__('Enable Asynchronous CSS Loading',$this->PLUGIN_SLUG),'</h5>
									<p class="description"></p>
									
								</li>
								
								<li style="margin-bottom: 3%;">
									
									<h5 style="margin-bottom: 0;"><input type="checkbox" name="optimize_css_exclude_external_css_enable" class="" ',$optimize_css_exclude_external_css_enable,' /> &nbsp; ',__('Exclude External CSS Files',$this->PLUGIN_SLUG),'</h5>
									<p class="description">',__('Plugin will ignore all external CSS files ( Which not CSS files in your self-hosted )',$this->PLUGIN_SLUG),'. <i>',__('You should not enable this feature unless an error occurs',$this->PLUGIN_SLUG),'</i></p>
									
								</li>
								
								<li style="margin-bottom: 3%;">
								
									<h5 style="margin-bottom: 0%;"><input type="checkbox" name="optimize_css_exclude_inline_css_enable" class="" ',$optimize_css_exclude_inline_css_enable,' /> &nbsp; ',__('Exclude Inline CSS Code',$this->PLUGIN_SLUG),'</h5>
									<p class="description">',__('Plugin will ignore all style (wrap by &#x3C;style&#x3E;&#x3C;/style&#x3E;) in your html',$this->PLUGIN_SLUG),'. <i>',__('You should not enable this feature unless an error occurs',$this->PLUGIN_SLUG),'</i></p>
									
								</li> 
								
								<li>
									<h5> ',__('Exclude',$this->PLUGIN_SLUG),' (',__('Contained in url, separate them by comma',$this->PLUGIN_SLUG),')</h5> 
									<input type="text" name="optimize_css_exclude_url" class="" ',$optimize_css_exclude_url,' style="width: 50%;" /> &nbsp;  
									<p class="description">',__('Plugin will ignore these css files urls',$this->PLUGIN_SLUG),'</p>
								</li>
								
								
							</ul>						
							<br />
							
						</div>
						
					</div><!-- //xtraffic_tabs_contents -->
					
					
					
					
					<div id="xtraffic_tabs_content4" class="xtraffic_tabs_contents">

						<h3>',__('Optimize HTML',$this->PLUGIN_SLUG),'</h3>
						
						<ul>
							
							<li>
								<h4 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_html_enable" class="wpoptimizebyxtraffic_show_hide_trigger" data-target="#optimize_html_container"  ',$optimize_html_enable,' /> &nbsp; ',__('Enable Optimize HTML',$this->PLUGIN_SLUG),' (',__('Recommended',$this->PLUGIN_SLUG),')</h4>
							</li>
							
						</ul>
						
						<div style="margin-top: 0;" id="optimize_html_container" class="wpoptimizebyxtraffic_show_hide_container">
							<ul>
								
								<li>
									
									<h5 style="margin-bottom: 3%;"><input type="checkbox" name="optimize_html_minify_html_enable" class="" ',$optimize_html_minify_html_enable,' /> &nbsp; ',__('Enable Minify HTML',$this->PLUGIN_SLUG),' ( ',__('Recommended',$this->PLUGIN_SLUG),' )</h5>
									<p class="description"></p>
									
								</li>
								
								
								
							</ul>						
							<br />
							
						</div>
						
					</div><!-- //xtraffic_tabs_contents -->
					
					
					
					
					<div id="xtraffic_tabs_content5" class="xtraffic_tabs_contents">

						<h3>CDN (Content Delivery Network)</h3>
						
						<ul>
							
							<li>
								<h4 style="margin-bottom: 3%;"><input type="checkbox" name="cdn_enable" class="wpoptimizebyxtraffic_show_hide_trigger" data-target="#cdn_container"  ',$cdn_enable,' /> &nbsp; ',__('Enable CDN',$this->PLUGIN_SLUG),'</h4>
							</li>
							
						</ul>
						
						<div style="margin-top: 0;" id="cdn_container" class="wpoptimizebyxtraffic_show_hide_container">
							<ul>
								
								<li style="margin-bottom: 3%;">
									<h6> ',__('CNAME (CDN)',$this->PLUGIN_SLUG),'</h6> 
									<input type="text" name="cdn_domain" class="" value="',$cdn_domain,'" style="width: 50%;" /> &nbsp;  
									<p class="description"></p>
								</li>
								
								<li style="margin-bottom: 3%;">
									<h6> ',__('Exclude',$this->PLUGIN_SLUG),' (',__('Contained in url, separate them by comma',$this->PLUGIN_SLUG),')</h6> 
									<input type="text" name="cdn_exclude_url" class="" value="',$cdn_exclude_url,'" style="width: 50%;" /> &nbsp;  
									<p class="description">',__('Plugin will ignore these urls',$this->PLUGIN_SLUG),'</p>
								</li>
								
								
								
							</ul>						
							<br />
							
						</div>
						
					</div><!-- //xtraffic_tabs_contents -->
					
					
					
					
					<div id="xtraffic_tabs_content6" class="xtraffic_tabs_contents">

						<h3>Config Memcache</h3>
						<ul>
								
							<li style="margin-bottom: 3%;">
								<h6> ',__('Memcache Server',$this->PLUGIN_SLUG),'</h6> 
								<textarea name="memcache_servers" rows="6" style="width:100%;" placeholder="Ex : 127.0.0.1:11211">'.implode(PHP_EOL,$options['memcache_servers']).'</textarea>
								<p class="description"></p>
							</li>
						</ul>	
					</div><!-- //xtraffic_tabs_contents -->
					
					
					
					
						
					<div class="submit"><input type="submit" name="Submit" value="',__('Update Options',$this->PLUGIN_SLUG),'" class="button-primary" /></div>
					
				</form>
			</div>

			<br/><br/>
			
		</div>
		
		',$wpOptimizeByxTraffic->base_get_sponsorsblock('vertical_01'),'
		

	</div>
	
</div>

';
		
		
	}
	
	

	
	
	
	// Handle our options
	public function get_options($input_parameters = false) 
	{
		
		if(!$input_parameters) {
			$input_parameters = array();
		}
		
		if(!isset($input_parameters['create_default_options_status'])) {
			$input_parameters['create_default_options_status'] = true;
		}
		
		if(!isset($input_parameters['save_options_when_different_status'])) {
			$input_parameters['save_options_when_different_status'] = true;
		}
		
		if(!isset($input_parameters['options_id'])) {
			$input_parameters['options_id'] = $this->db_option_key;
		}
		
		$input_parameters['options_id'] = (string)$input_parameters['options_id'];
		
		
		if(!isset($input_parameters['cache_status'])) {
			$input_parameters['cache_status'] = false;
		}
		
		$keyCache1 = PepVN_Data::fKey(array(
			__METHOD__
			, $input_parameters
		)); 
		
		if(isset($input_parameters['cache_status']) && $input_parameters['cache_status']) {
			
			if(isset($this->baseCacheData[$keyCache1]) && $this->baseCacheData[$keyCache1]) {
				return $this->baseCacheData[$keyCache1];
			}
			
		}
		
		$options = array();
		
		if($input_parameters['create_default_options_status']) {
			
			$options = array(
				
				/*
				* Optimize Speed Setting
				*/
				
				//optimize_cache
				'optimize_cache_enable' => '',//on
				
				'optimize_cache_browser_cache_enable' => 'on',//on
				'optimize_cache_front_page_cache_enable' => 'on',//on
				'optimize_cache_feed_page_cache_enable' => 'on',//on
				'optimize_cache_ssl_request_cache_enable' => 'on',//on
				'optimize_cache_mobile_device_cache_enable' => '',//on
				'optimize_cache_url_get_query_cache_enable' => 'on',//on
				'optimize_cache_logged_users_cache_enable' => '',//on
				
				'optimize_cache_database_cache_enable' => 'on',//on
				'optimize_cache_database_cache_methods' => array(),//array('apc' => 'apc','memcache' => 'memcache', 'file' => 'file')
				
				'optimize_cache_prebuild_cache_enable' => '',//on
				'optimize_cache_prebuild_cache_number_pages_each_process' => 1,//int 
				'optimize_cache_cachetimeout' => 86400,//int 
				'optimize_cache_exclude_url' => '/cart/,/checkout/',//text
				'optimize_cache_exclude_cookie' => 'xtraffic_no_cache=on,xtraffic_dont_cache',	//string
				
				'optimize_cache_dir_path_cache' => '',//text
				'memcache_servers' => array('127.0.0.1:11211'),//array
				
				
				//optimize_javascript
				'optimize_javascript_enable' => '',//on
				'optimize_javascript_combine_javascript_enable' => '',//on
				'optimize_javascript_minify_javascript_enable' => 'on',//on
				'optimize_javascript_asynchronous_javascript_loading_enable' => '',//on
				'optimize_javascript_exclude_external_javascript_enable' => 'on',//on
				'optimize_javascript_exclude_inline_javascript_enable' => 'on',//on 
				'optimize_javascript_exclude_url' => 'alexa.com,',//text
				
				
				//optimize_css
				'optimize_css_enable' => '',//on
				'optimize_css_combine_css_enable' => '',//on
				'optimize_css_minify_css_enable' => 'on',//on
				'optimize_css_asynchronous_css_loading_enable' => '',//on
				'optimize_css_exclude_external_css_enable' => 'on',//on
				'optimize_css_exclude_inline_css_enable' => 'on',//on 
				'optimize_css_exclude_url' => '',//text
				
				
				//optimize_html
				'optimize_html_enable' => '',//on
				'optimize_html_minify_html_enable' => 'on',//on
				
				
				//cdn
				'cdn_enable' => '',//on
				'cdn_domain' => '',//string
				'cdn_exclude_url' => 'captcha,/wp-admin/,.php,',//string
				
			);
		}
		
		

		$saved = get_option($input_parameters['options_id']);
		
		if (!empty($saved)) {
			
			foreach ($saved as $key => $option) {
				$options[$key] = $option;
			}
		}
		
		if($input_parameters['save_options_when_different_status']) {
			if ($saved != $options)	{
				$this->update_options($input_parameters['options_id'], $options);
			}
		}
		
		$this->baseCacheData[$keyCache1] = $options;

		return $options;

	}
	
	public function handle_options()
	{
		
		
		$options = $this->get_options();
		
		if ( isset($_POST['optimize_speed_submitted']) ) {
			
			check_admin_referer($this->PLUGIN_SLUG);
			
			//optimize_speed
			
			
			$arrayFields1 = array(
				
				//optimize_javascript
				'optimize_javascript_enable'
				,'optimize_javascript_combine_javascript_enable'
				,'optimize_javascript_minify_javascript_enable'
				,'optimize_javascript_asynchronous_javascript_loading_enable'
				,'optimize_javascript_exclude_external_javascript_enable'
				,'optimize_javascript_exclude_inline_javascript_enable'
				,'optimize_javascript_exclude_url'
				
				//optimize_css
				,'optimize_css_enable'
				,'optimize_css_combine_css_enable'
				,'optimize_css_minify_css_enable'
				,'optimize_css_asynchronous_css_loading_enable'
				,'optimize_css_exclude_external_css_enable'
				,'optimize_css_exclude_inline_css_enable'
				,'optimize_css_exclude_url'
				
				
				//optimize_html
				,'optimize_html_enable'
				,'optimize_html_minify_html_enable'
				
				
				//cdn
				,'cdn_enable'
				,'cdn_domain'
				,'cdn_exclude_url'
				
				//optimize_cache
				,'optimize_cache_enable'
				,'optimize_cache_browser_cache_enable'
				,'optimize_cache_front_page_cache_enable'
				,'optimize_cache_feed_page_cache_enable'
				,'optimize_cache_ssl_request_cache_enable'
				,'optimize_cache_mobile_device_cache_enable'
				,'optimize_cache_url_get_query_cache_enable'
				,'optimize_cache_logged_users_cache_enable'
				,'optimize_cache_database_cache_enable'
				,'optimize_cache_database_cache_methods'
				,'optimize_cache_prebuild_cache_enable'
				,'optimize_cache_prebuild_cache_number_pages_each_process'
				,'optimize_cache_cachetimeout'
				,'optimize_cache_exclude_url'
				,'optimize_cache_exclude_cookie'
				,'optimize_cache_dir_path_cache'
				,'memcache_servers'
				
			);
			
			foreach($arrayFields1 as $key1 => $value1) {
				if(isset($_POST[$value1])) {
					$options[$value1] = $_POST[$value1];
				} else {
					$options[$value1] = '';
				}
			}
			
			$options['optimize_javascript_exclude_url'] = preg_replace('#[\'\"]+#','',$options['optimize_javascript_exclude_url']);
			$options['optimize_css_exclude_url'] = preg_replace('#[\'\"]+#','',$options['optimize_css_exclude_url']);
			
			$options['optimize_cache_cachetimeout'] = abs((int)$options['optimize_cache_cachetimeout']);
			if($options['optimize_cache_cachetimeout'] < 300) {
				$options['optimize_cache_cachetimeout'] = 300;
			}
			
			$options['optimize_cache_database_cache_methods'] = array_unique((array)$options['optimize_cache_database_cache_methods']);
			
			$options['memcache_servers'] = (array)$options['memcache_servers'];
			$options['memcache_servers'] = PepVN_Data::cleanArray($options['memcache_servers']);
			$options['memcache_servers'] = array_unique($options['memcache_servers']);
			
			
			$options['optimize_cache_prebuild_cache_number_pages_each_process'] = abs((int)$options['optimize_cache_prebuild_cache_number_pages_each_process']);
			if($options['optimize_cache_prebuild_cache_number_pages_each_process'] < 1) {
				$options['optimize_cache_prebuild_cache_number_pages_each_process'] = 1;
			}
			 
			$keyField1 = 'cdn_domain';
			$options[$keyField1] = trim($options[$keyField1]);
			if($options[$keyField1]) {
				$valueField1 = $options[$keyField1];
				$valueField1 = 'http://'.PepVN_Data::removeProtocolUrl($valueField1);
				$valueField1 = PepVN_Data::parseUrl($valueField1); 
				if($valueField1) {
					if(isset($valueField1['host']) && $valueField1['host']) {
						$valueField1['host'] = trim($valueField1['host']);
						if($valueField1['host']) {
							$options[$keyField1] = strtolower($valueField1['host']);
						}
					}
				}
			}
			
			$keyField1 = 'cdn_exclude_url';
			$options[$keyField1] = preg_replace('#[\'\"]+#','',$options[$keyField1]);
			$options[$keyField1] = trim($options[$keyField1]);
			
			$this->update_options($this->db_option_key, $options);
			
			echo '<div class="updated fade"><p><b>'.$this->PLUGIN_NAME.'</b> : '.__('Options saved',$this->PLUGIN_SLUG).'</p></div>';
			global $wpOptimizeByxTraffic;
			$wpOptimizeByxTraffic->base_clear_data(',all,');
			
			PepVN_Data::$cacheObject->set_cache(WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_OPTIONS_CACHE_KEY,$options);
			
		}
		
		return $options;
		
	}
	
	
	public function update_options($options_id, $options, $input_configs = false)
	{
		if(!$input_configs) {
			$input_configs = array();
		}
		
		$base_is_set_server_config_for_optimize_speed = false;
		
		$optionsSaved = get_option($options_id);
		
		if (!empty($optionsSaved)) {
			
			global $wpOptimizeByxTraffic;
			
			foreach ($options as $key1 => $value1) {
				if(isset($wpOptimizeByxTraffic) && $wpOptimizeByxTraffic) {
					if($wpOptimizeByxTraffic->base_is_set_server_config_for_optimize_speed($key1)) {
						if(isset($optionsSaved[$key1])) {
							if($optionsSaved[$key1] != $value1) {
								$base_is_set_server_config_for_optimize_speed = true;
							}
						}
					}
				}
				
				$optionsSaved[$key1] = $value1;
			}
			
			if(isset($input_configs['merge_status']) && $input_configs['merge_status']) {
				$options = $optionsSaved;
			}
			
			$optionsSaved = 0;
			
		}
		
		$resultData = update_option($options_id, $options);
		
		if(!PepVN_Data::$cacheObject->get_cache(WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_OPTIONS_CACHE_KEY)) {
			PepVN_Data::$cacheObject->set_cache(WPOPTIMIZESPEEDBYXTRAFFIC_PLUGIN_OPTIONS_CACHE_KEY, $resultData);
		}
		
		if($base_is_set_server_config_for_optimize_speed) {
			$this->set_server_config_for_optimize_speed();
		}
		
		return $resultData;
	}
	

}//class WPOptimizeByxTraffic  

endif; //if ( !class_exists('WPOptimizeSpeedByxTraffic_OptimizeSpeed') )



