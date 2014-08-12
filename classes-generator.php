<?php
$basePath = getcwd();

// load autoloader
if (file_exists("$basePath/vendor/autoload.php")) {
    require_once "$basePath/vendor/autoload.php";
} elseif (file_exists("$basePath/init_autoload.php")) {
    require_once "$basePath/init_autoload.php";
} elseif (\Phar::running()) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    echo 'Error: I cannot find the autoloader of the application.' . PHP_EOL;
    exit(2);
}

require_once 'Generator.php';

/**
 * Example run:
 * php classes-generator.php --source=src --destination=classes --namespace=Classes --rootClass=SuperClass -r
 */

$shortOpts = '';
$shortOpts .= 'r::'; // --showRequires || -r

$longOpts = array(
    "source:", // --source=src
    "destination:", // --destination=classes
    "namespace::", // --namespace=Classes
    "rootClass::", // --rootClass=SuperClass
    "showRequires", // --showRequires
);

$options = getopt($shortOpts, $longOpts);

/**
 * Example:
 *
 * $params = array(
     'sourceDir' => 'src',
     'destinationDir' => 'classes',
     'namespace' => 'Classes',
     'rootClassName' => 'SuperClass',
     'rootClassNameForCollection' => 'CollectionSuperClass',
     'rootClassNamespace' => 'SuperClass',
     'rootClassForCollectionNamespace' => 'CollectionSuperClass'
     'showRequires' => true,
 );
 */
$params = array();

if (isset($options['source'])) {
    $params['sourceDir'] = $options['source'];
} else {
    exit("Parameter '--source' is required\n");
}

if (isset($options['destination'])) {
    $params['destinationDir'] = $options['destination'];
} else {
    exit("Parameter '--destination' is required\n");
}

if (isset($options['namespace'])) {
    $params['namespace'] = $options['namespace'];
}

if (isset($options['rootClass'])) {
    $params['rootClassName'] = $options['rootClass'];
}
if (isset($options['rootClassNameForCollection'])) {
    $params['rootClassNameForCollection'] = $options['rootClassNameForCollection'];
}
if (isset($options['rootClassNamespace'])) {
    $params['rootClassNamespace'] = $options['rootClassNamespace'];
}
if (isset($options['rootClassForCollectionNamespace'])) {
    $params['rootClassForCollectionNamespace'] = $options['rootClassForCollectionNamespace'];
}
if (isset($options['showRequires']) || isset($options['r'])) {
    $params['showRequires'] = true;
}


$classesGenerator = new ClassesGenerator\Generator($params);
$classesGenerator->generate();
