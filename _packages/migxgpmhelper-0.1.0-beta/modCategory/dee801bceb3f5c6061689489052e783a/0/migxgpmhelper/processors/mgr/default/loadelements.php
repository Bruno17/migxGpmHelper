<?php

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

$configs = $modx->getOption('configs',$scriptProperties,'');
$classname = $modx->getOption('classname',$config,'');
$elementsClass = '';

switch ($configs){
    case 'mghsnippets':
    case 'mghsnippets:migxgpmhelper':
        $elementsClass = 'modSnippet';
        break;
    case 'mghcategories':
    case 'mghcategories:migxgpmhelper':
        $elementsClass = 'modCategory';
        break;
    case 'mghchunks':
    case 'mghchunks:migxgpmhelper':
        $elementsClass = 'modChunk';
        break;
    case 'mghtemplates':
    case 'mghtemplates:migxgpmhelper':
        $elementsClass = 'modTemplate';
        break; 
    case 'mghplugins':
    case 'mghplugins:migxgpmhelper':
        $elementsClass = 'modPlugin';
        break;                               
}

if (!empty($elementsClass) && $elements = $modx->getIterator($elementsClass)){
    foreach ($elements as $element){
        $id = $element->get('id');
        if ($object = $modx->getObject($classname,array('element_id' => $id))){
            
        }else{
            $object = $modx->newObject($classname);
            $object->set('element_id',$id);
            $object->save();
        }    
    }
}

return $modx->error->success();
