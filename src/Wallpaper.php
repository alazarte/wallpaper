<?php

namespace Wallpaper;

// require __DIR__ . '/../vendor/autoload.php';

class Wallpaper
{
    const EXTENSION_SEPARATOR = ".";

    protected $config;
    protected $logger;
    protected $ignoreHistory;
    protected $customJsonUrl;

    public function __construct($config) 
    {
        $this->config = $config;
        $this->logger = new Logger();
        $this->ignoreHistory = false;
        $this->customJsonUrl = "";
    }

    protected function checkValidConfig()
    {
        if(! $this->checkValidJsonUrl($this->config->defaultUrl)) {
            $this->logger->error("Default json url is not valid");
        }
        if(is_dir($this->config->downloadsFolder)) {
            $this->config->error("Downloads folder does not exists");
        }
        if(is_dir($this->config->historyFile)) {
            $this->config->error("History file does not exists");
        }
    }

    /**
     * This method removes url from an array with urls that are found in
     * the history file
     *
     * @param   Array   $urlArray   Array with urls to download images from
     * @return  Array               Array with urls that are not already in history
     */
    protected function removeUrlsAlreadyInHistory($urlArray) 
    {
        if(! file_exists($this->config->historyFilepath)) {
            $this->logger->debug("History file does not exists");
            return $urlArray;
        }
        $handle = fopen($this->config->historyFilepath,'r');
        $urlsHistory = explode(",",fgets($handle));
        $resultArray = array();

        foreach($urlArray as $url) {
            if(! in_array($url,$urlsHistory)) {
                array_push($resultArray,$url);
            } else {
                $this->logger->warning($url." already in history...");
            }
        }

        fclose($handle);
        return $resultArray;
    }

    /**
     * Adds each url in a array to the history file to avoid 
     * downloading the same image again
     *
     * @param   Array   $urlArray   Array with the urls to add to the history file as csv
     */
    protected function addArrayToHistory($url_array) 
    {
        $handle = fopen($this->config->historyFilepath,'a+');
        foreach($url_array as $url) {
            fwrite($handle, $url.",");
        }
        fclose($handle);
    }

    protected function getJsonFromUrl($url) 
    {
        $jsonFile = file_get_contents($url);
        $json = json_decode($jsonFile);
        return $json;
    }

    /**
     * Try to decode contents as json object and find images in hard-coded path
     *
     * @returns     Array   Array with all images found as url
     */
    public function getImageLinksFromUrl() 
    {
        if(empty($this->customJsonUrl)) {
            $url = $this->config->defaultUrl;
        }
        $json = $this->getJsonFromUrl($url);
        $urlsArray = array();
        foreach($json->data->children as $children){
            if(isset($children->data->url)) {
                $sourceUrl = $children->data->url;
                if($this->checkValidUrl($sourceUrl)) {
                    array_push($urlsArray,$sourceUrl);
                }
            }
        }
        return $urlsArray;
    }

    /**
     * Simple validaton of a json url
     *
     * @param   String  $url    Url to validate
     * @returns Boolean         True if regex is matched
     */
    protected function checkValidUrl($url) 
    {
        return (preg_match('/.*\.(jpg|jpeg|png)$/',$url)===1);
    }

    /**
     * Generates a 5 letter pseudo-random string to use as the image name
     *
     * @returns String      The 5 letter string
     */
    protected function generateNewName() 
    {
        return (substr(md5(microtime()),rand(0,26),5)) .$this::EXTENSION_SEPARATOR. $this->config->imagesExtension;
    }

    /**
     * Download image from url to the path from the config file with a new name
     *
     * @param   String  $url    Url that contains the image
     */
    public function downloadImageFromUrl($url) 
    {
        $output_name = $this->generateNewName();
        $this->logger->notice("Downloading from ".$url." as ".$output_name."...");
        file_put_contents($this->config->downloadsPath.DIRECTORY_SEPARATOR.$output_name,file_get_contents($url));
    }

    protected function processUrls($urls)
    {
        if(! $this->ignoreHistory) {
            $processedUrls = $this->removeUrlsAlreadyInHistory($urls);
        }
        return $processedUrls;
    }

    public function downloadImagesFromUrlArray($urls) 
    {
        $processedUrls = $this->processUrls($urls);
        foreach($processedUrls as $url) {
            $this->downloadImageFromUrl($url);
        }
        $this->addArrayToHistory($processedUrls);
    }

    public function checkValidJsonUrl($url)
    {
        if(! preg_match('/^http\:\/\/www\./',$url)) {
            return "http://www." . $url;
        } else return $url;
    }
}

?>
