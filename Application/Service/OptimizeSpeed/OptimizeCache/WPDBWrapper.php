<?php 
namespace WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeCache;

use WpPepVN\Utils
	,WpPepVN\DependencyInjectionInterface
	,WPOptimizeByxTraffic\Application\Service\PepVN_Data
	,WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed
;

class WPDBWrapper
{
	public $wppepvn_wpdb_init_status = true;
	
	private $_wppepvn_wpDbObj = false;
	
	private $_wppepvn_wpExtend = false;
	
	private static $_wppepvn_tempData = array();
	
    public function __construct(DependencyInjectionInterface $di, $wpdbObj) 
    {
		$this->_wppepvn_wpExtend = $di->getShared('wpExtend');
		
		$this->_wppepvn_wpDbObj = $wpdbObj;
		
	}
    
	private function _wppepvn_is_cachable($method,$args,$key_check)
	{
		$key_check = hash('crc32b',$key_check);
		
		if(!isset(self::$_wppepvn_tempData[$key_check])) {
			
			$isCachableStatus = true;
			
			if($isCachableStatus) {
				
				if(!isset(self::$_wppepvn_tempData['_is_ajax'])) {
					if ( defined('DOING_AJAX') && DOING_AJAX ) {
						self::$_wppepvn_tempData['_is_ajax'] = true;
					} else {
						self::$_wppepvn_tempData['_is_ajax'] = false;
					}
				}
				
				if(self::$_wppepvn_tempData['_is_ajax']) {
					$isCachableStatus = false;
				}
			}
			
			if($isCachableStatus) {
				if(
					$this->_wppepvn_wpExtend->is_admin()
					|| $this->_wppepvn_wpExtend->isLoginPage()
				) {	//not cache admin page
					$isCachableStatus = false;
				}
			}
			
			if($isCachableStatus) {
				
				if(!isset(self::$_wppepvn_tempData['_patternsMethodsNotCache1'])) {
					$tmp = array(
						'insert'
						,'replace'
						,'update'
						,'delete'
						,'get_results'
						,'query'
						,'get_row'
						,'get_col'
						,'get_var'
						
						
						//,'prepare'
						//,'get_row'
						//,'get_col'
						//,'get_var'
					);
					
					self::$_wppepvn_tempData['_patternsMethodsNotCache1'] = '#^('.implode('|',$tmp).').*?#s';
				}
				
				if(preg_match(self::$_wppepvn_tempData['_patternsMethodsNotCache1'],$method)) {
					$isCachableStatus = false;
				} else if(!preg_match('#^get_.+#s',$method)) {
					$isCachableStatus = false;
					
					if(!isset(self::$_wppepvn_tempData['_patternsMethodsNotCache2'])) {
						$tmp = array(
							'bail'
							,'check_connection'
							,'db_connect'
							,'flush'
							,'get_caller'
							,'hide_errors'
							,'init_charset'
							,'print_error'
							,'replace'
							,'select'
							,'set_blog_id'
							,'set_charset'
							,'set_prefix'
							,'set_sql_mode'
							,'show_errors'
							,'suppress_errors'
							,'timer_start'
							,'timer_stop'
						);
						
						self::$_wppepvn_tempData['_patternsMethodsNotCache2'] = '#^('.implode('|',$tmp).')#';
					}
					
					if(!preg_match(self::$_wppepvn_tempData['_patternsMethodsNotCache2'],$method)) {
						if(isset($args[0]) && $args[0] && is_string($args[0])) {
							if(preg_match('#^([\s \t\(])*?SELECT[\s \t]+#is',$args[0])) {
								if(!preg_match('#(FOUND_ROWS)#is',$args[0])) {
									$isCachableStatus = true; 
								}
							}
						}
					}
					
				} else {
					if(isset($args[0]) && $args[0]) {
						$args[0] = (string)$args[0];
						if(preg_match('#.*?(FOUND_ROWS).*?#is',$args[0])) {
							$isCachableStatus = false;
						}
					}
				}
				
			}
			
			self::$_wppepvn_tempData[$key_check] = $isCachableStatus;
		}
		
		return self::$_wppepvn_tempData[$key_check];
	}
	
	public function __isset( $name ) {
		return isset( $this->_wppepvn_wpDbObj->$name );
	}
	
	public function __set( $name, $value ) 
	{
		$this->_wppepvn_wpDbObj->$name = $value;
	}
	
	public function __unset( $name ) 
	{
		unset( $this->_wppepvn_wpDbObj->$name );
	}
	
	public function __get($varname)
    {
		return $this->_wppepvn_wpDbObj->$varname;
    }
	
	public static function __callStatic($method, $args)
    {
		return $this->_wppepvn_process_call_wpdb_method($method,$args); 
    }
	
	public function __call($method,$args)
    {
		return $this->_wppepvn_process_call_wpdb_method($method,$args);
    }
	
	private function _wppepvn_is_process_call_wpdb_method($method) 
	{
		$key_check = 'z'.hash('crc32b', '_wppepvn_is_process_call_wpdb_method_'.$method);
		
		if(!isset(self::$_wppepvn_tempData[$key_check])) {
			
			self::$_wppepvn_tempData[$key_check] = false;
			
			if(
				!method_exists($this,$method)
				&& method_exists($this->_wppepvn_wpDbObj,$method)
			) {
				self::$_wppepvn_tempData[$key_check] = true;
			}
		}
		
		return self::$_wppepvn_tempData[$key_check];
	}
	
	private function _wppepvn_process_call_wpdb_method($method,$args) 
	{
		
		$resultData = null;
		
		if(
			$this->_wppepvn_is_process_call_wpdb_method($method)
		) {
			
			if(0 === strpos($method,'escape_by_ref')) {
				return $this->_wppepvn_wpDbObj->escape_by_ref($args[0]);
			}
			
			$keyCache = Utils::hashKey(array(
				'WPDBWrapper'
				,'_wppepvn_process_call_wpdb_method'
				,$method
				,$args
			));
			
			$isCachableStatus = $this->_wppepvn_is_cachable($method,$args,$keyCache);
			
			if($isCachableStatus) {
				if(isset(self::$_wppepvn_tempData[$keyCache])) {
					return Utils::ungzVar(self::$_wppepvn_tempData[$keyCache]);
				} else {
					$resultData = PepVN_Data::$cacheDbObject->get_cache($keyCache);
					if(null !== $resultData) {
						self::$_wppepvn_tempData[$keyCache] = Utils::gzVar($resultData);
						return $resultData;
					}
				}
			}
			
			if(null === $resultData) {
				$resultData = call_user_func_array(array($this->_wppepvn_wpDbObj, $method), $args);
				
				if($isCachableStatus) {
					if(null !== $resultData) {
						PepVN_Data::$cacheDbObject->set_cache($keyCache,$resultData);
						self::$_wppepvn_tempData[$keyCache] = Utils::gzVar($resultData);
					}
					
				}
				
			}
			
		}
		
		return $resultData;
	}
	
	
	private function _wppepvn_getTempData( $key ) 
	{
		return Utils::ungzVar(self::$_wppepvn_tempData[$key]);
	}
	
	
	private function _wppepvn_setTempData($key,$data) 
	{
		self::$_wppepvn_tempData[$key] = Utils::gzVar($data);
	}
	
	
	private function _wppepvn_isHasTempData( $key ) 
	{
		return isset(self::$_wppepvn_tempData[$key]);
	}
	
	public function query($query) 
	{
		return $this->_wppepvn_query( $query );
	}
	
	private function _wppepvn_query( $query ) 
	{
		if ( ! $this->_wppepvn_wpDbObj->ready ) {
			return false;
		}
		
		/**
		 * Filter the database query.
		 *
		 * Some queries are made before the plugins have been loaded,
		 * and thus cannot be filtered with this method.
		 *
		 * @since 2.1.0
		 *
		 * @param string $query Database query.
		 */
		
		$this->_wppepvn_wpDbObj->flush();
		
		$this->_wppepvn_wpDbObj->last_query = $query;
		
		$keyCacheQuery = Utils::hashKey(array(
			__METHOD__
			, $query
		));
		
		if($this->_wppepvn_isHasTempData($keyCacheQuery)) {
			
			$tmp = $this->_wppepvn_getTempData($keyCacheQuery);
			
			$this->_wppepvn_wpDbObj->last_error = '';
			
			$this->_wppepvn_wpDbObj->last_query = $tmp['last_query'];
			$this->_wppepvn_wpDbObj->last_result = $tmp['last_result'];
			$this->_wppepvn_wpDbObj->col_info = $tmp['col_info'];
			$this->_wppepvn_wpDbObj->num_rows = $tmp['num_rows'];
			
			unset($tmp);
			
			$return_val = $this->_wppepvn_wpDbObj->num_rows;
			
		} else {
			
			$rsDbCached = PepVN_Data::$cacheDbObject->get_cache($keyCacheQuery);
			
			if(null !== $rsDbCached) {
				$this->_wppepvn_setTempData($keyCacheQuery,$rsDbCached);
				
				$this->_wppepvn_wpDbObj->last_error = '';
				
				$this->_wppepvn_wpDbObj->last_query = $rsDbCached['last_query'];
				$this->_wppepvn_wpDbObj->last_result = $rsDbCached['last_result'];
				$this->_wppepvn_wpDbObj->col_info = $rsDbCached['col_info'];
				$this->_wppepvn_wpDbObj->num_rows = $rsDbCached['num_rows'];
				
				unset($rsDbCached);
				
				$return_val = $this->_wppepvn_wpDbObj->num_rows;
				
				
			} else {
				
				$return_val = $this->_wppepvn_wpDbObj->query( $query );
				
				if ( $return_val === false ) { // error executing sql query
					return false;
				} else {
					if(preg_match('#^([\s \t\(])*?SELECT[\s \t]+#is',$query)) {
						if(!preg_match('#(FOUND_ROWS)#is',$query)) {
							
							$tmp = array(
								'last_query' => $this->_wppepvn_wpDbObj->last_query
								,'last_result' => $this->_wppepvn_wpDbObj->last_result
								,'col_info' => $this->_wppepvn_wpDbObj->col_info
								,'num_rows' => $this->_wppepvn_wpDbObj->num_rows
							);
							
							$this->_wppepvn_setTempData($keyCacheQuery,$tmp);
							
							PepVN_Data::$cacheDbObject->set_cache($keyCacheQuery,$tmp);
							
							unset($tmp);
							
						}
					}
				}
			}
		}
		
		return $return_val;
	}
	
	public function get_var( $query = null, $x = 0, $y = 0 ) 
	{
		$this->_wppepvn_wpDbObj->func_call = "\$db->get_var(\"$query\", $x, $y)";
		
		if ( $query ) {
			$this->query( $query );
		}
		// Extract var out of cached results based x,y vals
		if ( !empty( $this->_wppepvn_wpDbObj->last_result[$y] ) ) {
			$values = array_values( get_object_vars( $this->_wppepvn_wpDbObj->last_result[$y] ) );
		}
		// If there is a value return it else return null
		return ( isset( $values[$x] ) && $values[$x] !== '' ) ? $values[$x] : null;
	}
	
	public function get_row( $query = null, $output = OBJECT, $y = 0 ) 
	{
		$this->_wppepvn_wpDbObj->func_call = "\$db->get_row(\"$query\",$output,$y)";
		
		if ( $query ) {
			$this->query( $query );
		} else {
			return null;
		}
		if ( !isset( $this->_wppepvn_wpDbObj->last_result[$y] ) )
			return null;
		if ( $output == OBJECT ) {
			return $this->_wppepvn_wpDbObj->last_result[$y] ? $this->_wppepvn_wpDbObj->last_result[$y] : null;
		} elseif ( $output == ARRAY_A ) {
			return $this->_wppepvn_wpDbObj->last_result[$y] ? get_object_vars( $this->_wppepvn_wpDbObj->last_result[$y] ) : null;
		} elseif ( $output == ARRAY_N ) {
			return $this->_wppepvn_wpDbObj->last_result[$y] ? array_values( get_object_vars( $this->_wppepvn_wpDbObj->last_result[$y] ) ) : null;
		} elseif ( strtoupper( $output ) === OBJECT ) {
			// Back compat for OBJECT being previously case insensitive.
			return $this->_wppepvn_wpDbObj->last_result[$y] ? $this->_wppepvn_wpDbObj->last_result[$y] : null;
		} else {
			$this->_wppepvn_wpDbObj->print_error( " \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N" );
		}
	}
	
	public function get_col( $query = null , $x = 0 ) 
	{
		
		if ( $query ) {
			$this->query( $query );
		}
		
		$new_array = array();
		// Extract the column values
		for ( $i = 0, $j = count( $this->_wppepvn_wpDbObj->last_result ); $i < $j; $i++ ) {
			$new_array[$i] = $this->get_var( null, $x, $i );
		}
		return $new_array;
	}
	
	
	public function get_results( $query = null, $output = OBJECT ) 
	{
		$this->func_call = "\$db->get_results(\"$query\", $output)";
				
		if ( $query ) {
			$this->query( $query );
		} else {
			return null;
		}
		$new_array = array();
		if ( $output == OBJECT ) {
			// Return an integer-keyed array of row objects
			return $this->_wppepvn_wpDbObj->last_result;
		} elseif ( $output == OBJECT_K ) {
			// Return an array of row objects with keys from column 1
			// (Duplicates are discarded)
			foreach ( $this->_wppepvn_wpDbObj->last_result as $row ) {
				$var_by_ref = get_object_vars( $row );
				$key = array_shift( $var_by_ref );
				if ( ! isset( $new_array[ $key ] ) )
					$new_array[ $key ] = $row;
			}
			return $new_array;
		} elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
			// Return an integer-keyed array of...
			if ( $this->_wppepvn_wpDbObj->last_result ) {
				foreach( (array) $this->_wppepvn_wpDbObj->last_result as $row ) {
					if ( $output == ARRAY_N ) {
						// ...integer-keyed row arrays
						$new_array[] = array_values( get_object_vars( $row ) );
					} else {
						// ...column name-keyed row arrays
						$new_array[] = get_object_vars( $row );
					}
				}
			}
			return $new_array;
		} elseif ( strtoupper( $output ) === OBJECT ) {
			// Back compat for OBJECT being previously case insensitive.
			return $this->_wppepvn_wpDbObj->last_result;
		}
		return null;
	}
}
