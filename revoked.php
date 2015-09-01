<?php

require_once 'config.php';

header('Content-Type: application/json');

if (isset($_GET['fingerprints'])) {
    $fingerprints = explode(',', $_GET['fingerprints']);
} else {
    $fingerprints = Array();
}

$json = file_get_contents($fingerprintsJSON);
$in_fingerprintsObj = json_decode($json, true);

$out_fingerprintsObj = new ArrayObject();

foreach ($fingerprints as $fingerprint) {
    if (!isset($in_fingerprintsObj[$fingerprint])) {
        continue;
    }

    $in_fingerprintObj = $in_fingerprintsObj[$fingerprint];

    if (!isset($in_fingerprintObj["status"])) {
        continue;
    }

    $status = $in_fingerprintsObj[$fingerprint]["status"];
    if ($status != "valid" && $status != "revoked") {
        continue;
    }

    $out_fingerprintsObj[$fingerprint] = $status;
}

$json_options = JSON_UNESCAPED_SLASHES;
if (isset($_GET['pretty'])) {
    $json_options |= JSON_PRETTY_PRINT;
}

print json_encode($out_fingerprintsObj, $json_options);
