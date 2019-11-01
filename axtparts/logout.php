<?php
// ********************************************
// Copyright 2003-2015 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: logout.php 71 2016-07-11 09:54:06Z gswan $

session_start();
header("Cache-control: private");

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "logout.php";

$myparts = new axtparts();
$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if ($dbh)
{
	$myparts->UserLogout($dbh);
	$dbh->close();
}

?>