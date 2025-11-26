<?php

include_once(dirname(__FILE__).'/EndpointLoggerInterface.php');
use PrestaShop\PrestaShop\Core\Database\Db;

class DefaultEndpointLogger implements EndpointLoggerInterface
{
    protected $db;

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    public function logRequest($method, $url, array $data)
    {
        $this->db->insert('alsernet_forms_requests', [
            'endpoint_type' => pSQL($this->getType()),
            'method'        => pSQL($method),
            'url'           => pSQL($url),
            'payload'       => pSQL(json_encode($data)),
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        return $this->db->Insert_ID();
    }

    public function updateRequestLog($id, $status, array $responseData = [])
    {
        $this->db->update('alsernet_forms_requests', [
            'status'    => pSQL($status),
            'response'  => pSQL(json_encode($responseData)),
            'synced_at' => ($status === 'success') ? date('Y-m-d H:i:s') : null,
        ], 'id_alsernetforms_request = ' . (int) $id);
    }

    protected function getType()
    {
        return 'default';
    }
}