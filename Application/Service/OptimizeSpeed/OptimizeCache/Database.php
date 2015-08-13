<?php 
namespace WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeCache;

use WpPepVN\Utils
	,WpPepVN\System
	,WpPepVN\Hash
	,WPOptimizeByxTraffic\Application\Service\PepVN_Data
	,WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed
	,WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeCache\WPDBWrapper
	,WpPepVN\DependencyInjectionInterface
;

class Database 
{
	private static $_tempData = array();
	
    public function __construct(DependencyInjectionInterface $di) 
    {
		$this->di = $di;
	}
	
	public function init($options)
	{
		$wpExtend = $this->di->getShared('wpExtend');
		
		$checkStatus = false;
		
		if(!isset(self::$_tempData['init_frontend_status'])) {
			self::$_tempData['init_frontend_status'] = true;
			if(isset($options['optimize_cache_enable']) && ('on' === $options['optimize_cache_enable'])) {
				if(isset($options['optimize_cache_database_cache_enable']) && ('on' === $options['optimize_cache_database_cache_enable'])) {
					$checkStatus = true;
				}
			}
		}
		
		if(true === $checkStatus) {
			global $wpdb;
			if(isset($wpdb) && $wpdb) {
				if(isset($wpdb->wppepvn_wpdb_init_status)) {
					$checkStatus = false;
				}
			} else {
				$checkStatus = false;
			}
		}
		
		if(true === $checkStatus) {
				
			//cacheObject : store cache for short time (less than 1 day)
			$pepvnDirCachePathTemp = WP_OPTIMIZE_BY_XTRAFFIC_PLUGIN_STORAGES_CACHE_DIR.'db'.DIRECTORY_SEPARATOR;
			
			if(!is_dir($pepvnDirCachePathTemp)) {
				System::mkdir($pepvnDirCachePathTemp);
			}

			if(is_dir($pepvnDirCachePathTemp) && is_readable($pepvnDirCachePathTemp) && is_writable($pepvnDirCachePathTemp)) {
				
				$pepvnCacheHashKeySaltTemp = PepVN_Data::$defaultParams['fullDomainName'] . $pepvnDirCachePathTemp;
				
				if(defined('WP_PEPVN_SITE_SALT')) {
					$pepvnCacheHashKeySaltTemp .= '_'.WP_PEPVN_SITE_SALT;
				}
				
				$optimize_cache_cachetimeout = 86400;
				if(isset($options['optimize_cache_cachetimeout']) && $options['optimize_cache_cachetimeout']) {
					$options['optimize_cache_cachetimeout'] = (int)$options['optimize_cache_cachetimeout'];
					if($options['optimize_cache_cachetimeout']>0) {
						$optimize_cache_cachetimeout = $options['optimize_cache_cachetimeout'];
					}
				}
				
				$cacheMethods = array();
				
				if(
					isset($options['optimize_cache_database_cache_methods']['apc'])
					&& ($options['optimize_cache_database_cache_methods']['apc'])
					&& ('apc' === $options['optimize_cache_database_cache_methods']['apc'])
				) {
					if(System::hasAPC()) {
						$cacheTimeoutTemp = ceil($optimize_cache_cachetimeout / 3);
						$cacheTimeoutTemp = (int)$cacheTimeoutTemp;
						$cacheMethods['apc'] = array(
							'cache_timeout' => $cacheTimeoutTemp
						);
					}
				}
				
				if(
					isset($options['optimize_cache_database_cache_methods']['memcache'])
					&& ($options['optimize_cache_database_cache_methods']['memcache'])
					&& ('memcache' === $options['optimize_cache_database_cache_methods']['memcache'])
				) {
					if(
						isset($options['memcache_servers'])
						&& ($options['memcache_servers'])
					) {
						$options['memcache_servers'] = PepVN_Data::cleanArray($options['memcache_servers']);
						if(!empty($options['memcache_servers'])) {
							if(System::hasMemcached() || System::hasMemcache()) {
								$memcacheServers = array();
								
								foreach($options['memcache_servers'] as $server) {
									if($server) {
										$server = explode(':',$server,2);
										$serverTemp = array(
											'host' => $server[0]
										);
										if(isset($server[1])) {
											$serverTemp['port'] = $server[1];
										}
										$memcacheServers[] = $serverTemp;
									}
								}
								
								if(!empty($memcacheServers)) {
									$cacheTimeoutTemp = ceil($optimize_cache_cachetimeout / 2);
									$cacheTimeoutTemp = (int)$cacheTimeoutTemp;
									$cacheMethods['memcache'] = array(
										'cache_timeout' => $cacheTimeoutTemp
										,'object' => false
										,'servers' => $memcacheServers
									);
								}
							}
						}
					}
				}
				
				$cacheMethods['file'] = array(
					'cache_timeout' => $optimize_cache_cachetimeout
					, 'cache_dir' => $pepvnDirCachePathTemp 
				);
				
				PepVN_Data::$cacheDbObject = new \WPOptimizeByxTraffic\Application\Service\PepVN_Cache(array(
					'cache_timeout' => $optimize_cache_cachetimeout		//seconds
					,'hash_key_method' => 'crc32b'		//best is crc32b
					,'hash_key_salt' => Hash::crc32b($pepvnCacheHashKeySaltTemp)
					,'gzcompress_level' => 2	// should be greater than 0 (>0, 2 is best) to save RAM in case of using Memcache, APC, ...
					,'key_prefix' => 'dtdb_'
					,'cache_methods' => $cacheMethods
				));
				
				if(!$wpExtend->is_admin()) {
					$wpdb = new WPDBWrapper($this->di, $wpdb);
				}
				
				unset($cacheMethods);
				
			}
		}
		
	}
	
}
