<?php
require_once __DIR__ . '/../config/config.php';

class S3Service {
    private $bucket;
    private $folder;
    private $region;
    private $accessKey;
    private $secretKey;
    private $sessionToken;
    private $endpoint;
    private $client = null;
    private $configured = false;

    public function __construct() {
        $this->bucket = Config::get('aws_s3_bucket');
        $this->folder = Config::get('aws_s3_folder');
        $this->region = Config::get('aws_region');
        $this->accessKey = Config::get('aws_access_key');
        $this->secretKey = Config::get('aws_secret_key');
        $this->sessionToken = Config::get('aws_session_token');
        $this->endpoint = Config::get('aws_endpoint');

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
            $options = [
                'region' => $this->region,
                'version' => 'latest',
            ];

            $credentials = [];
            if (!empty($this->accessKey)) {
                $credentials['key'] = $this->accessKey;
            }
            if (!empty($this->secretKey)) {
                $credentials['secret'] = $this->secretKey;
            }
            if (!empty($this->sessionToken)) {
                $credentials['token'] = $this->sessionToken;
            }

            if (!empty($credentials)) {
                $options['credentials'] = $credentials;
            }

            if (!empty($this->endpoint)) {
                $options['endpoint'] = $this->endpoint;
                $options['use_path_style_endpoint'] = true;
            }

            $this->client = new Aws\S3\S3Client($options);
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
            ]);

            return $this->getPresignedUrl($fileName);
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
        return $this->getPresignedUrl($fileName);
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