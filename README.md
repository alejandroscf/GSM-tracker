GSM-Tracker
===========

GSM-Tracker is a small bit of PHP to accept location pings (Cell Id from a GSM device), keep a log of positions and display a map with that location. Also keep track of battery status.

You need an OpenCellID API key in order to query their data base for the location from the gsm cell info. You can request it in the web page: http://opencellid.org/#action=database.requestForApiKey

You need a Mozilla Location Service (MSL) API key in order to query their database for the location from the gsm cell info. You can request by email: https://location.services.mozilla.com/api

TLDR:
=====

The tracked device must do http request with the following parameters:
`http://<yourserver>/tracker.php?key=KEY&mcc=MCC&mnc=MNC&lac=LAC&ci=CI&sig=RSSI[&vcell=VCELL][&soc=STATEofCHARGE]`
