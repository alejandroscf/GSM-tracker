<?php
    include "settings.php";

    //$loc_string = file_get_contents($filePath);
    //$info = unserialize($loc_string);
    $strings = file($logPath, FILE_IGNORE_NEW_LINES);
    $info = unserialize(end($strings));
    $timestampSeconds = round($info['timestamp']/1000,0);
    $minutesAgo = round((time() - $timestampSeconds)/60,1);
    //$lat = $info['lat'];
    //$lon = $info['lon'];
    $mcc = $info['mcc'];
    $mnc = $info['mnc'];
    $lac = $info['lac'];
    $ci = $info['ci'];

    $mapUrl = 'http://www.openstreetmap.org';
    $mapUrl = $mapUrl . "?mlat=$lat&mlon=$lon#map=$zoom/$lat/$lon";
    $bbox_offset = 360/(pow(2,$zoom+1));
    $bbox = ($lon-$bbox_offset)."%2C".($lat-$bbox_offset)."%2C".($lon+$bbox_offset)."%2C".($lat+$bbox_offset);
    $marker = "$lat%2C$lon";
    $embedUrl = 'http://www.openstreetmap.org/export/embed.html';
    $embedUrl = $embedUrl . "?bbox=$bbox&layer=mapnik&marker=$marker"; 

    $openCellUrl = 'http://opencellid.org/cell/get';
    $openCellUrl = $openCellUrl . "?key=$OpenCellIDkey&radio=GSM&format=xml";
    $openCellUrl = $openCellUrl . "&mcc=$mcc";
    $openCellUrl = $openCellUrl . "&mnc=$mnc";
    $openCellUrl = $openCellUrl . "&lac=$lac";
    $openCellUrl = $openCellUrl . "&cellid=$ci";

    $cellPosition = file_get_contents($openCellUrl);
    if ($cellPosition === FALSE) {
       $error = "<h3>Failed OpenCellID request $openCellUrl </h3>";
    } else {
       $xmlRsp = new SimpleXMLElement($cellPosition);
       $lat = $xmlRsp->cell[0]['lat'];
       $lon = $xmlRsp->cell[0]['lon'];
    }
   
?>
<html>
<head>
    <title><?=$name?>'s Location</title>
    <meta http-equiv="refresh" content="30" />
    <meta charset="utf-8" />
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0">-->
    <link rel="stylesheet" href="leaflet/leaflet.css" />
    <script type="text/javascript" src="leaflet/leaflet.js"></script>
    <script type="text/javascript" src="leafletembed.js"></script>
    <script type="text/javascript" src="leaflet-omnivore/leaflet-omnivore.min.js"></script>

</head>
<body>
    <h2><?=$name?>'s status as of <?=$minutesAgo?> minutes ago:</h2>
    <h3>Battery: <?=$info['soc']?> % (<?=$info['vcell']?> V)</h3>
    <h3>MCC: <?=$mcc?></h3>
    <h3>MNC: <?=$mnc?></h3>
    <h3>LAC: <?=$lac?></h3>
    <h3>CI: <?=$ci?></h3>
    <?=$error?>
    <!-- <iframe width="<?=$width?>" height="<?=$height?>" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?=$embedUrl?>" style="border: 1px solid black"></iframe> -->
    <div id="map" style="width: <?=$width?>px; height: <?=$height?>px"></div>
    <br/>
    <small><a href="<?=$mapUrl?>">View Larger Map</a></small>
        <script>
            initmap();
            L.marker([<?=$lat?>, <?=$lon?>]).addTo(map)
            map.setView(new L.LatLng(<?=$lat?>, <?=$lon?>),<?=$zoom?>);
            //omnivore.gpx('s2g.php?file=tmplocation.log').addTo(map);
        </script>

</body>
</html>
