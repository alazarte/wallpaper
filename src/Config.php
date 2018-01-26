<?php

class Logger
{
    $errorTag = "ERROR";

    protected function validateMessage($msg)
    {
        return true;
    }

    public function error($msg)
    {
        if($this->validate($msg)) {
            echo PHP_EOL . "[$this->errorTag] $msg" . PHP_EOL ;
        } else {
            echo PHP_EOL . " Error parsing message " . PHP_EOL;
        }
    }
}

class Json
{
    public static function getContents($filepath)
    {
        $handler = fopen($filepath,"r");
        $contents = fread($handler,filesize());
        fclose($handler);

        $jsonContents = json_decode($contents);
        return $jsonContents;
    }
}

class Config
{
    protected $schema;
    protected $config;

    public function __construct($configFilepath, $schemaFilepath)
    {
        $this->schemaFilepath = $schemaFilepath;
        $this->configFilepath = $configFilepath;
        $this->logger = new Logger();
    }

    protected function validateConfig()
    {
        $validator = new SchemaStorage();
        $jsonContents = Json::getContents($configFilepath, true);
        $validator->validate($jsonContents,$this->schemaFilepath);
        if($validator->isValid()) {
            return true;
        } else {
            $this->validatorErrors = $validator->getErrors();
            return false;
        }
    }
    public function getConfig()
    {
        $this->validateConfig();
        return $this->config;
    }
}

?>
