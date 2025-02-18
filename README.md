# ŽSR Train View
This website, running in PHP, will display every currently running train inside Slovakia.

Disclaimer: 
  This website won't show trains that are private (e.g. cargo trains).
  ŽSR API isn't limited, but they may limit the IP address of your webserver, set the script updating to at least 45 seconds!
  The script _may_ violate ŽSR rules. You take full responsibility when deploying this!

## How to install
Download the files into your webserver, fill out the 'index.php' and 'getTrainData.php' and also 'fetch_vlaciky_data.php' with your database credentials and run the 'fetch_vlaciky_data.php' ! 
I suggest either putting it into a .sh script, for auto-update (less than a minute) or to a crontab (by-minute updates, best chance of not getting IP banned)
