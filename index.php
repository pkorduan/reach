<html>
  <head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.7.0/bootstrap-table.min.js"></script>
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.7.0/bootstrap-table.min.css">  
    
    <script language="javascript" type="text/javascript">
      function exefunction() {
        var selectedSchema = document.getElementById("selectedSchema");

        var schema = selectedSchema.options[selectedSchema.selectedIndex].value;

        var coords = document.getElementById("coords").value;

        var minutes = document.getElementById("minutes").value;

        var intervall = document.getElementById("intervall").value;

        var target = document.getElementById("target").value;

        var hullChkbx = document.getElementById("hull").checked;

        if (hullChkbx == true)
          var hull = "true";
        else
          var hull = "false";
        
        var featCollChkbx = document.getElementById("featColl").checked;
        if (featCollChkbx == true)
          var featColl = "true";
        else
          var featColl = "false";
        
        var createTabsChkbx = document.getElementById("createTabs").checked;

        if (createTabsChkbx == true)
          var createTabs = "true";
        else
          var createTabs = "false";
        
        var diffPolysChkbx = document.getElementById("diffPolys").checked;
        if (diffPolysChkbx == true)
          var diffPolys = "true";
        else
          var diffPolys = "false";

        window.location = 'reach.php?schema=' + schema + '&coords=' + coords + '&minutes=' + minutes + '&intervall=' + intervall + '&hull=' + hull + '&target=' + target + '&featColl=' + featColl + '&createTabs=' + createTabs + '&diffPolys=' + diffPolys;
      }
        </script>
    <title>Pflegeportal LUP Erreichbarkeitsanalyse</title>
  </head>
    <body>
    <div class="container">
      <h4>Schemaauswahl</h4><?php
      include( dirname(__FILE__) . "/conf/database_conf.php");
      $schema_sql = "SELECT schema_name FROM information_schema.schemata ORDER BY schema_name";
      #echo $schema_sql;
      #echo "<br>";
      $result = pg_query($db_conn, $schema_sql); ?>
      <select class="form-control" id="selectedSchema" size="15"><?php
      while ($row = pg_fetch_array($result)) { ?>
        <option value="<?php echo $row[0]; ?>"><?php echo $row[0]; ?></option><?php
      } ?>
      </select>
      <?php #print_r($result); ?>

      <h4>Koordinaten</h4>
      <input type="text" id="coords" name="coords" list="coordsName"/>
      Format: <i>lat,lon</i>
      <datalist id="coordsName">
        <option value="53.6,11.4">53.6,11.4</option>
      </datalist>

      <h4>Minuten</h4>
      <input type="text" id="minutes" name="minutes" list="minutesName"/>
      <datalist id="minutesName">
        <option value="10">10</option>
        <option value="15">15</option>
      </datalist>

      <h4>Intervall in Minuten</h4>
      <input type="text" id="intervall" name="intervall" list="intervallName"/>
      <datalist id="intervallName">
        <option value="3">3</option>
        <option value="5">5</option>
      </datalist>

      <h4>Target (%, z.B. "0.7") f&uuml;r ST_ConcaveHull</h4>
      <input type="text" id="target" name="target" list="target"/>
      <datalist id="targetName">
        <option value="0.9">0.9</option>
        <option value="0.7">0.7</option>
        <option value="0.5">0.5</option>
      </datalist>

      <div class="checkbox">
        <label><input type="checkbox" id="hull">Nur &auml;u&szlig;ere H&uuml;llpunkte (sonst komplette Punktwolke)</label>
      </div>
      <div class="checkbox">
        <label><input type="checkbox" checked="checked" id="featColl">Ausgabe: FeatureCollection (sonst MultiPolygon)</label>
      </div>
      <div class="checkbox">
        <label><input type="checkbox" id="createTabs">Lege Tabellen in Datenbank an</label>
      </div>
      <div class="checkbox">
        <label><input type="checkbox" checked="checked" id="diffPolys">Gebe Differenzpolygone bei Intervallen aus ("gelochte" Polygone, sonst: vollst&auml;ndige)</label>
      </div>
      <div class="text-center" id="queryButton">
        <!--<button type="submit" class="btn btn-primary btn-sm" id="queryNERC" onclick="document.location.href='use-xmi2db.php?truncate=1&file=xplanerweitert20150609.xmi&schema=xplan_argotest&basepackage=Raumordnungsplan_Kernmodell'"><span class="glyphicon glyphicon-ok"> </span> Suche passende Begriffe</button>-->
        <button type="submit" class="btn btn-primary btn-sm" onclick="exefunction()"><span class="glyphicon glyphicon-ok"> </span> Erzeuge GeoJSON</button>
      </div>
    </div>
  </body>
</html>