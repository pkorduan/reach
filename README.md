# reach
Reachability Analyse
Provide a page to define a request for a reachability analyse and
functions to handle the response from the reachability analyse service.
index.php set parameter for a request and call reach.php.
reach.php send the request to the service osm2po to get points
from the end of shortes pathes from a defined center point.
reach.php convert the points to a polygon that covers all points
reachable in the requested time from the center point allong roads from
Open Street Map Project. http://openstreetmap.org.

Installation
- Install osm2po service from http://osm2po.de.
- Choose your region from http://download.geofabrik.de and start the service.
- Install postgres from http://postgres.org with PostGIS Extension from
http://postgis.org and create a database with CREATE EXTENSION postgis.
- Clone this project into a web directory which supports PHP 5.
- Copy files conf/database_conf_sample.php to conf/database_conf.php
and set your credentials for a postgres database.
- Copy files conf/reach_conf_sample.php to conf/reach_conf.php and
change the values of the constants to your situation.

Run
Call index.php from your project. Set the schema to store point and polygon
data, some parameters and hit the button "Erzeuge GeoJSON"