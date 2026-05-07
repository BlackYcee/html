<?php
require_once __DIR__ . '/../config/config.php';

class S3Service {
    private $bucket;
    private $folder;
    private $region;
    private $client = null;
    private $configured = false;

    public function __construct() {
        $this->bucket = Config::get('aws_s3_bucket');
        $this->folder = Config::get('aws_s3_folder');
        $this->region = Config::get('aws_region', 'us-east-1');

        if (!empty($this->bucket)) {
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
            $this->client = new Aws\S3\S3Client([
                'region' => $this->region,
                'version' => 'latest',
            ]);
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
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $filePath,
            ]);

            return $this->getPublicUrl($fileName);
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
        return $this->getPublicUrl($fileName);
    }

    public function getPublicUrl($fileName) {
        $key = $this->folder . '/' . $fileName;
        return $this->client->getObjectUrl($this->bucket, $key);
    }

    public function getPresignedUrl($fileName, $expires = '+20 minutes') {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $key = $this->folder . '/' . $fileName;
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            $request = $this->client->createPresignedRequest($cmd, $expires);
            return (string)$request->getUri();
        } catch (Exception $e) {
            return null;
        }
    }
}