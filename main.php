<?php

require('./Wallpaper.php');

function usage() {
    print(
          "Usage main\n"
        . "\tMake sure to set the json source first\n\n"
        . "\t-v\n"
        . "\t--verbose\n"
        . "\t\tPrints when executing\n"
        . "\t-g\n"
        . "\t--get-urls\n"
        . "\t\tGet urls without downloading them\n"
        . "\t-f\n"
        . "\t--force\n"
        . "\t\tIgnore history of urls when downloading\n"
        . "\t-s=link\n"
        . "\t--set-json=link\n"
        . "\t\tSet json link to download images from\n"
        . "\t-r\n"
        . "\t--download\n"
        . "\t\tDownload all images...\n"
        . "\t-c\n"
        . "\t--count\n"
        . "\t\tCounts images not in history\n"
        . "\t-h\n"
        . "\t--help\n"
        . "\t\tPrints this help message\n"
    );
}

function check_config($config) {
    if(!is_dir($config->paths->downloads)) {
        mkdir($config->paths->downloads);
    }
}

$config = (object) array(
    "paths" => (object) array(
        "downloads" => "./downloads"
    )
);

$wall = new Wallpaper();

$params["verbose"] = false;
$params["get-urls"] = false;
$params["force"] = false;
$params["help"] = false;
$params["download"] = false;
$params["set-json"] = false;
$params["download"] = false;
$params["count"] = false;

// v : verbose
// g : get-urls
// f : force
// h : help
// s : set-json link
$shortopts = "vgfhs:dc";
$longopts = array(
    "verbose",
    "get-urls",
    "force",
    "help",
    "set-json:",
    "download",
    "count"
    );

$options = getopt($shortopts,$longopts);

if(isset($options["verbose"]) || isset($options["v"])) {
    $params["verbose"] = true;
}
if (isset($options["get-urls"]) || isset($options["g"])) {
    $params["get-urls"] = true;
}
if (isset($options["force"]) || isset($options["f"])) {
    $params["force"] = true;
}
if (isset($options["help"]) || isset($options["h"])) {
    $params["help"] = true;
}
if (isset($options["download"]) || isset($options["d"])) {
    $params["download"] = true;
}
if (isset($options["count"]) || isset($options["c"])) {
    $params["count"] = true;
}
if (isset($options["set-json"])) {
    $params["set-json"] = $options["set-json"];
} else if (isset($options["s"])) {
    $params["set-json"] = $options["s"];
} else {
    $params["set-json"] = "reddit.com/r/wallpapers.json";
}

if($params["help"]) {
    usage();
} else {
    if($params["set-json"]) {
        $wall->set_json_url($params["set-json"]);
        $wall->set_verbose_mode($params["verbose"]);
        if($params["get-urls"]) {
            $urls = $wall->get_array_from_url();
            foreach($urls as $url) {
                print($url."\n");
            }
        } else if ($params["download"]) {
            check_config($config);
            $wall->run($config,$params["force"]);
            $wall->reformat_name_images($config);
        } else if($params["count"]) {
            $count = $wall->count_new_images();
            if($count==0) print("No new images...\n");
            else print($count . " new images!\n");
        } else {
            usage();
        }
    } else {
        usage();
    }
}
?>
