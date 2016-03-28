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
    $sig = $info['sig'];

    // Get location from OpenCellID database
    $openCellUrl = 'http://opencellid.org/cell/get';
    $openCellUrl = $openCellUrl . "?key=$OpenCellIDkey&radio=GSM&format=xml";
    $openCellUrl = $openCellUrl . "&mcc=$mcc";
    $openCellUrl = $openCellUrl . "&mnc=$mnc";
    $openCellUrl = $openCellUrl . "&lac=$lac";
    $openCellUrl = $openCellUrl . "&cellid=$ci";

    $OpenCellXML = file_get_contents($openCellUrl);
    if ($OpenCellXML === FALSE) {
       $error = "<h3>Failed OpenCellID request $openCellUrl </h3>";
    } else {
       $OpenXmlRsp = new SimpleXMLElement($OpenCellXML);
       $lat = $OpenXmlRsp->cell[0]['lat'];
       $lon = $OpenXmlRsp->cell[0]['lon'];
       $range = $OpenXmlRsp->cell[0]['range'];
    }

    // Get location from Mozzilla Location Service (MSL)
    $MSLUrl = "https://location.services.mozilla.com/v1/geolocate";
    $MSLUrl = $MSLUrl . "?key=$MSLkey";
    
    $MSLBody = json_encode(
      array (
       'cellTowers' => array (array(
        'radioType' => "gsm",
        'mobileCountryCode' => "214",
        'mobileNetworkCode' => "7",
        'locationAreaCode' => "5006",
        'cellId' => "6563",
        'signalStrength' => "-63"
       ))
      )
    );

    $MSLOpts = array(
       'http'=>array(
          'method' => "POST",
          'content' => $MSLBody
       )
    );
    $MSLcontext = stream_context_create($MSLOpts);

    $MSLjson = file_get_contents($MSLUrl,false,$MSLcontext);
    //$MSLjson = file_get_contents($MSLUrl);
    if ($MSLjson === FALSE) {
       $error = "<h3>Failed MSL request $MSLUrl </h3></br><div>$MSLBody</div>";
    } else {
       $MSLjsonRsp = json_decode($MSLjson);
       $MSLlat = $MSLjsonRsp->location->lat;
       $MSLlon = $MSLjsonRsp->location->lng;
       $MSLrange = $MSLjsonRsp->accuracy;
    }
    

    $mapUrl = 'http://www.openstreetmap.org';
    $mapUrl = $mapUrl . "?mlat=$lat&mlon=$lon#map=$zoom/$lat/$lon";
    $bbox_offset = 360/(pow(2,$zoom+1));
    $bbox = ($lon-$bbox_offset)."%2C".($lat-$bbox_offset)."%2C".($lon+$bbox_offset)."%2C".($lat+$bbox_offset);
    $marker = "$lat%2C$lon";
    $embedUrl = 'http://www.openstreetmap.org/export/embed.html';
    $embedUrl = $embedUrl . "?bbox=$bbox&layer=mapnik&marker=$marker"; 

   
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
    <h3>RSSI: <?=$sig?></h3>
    <?=$error?>
    <!-- <iframe width="<?=$width?>" height="<?=$height?>" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?=$embedUrl?>" style="border: 1px solid black"></iframe> -->
    <div id="map" style="width: <?=$width?>px; height: <?=$height?>px"></div>
    <br/>
    <small><a href="<?=$mapUrl?>">View Larger Map</a></small>
        <script>
            initmap();
            var OpenCellMarker = L.marker([<?=$lat?>, <?=$lon?>]).addTo(map);
            var OpenCellCircle = L.circle([<?=$lat?>, <?=$lon?>], <?=$range?>, {color: 'green'}).addTo(map);
            OpenCellMarker.bindPopup("OpenCellID:</br>Lat: <?=$lat?></br>Lon: <?=$lon?>");
            var MSLMarker = L.marker([<?=$MSLlat?>, <?=$MSLlon?>]).addTo(map);
            var MSLCircle = L.circle([<?=$MSLlat?>, <?=$MSLlon?>], <?=$MSLrange?>, {color: 'orange'}).addTo(map);
            MSLMarker.bindPopup("MSL:</br>Lat: <?=$MSLlat?></br>Lon: <?=$MSLlon?>");
            map.setView(new L.LatLng(<?=$lat?>, <?=$lon?>),<?=$zoom?>);
            //omnivore.gpx('s2g.php?file=tmplocation.log').addTo(map);
        </script>

</body>
</html>
