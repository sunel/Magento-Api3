<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

$mageFilename = getcwd() . '/app/Mage.php';

if (!file_exists($mageFilename)) {
    echo 'Mage file not found';
    exit;
}

define('ROOT_DIR', __DIR__);
$bootstrap = getcwd() . '/lib/api3/bootstrap.php';

if (!file_exists($bootstrap)) {
    echo 'Bootstrap file not found';
    exit;
}
require $bootstrap;
require $mageFilename;

Mage::register('custom_entry_point', true);
Mage::$headersSentThrowsException = false;
Mage::init('admin');
Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_GLOBAL, Mage_Core_Model_App_Area::PART_EVENTS);
Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_ADMINHTML, Mage_Core_Model_App_Area::PART_EVENTS);
Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);

$app = new Sunel\Api\Application();

$app->register('Sunel\Api\Provider\Magento');
$app->register('Sunel\Api\Provider\Core');

$app->run();
