<?php

namespace Modules\Mailing\Library\Storage;

use Modules\Mailing\Library\Storage\Contracts\Storable;
use Modules\Mailing\Library\Storage\Contracts\StorageService;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class S3 implements StorageService
{
    protected $apiKey;

    protected $apiSecret;

    protected $region;

    protected $bucket;

    public function __construct($apiKey, $apiSecret, $region, $bucket) // What interface?
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->region = $region;
        $this->bucket = $bucket;
    }

    public function store(Storable $object)
    {
        // Upload an object by streaming the contents of a file
        // $pathToFile should be absolute path to a file on disk
        $result = $this->getClient()->putObject([
            'Bucket' => $this->bucket,
            'Key' => $object->getArchivePath(),
            'SourceFile' => $object->toZip(),
            'Metadata' => [
                'Foo' => 'abc',
                'Baz' => '123',
            ],
        ]);
    }

    public function getClient()
    {
        $client = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => new Credentials($this->apiKey, $this->apiSecret),
        ]);

        return $client;
    }
}
