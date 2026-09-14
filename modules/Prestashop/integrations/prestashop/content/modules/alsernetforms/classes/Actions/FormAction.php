<?php

include_once dirname(__FILE__).'/BaseAction.php';
include_once dirname(__FILE__).'/../loggers/DefaultEndpointLogger.php';

class FormAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct();

        $this->endpoint = 'api/forms';
        $this->actionType = 'forms';
    }

    protected function createLogger()
    {
        return new DefaultEndpointLogger('form');
    }

    public function submitForm(array $formData, $formType = 'contact', array $context = [])
    {
        $payload = [
            'action' => 'submit',
            'type' => $formType,
            'data' => $formData,
        ];

        return $this->execute($payload, $context);
    }

    protected function mapResponse(array $response)
    {
        $responseData = $response['response'] ?? [];

        return [
            'status' => $response['status'],  // 'success' | 'pending' | 'error'
            'request_id' => $response['request_id'] ?? null,
            'data' => [
                'message' => $responseData['data']['message'] ?? 'Formulario enviado',
                'submission_id' => $responseData['data']['submission_id'] ?? null,
                'validation_errors' => $responseData['data']['validation_errors'] ?? [],
            ],
            'error' => $response['message'] ?? null,
        ];
    }
}
