<?php

require __DIR__ . '/../vendor/autoload.php';

use \Wallpaper\Wallpaper;
use \Wallpaper\Config;

$configFilepath = __DIR__ . "/../config/config.json";
$schemaFilepath = __DIR__ . "/../config/schema/wallpaper.schema.json";

$configInstance = new Config($configFilepath,$schemaFilepath);
$config = $configInstance->getConfig();

if($config) {
    $wallpaper = new Wallpaper($config->wallpaper);

    $images = $wallpaper->getImageLinksFromUrl();
    $wallpaper->downloadImagesFromUrlArray($images);
} else {
    print_r($configInstance->getErrors());
}

?>
