<?php

require ('base.php');

class resolvetables extends mghBase {

    function run() {
        global $modx;

        if ($object = $modx->getObject($this->classname, $this->object_id)) {
            $configArray = $object->toArray();
            $packageName = $modx->getOption('package', $configArray);
            $packageNameLower = strtolower($modx->getOption('package', $configArray));

            $this->dir = $this->packages_dir . $packageNameLower . '/';
            $this->build_dir = $this->dir . '_build';
            $this->configFile = $this->build_dir . '/config.json';

            if (!$this->recursive_mkdir($this->build_dir, 0755)) {
                return 'failure';
            }

            $packagepath = $modx->getOption('core_path') . 'components/' . $packageNameLower . '/';
            $modelpath = $packagepath . 'model/';
            $schemapath = $modelpath . 'schema/';
            $schemafile = $schemapath . $packageNameLower . '.mysql.schema.xml';

            $this->writeGeneralConfig();

            if (file_exists($schemafile)) {

                $manager = $modx->getManager();
                $generator = $manager->getGenerator();

                $prefix = null;

                $modx->addPackage($packageNameLower, $modelpath, $prefix);
                $pkgman = $modx->migx->loadPackageManager();
                $pkgman->manager = $modx->getManager();
                $pkgman->parseSchema($schemafile, $modelpath, true);
                $classes = array();
                if (count($pkgman->packageClasses) > 0) {
                    foreach ($pkgman->packageClasses as $class => $value) {
                        $classes[] = $class;
                    }
                }


                $config = $this->getConfig();

                $config['database']['tables'] = $classes;

                $content = json_encode($config);
                $content = $modx->migx->indent($content);
                $this->writeElFile($this->configFile, $content);

            }
        }
    }


}

$process = new resolvetables($scriptProperties);
$process->run();


return $modx->error->success('');
