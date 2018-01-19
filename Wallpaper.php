<?php
class Wallpaper {

    private $history_filename;
    private $json_url;
    private $verbose_mode;
//    private $mode_history_file = "csv"; // means separated by comma

    function __construct() {
        $this->history_filename = __DIR__ . "/Whistory";
        $this->json_url = "";
        $this->verbose_mode = false;
    }

    public function set_verbose_mode($mode) {
        $this->verbose_mode = $mode;
    }

    // in : array with urls to download images from
    // out : urls that aren't already in history
    // avoid downloading same image
    public function check_urls_in_history($urls_array) {
        if(! file_exists($this->history_filename)) {
            return $urls_array;
        }
        $handle = fopen($this->history_filename,'r');
        $history_array = explode(",",fgets($handle));
        $result_array = array();

        foreach($urls_array as $url) {
            if(! in_array($url,$history_array)) {
                array_push($result_array,$url);
            } else {
                if($this->verbose_mode)
                    print($url." already in history...\n");
            }
        }

        fclose($handle);
        return $result_array;
    }

    // in : array of urls to add to history
    // out : nothing
    // adds each url in array to history for avoiding re-download same img
    private function add_array_to_history($url_array) {
        $handle = fopen($this->history_filename,'a+');
        foreach($url_array as $url) {
            fwrite($handle, $url.",");
        }
        fclose($handle);
    }

    // in : url to json file
    // out : array with urls of images found
    // try to scan page to find images, is best to specify limit in the url
    public function get_array_from_url() {
        if(! empty($this->json_url)) {
            $json_file = file_get_contents($this->json_url);
            $json = json_decode($json_file,true);
            $urls_array = array();
            foreach($json["data"]["children"] as $children){
                if(isset($children["data"]["url"])) {
                    $source_url = $children["data"]["url"];
                    if($this->check_valid_url($source_url)) {
                        array_push($urls_array,$source_url);
                    }
                }
            }
            return $urls_array;
        } else return false;
    }

    // in : string to url
    // out : boolean if valid url
    // validation consist in trying to check if it is an url to an image
    private function check_valid_url($url) {
        return (preg_match('/.*\.(jpg|jpeg|png)$/',$url)===1);
    }

    // in : nothing
    // out : random name for image
    // ...
    private function generate_new_name() {
        return (substr(md5(microtime()),rand(0,26),5) . ".jpg");
    }

    // in : url containing an image
    // out : should be boolean if success downloading
    // downloads an image to a random name in local script path
    public function download_image_from_url($download_path,$url) {
        $output_name = $this->generate_new_name();
        if($this->verbose_mode)
            print("Downloading from ".$url." as ".$output_name."...\n");
        file_put_contents($download_path.DIRECTORY_SEPARATOR.$output_name,file_get_contents($url));
    }

    public function download_images_from_array($download_path,$urls) {
        foreach($urls as $url) {
            $this->download_image_from_url($download_path,$url);
        }
        $this->add_array_to_history($urls);
    }

    public function check_valid_json_url($url){
        if(! preg_match('/^http\:\/\/www\./',$url)) {
            return "http://www." . $url;
        } else return $url;
    }

    public function set_json_url($json_url) {
        $this->json_url = $this->check_valid_json_url($json_url);
    }
    public function get_json_url() {
        return $this->json_url;
    }

    public function count_new_images() {
        if(! empty($this->json_url)){
            $urls = $this->get_array_from_url($this->json_url);
            $urls = $this->check_urls_in_history($urls);
            return count($urls);
        }
    }

    // uses methods of class to download images with the given url
    public function run($config, $force = false) {
        if(! empty($this->json_url)){
            $urls = $this->get_array_from_url($this->json_url);
            if(!$force)
                $urls = $this->check_urls_in_history($urls);
            $this->download_images_from_array($config->paths->downloads,$urls);
        } else {
            print("Invalid json link...\n");
        }
    }

    // creates a bash script to run and modify output format
    // BUG of script, output format must be ok from the beginning
    public function reformat_name_images($config) {
        $output_name = "temp.sh";
        if(false !== ($handle = fopen($output_name,"a+"))){
            exec("file ".$config->paths->downloads."/*",$output_array);
            foreach($output_array as $file) {
                $regex_file = preg_match("/(([A-Za-z]|[0-9])*)\.(jpg):\ *(JPEG|PNG)/",$file,$matches);
                if(isset($matches[1]) && $matches[3] && isset($matches[4])) {
                    if(!empty($matches[1]) && $matches[3] && !empty($matches[4])) {
                        $filename = $matches[1];
                        $original_format = $matches[3];
                        $format = $matches[4];
                        print(".");
                        $str = "mv " . $filename . "." . $original_format . " " . $filename . "." . $format."\n";
                        fwrite($handle,$str);
                    }
                }
            }
            fwrite($handle,"rm ".$output_name);
            print("\n");
            fclose($handle);
        }
    }
}

?>
