<?php

interface EndpointLoggerInterface
{
    public function logRequest($method, $url, array $data);
    public function updateRequestLog($id, $status, array $responseData = []);
}