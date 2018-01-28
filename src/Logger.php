<?php

namespace Wallpaper;

class Logger
{
    protected $tags;

    public function __construct()
    {
        $this->tags["error"] = "ERROR";
        $this->tags["warning"] = "WARNING";
        $this->tags["notice"] = "NOTICE";
        $this->tags["debug"] = "DEBUG";
    }

    protected function validateMessage($msg)
    {
        return true;
    }

    protected function printMessage($tag,$msg)
    {
        if($this->validateMessage($msg)) {
            echo PHP_EOL . "[$tag] $msg" . PHP_EOL ;
        } else {
            echo PHP_EOL . " Error parsing message " . PHP_EOL;
        }
    }

    public function error($msg)
    {
        $this->printMessage($this->tags["error"],$msg);
    }
    public function warning($msg)
    {
        $this->printMessage($this->tags["warning"],$msg);
    }
    public function notice($msg)
    {
        $this->printMessage($this->tags["notice"],$msg);
    }
    public function debug($msg)
    {
        $this->printMessage($this->tags["debug"],$msg);
    }
}

?>
