<?php

class mghBase {

    function __construct($scriptProperties) {
        global $modx;
        $config = $modx->migx->customconfigs;
        $prefix = isset($config['prefix']) && !empty($config['prefix']) ? $config['prefix'] : null;
        $errormsg = '';

        if (isset($config['use_custom_prefix']) && !empty($config['use_custom_prefix'])) {
            $prefix = isset($config['prefix']) ? $config['prefix'] : '';
        }
        $packageName = $config['packageName'];

        $packagepath = $modx->getOption('core_path') . 'components/' . $packageName . '/';
        $modelpath = $packagepath . 'model/';
        $is_container = $modx->getOption('is_container', $config, false);
        if (is_dir($modelpath)) {
            $modx->addPackage($packageName, $modelpath, $prefix);
        }

        $configs = $modx->getOption('configs', $scriptProperties, '');
        $this->task = $modx->getOption('task', $scriptProperties, '');
        $this->object_id = $modx->getOption('object_id', $scriptProperties, '');
        $this->classname = $modx->getOption('classname', $config, '');

        $this->elementSettings = $modx->getOption('elementSettings', $config, '');
        $this->packages_dir = rtrim($modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';

    }

    function run() {
        global $modx;
    }

    function recursive_mkdir($path, $mode = 0777) {
        $dirs = explode(DIRECTORY_SEPARATOR, $path);
        $count = count($dirs);
        $path = '';
        for ($i = 0; $i < $count; ++$i) {
            $path .= DIRECTORY_SEPARATOR . $dirs[$i];
            if (!is_dir($path) && !mkdir($path, $mode)) {
                return false;
            }
        }
        return true;
    }

    function writeElFile($filename, $content) {
        if (!$handle = fopen($filename, "w+")) {
            //print "Kann die Datei $filename nicht öffnen";
        }

        if (!fwrite($handle, $content)) {
            //print "Kann in die Datei $filename nicht schreiben";
        }
        fclose($handle);
    }
    
    function writeGeneralConfig(){
        global $modx;
        if ($this->classname=='mghPackage' && $object = $modx->getObject($this->classname, $this->object_id)) {
            
        } else {
            $object = $modx->newObject('mghPackage');
        } 
        
        $config = $this->getConfig();
        
        $release = $object->get('release');
        $config['name'] = $object->get('package');
        $config['lowCaseName'] = strtolower($config['name']);
        $config['description'] = $object->get('packageDescription');
        $config['author'] = '';
        $config['version'] = $object->get('version') . (!empty($release) ? '-' . $release : '');
        
        $content = json_encode($config);
        $content = $modx->migx->indent($content);
        $this->writeElFile($this->configFile, $content);        
        
    }

    function getConfig() {
        $config = array();

        if (file_exists($this->configFile)) {
            $config = file_get_contents($this->configFile);
            $config = json_decode($config, 1);
        }

        $config = is_array($config) ? $config : array();

        return $config;
    }

}

