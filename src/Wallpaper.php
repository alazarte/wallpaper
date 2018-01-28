<?php

namespace Wallpaper;

require __DIR__ . '/../vendor/autoload.php';

class Wallpaper
{
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

    // in : array with urls to download images from
    // out : urls that aren't already in history
    // avoid downloading same image
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

    // in : array of urls to add to history
    // out : nothing
    // adds each url in array to history for avoiding re-download same img
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

    // in : url to json file
    // out : array with urls of images found
    // try to scan page to find images, is best to specify limit in the url
    protected function getImageLinksFromUrl() 
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

    // in : string to url
    // out : boolean if valid url
    // validation consist in trying to check if it is an url to an image
    protected function checkValidUrl($url) 
    {
        return (preg_match('/.*\.(jpg|jpeg|png)$/',$url)===1);
    }

    // in : nothing
    // out : random name for image
    // ...
    protected function generateNewName() 
    {
        return (substr(md5(microtime()),rand(0,26),5));
    }

    // in : url containing an image
    // out : should be boolean if success downloading
    // downloads an image to a random name in local script path
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

    public function countNewImages() 
    {
        if(! empty($this->config->jsonUrl)){
            $urls = $this->getImageLinksFromUrl($this->config->jsonUrl);
            $urls = $this->checkUrlsInHistory($urls);
            return count($urls);
        }
    }
}

?>
