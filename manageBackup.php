<?php
/*=========================================================================

  Program:   CDash - Cross-Platform Dashboard System
  Module:    $Id$
  Language:  PHP
  Date:      $Date$
  Version:   $Revision$

  Copyright (c) 2002 Kitware, Inc.  All rights reserved.
  See Copyright.txt or http://www.cmake.org/HTML/Copyright.html for details.

     This software is distributed WITHOUT ANY WARRANTY; without even 
     the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR 
     PURPOSE.  See the above copyright notices for more information.

=========================================================================*/
include("config.php");
require_once("pdo.php");
include('login.php');
include("version.php");

if($session_OK) 
{
include_once('common.php');
include_once("ctestparser.php");

set_time_limit(0);

$db = pdo_connect("$CDASH_DB_HOST", "$CDASH_DB_LOGIN","$CDASH_DB_PASS");
pdo_select_db("$CDASH_DB_NAME",$db);

checkUserPolicy(@$_SESSION['cdash']['loginid'],0); // only admin
$xml = "<cdash>";
$xml .= "<cssfile>".$CDASH_CSS_FILE."</cssfile>";
$xml .= "<version>".$CDASH_VERSION."</version>";
$xml .= "<title>CDash - Backup</title>";
$xml .= "<menutitle>CDash</menutitle>";
$xml .= "<menusubtitle>Backup</menusubtitle>";
$xml .= "<backurl>user.php</backurl>";
$xml .= "</cdash>";

// Now doing the xslt transition
generate_XSLT($xml,"manageBackup");

} // end session
?>