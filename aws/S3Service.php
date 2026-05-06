<?php
require_once __DIR__ . '/../config/config.php';

class S3Service {
    private $bucket;
    private $folder;
    private $region;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $client = null;
    private $configured = false;

    public function __construct() {
        $this->bucket = Config::get('aws_s3_bucket');
        $this->folder = Config::get('aws_s3_folder');
        $this->region = Config::get('aws_region');
        $this->accessKey = Config::get('aws_access_key');
        $this->secretKey = Config::get('aws_secret_key');
        $this->endpoint = Config::get('aws_endpoint');

        if (!empty($this->accessKey) && !empty($this->secretKey) && !empty($this->bucket)) {
            $this->configured = true;
            $this->initClient();
        }
    }

    private function initClient() {
        if (!class_exists('Aws\S3\S3Client')) {
            $this->configured = false;
            return;
        }

        try {
            if (!empty($this->endpoint)) {
                $this->client = new Aws\S3\S3Client([
                    'region' => $this->region,
                    'version' => 'latest',
                    'credentials' => [
                        'key' => $this->accessKey,
                        'secret' => $this->secretKey,
                    ],
                    'endpoint' => $this->endpoint,
                    'use_path_style_endpoint' => true,
                ]);
            } else {
                $this->client = new Aws\S3\S3Client([
                    'region' => $this->region,
                    'version' => 'latest',
                    'credentials' => [
                        'key' => $this->accessKey,
                        'secret' => $this->secretKey,
                    ],
                ]);
            }
        } catch (Exception $e) {
            $this->configured = false;
        }
    }

    public function isConfigured() {
        return $this->configured && $this->client !== null;
    }

    public function upload($filePath, $fileName) {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $key = $this->folder . '/' . $fileName;
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $filePath,
                'ACL' => 'public-read',
            ]);

            return $result['ObjectURL'];
        } catch (Exception $e) {
            return null;
        }
    }

    public function delete($fileName) {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $key = $this->folder . '/' . $fileName;
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUrl($fileName) {
        if (!$this->isConfigured()) {
            return null;
        }

        return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$this->folder}/{$fileName}";
    }
}