<?php 
namespace WPOptimizeSpeedByxTraffic\Application\Module\Backend;

use WPOptimizeSpeedByxTraffic\Application\Module\Backend\Service\AdminMenu
	, WPOptimizeByxTraffic\Application\Service\PepVN_Data
	, WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed
;

class Module extends \WpPepVN\Mvc\Module
{
    const MODULE_DIR = __DIR__;
    
    public function __construct() 
    {
        parent::__construct();
    }
    
    public function init(\WpPepVN\DependencyInjectionInterface $di) 
    {
		global $wpOptimizeByxTraffic;
		
        parent::init($di);
        
		include_once(self::MODULE_DIR . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'service.php');
		
        $this->di = $di;
		
		$this->init_admin_menu();
		
		$optimizeSpeed = $this->di->getShared('optimizeSpeed');
		$optimizeSpeed->initBackend();
		
		$cacheRequestUri = $this->di->getShared('cacheRequestUri');
		
		$hook = $this->di->get('hook');
		$hook->add_action('clean_cache', array(&$cacheRequestUri,'remove_cache_for_web_server'));
		
	}
	
	
	public function init_admin_menu() 
    {
		$adminMenu = new AdminMenu($this->di);
		
		$hook = $this->di->get('hook');
		
		$configDefault = array(
			'view_configs' => array(
				'setBasePath' => self::MODULE_DIR . DIRECTORY_SEPARATOR
			)
			,'router_configs' => array(
				'setControllerDir' => self::MODULE_DIR . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR
				,'setNamespace' => '\\'. __NAMESPACE__ .'\\Controller'
			)
			
		);
		
		$hook->add_filter('register_admin_page', function($register_admin_page) use ($configDefault) {
			$register_admin_page['OptimizeSpeed'] = $configDefault;
			
			return $register_admin_page;
		});
		
		add_action('admin_menu', array($adminMenu, 'init_admin_menu'), WP_PEPVN_PRIORITY_LAST);
	}
	
}