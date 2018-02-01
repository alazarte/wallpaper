<?php

namespace Wallpaper;

class Json
{
    public static function decodeAsObject($filepath)
    {
        $handler = fopen($filepath,"r");
        $contents = fread($handler,filesize($filepath));
        fclose($handler);

        $jsonContents = json_decode($contents);
        return $jsonContents;
    }
}

?>
