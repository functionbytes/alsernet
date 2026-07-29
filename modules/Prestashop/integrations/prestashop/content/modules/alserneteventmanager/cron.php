<?php

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/alserneteventmanager.php';

$module = Module::getInstanceByName('alserneteventmanager');
if ($module) {
    $module->processEventStatus();
}