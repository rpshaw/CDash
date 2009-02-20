<?php
require_once(dirname(__FILE__) . '/simpletest/unit_tester.php');
require_once(dirname(__FILE__) . '/simpletest/mock_objects.php');
require_once(dirname(__FILE__) . '/simpletest/web_tester.php');
/**
 * The test manager interface kw tests with simpletest test.
 *
 * @package    kwtests
 */
class TestManager 
{
  var $testDir  = null;
  var $database = null;
  
  function setDatabase($db){
    $this->database = $db;
  }
  
  
  /**
     * set the tests directory where the test files are placed
     * @param string $dir
     */
  function setTestDirectory($dir){
     $this->testDir = $dir;
  }
  

  /**
     * run all the tests
     * @return the result the test running 
     * @param object $reporter
     */
  function runAllTests(&$reporter) {
    $testsFile = $this->getTestCaseList();
    $test = &new GroupTest('All Tests');
    foreach($testsFile as $path=>$file)
      {
      $test->addTestFile($path);
      }
    return $test->run($reporter);
  }

  /**
     * Match all the test files inside the test directory 
     * @return an array of the test files
     */
  function getTestCaseList() {
    if(!$this->testDir)
      {
      die ("please, set the test directory\n");
      }
    $testsFile = array();
    foreach(glob($this->testDir.'/test_*.php') as $file)
      {
      $fileinfo = pathinfo($file);
      if(strcmp($fileinfo['basename'],'test_install.php') != 0 &&
         strcmp($fileinfo['basename'],'test_uninstall.php') != 0)
        {
        $testsFile[$fileinfo['dirname'].'/'.$fileinfo['basename']] = $fileinfo['basename'];
        }
      }
    return $testsFile;
  }
  
  
  /**
    * perform a connection to the database 
    * @return the result of the connection
    * @param string $host
    * @param string $user
    * @param string $password
    * @param string $dbname
    * @param string $dbtype
    * @access protected
    */
  function _connectToDb($host,$user,$password,$dbname,$dbtype)
  {
    $database = new database($dbtype);
    $database->setHost($host);
    $database->setUser($user);
    $database->setPassword($password);
    $database->setDb($dbname);
    return $database->connectedToDb();
  }
   
  /**
     * drop the old test database 
     * @return success/failure depending of the database dropping
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param string $dbtype
     * @access protected
     */
   function _uninstalldb4test($host,$user,$password,$dbname,$dbtype)
   {
     if(!strcmp($dbname,'cdash4simpletest'))
       {
       $database = new database($dbtype);
       $database->setHost($host);
       $database->setUser($user);
       $database->setPassword($password);
       return $database->drop($dbname);
       }
    else
      {
      die("We cannot test cdash because test database is not cdash4simpletest\n");
      }
   }
  
  
  /**
     * create the new test database 
     * @return success/failure depending of the database creating
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param string $dbtype
     * @access protected
     */
  function _installdb4test($host,$user,$password,$dbname,$dbtype)
  {
    if(!strcmp($dbname,'cdash4simpletest'))
       {
       $database = new database($dbtype);
       $database->setHost($host);
       $database->setUser($user);
       $database->setPassword($password);
       $dbcreated = true;
       if(!$database->create($dbname))
        {
        $dbcreated = false;
        $msg = 'error mysql_query(CREATE DATABASE)';
        die("Error" . " File: " . __FILE__ . " on line: " . __LINE__.": $msg");
        return false;
        }
      if($dbcreated)
        {
        $sqlfile = str_replace("/testing/kwtest","", dirname(__FILE__))."/sql/".$dbtype."/cdash.sql";
        $database->fillDb($sqlfile);
        }
      return true;
       }
    else
      {
      die("We cannot test cdash because test database is not cdash4simpletest\n");
      }
  }  
} 



/**
 * The cdash test manager interface cdash test with simpletest
 *
 * @package    kwtests
 */
class CDashTestManager extends TestManager
{
   var $_urlToCdash = null;
  
  /**
     * run all the tests in the current directory
     * @return the result of the test
     * @param object $reporter
     */
   function runAllTests($reporter)
     {
     $reporter->paintTestCaseList($this->getTestCaseList());
     parent::runAllTests($reporter);
     }
  
  
  /**
     *    Set the url of the CDash server     
     *    @param string $url  url via we make the curl to send the report
     */
   function setCDashServer($servername){
     if(!empty($servername))
      {
      $this->_urlToCdash = $servername;
      }
   }
  
 /**
    * update the svn repository 
    * @param object $reporter
    * @param string $svnroot
  */
   
   function updateSVN($reporter,$svnroot){
      if(!empty($svnroot))
       {
       $reporter->paintUpdateStart();
       $execution_time = $this->__performSvnUpdate($reporter,$svnroot);
       // We put in minute the execution time of the svn update
       if(is_numeric($execution_time))
         {
         $execution_time = round($execution_time / 60 , 3);
         }
       $reporter->paintUpdateEnd($execution_time);
       }
    }
  
   
  /**
     *    perform an update of a revision in the svn
     *    @return the time execution of the svn update 
     *    @param object $reporter
     *    @param string $svnroot
     *    @access private
     */
    function __performSvnUpdate($reporter,$svnroot){
      $time_start = (float) array_sum(explode(' ',microtime()));
      $raw_output = $this->__performSvnCommand(`svn info $svnroot 2>&1 | grep Revision`);
      // We catch the current revision of the repository
      $currentRevision = str_replace('Revision: ','',$raw_output[0]);
      $raw_output = $this->__performSvnCommand(`svn update $svnroot 2>&1 | grep revision`);
      if(strpos($raw_output[0],'revision') === false)
        {
        $execution_time  = "Svn Error:\nsvn update did not return the right standard output.\n";
        $execution_time .= "svn update should not work on your repository\n";
        return $execution_time;
        }
      if(strpos($raw_output[0],'At revision') !== false)
        {
        $time_end = (float) array_sum(explode(' ',microtime()));
        $execution_time = $time_end - $time_start;
        echo "Old revision of repository is: $currentRevision\nCurrent revision of repository is: $currentRevision\n";
        echo "Project is up to date\n";
        return $execution_time;
        }
      $newRevision = str_replace('Updated to revision ','',$raw_output[0]);
      $newRevision = strtok($newRevision,'.');
      $raw_output = `svn log $svnroot -r $currentRevision:$newRevision -v --xml 2>&1`;
      $reporter->paintUpdateFile($raw_output);
      $time_end = (float) array_sum(explode(' ',microtime()));
      $execution_time = $time_end - $time_start;
      echo "Your Repository has just been updating from revision $currentRevision to revision $newRevision\n";
      echo "\tRepository concerned: $svnroot\n\tUse SVN repository type\n";
      echo "Project is up to date\n";
      return $execution_time;
    }


  /**
     * perform a command line
     * @return an array of the output result of the commandline
     * @param command $commandline
     */
    function __performSvnCommand($commandline)
    {
      return explode("\n", $commandline);
    }

    
 /**
    * configure the database for the test by droping the old
    * test database and creating a new one
    * @param object $reporter
    * @param array $db
    */
  function configure($reporter)
   {
      if(!$this->database)
       {
       echo "Please, set the database to the test manager before configure the test\n";
       return false;
       }
     $reporter->paintConfigureStart();
     $time_start = (float) array_sum(explode(' ',microtime()));
     $result = $this->_uninstalldb4test($this->database['host'],
                                         $this->database['login'],
                                         $this->database['pwd'],
                                         $this->database['name'],
                                         $this->database['type']);
    $time_end = (float) array_sum(explode(' ',microtime()));
    $execution_time = $time_end - $time_start;
    $time_start = $time_end;
    $reporter->paintConfigureUninstallResult($result);
    $result = $this->_connectToDb($this->database['host'],
                                 $this->database['login'],
                                 $this->database['pwd'],
                                 $this->database['name'],
                                 $this->database['type']);
    $reporter->paintConfigureConnection($result);
    $result = $this->_installdb4test($this->database['host'],
                                      $this->database['login'],
                                      $this->database['pwd'],
                                      $this->database['name'],
                                      $this->database['type']);
    $time_end = (float) array_sum(explode(' ',microtime()));
    $execution_time += ($time_end - $time_start);
    $execution_time = round($execution_time / 60 , 3);
    $reporter->paintConfigureInstallResult($result);
    $result = $this->_connectToDb($this->database['host'],
                                 $this->database['login'],
                                 $this->database['pwd'],
                                 $this->database['name'],
                                 $this->database['type']);
    $reporter->paintConfigureConnection($result);
    $reporter->paintConfigureEnd($execution_time);
   }
  
 
    
  /**
     *    Send via a curl to the CDash server the xml reports     
     *    @return true on success / false on failure
     */
    function sendToCdash($reporter,$directory){
      if(!$this->_urlToCdash)
        {
        echo "please set the url to the cdash server before calling sendToCdash method\n";
        return false;
        }
      $reporter->close();
      $msg = "Submit files (using http)\n\tUsing HTTP submit method\n\t";
      $msg .= "Drop site: ".$this->_urlToCdash."?project=CDash\n";
      echo $msg;
      $filename = $directory.'/Build.xml';
      $this->__uploadViaCurl($filename);
      echo "\tUploaded: $filename\n";
      $filename = $directory.'/Configure.xml';
      $this->__uploadViaCurl($filename);
      echo "\tUploaded: $filename\n";
      $filename = $directory.'/Test.xml';
      $this->__uploadViaCurl($filename);
      echo "\tUploaded: $filename\n";
      $filename = $directory.'/Update.xml';
      $this->__uploadViaCurl($filename);
      echo "\tUploaded: $filename\n";
      echo "\tSubmission successful\n";
      return true;
    }
    
  /**
     *    Perform a curl to upload the filename to the CDash Server
     *    @param object $filename
     */
    function __uploadViaCurl($filename){
      $fp = fopen($filename, 'r');
      $ch = curl_init($this->_urlToCdash.'/submit.php?project=CDash');
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_UPLOAD, 1);
      curl_setopt($ch, CURLOPT_INFILE, $fp);
      curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filename));
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);
    }  
}


class HtmlTestManager extends TestManager
{
   function runAllTests($reporter)
     {
     $this->_uninstalldb4test($this->database['host'],
                              $this->database['login'],
                              $this->database['pwd'],
                              $this->database['name'],
                              $this->database['type']);
     $this->_installdb4test($this->database['host'],
                            $this->database['login'],
                            $this->database['pwd'],
                            $this->database['name'],
                            $this->database['type']);
     parent::runAllTests($reporter);
     }
}
?>