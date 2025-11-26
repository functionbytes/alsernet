<?php

include_once(dirname(__FILE__).'/DefaultEndpointLogger.php');
class FormEndpointLogger extends DefaultEndpointLogger
{
    protected function getType()
    {
        return 'form';
    }
}