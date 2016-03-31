<?php
class Location{
   public $lat;
   public $lon;
   public $range;
   public $url;
   public $failed;
   public $error;
};

function MSLquery($mcc, $mnc, $lac, $ci, $sig) {
    global $MSLkey;

    $Loc = new Location();
    $MSLUrl = "https://location.services.mozilla.com/v1/geolocate";
    $MSLUrl = $MSLUrl . "?key=$MSLkey";

    $MSLBody = json_encode(
      array (
       'cellTowers' => array (array(
        'radioType' => "gsm",
        'mobileCountryCode' => "$mcc",
        'mobileNetworkCode' => "$mnc",
        'locationAreaCode' => "$lac",
        'cellId' => "$ci",
        'signalStrength' => "$sig"
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
       $Loc->failed = TRUE;
       $Loc->error = "<h3>Failed MSL request $MSLUrl </h3></br><div>$MSLBody</div>";
       $Loc->error .= "<div>".print_r($http_response_header[0],TRUE)."</div>";
    } else {
       $MSLjsonRsp = json_decode($MSLjson);
       $Loc->lat = $MSLjsonRsp->location->lat;
       $Loc->lon = $MSLjsonRsp->location->lng;
       $Loc->range = $MSLjsonRsp->accuracy;
    };

    return $Loc;

}

function OpenCellIDquery($mcc, $mnc, $lac, $ci, $sig) {
   
    global $OpenCellIDkey;

    $Loc = new Location();
    $openCellUrl = 'http://opencellid.org/cell/get';
    $openCellUrl = $openCellUrl . "?key=$OpenCellIDkey&radio=GSM&format=xml";
    $openCellUrl = $openCellUrl . "&mcc=$mcc";
    $openCellUrl = $openCellUrl . "&mnc=$mnc";
    $openCellUrl = $openCellUrl . "&lac=$lac";
    $openCellUrl = $openCellUrl . "&cellid=$ci";

    $OpenCellXML = file_get_contents($openCellUrl);
    if ($OpenCellXML === FALSE) {
       $Loc->failed = TRUE;
       $Loc->error = "<h3>Failed OpenCellID request </h3><a href=$openCellUrl>$openCellUrl</a><div>".print_r($http_response_header[0],TRUE)."</div>";
    } else {
       $OpenXmlRsp = new SimpleXMLElement($OpenCellXML);
       $Loc->lat = $OpenXmlRsp->cell[0]['lat'];
       $Loc->lon = $OpenXmlRsp->cell[0]['lon'];
       $Loc->range = $OpenXmlRsp->cell[0]['range'];
    }


    return $Loc;
}
?>
