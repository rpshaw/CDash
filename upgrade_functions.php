<?php
// Helper function to alter a table
function AddTableField($table,$field,$mySQLType,$pgSqlType,$default)
{
  include("cdash/config.php");

  $sql = '';
  if($default !== false)
    {
    $sql = " DEFAULT '".$default."'";
    }

  $query = pdo_query("SELECT ".$field." FROM ".$table." LIMIT 1");
  if(!$query)
    {
    add_log("Adding $field to $table","AddTableField");
    if($CDASH_DB_TYPE == "pgsql")
      {
      pdo_query("ALTER TABLE \"".$table."\" ADD \"".$field."\" ".$pgSqlType.$sql);
      }
    else
      {
      pdo_query("ALTER TABLE ".$table." ADD ".$field." ".$mySQLType.$sql);
      }

    add_last_sql_error("AddTableField");
    add_log("Done adding $field to $table","AddTableField");
    }
}

/** Remove a table field */
function RemoveTableField($table,$field)
{
  include("cdash/config.php");
  $query = pdo_query("SELECT ".$field." FROM ".$table." LIMIT 1");
  if($query)
    {
    add_log("Droping $field from $table","DropTableField");
    if($CDASH_DB_TYPE == "pgsql")
      {
      pdo_query("ALTER TABLE \"".$table."\" DROP COLUMN \"".$field."\"");
      }
    else
      {
      pdo_query("ALTER TABLE ".$table." DROP ".$field);
      }
    add_last_sql_error("DropTableField");
    add_log("Done droping $field from $table","DropTableField");
    }
}

// Rename a table vield
function RenameTableField($table,$field,$newfield,$mySQLType,$pgSqlType,$default)
{
  include("cdash/config.php");
  $query = pdo_query("SELECT ".$field." FROM ".$table." LIMIT 1");
  if($query)
    {
    add_log("Changing $field to $newfield for $table","RenameTableField");
    if($CDASH_DB_TYPE == "pgsql")
      {
      pdo_query("ALTER TABLE \"".$table."\" RENAME \"".$field."\" TO \"".$newfield."\"");
      pdo_query("ALTER TABLE \"".$table."\" ALTER COLUMN \"".$newfield."\" TYPE ".$pgSqlType);
      pdo_query("ALTER TABLE \"".$table."\" ALTER COLUMN \"".$newfield."\" SET DEFAULT ".$default);
      }
    else
      {
      pdo_query("ALTER TABLE ".$table." CHANGE ".$field." ".$newfield." ".$mySQLType." DEFAULT '".$default."'");
      add_last_sql_error("RenameTableField");
      }
    add_log("Done renaming $field to $newfield for $table","RenameTableField");
    }
}

// Helper function to add an index to a table
function AddTableIndex($table,$field)
{
  include("cdash/config.php");

  $index_name = $field;
  // Support for multiple column indices
  if (is_array($field))
    {
    $index_name = implode("_", $field);
    $field = implode(",", $field);
    }

  if(!pdo_check_index_exists($table,$field))
    {
    add_log("Adding index $field to $table","AddTableIndex");
    if($CDASH_DB_TYPE == "pgsql")
      {
      @pdo_query("CREATE INDEX $index_name ON $table ($field)");
      }
    else
      {
      pdo_query("ALTER TABLE $table ADD INDEX $index_name ($field)");
      add_last_sql_error("AddTableIndex");
      }
    add_log("Done adding index $field to $table","AddTableIndex");
    }
}

// Helper function to remove an index to a table
function RemoveTableIndex($table,$field)
{
  include("cdash/config.php");
  if(pdo_check_index_exists($table,$field))
    {
    add_log("Removing index $field from $table","RemoveTableIndex");

    if($CDASH_DB_TYPE == "pgsql")
      {
      pdo_query("DROP INDEX ".$table."_".$field."_idx");
      }
    else
      {
      pdo_query("ALTER TABLE ".$table." DROP INDEX ".$field);
      }
    add_log("Done removing index $field from $table","RemoveTableIndex");
    add_last_sql_error("RemoveTableIndex");
    }
}

// Helper function to modify a table
function ModifyTableField($table,$field,$mySQLType,$pgSqlType,$default,$notnull,$autoincrement)
{
  include("cdash/config.php");

  //$check = pdo_query("SELECT ".$field." FROM ".$table." LIMIT 1");
  //$type  = pdo_field_type($check,0);
  //add_log($type,"ModifyTableField");
  if(1)
    {
    add_log("Modifying $field to $table","ModifyTableField");
    if($CDASH_DB_TYPE == "pgsql")
      {
      // ALTER TABLE "buildfailureargument" ALTER COLUMN "argument" TYPE VARCHAR( 255 );
      // ALTER TABLE "buildfailureargument" ALTER COLUMN "argument" SET NOT NULL;
      // ALTER TABLE "dynamicanalysisdefect" ALTER COLUMN "value" SET DEFAULT 0;
      pdo_query("ALTER TABLE \"".$table."\" ALTER COLUMN  \"".$field."\" TYPE ".$pgSqlType);
      if($notnull)
        {
        pdo_query("ALTER TABLE \"".$table."\" ALTER COLUMN  \"".$field."\" SET NOT NULL");
        }
      if(strlen($default)>0)
        {
        pdo_query("ALTER TABLE \"".$table."\" ALTER COLUMN  \"".$field."\" SET DEFAULT ".$default);
        }
      if($autoincrement)
        {
        pdo_query("DROP INDEX \"".$table."_".$field."_idx\"");
        pdo_query("ALTER TABLE \"".$table."\" ADD PRIMARY KEY (\"".$field."\")");
        pdo_query("CREATE SEQUENCE \"".$table."_".$field."_seq\"");
        pdo_query("ALTER TABLE  \"".$table."\" ALTER COLUMN \"".$field."\" SET DEFAULT nextval('".$table."_".$field."_seq')");
        pdo_query("ALTER SEQUENCE \"".$table."_".$field."_seq\" OWNED BY \"".$table."\".\"".$field."\"");
        }
      }
    else
      {
      //ALTER TABLE dynamicanalysisdefect MODIFY value INT NOT NULL DEFAULT 0;
      $sql = "ALTER TABLE ".$table." MODIFY ".$field." ".$mySQLType;
      if($notnull)
        {
        $sql .= " NOT NULL";
        }
      if(strlen($default)>0)
        {
        $sql .= " DEFAULT '".$default."'";
        }
      if($autoincrement)
        {
        $sql .= " AUTO_INCREMENT";
        }
      pdo_query($sql);
      }
    add_last_sql_error("ModifyTableField");
    add_log("Done modifying $field to $table","ModifyTableField");
    }
}

// Helper function to add an index to a table
function AddTablePrimaryKey($table,$field)
{
  include("cdash/config.php");
  add_log("Adding primarykey $field to $table","AddTablePrimaryKey");
  if($CDASH_DB_TYPE == "pgsql")
    {
    pdo_query("ALTER TABLE \"".$table."\" ADD PRIMARY KEY (\"".$field."\")");
    }
  else
    {
    pdo_query("ALTER IGNORE TABLE ".$table." ADD PRIMARY KEY ( ".$field." )");
    }
  //add_last_sql_error("AddTablePrimaryKey");
  add_log("Done adding primarykey $field to $table","AddTablePrimaryKey");
}

// Helper function to add an index to a table
function RemoveTablePrimaryKey($table)
{
  include("cdash/config.php");
  add_log("Removing primarykey from $table","RemoveTablePrimaryKey");
  if($CDASH_DB_TYPE == "pgsql")
    {
    pdo_query("ALTER TABLE \"".$table."\" DROP CONSTRAINT \"value_pkey\"");
    pdo_query("ALTER TABLE \"".$table."\" DROP CONSTRAINT \"".$table."_pkey\"");
    }
  else
    {
    pdo_query("ALTER TABLE ".$table." DROP PRIMARY KEY");
    }
  //add_last_sql_error("RemoveTablePrimaryKey");
  add_log("Done removing primarykey from $table","RemoveTablePrimaryKey");
}



/** Compress the notes. Since they are almost always the same form build to build */
function CompressNotes()
{
  // Rename the old note table
  if(!pdo_query("RENAME TABLE note TO notetemp"))
    {
    echo pdo_error();
    echo "Cannot rename table note to notetemp";
    return false;
    }

  // Create the new note table
  if(!pdo_query("CREATE TABLE note (
     id bigint(20) NOT NULL auto_increment,
     text mediumtext NOT NULL,
     name varchar(255) NOT NULL,
     crc32 int(11) NOT NULL,
     PRIMARY KEY  (id),
     KEY crc32 (crc32))"))
     {
     echo pdo_error();
     echo "Cannot create new table 'note'";
     return false;
     }

  // Move each note from notetemp to the new table
  $note = pdo_query("SELECT * FROM notetemp ORDER BY buildid ASC");
  while($note_array = pdo_fetch_array($note))
    {
    $text = $note_array["text"];
    $name = $note_array["name"];
    $time = $note_array["time"];
    $buildid = $note_array["buildid"];
    $crc32 = crc32($text.$name);

    $notecrc32 =  pdo_query("SELECT id FROM note WHERE crc32='$crc32'");
    if(pdo_num_rows($notecrc32) == 0)
      {
      pdo_query("INSERT INTO note (text,name,crc32) VALUES ('$text','$name','$crc32')");
      $noteid = pdo_insert_id("note");
      echo pdo_error();
      }
    else // already there
      {
      $notecrc32_array = pdo_fetch_array($notecrc32);
      $noteid = $notecrc32_array["id"];
      }

    pdo_query("INSERT INTO build2note (buildid,noteid,time) VALUES ('$buildid','$noteid','$time')");
    echo pdo_error();
    }

  // Drop the old note table
  pdo_query("DROP TABLE notetemp");
  echo pdo_error();
} // end CompressNotes()

/** Compute the timing for test
 *  For each test we compare with the previous build and if the percentage time
 *  is more than the project.testtimepercent we increas test.timestatus by one.
 *  We also store the test.reftime which is the time of the test passing
 *
 *  If test.timestatus is more than project.testtimewindow we reset
 *  the test.timestatus to zero and we set the test.reftime to the previous build time.
 */
function ComputeTestTiming($days = 4)
{
  // Loop through the projects
  $project = pdo_query("SELECT id,testtimestd,testtimestdthreshold FROM project");
  $weight = 0.3;


  while($project_array = pdo_fetch_array($project))
    {
    $projectid = $project_array["id"];
    $testtimestd = $project_array["testtimestd"];
    $projecttimestdthreshold = $project_array["testtimestdthreshold"];

    // only test a couple of days
    $now = gmdate(FMT_DATETIME,time()-3600*24*$days);

    // Find the builds
    $builds = pdo_query("SELECT starttime,siteid,name,type,id
                               FROM build
                               WHERE build.projectid='$projectid' AND build.starttime>'$now'
                               ORDER BY build.starttime ASC");

    $total = pdo_num_rows($builds);
    echo pdo_error();

    $i=0;
    $previousperc = 0;
    while($build_array = pdo_fetch_array($builds))
      {
      $buildid = $build_array["id"];
      $buildname = $build_array["name"];
      $buildtype = $build_array["type"];
      $starttime = $build_array["starttime"];
      $siteid = $build_array["siteid"];

      // Find the previous build
      $previousbuild = pdo_query("SELECT id FROM build
                                    WHERE build.siteid='$siteid'
                                    AND build.type='$buildtype' AND build.name='$buildname'
                                    AND build.projectid='$projectid'
                                    AND build.starttime<'$starttime'
                                    AND build.starttime>'$now'
                                    ORDER BY build.starttime DESC LIMIT 1");

      echo pdo_error();

      // If we have one
      if(pdo_num_rows($previousbuild)>0)
        {
        // Loop through the tests
        $previousbuild_array = pdo_fetch_array($previousbuild);
        $previousbuildid = $previousbuild_array ["id"];

        $tests = pdo_query("SELECT build2test.time,build2test.testid,test.name
                              FROM build2test,test WHERE build2test.buildid='$buildid'
                              AND build2test.testid=test.id
                              ");
        echo pdo_error();

        flush();
        ob_flush();

        // Find the previous test
        $previoustest = pdo_query("SELECT build2test.testid,test.name FROM build2test,test
                                     WHERE build2test.buildid='$previousbuildid'
                                     AND test.id=build2test.testid
                                     ");
        echo pdo_error();

        $testarray = array();
        while($test_array = pdo_fetch_array($previoustest))
          {
          $test = array();
          $test['id'] = $test_array["testid"];
          $test['name'] = $test_array["name"];
          $testarray[] = $test;
          }

        while($test_array = pdo_fetch_array($tests))
          {
          $testtime = $test_array['time'];
          $testid = $test_array['testid'];
          $testname = $test_array['name'];

         $previoustestid = 0;

         foreach($testarray as $test)
          {
          if($test['name']==$testname)
            {
            $previoustestid = $test['id'];
            break;
            }
          }


        if($previoustestid>0)
            {
            $previoustest = pdo_query("SELECT timemean,timestd FROM build2test
                                       WHERE buildid='$previousbuildid'
                                       AND build2test.testid='$previoustestid'
                                       ");

            $previoustest_array = pdo_fetch_array($previoustest);
            $previoustimemean = $previoustest_array["timemean"];
            $previoustimestd = $previoustest_array["timestd"];

           // Check the current status
          if($previoustimestd<$projecttimestdthreshold)
            {
            $previoustimestd = $projecttimestdthreshold;
            }

            // Update the mean and std
            $timemean = (1-$weight)*$previoustimemean+$weight*$testtime;
            $timestd = sqrt((1-$weight)*$previoustimestd*$previoustimestd + $weight*($testtime-$timemean)*($testtime-$timemean));

            // Check the current status
            if($testtime > $previoustimemean+$testtimestd*$previoustimestd) // only do positive std
              {
              $timestatus = 1; // flag
               }
            else
              {
              $timestatus = 0;
              }
            }
         else // the test doesn't exist
            {
            $timestd = 0;
            $timestatus = 0;
            $timemean = $testtime;
            }



          pdo_query("UPDATE build2test SET timemean='$timemean',timestd='$timestd',timestatus='$timestatus'
                        WHERE buildid='$buildid' AND testid='$testid'");

          }  // end loop through the test

        }
      else // this is the first build
        {
        $timestd = 0;
        $timestatus = 0;

        // Loop throught the tests
        $tests = pdo_query("SELECT time,testid FROM build2test WHERE buildid='$buildid'");
        while($test_array = pdo_fetch_array($tests))
          {
          $timemean = $test_array['time'];
          $testid = $test_array['testid'];

           pdo_query("UPDATE build2test SET timemean='$timemean',timestd='$timestd',timestatus='$timestatus'
                        WHERE buildid='$buildid' AND testid='$testid'");
          }
      } // loop through the tests

      // Progress bar
      $perc = ($i/$total)*100;
      if($perc-$previousperc>5)
        {
        echo round($perc,3)."% done.<br>";
        flush();
        ob_flush();
        $previousperc = $perc;
        }
      $i++;
      } // end looping through builds
    } // end looping through projects
}


/** Compute the statistics for the updated file. Number of produced errors, warning, test failings. */
function ComputeUpdateStatistics($days = 4)
{
  include_once('models/build.php');

  // Loop through the projects
  $project = pdo_query("SELECT id FROM project");

  while($project_array = pdo_fetch_array($project))
    {
    $projectid = $project_array["id"];

    // only test a couple of days
    $now = gmdate(FMT_DATETIME,time()-3600*24*$days);

    // Find the builds
    $builds = pdo_query("SELECT starttime,siteid,name,type,id
                               FROM build
                               WHERE build.projectid='$projectid' AND build.starttime>'$now'
                               ORDER BY build.starttime ASC");

    $total = pdo_num_rows($builds);
    echo pdo_error();

    $i=0;
    $previousperc = 0;
    while($build_array = pdo_fetch_array($builds))
      {
      $Build = new Build();
      $Build->Id = $build_array["id"];
      $Build->ProjectId = $projectid;
      $Build->ComputeUpdateStatistics();

      // Progress bar
      $perc = ($i/$total)*100;
      if($perc-$previousperc>5)
        {
        echo round($perc,3)."% done.<br>";
        flush();
        ob_flush();
        $previousperc = $perc;
        }
      $i++;
      } // end looping through builds
    } // end looping through projects
}

/** Delete unused rows */
function delete_unused_rows($table,$field,$targettable,$selectfield='id')
{
  pdo_query("DELETE FROM $table WHERE $field NOT IN (SELECT $selectfield AS $field FROM $targettable)");
  echo pdo_error();
}

/** Move some columns from buildfailure to buildfailuredetails table.
 *  This function is parameterized to make it easier to test.
 **/
function UpgradeBuildFailureTable($from_table='buildfailure', $to_table='buildfailuredetails')
{
  // Check if the buildfailure table has a column named 'stdout'. If not,
  // we should return early because this upgrade has already been performed.
  $result = pdo_query(
    "SELECT column_name FROM information_schema.columns
     WHERE table_name='$from_table' and column_name='stdoutput'");
  if (pdo_num_rows($result) == 0)
    {
    return;
    }

  // Add the detailsid field to our buildfailure table.
  AddTableField($from_table, 'detailsid', 'bigint(20)', 'bigserial', '0');

  // Iterate over buildfailure rows.
  // We break this up into separate queries of 5,000 each because otherwise
  // memory usage increases with each iteration of our loop.
  $count_results = pdo_single_row_query(
    "SELECT COUNT(1) AS numfails FROM $from_table");
  $numfails = intval($count_results['numfails']);
  $numconverted = 0;
  $last_id = 0;
  $stride = 5000;
  while ($numconverted < $numfails)
    {
    $result = pdo_query(
      "SELECT * FROM $from_table WHERE id > $last_id ORDER BY id LIMIT $stride");
    while($row = pdo_fetch_array($result))
      {
      // Compute crc32 for this buildfailure's details.
      $crc32 = crc32(
        $row['outputfile'] . $row['stdoutput'] . $row['stderror'] .
        $row['sourcefile']);

      // Get detailsid if it already exists, otherwise insert a new row.
      $details_result = pdo_single_row_query(
        "SELECT id FROM $to_table WHERE crc32=" . qnum($crc32));
      if ($details_result && array_key_exists('id', $details_result))
        {
        $details_id = $details_result['id'];
        }
      else
        {
        $type = $row['type'];
        $stdoutput = pdo_real_escape_string($row['stdoutput']);
        $stderror = pdo_real_escape_string($row['stderror']);
        $exitcondition = pdo_real_escape_string($row['exitcondition']);
        $language = pdo_real_escape_string($row['language']);
        $targetname = pdo_real_escape_string($row['targetname']);
        $outputfile = pdo_real_escape_string($row['outputfile']);
        $outputtype = pdo_real_escape_string($row['outputtype']);

        $query =
          "INSERT INTO $to_table
            (type, stdoutput, stderror, exitcondition, language, targetname,
             outputfile, outputtype, crc32)
           VALUES
            ('$type', '$stdoutput', '$stderror', '$exitcondition', '$language',
             '$targetname', '$outputfile', '$outputtype','$crc32')";
        if (!pdo_query($query))
          {
          add_last_sql_error("UpgradeBuildFailureTable::InsertDetails", 0, $row['id']);
          }
        $details_id = pdo_insert_id($to_table);
        }

      $query =
        "UPDATE $from_table SET detailsid=".qnum($details_id)."
         WHERE id=".qnum($row['id']);
      if (!pdo_query($query))
        {
        add_last_sql_error("UpgradeBuildFailureTable::UpdateDetailsId", 0, $details_id);
        }
      $last_id = $row['id'];
      }
    $numconverted += $stride;
    }

  // Remove old columns from buildfailure table.
  RemoveTableField($from_table, 'type');
  RemoveTableField($from_table, 'stdoutput');
  RemoveTableField($from_table, 'stderror');
  RemoveTableField($from_table, 'exitcondition');
  RemoveTableField($from_table, 'language');
  RemoveTableField($from_table, 'targetname');
  RemoveTableField($from_table, 'outputfile');
  RemoveTableField($from_table, 'outputtype');
  RemoveTableField($from_table, 'crc32');
}


/** Support for compressed coverage.
 *  This is done in two steps.
 *  First step: Reducing the size of the coverage file by computing the crc32 in coveragefile
 *              and changing the appropriate fileid in coverage and coveragefilelog
 *  Second step: Reducing the size of the coveragefilelog by computing the crc32 of the groupid
 *               if the same coverage is beeing stored over and over again then it's discarded (same groupid)
 */
function CompressCoverage()
{
  /** FIRST STEP */
  // Compute the crc32 of the fullpath+file
  $coveragefile =  pdo_query("SELECT count(*) AS num FROM coveragefile WHERE crc32 IS NULL");
  $coveragefile_array = pdo_fetch_array($coveragefile);
  $total = $coveragefile_array["num"];

  $i=0;
  $previousperc = 0;
  $coveragefile = pdo_query("SELECT * FROM coveragefile WHERE crc32 IS NULL LIMIT 1000");
  while(pdo_num_rows($coveragefile)>0)
    {
    while($coveragefile_array = pdo_fetch_array($coveragefile))
      {
      $fullpath = $coveragefile_array["fullpath"];
      $file = $coveragefile_array["file"];
      $id = $coveragefile_array["id"];
      $crc32 = crc32($fullpath.$file);
      pdo_query("UPDATE coveragefile SET crc32='$crc32' WHERE id='$id'");
      }
    $i+=1000;
    $coveragefile = pdo_query("SELECT * FROM coveragefile WHERE crc32 IS NULL LIMIT 1000");
    $perc = ($i/$total)*100;
    if($perc-$previousperc>10)
      {
      echo round($perc,3)."% done.<br>";
      flush();
      ob_flush();
      $previousperc = $perc;
      }
    }

  // Delete files with the same crc32 and upgrade
  $previouscrc32 = 0;
  $coveragefile = pdo_query("SELECT id,crc32 FROM coveragefile ORDER BY crc32 ASC,id ASC");
  $total = pdo_num_rows($coveragefile);
  $i=0;
  $previousperc = 0;
  while($coveragefile_array = pdo_fetch_array($coveragefile))
    {
    $id = $coveragefile_array["id"];
    $crc32 = $coveragefile_array["crc32"];
    if($crc32 == $previouscrc32)
      {
      pdo_query("UPDATE coverage SET fileid='$currentid' WHERE fileid='$id'");
      pdo_query("UPDATE coveragefilelog SET fileid='$currentid' WHERE fileid='$id'");
      pdo_query("DELETE FROM coveragefile WHERE id='$id'");
      }
    else
      {
      $currentid = $id;
      $perc = ($i/$total)*100;
      if($perc-$previousperc>10)
        {
        echo round($perc,3)."% done.<br>";
        flush();
        ob_flush();
        $previousperc = $perc;
        }
      }
    $previouscrc32 = $crc32;
    $i++;
    }

  /** Remove the Duplicates in the coverage section */
  $coverage = pdo_query("SELECT buildid,fileid,count(*) as cnt FROM coverage GROUP BY buildid,fileid");
  while($coverage_array = pdo_fetch_array($coverage))
    {
    $cnt = $coverage_array["cnt"];
    if($cnt > 1)
      {
      $buildid = $coverage_array["buildid"];
      $fileid = $coverage_array["fileid"];
      $limit = $cnt-1;
      $sql = "DELETE FROM coverage WHERE buildid='$buildid' AND fileid='$fileid'";
      $sql .= " LIMIT ".$limit;
      pdo_query($sql);
      }
    }

  /** SECOND STEP */
}



?>
