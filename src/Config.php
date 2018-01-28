<?php

namespace Wallpaper;

require __DIR__ . '/../vendor/autoload.php';

class Json
{
    public static function decode($filepath)
    {
        $handler = fopen($filepath,"r");
        $contents = fread($handler,filesize($filepath));
        fclose($handler);

        $jsonContents = json_decode($contents);
        return $jsonContents;
    }
}

class Config
{
    protected $schemaFilepath;
    protected $configFilepath;
    protected $logger;
    protected $validatorErrors;
    protected $schemaObject;
    protected $configObject;

    public function __construct($configFilepath, $schemaFilepath)
    {
        $this->schemaFilepath = $schemaFilepath;
        $this->configFilepath = $configFilepath;
        $this->schemaObject = Json::decode($this->schemaFilepath);
        $this->configObject = Json::decode($this->configFilepath);

        $this->logger = new Logger();
        $this->validatorErrors = array();
    }

    protected function validateConfig()
    {
        $schemaStorage = new \JsonSchema\SchemaStorage();
        $schemaStorage->addSchema('file://wallpaper.schema.json',$this->schemaObject);
        $jsonValidator = new \JsonSchema\Validator(new \JsonSchema\Constraints\Factory($schemaStorage));
        $jsonValidator->validate($this->configObject, $this->schemaObject);

        if($jsonValidator->isValid()) {
            return true;
        } else {
            $this->validatorErrors = $jsonValidator->getErrors();
            return false;
        }
    }
    public function getConfig()
    {
        if($this->validateConfig()) {
            return $this->configObject;
        } else {
            return null;
        }
    }
    public function getErrors()
    {
        return $this->validatorErrors;
    }
}

?>
