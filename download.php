<?php

require_once 'config.php';

header('Content-Type: application/json');

function __autoload($className)
{
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    require_once $file . '.php'; 
}

function expand_vars($str, $vars)
{
    preg_match_all('/\${([a-zA-Z0-9_]+)}/', $str, $out, PREG_PATTERN_ORDER);

    foreach(array_unique($out[1]) as $var) {
        if (array_key_exists($var, $vars)) {
            $replacement = $vars[$var];
        } else {
            $replacement = '';
        }

        $str = str_replace('${' . $var . '}', $replacement, $str);
    }

    return $str;
}

function str_ends_with($haystack, $needle)
{
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

if (isset($_GET['component'])) {
    $component = $_GET['component'];
} else {
    print '{}';
    return;
}

if (isset($_GET['platform'])) {
    $platform = $_GET['platform'];
} else {
    $platform = 'all';
}

if (isset($_GET['version'])) {
    $version = $_GET['version'];
} else {
    $version = '0.0.0';
}

$json = file_get_contents($componentsJSON);
$in_componentsObj = json_decode($json, true);

$out_componentObj = new ArrayObject();

if (isset($in_componentsObj[$component])) {
    $in_componentObj = $in_componentsObj[$component];
} else {
    if (str_ends_with($component, '.pk3') &&
        file_exists($downloadDir . '/' . $component)) {
        $in_componentObj = Array(
            'download' => '${component}',
        );
    } else {
        print '{}';
        return;
    }
}

if (!isset($in_componentObj['download'])) {
    if (!isset($in_componentObj['platform']) ||
        in_array('all', $in_componentObj['platform'])) {
        $in_componentObj['download'] = '${component}_${version}.zip';
    } else {
        $in_componentObj['download'] = '${component}_${platform}_${version}.zip';
    }
}

$vars = Array(
    'component' => $component,
    'platform' => $platform,
    'version' => $version,
);

$file = expand_vars($in_componentObj['download'], $vars);

if (!file_exists($downloadDir . '/' . $file)) {
    print '{}';
    return;
}

$hashes = Array();
$hashFile = dirname($downloadDir . '/' . $file) . '/sha256sums';
foreach (file($hashFile) as $line) {
    $line = trim($line);
    $hashes[substr($line, 66)] = substr($line, 0, 64);
}

$out_componentObj['download'] = $downloadURL . '/' . $file;
$out_componentObj['size'] = filesize($downloadDir . '/' . $file);
if (isset($hashes[basename($file)])) {
    $out_componentObj['sha256'] = $hashes[basename($file)];
}

$json_options = JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT;
if (isset($_GET['pretty'])) {
    $json_options |= JSON_PRETTY_PRINT;
}

print json_encode($out_componentObj, $json_options);
