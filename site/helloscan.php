<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 ff=unix fenc=utf8: */

/**
*
* HelloScan for Joomla
*
* @package HelloScan_Joomla
* @author Yves Tannier [grafactory.net]
* @copyright 2011 Yves Tannier
* @link http://helloscan.mobi
* @version 0.1
* @license MIT Licence
*/

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
 
// Require the base controller
/*if(version_compare(JVERSION,'1.7.0','ge')) {
    require_once( JPATH_COMPONENT.DS.'controller.1.7.php' );
} elseif(version_compare(JVERSION,'1.6.0','ge')) {
    require_once( JPATH_COMPONENT.DS.'controller.1.7.php' );
}*/
require_once( JPATH_COMPONENT.DS.'controller.php' );
 
// Require specific controller if requested
if($controller = JRequest::getWord('controller')) {
    $path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
    if (file_exists($path)) {
        require_once $path;
    } else {
        $controller = '';
    }
}
 
// Create the controller
$classname    = 'HelloscanController'.$controller;
$controller   = new $classname( );
 
// Perform the Request task
$controller->execute(JRequest::getWord('task'));
