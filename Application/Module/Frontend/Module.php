<?php 
namespace WPOptimizeSpeedByxTraffic\Application\Module\Frontend;

use WPOptimizeSpeedByxTraffic\Application\Service\CacheRequestUri
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
		
        $this->di = $di;
		
		$cacheRequestUri = new CacheRequestUri($this->di);
		
		$this->di->set('cacheRequestUri',$cacheRequestUri,true);
		
		add_action( 'wp', array($this, 'add_action_wp'), WP_PEPVN_PRIORITY_LAST );
		
	}
	
	
	public function add_action_wp()
	{
		
		$optimizeSpeed = $this->di->getShared('optimizeSpeed');
		
		$optimizeSpeed->initFrontend();
		
	}
	
	
	
}