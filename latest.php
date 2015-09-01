<?php

require_once 'config.php';

header('Content-Type: application/json');

function __autoload($className)
{
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    require_once $file . '.php'; 
}

function add_component($component, $isDependency=false)
{
    global $platform, $channel;
    global $in_componentsObj, $out_componentsObj;
    
    if (isset($out_componentsObj[$component])) {
        return;
    }

    if (isset($in_componentsObj[$component])) {
        $in_componentObj = $in_componentsObj[$component];
    } elseif ($isDependency) {
        $in_componentObj = Array();
    } else {
        return;
    }

    if (isset($in_componentObj['platform'])
         && !in_array($platform, $in_componentObj['platform'])
         && !in_array('all', $in_componentObj['platform'])) {
        return;
    }

    $out_version = null;

    switch ($channel) {
    case 'alpha':
        if (isset($in_componentObj['alpha'])) {
            $out_version = new Semver($in_componentObj['alpha']);
            $out_channel = 'alpha';
        }
    case 'beta':
        if (isset($in_componentObj['beta'])) {
            $beta = new Semver($in_componentObj['beta']);
            if (!$out_version || Semver::greaterThan($beta, $out_version)) {
                $out_version = $beta;
                $out_channel = 'beta';
            }
        }
    case 'release':
    default:
        if (isset($in_componentObj['release'])) {
            $release = new Semver($in_componentObj['release']);
        } else {
            $release = new Semver("1.0.0");
        }
        if (!$out_version || Semver::greaterThan($release, $out_version)) {
            $out_version = $release;
            $out_channel = 'release';
        }
    }

    if (!$out_version) {
        return;
    }

    if (isset($in_componentObj['dependencies'])) {
        foreach ($in_componentObj['dependencies'] as $dependency) {
            add_component($dependency, true);
        }
    }

    $out_componentsObj[$component] = (string)$out_version;
}

if (isset($_GET['platform'])) {
    $platform = $_GET['platform'];
} else {
    $platform = 'all';
}

if (isset($_GET['channel'])) {
    $channel = $_GET['channel'];
} else {
    $channel = 'release';
}

if (isset($_GET['components'])) {
    $components = explode(',', $_GET['components']);
} else {
    $components = Array();
}

$json = file_get_contents($componentsJSON);
$in_componentsObj = json_decode($json, true);

$out_componentsObj = new ArrayObject();

foreach ($components as $component) {
    add_component($component);
}

$json_options = JSON_UNESCAPED_SLASHES;
if (isset($_GET['pretty'])) {
    $json_options |= JSON_PRETTY_PRINT;
}

print json_encode($out_componentsObj, $json_options);
