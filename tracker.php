<?php
    require "settings.php";
    require "serialized-to-gpx.php";
    ignore_user_abort("true");
    $key = $_GET['key'];
    if($key != $secretKey) {
        print "key doesn't match";
        return;
    }

    $info['mcc'] = $_GET['mcc'];
    $info['mnc'] = $_GET['mnc'];
    $info['lac'] = $_GET['lac'];
    $info['ci'] = $_GET['ci'];
    $info['vcell'] = $_GET['vcell'];
    $info['soc'] = $_GET['soc'];
    $info['sig'] = $_GET['sig'];
    $info['timestamp'] = time()*1000;


    $loc_string = file_get_contents($filePath);
    $old = unserialize($loc_string);
    $newPath = $gpxDirectory . date("Y-m-d",round($old['timestamp']/1000)) . $logName;
    // Separa el fichero si han pasado 24 horas
    if (date("d",round($old['timestamp']/1000)) != date("d")) {
       copy($logPath, $logPath . ".old");
       rename($logPath, $newPath);
    }
    $fh = fopen($logPath, 'a');
    fwrite($fh, serialize($info) . "\n");
    fclose($fh);
    $fh = fopen($filePath, 'w');
    fwrite($fh, serialize($info));
    fclose($fh);
?>
