<?php

require('base.php');

class writetofiles extends mghBase {

    function run() {
        global $modx;
        if ($this->classname == 'mghCategory') {
            //get elements of Category
            if ($mghCategory = $modx->getObject($this->classname, $this->object_id)) {
                //$category_id = $mghCategory->get('element_id');
                $category_name = '';
                if ($category = $mghCategory->getOne('Element')) {
                    $this->parent_category_id = $category->get('id');

                    //$category_name = $category->get('category');
                    //print_r($category->toArray());die();
                    $this->elementsPackage = $mghCategory->get('package');
                    //$elementsPath = $mghCategory->get('static_path');

                    if (!empty($this->packages_dir) && !empty($this->elementsPackage)) {

                        $this->dir = $this->packages_dir . $this->elementsPackage . '/';
                        $this->build_dir = $this->dir . '_build';
                        $this->configFile = $this->build_dir . '/config.json';

                        if (!$this->recursive_mkdir($this->dir, 0755)) {
                            return 'failure';
                        }
                        if (!$this->recursive_mkdir($this->build_dir, 0755)) {
                            return 'failure';
                        }
                    }

                    $this->getCategories();
                    $this->writeCategoriesConfig();

                    if ($this->task == 'elements') {
                        foreach ($this->elementSettings as $task => $settings) {
                            $this->writeElFiles($task, $settings);
                        }
                    } else {
                        $settings = $modx->getOption($this->task, $this->elementSettings, array());
                        $this->writeElFiles($this->task, $settings);
                    }
                }
            }

        } else {

        }
    }

    function writeCategoriesConfig() {
        global $modx;
        $config = $this->getConfig();
        //print_r($config);
        $categories = array();
        foreach ($this->categories as $category) {
            if (strtolower($category->get('category')) == strtolower($this->elementsPackage)){
                
                continue;
            }
            
            
            $cat = array();
            $cat['name'] = $category->get('category');
            $cat['rank'] = $category->get('rank');

            if ($parent = $category->getOne('Parent')) {
                if (strtolower($parent->get('category')) != strtolower($this->elementsPackage)){
                    //write parent, only if parent is not the main - category
                    $cat['parent'] = $parent->get('category');    
                }
                
            }
            $categories[] = $cat;
        }
 
        $config['package']['elements']['categories'] = $categories;

        //print_r($config);
        //die();

        $content = json_encode($config);
        $content = $modx->migx->indent($content);
        $this->writeElFile($this->configFile, $content);
        //die();
    }
    
    

    function getCategories() {
        global $modx;

        $this->categories = null;
        $category_ids = $this->getCategoryChildIds($this->parent_category_id);
        $this->category_ids = array_merge(array($this->parent_category_id), $category_ids);
        if (count($this->category_ids > 0)) {
            $this->categories = $modx->getCollection('modCategory', array('id:IN' => $this->category_ids));
        }
 
        
    }

    function getCategoryChildIds($id = null) {
        global $modx;
        $child_ids = array();
        if (!empty($id)) {
            $id = is_int($id) ? $id : intval($id);
            //$c = $modx->newQuery('modCategory');
            //$c->where();
            if ($collection = $modx->getCollection('modCategory', array('parent' => $id))) {
                foreach ($collection as $object) {
                    $child_ids[] = $object->get('id');
                }
                foreach ($child_ids as $child_id) {
                    if ($ids = $this->getCategoryChildIds($child_id)) {
                        $child_ids = array_merge($child_ids, $ids);
                    }
                }
            }

        }
        return $child_ids;
    }

    function writeCategoryElFiles($collection, $settings, $elements_dir, $category_name, $elements) {
        global $modx;

        $nameField = $modx->getOption('nameField', $settings, 'name');
        $elementsSuffix = $modx->getOption('elementsSuffix', $settings, '.php');
        $elementsClass = $modx->getOption('elementsClass', $settings, false);
        $contentField = $modx->getOption('contentField', $settings, '');
        $mghClass = str_replace('mod', 'mgh', $elementClass);

        foreach ($collection as $object) {
            $id = $object->get('id');
            $exclude = false;
            if ($mghObject = $modx->getObject($mghClass, array('element_id' => $id))) {
                $exclude = $mghObject->get('exclude');
            }

            if ($exclude) {
                continue;
            }

            $element = $object->toArray();
            //print_r($element);die();
            //$element = array();
            
            unset($element['id']);
            unset($element['category']);
            unset($element[$nameField]);
            $element['name'] = $object->get($nameField);
            
            if ($object instanceof modPlugin) {
                $plugin_events = array();
                if ($events = $object->getMany('PluginEvents')) {
                    foreach ($events as $event_o) {
                        /*
                        $event = $event_o->toArray();
                        if ($event_e = $event_o->getOne('Event')) {
                            $systemevent = $event_e->toArray();
                            $event = array_merge($event, $systemevent);
                        }

                        $event['pluginid'] = $object->get('name');
                        */
                        $plugin_events[] = $event_o->get('event');
                    }
                }
                
                
                $element['events'] = $plugin_events;
            }

            unset($element[$contentField]);
            unset($element['content']);
            $filename = strtolower($object->get($nameField)) . $elementsSuffix;
            $element['file'] = $filename;
            $filename = $elements_dir . '/' . $filename;
            $content = $object->getContent();
            if (strstr($elementsSuffix, '.php')) {
                $content = '<?php' . "\n" . $content;
            }

            $this->writeElFile($filename, $content);

            //$element['category'] = 0;
            if (strtolower($category_name) != strtolower($this->elementsPackage)){
                //write category only, if not the maincategory
                $element['category'] = $category_name;    
            }
            
            $elements[] = $element;
        }
        return $elements;
    }

    function writeElFiles($task, $settings) {
        global $modx;


        if (!empty($this->packages_dir) && !empty($this->elementsPackage)) {

            $elements_dir = $this->dir . 'core/components/' . $this->elementsPackage . '/elements/' . $task;
            
            if (!$this->recursive_mkdir($elements_dir, 0755)) {
                return 'failure';
            }

            $nameField = $modx->getOption('nameField', $settings, 'name');
            $elementsSuffix = $modx->getOption('elementsSuffix', $settings, '.php');
            $elementsClass = $modx->getOption('elementsClass', $settings, false);
            $contentField = $modx->getOption('contentField', $settings, '');
            $mghClass = str_replace('mod', 'mgh', $elementClass);

            if ($elementsClass) {
                $elements = array();


                foreach ($this->categories as $category) {
                    if ($collection = $modx->getIterator($elementsClass, array('category' => $category->get('id')))) {
                        $elements = $this->writeCategoryElFiles($collection, $settings, $elements_dir, $category->get('category'), $elements);
                    }
                }


                $config = $this->getConfig();


                $config['package']['elements'][$task] = $elements;


                $content = $modx->toJson($config);
                $content = $modx->migx->indent($content);
                //$filename = $elements_dir . '/' . $task . '.' . $category_id . '.json';
                $this->writeElFile($this->configFile, $content);
            }
        }
    }
}

$process = new writetofiles($scriptProperties);
$process->run();


return $modx->error->success();
