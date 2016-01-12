<?php
  /*
  * JSONP support implemented here
  * @param string    - json
  * @return string   - json OR JSONP, i.e. javascript
  */
  //See http://stackoverflow.com/questions/1678214/javascript-how-do-i-create-jsonp
  include( dirname(__FILE__) . "/conf/database_conf.php");
  
  function geojson2multipoint($json) {
    return 'MultiPoint(' . implode(
      array_map(
        function($c) {
          return '(' . $c[0] . ' ' . $c[1] . ')';
        },
        json_decode($json)->coordinates
      ),
      ','
    ) . ')';
  }

  function output_json($json) {
    if(array_key_exists('callback', $_GET)){
      header('Content-Type: text/javascript; charset=utf8');
      header('Access-Control-Allow-Origin: http://www.example.com/');
      header('Access-Control-Max-Age: 3628800');
      header('Access-Control-Allow-Methods: GET');

      $callback = $_GET['callback'];
      echo $callback.'('.$json.');';
    }
    else {
      // normal JSON string
      header('Content-Type: application/json; charset=utf8');
      echo $json;
    }
  }
  
  function buildFeature($geo,$minutes) {
    $feature = array();
    $feature_sub = array("type" => "Feature", "geometry" => $geo, "properties" => array("minutes" => $minutes));
    array_push($feature, $feature_sub);
    return $feature_sub;
  }
  
  function buildFeatureCollection($features) {
    $json_output = array("type" => "FeatureCollection", "features" => $features);
    return $json_output;
  }
  
  function buildMultiPolygon($geo) {
    $json_output = array("type" => "MultiPolygon", "coordinates" => $geo);
    return $json_output;
  }
  
  function analyzeReachability($schema, $coords, $hours, $target, $hull, $multipoly, $diffPolysVal) {
    global $db_conn;
    
    //1. get hull (?) of points for given coords and give timespan
    //Btw. cloud points is "cmd=fx"
    $osm2poreq = urldecode(
      "http://localhost:8888/Osm2poService?cmd=" . $hull . "&source=" . $coords . "&findShortestPath=false&maxCost=" . $hours . "&format=geojson"
    );
    #echo '<br>' . $osm2poreq;
    $response = file_get_contents($osm2poreq);
    #echo '<br>Rückgabe: ' . $response;

    //2. temporarly insert points into PostGIS db
    $postgis_obj = "
      INSERT INTO " . $schema . ".temp_reach_multipoint (
        the_geom
      )
      VALUES (
        ST_GeomFromText('" . geojson2multipoint($response) . "', 4326)
      )
      RETURNING id
    ";
    #echo '<br>Insert Points: ' . $postgis_obj;
    $result = pg_query($db_conn, $postgis_obj);
    $row = pg_fetch_array($result);
    $id_multipoint = $row[0];
    #echo '<br>' . $id_multipoint;
        
    //3. Compute hull using PostGIS function and store it in temp table
    $postgis_hull = "
      INSERT INTO " . $schema . ".temp_reach_hull (
        the_geom
      )
      SELECT
        ST_ConcaveHull(the_geom, ".$target.")
      FROM " .
        $schema . ".temp_reach_multipoint 
      WHERE
        id = " . $id_multipoint . "
      RETURNING id
    ";
    #echo '<br>Create concave hull: ' . $postgis_hull;

    $result_hull = pg_query($db_conn, $postgis_hull);
    $row_hull = pg_fetch_array($result_hull);
    $id_hull = $row_hull[0];
    #echo '<br>Id concave hull: ' . $id_hull;
    
    if ($diffPolysVal=="true")
      return $id_hull;
    else {
      //4. Get GeoJSON representation for hull
      $postgis_hull_geojson = "
        SELECT
          ST_AsGeoJSON(the_geom) AS Geojson
        FROM " .
          $schema . ".temp_reach_hull
        WHERE
          id = " . $id_hull . ";
      ";
      # echo '<br>Get geojson: ' . $postgis_hull_geojson;
      $result_hull_geojson = pg_query($db_conn, $postgis_hull_geojson);
      $row_hull_geojson = pg_fetch_array($result_hull_geojson);
      $hull_geojson = $row_hull_geojson[0];
      # echo '<br>hull geojson: ' . $hull_geojson;
          
      //X. delete temp hull from PostGIS db
      clearEntry($id_hull, $schema, "temp_reach_hull");
      
      //output_json(json_encode(json_decode($hull_geojson), JSON_PRETTY_PRINT));
      //output_json(json_encode(json_decode($hull_geojson)));
      if ($multipoly)
        return json_decode($hull_geojson)->coordinates;
      else
        return $hull_geojson;
    }
    //X. delete temp multipoint from PostGIS db
    clearEntry($id_multipoint, $schema, "temp_reach_multipoint");
  }
  
  function createTables($schema) {
    global $db_conn;
    
    //Load SQL dump file and replace "schema_name" placeholder with desired schema name
    $sql_dump = str_replace(
      'schema_name',
      $schema,
      file_get_contents('sql/db-schema.sql')
    );
    #echo '<br>SQL Dump: ' . $sql_dump;

    pg_query($db_conn, $sql_dump);
  }
  
  function clearEntry($id, $schema, $table) {
    global $db_conn;
    
    $postgis_del = "
      DELETE
      FROM " .
        $schema . "." . $table . "
      WHERE
        id = " . $id;
    //echo $postgis_del.'<br>';
    #$result_del = pg_query($db_conn, $postgis_del);
    #$row_del = pg_fetch_array($result_del);
    //echo $row_del[0].'<br>';
  }

  $schema = $_REQUEST['schema'];
  #echo '<br>Schema: ' . $schema;
  
  $coords = $_REQUEST['coords'];
  #echo 'Coords: ' . $coords;
  
  $minutes = $_REQUEST['minutes'];
  #echo '<br>Minutes: ' . $minutes;
  $hours = $minutes / 60;
  #echo '<br>Hours: ' . $hours;
  
  $intervall = $_REQUEST['intervall'];
  #echo '<br>Intervall: ' . $intervall;
  
  $target = $_REQUEST['target'];
  #echo '<br>Target: ' . $target;
  
  $hullVal = $_REQUEST['hull'];

  if ($hullVal=="true")
    $hull = "fh";
  else if ($hullVal=="false")
    $hull = "fx";
  else
    $hull = "error";
  
  $featCollVal = $_REQUEST['featColl'];
  
  if ($featCollVal=="true")
    $featColl = "fh";
  else if ($featCollVal=="false")
    $featColl = "fx";
  else
    $featColl = "error";
  //echo $hull.'<br>';

  //Create Tables or not
  $createTabsVal = $_REQUEST['createTabs'];
  //echo $createTabsVal;
  if ($_REQUEST['createTabs']=="true")
    createTables($schema);
  
  $diffPolysVal = $_REQUEST['diffPolys'];
  
  global $db_conn;
  
  if ($intervall == "" || $intervall == 1)
    output_json(
      json_encode(
        buildFeatureCollection(
          array(
            buildFeature(
              json_decode(
                analyzeReachability($schema,$coords,$hours,$target,$hull, null, null)
              ),
              $minutes
            )
          )
        )
      )
    );
  else {
    $intervallDiff = $minutes / $intervall;
    //echo $intervallDiff.'<br>';
    $geo = array();
    $ids = array();
    for ($i = 1; $i <= $intervall; $i++) {
      if ($i == 1) 
        $minutesCount = $minutes;
      else
        $minutesCount = $minutesCount - $intervallDiff;
      //echo $minutesCount.'<br>';
      if ($featCollVal=="true")
        $multipoly = false;
      if ($featCollVal=="false")
        $multipoly = true;
      
      //Speicher HullPolygon in DB und gebe ID zurück, damit später ST_SymDifference gemacht werden kann
      if ($diffPolysVal=="true")
        $id = analyzeReachability($schema, $coords, $minutesCount / 60, $target, $hull, $multipoly, $diffPolysVal);
      //Wenn ST_SymDifference nicht gebraucht wird, gebe HullPolygon zurück undf pack es in ein Array
      else {
        $hullReach = analyzeReachability($schema, $coords, $minutesCount / 60, $target, $hull, $multipoly, null);
        if ($featCollVal=="true")
          array_push($geo, buildFeature(json_decode($hullReach),$minutesCount));
        if ($featCollVal=="false")
          array_push($geo, $hullReach);
      }
      array_push($ids, $id);
    }
    
    if ($diffPolysVal=="true") {
      //Erzeuge "gelochte" Polygone mit ST_SymDifference
      for ($i = 1; $i <= $intervall; $i++) {
        if ($i == 1)
          $minutesCount = $minutes;
        else
          $minutesCount = $minutesCount - $intervallDiff;
        
        //1. Polygon (das kleinste mit dem geringsten Zeitwert) wird so wie es ist abgespeichert
        if ($i==$intervall) {
          $postgis_diff = "
            UPDATE
              temp_reach_hull
            SET
              diff = st_multi(the_geom)
            WHERE
              id=" . $ids[$intervall - 1] . "
            RETURNING ST_AsGeoJSON(diff)";
          //echo $postgis_diff.'<br>';
          $result_geojson = pg_query($db_conn, $postgis_diff);
          $row_geojson = pg_fetch_array($result_geojson);
          $geojson = $row_geojson[0];
          
          //echo "geojson Nr. ".$i." :".print_r(json_decode($geojson)->coordinates)."<br>";
          
          //echo "ACHTUNG: ".$geojson;
          if ($featCollVal=="true") array_push($geo, buildFeature(json_decode($geojson),$minutesCount));
          if ($featCollVal=="false") array_push($geo, json_decode($geojson)->coordinates);
        }
        //Bei alle weiteren wird die ST_SymDifference zwischen den einzelnen Zeitscheiben gebildet
        else {
          //echo "mind 2";
          $postgis_diff = "
            UPDATE
              temp_reach_hull
            SET
              diff = (
                SELECT
                  st_multi(
                    ST_SymDifference(t1.the_geom, t2.the_geom)
                  ) AS diff
                FROM
                  (
                    SELECT
                      the_geom
                    FROM
                      temp_reach_hull
                    WHERE
                      id=" . $ids[$intervall-$i-1] . "
                  ) AS t1,
                  (
                    SELECT
                      the_geom
                    FROM
                      temp_reach_hull
                    WHERE
                      id=" . $ids[$intervall - $i] . "
                  ) AS t2
                )
              WHERE
                id=" . $ids[$intervall - $i - 1] . "
              RETURNING
                ST_AsGeoJSON(diff)
            ";
          //echo $postgis_diff.'<br>';
          $result_geojson = pg_query($db_conn, $postgis_diff);
          $row_geojson = pg_fetch_array($result_geojson);
          $geojson = $row_geojson[0];
          
          //echo "geojson Nr. ".$i." :".print_r(json_decode($geojson)->coordinates)."<br>";
          
          if ($featCollVal=="true") array_push($geo, buildFeature(json_decode($geojson),$minutesCount));
          if ($featCollVal=="false") array_push($geo, json_decode($geojson)->coordinates);
        }
      }
    }
      
    //X. delete temp hulls from PostGIS db
    foreach($ids as $id)
      clearEntry($id, $schema, "temp_reach_hull");
    
    //baue FeatureCollection oder MultiPolygon mit den HullPolygonen im Array $geo und gebe am Ende das Ergebnis als GeoJSON aus
    if ($featCollVal=="true")
      $json_output = buildFeatureCollection($geo);
    if ($featCollVal=="false")
      $json_output = buildMultiPolygon($geo);

    output_json(
      json_encode(
        $json_output
      )
    );
  }
?>