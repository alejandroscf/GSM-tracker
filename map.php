<?php
    include "settings.php";
    include "gsm.php";

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
    $error = NULL;
    $soc = $info['soc'];

    // Get location from OpenCellID database
    $OpenLoc = OpenCellIDquery($mcc, $mnc, $lac, $ci, $sig);
    $error .= $OpenLoc->error;

    // Get location from Mozzilla Location Service (MSL)
    $MSLLoc = MSLquery($mcc, $mnc, $lac, $ci, $sig);
    $error .= $MSLLoc->error;
    
    if (isset($OpenLoc->lat)) { 
        $lat = $OpenLoc->lat;
    } elseif (isset($MSLLoc->lat)) {
        $lat = $MSLLoc->lat;
    } else {
        $error .= "<h3>Error getting location</h3>";
    }

    if (isset($OpenLoc->lon)) { 
        $lon = $OpenLoc->lon;
    } elseif (isset($MSLLoc->lon)) {
        $lon = $MSLLoc->lon;
    }

    if (isset($OpenLoc->range)) { 
        $range = $OpenLoc->range;
    } elseif (isset($MSLLoc->range)) {
        $range = $MSLLoc->range;
    }

    $mapUrl = 'http://www.openstreetmap.org';
    $mapUrl = $mapUrl . "?mlat=$lat&mlon=$lon#map=$zoom/$lat/$lon";
    $bbox_offset = 360/(pow(2,$zoom+1));
    $bbox = ($lon-$bbox_offset)."%2C".($lat-$bbox_offset)."%2C".($lon+$bbox_offset)."%2C".($lat+$bbox_offset);
    $marker = "$lat%2C$lon";
    $embedUrl = 'http://www.openstreetmap.org/export/embed.html';
    $embedUrl = $embedUrl . "?bbox=$bbox&layer=mapnik&marker=$marker"; 

    // Prepare history data
    $points1 = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $points2 = file($logPath.".old", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $points = array_merge($points2,$points1);
    unset($points1);
    unset($points2);


   
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
    <script type="text/javascript" src="Chart.js/Chart.js"></script>
    <script type="text/javascript" src="Chart.Scatter/Chart.Scatter.js"></script>

</head>
<body>
    <h2><?=$name?>'s status as of <?=$minutesAgo?> minutes ago:</h2>
    <h3>Battery: <?=isset($soc)?$soc."% (".$info['vcell']." V)</h3>
    <canvas id=\"batteryChart\" width=\"200\" height=\"200\"></canvas>
    ":"No information available</h3>"?>
    <canvas id="historyChart" width="400" height="200"></canvas>
    <h3>MCC: <?=$mcc?> MNC: <?=$mnc?> LAC: <?=$lac?> CI: <?=$ci?> RSSI: <?=$sig?></h3>
    <?=$error?>
    <!-- <iframe width="<?=$width?>" height="<?=$height?>" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?=$embedUrl?>" style="border: 1px solid black"></iframe> -->
    <div id="map" style="width: <?=$width?>px; height: <?=$height?>px"></div>
    <br/>
    <small><a href="<?=$mapUrl?>">View Larger Map</a></small>
        <script>
            initmap();
            <?php
            if (isset($lat, $lon, $range)) { 
                if (isset($OpenLoc->lat, $OpenLoc->lon)) echo "var OpenCellMarker = L.marker([$OpenLoc->lat, $OpenLoc->lon]).addTo(map);
                var OpenCellCircle = L.circle([$OpenLoc->lat, $OpenLoc->lon], $OpenLoc->range, {color: 'green'}).addTo(map);
                OpenCellMarker.bindPopup('<a href=\"http://opencellid.org/#action=filters.measuresOfGivenBaseStation&mcc=$mcc&mnc=$mnc&lac=$lac&cellid=$ci\" target=\"_blank\">OpenCellID</a>:</br>Lat: $OpenLoc->lat</br>Lon: $OpenLoc->lon');\n";
                if (isset($MSLLoc->lat, $MSLLoc->lon)) echo "var MSLMarker = L.marker([$MSLLoc->lat, $MSLLoc->lon]).addTo(map);
                var MSLCircle = L.circle([$MSLLoc->lat, $MSLLoc->lon], $MSLLoc->range, {color: 'orange'}).addTo(map);
                MSLMarker.bindPopup('MSL:</br>Lat: $MSLLoc->lat</br>Lon: $MSLLoc->lon');\n";
                echo "map.setView(new L.LatLng($lat, $lon),$zoom);
";
            };
            ?>  
            //omnivore.gpx('s2g.php?file=tmplocation.log').addTo(map);

            // Charts.js
            var batteryCtx = document.getElementById("batteryChart").getContext("2d");
            var data = [
                {
                    value: <?=isset($soc)?$soc:"0"?>,
                    color: "rgba(0,0,220,0.8)",
                    highlight: "rgba(0,0,220,1)",
                    label: "Charge",
                },
                {
                    value: <?=(100-$soc)?>,
                    color: "rgba(0,0,0,0)",
                    highlight: "rgba(0,0,0,0)",
                    label: "Empty",
                }
            ]
            var batteryChart = new Chart(batteryCtx).Pie(data, {showScale: true});
            
            var historyCtx = document.getElementById("historyChart").getContext("2d");
            var historyData = [{
                label: 'Battery level',
                strokeColor: '#F16220',
                pointColor: '#F16220',
                pointStrokeColor: '#fff',
                data: [
                <?php
                // Generate javascript array of battery use
                for ($i = count($points) - 24*4, $sizePoints = count($points); $i < $sizePoints ; $i++ ) {
                    $point = unserialize($points[$i]);
                    if (isset($point["timestamp"],$point["soc"])) {
                        echo "                {x: " . $point["timestamp"] . ", y: " . $point["soc"] . "},\n";
                    };
                };

            ?>
                ]
            },
            ];
            options = {
                pointDot: false,
                bezierCurve: false,
                scaleType: "date",
                useUtc: false,
                animation: false,
            }
            var historyChart = new Chart(historyCtx).Scatter(historyData,options);
        </script>

</body>
</html>
