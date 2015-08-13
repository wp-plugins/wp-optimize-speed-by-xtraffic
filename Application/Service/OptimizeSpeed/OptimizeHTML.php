<?php 
namespace WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed;

use WpPepVN\Utils
	,WpPepVN\DependencyInjectionInterface
	, WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed
;

class OptimizeHTML
{
	public $di = false;
	
	private $_tempData = array();
	
    public function __construct(DependencyInjectionInterface $di) 
    {
		$this->di = $di;
	}
    
	public function process($html)
	{
		
	}
}