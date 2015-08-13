<?php 

use WPOptimizeSpeedByxTraffic\Application\Service\CacheRequestUri
	, WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed
	, WPOptimizeSpeedByxTraffic\Application\Service\OptimizeSpeed\OptimizeCDN
;

$tmp = array(
	'wpExtend'
	,'remote'
	,'cache'
	,'device'
	,'cacheManager'
	,'session'
);

$tmp = array_unique($tmp);
foreach($tmp as $value) {
	if($value) {
		$di->set($value, $wpOptimizeByxTraffic->di->getShared($value), true);
	}
}
unset($tmp);

$optimizeSpeed = new OptimizeSpeed($di);
$di->set('optimizeSpeed', $optimizeSpeed, true);

$optimizeCDN = new OptimizeCDN($di);
$di->set('optimizeCDN', $optimizeCDN, true);

$cacheRequestUri = new CacheRequestUri($di);
$di->set('cacheRequestUri', $cacheRequestUri, true);
