<?php


namespace Cuveen\Storage;


use Cuveen\Config\Config;
use Cuveen\Exception\CuveenException;

class Storage
{
    protected $config;

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    public function download($filePath, $suggestedFilename = false, $mimeType = null)
    {
        if(empty($suggestedFilename)){
            $suggestedFilename = basename($filePath);
        }

        $fileRoot = (strpos($filePath, $this->config->get('base_path')) !== false)?$filePath:realpath($this->config->get('base_path').DIRECTORY_SEPARATOR.$filePath);
        $fileRoot = str_replace('\\','/',$fileRoot);
        if (\file_exists($fileRoot) && \is_file($fileRoot)) {
            if ($mimeType === null) {
                // use a reasonable default value
                $mimeType = 'application/octet-stream';
            }
            $this->sendDownloadHeaders($mimeType, $suggestedFilename, filesize($fileRoot));
            readfile($fileRoot);
        }
        else{
            return new CuveenException('File `'.str_replace($this->config->get('base_path'),'',$filePath).'` not found');
        }
    }

    private function sendDownloadHeaders($mimeType, $suggestedFilename, $size)
    {
        header('Content-Type: '.$mimeType, true);
        header('Content-Disposition: attachment; filename="'.$suggestedFilename.'"', true);
        header('Content-Length: '.$size, true);
        header('Accept-Ranges: none', true);
        header('Cache-Control: no-cache', true);
        header('Connection: close', true);
    }

    public function serveFile($filePath, $mimeType) {
        // if the file exists at the specified path
        $fileRoot = (strpos($filePath, $this->config->get('base_path')) !== false)?realpath($this->config->get('base_path').DIRECTORY_SEPARATOR.$filePath):$filePath;
        if (\file_exists($fileRoot) && \is_file($fileRoot)) {
            \header('Content-Type: ' . $mimeType, true);
            \header('Accept-Ranges: none', true);
            \header('Connection: close', true);

            // pipe the actual file contents through PHP
            \readfile($fileRoot);
        }
        // if the file could not be found
        else {
            return new CuveenException('File `' . str_replace($this->config->get('base_path'),'',$filePath) . '` not found');
        }
    }

}