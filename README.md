GSM-Tracker
===========

GSM-Tracker is a small bit of PHP to accept location pings (Cell Id from a GSM device), keep a log of positions and display a map with that location. Also keep track of battery status of the device.

API keys
--------

In order to get the geographic location from the gsm cell data, you need a database of known cell towers ai its location. We'll query two databases OpenCellId and Mozilla Location Service (MSL) through theirs APIs.

You need API keys in order to query their databases:
- OpenCellID: You can request it in the web page: http://opencellid.org/#action=database.requestForApiKey

- Mozilla Location Service (MSL): You can request it by email: https://location.services.mozilla.com/api

Quick Setup guide:
------------------
1. Clone the repo.
1. Rename settings.php.sample to settings.php and set your keys and other parameters.
1. Deploy it in your server or hosting.
1. Set your device to do http requests to the server with the following parameters: ```http://<yourserver>/tracker.php?key=KEY&mcc=MCC&mnc=MNC&lac=LAC&ci=CI&sig=RSSI[&vcell=VCELL][&soc=STATEofCHARGE]```
1. You are already keeping track of your device location (yay!).
