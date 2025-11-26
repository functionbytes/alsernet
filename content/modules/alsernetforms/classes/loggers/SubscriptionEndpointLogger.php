<?php


include_once(dirname(__FILE__).'/DefaultEndpointLogger.php');

class SubscriptionEndpointLogger extends DefaultEndpointLogger
{
    protected function getType()
    {
        return 'subscription';
    }
}