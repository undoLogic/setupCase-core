<?php
// install
//composer require aws/aws-sdk-php


namespace App\Util;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Datasource\FactoryLocator;

//This requires to setup with composer
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\MultipartUploader;

class OfflineBox
{

    function MOVE_TO_MODEL_uploadDatabaseBackupToS3() {

        //IMPORTANT: You need to setup with Composer
        //add to launchPad POST_COMMANDS: composer require aws/aws-sdk-php

        ini_set('max_execution_time', 600);

        $offlineBox = new OfflineBox();

        $status = [
            'success' => 0,
            'errors' => 0
        ];

        $databasesToBackup = [
            'default',
            //if you have multiple databases add the connection name here
        ];

        foreach ($databasesToBackup as $database) {

            $response = $offlineBox->mysqlDump($database);
            $status[$database]['mysqlDump'] = $response;

            if ($response['STATUS'] == 200) {
                $res = $offlineBox->upload(
                    $response['file'],
                    $response['pathInfo']['basename']
                );
                $status[$database]['upload'] = $res;

                if ($res['STATUS'] == 200) {
                    $status['success']++;
                } else {
                    $status['errors']++;
                }
            } else {
                //ERROR
                $status['errors']++;
            }
        }

        $res = [];
        if ($status['success'] == count($databasesToBackup)) {
            $res['STATUS'] = 200;
            $res['MSG'] = 'Backup completed successfully';
            $res['LOG'] = $status;
        } else {
            $res['STATUS'] = 400;
            $res['MSG'] = 'ERRORS creating backups';
            $res['LOG'] = $status;
        }

        return $res;
    }


    public function __construct() {

        //Best is to add these to your server php.ini
        // and get them instead of adding to source-control
        $spaceKey = '';
        $spaceSecret = '';
        $spaceRegion = ''; // For example, the region for New York would be 'nyc3'
        $spaceName = '';

        $this->spaceKey = $spaceKey;
        $this->spaceSecret = $spaceSecret;
        $this->spaceRegion = $spaceRegion;
        $this->spaceName = $spaceName;

        $this->s3 = new S3Client([
            'version' => 'latest',
            'region'  => $this->spaceRegion,
            'endpoint' => 'https://'.$this->spaceRegion.'.digitaloceanspaces.com',
            'credentials' => [
                'key'    => $this->spaceKey,
                'secret' => $this->spaceSecret,
            ],
        ]);
    }

    //composer require aws/aws-sdk-php

    // Private data to store sensitive information
    private $spaceKey;
    private $spaceSecret;
    private $spaceRegion;
    private $spaceName;


    public function upload($filename, $keyName, $contentMD5 = false) {

        Log::debug('Starting upload: '.$keyName);

        $res = [];

        try {

            $options = [
                'Bucket' => $this->spaceName,
                'Key'    => $keyName,
                'SourceFile' => $filename,
                'ACL'    => 'private', // Access control level (optional)
            ];
            if ($contentMD5) {
                $options['ContentMD5'] = $contentMD5;
            }

            //pr($options);

            // Upload a file
            $result = $this->s3->putObject($options);

            $res['STATUS'] = 200;
            $res['MSG'] = "File uploaded successfully. URL: {$result['ObjectURL']}\n";
            $res['options'] = $options;

        } catch (AwsException $e) {
            // Output error message if fails
            echo $e->getMessage() . "\n";

            $res['STATUS'] = 500;
            $res['MSG'] = json_encode($e->getMessage());

        }

        Log::debug('upload completed');

        return $res;

    }

//    public function uploadLarge($filePath, $keyName, $contentMD5 = false) {
//
//        Log::debug('Starting uploadLarge: '.$keyName);
//
//        $res = [];
//
//        try {
//            // Use MultipartUploader for large file upload
//            $uploader = new MultipartUploader($this->s3, $filePath, [
//                'bucket' => $this->spaceName,
//                'key'    => $keyName,
//                'acl'    => 'private',
//            ]);
//
//            // Perform the upload
//            $result = $uploader->upload();
//
//            $res['STATUS'] = 200;
//            $res['MSG'] = "File uploaded successfully. URL: {$result['ObjectURL']}\n";
//
//        } catch (AwsException $e) {
//            // Output error message if fails
//            echo $e->getMessage() . "\n";
//
//            $res['STATUS'] = 500;
//            $res['MSG'] = json_encode($e->getMessage());
//
//        }
//
//
//        Log::debug('uploadLarge completed');
//
//
//        return $res;
//
//    }




    public function mysqlDump($connectionName = 'default', $filenamePrefix = '') {
        $res = [];

        $connection = ConnectionManager::get($connectionName);

        $config = $connection->config();

        //dd($config);
        $host = $config['host'];
        $port = $config['port'] ?? 3306;

        $username = $config['username'];
        $password = $config['password'];
        $database = $config['database'];

        $date = date('Y-m-d');
        $version = date('His');
        $file = TMP . $date.'_'.$filenamePrefix.$connectionName.'_' . $version . '.sql';

        // mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database),
            escapeshellarg($file)
        );


        // Execute the command
        exec($command, $output, $returnVar);


        if ($returnVar == 0) {

            $pathInfo = pathinfo($file);

            $res['STATUS'] = 200;
            $res['MSG'] = 'mysqldump completed: '.$file;
            $res['file'] = $file;
            $res['pathInfo'] = $pathInfo;
        } else {
            $res['STATUS'] = 500;
            $res['MSG'] = 'mysqldump failed: '.$file;
        }

        return $res;




    }


}
