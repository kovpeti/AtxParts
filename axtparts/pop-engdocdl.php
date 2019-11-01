<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-engdocdl.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// engdocid: ID of document to download

session_start();

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-engdocdl.php";
$formname = "popengdocdl";
$formtitle= "Download Document";
$popx = 600;
$popy = 600;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if (!$dbh)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

$docid = false;
if (isset($_GET['engdocid']))
	$docid = trim(urldecode(($_GET["engdocid"])));
if (!is_numeric($docid))
	$docid = false;

// Handle download request here - this is all this popup does
if ($docid !== false)
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ENGDOCS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "select engdocid, "
			. "\n engdocpath, "
			. "\n engdocdescr "
			. "\n from engdocs "
			. "\n where engdocid='".$dbh->real_escape_string($docid)."' "
			;
			
		$s_p = $dbh->query($q_p);
		if (!$s_p)
		{
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			$myparts->PopMeClose();
			die();
		}
		else 
		{
			$r_p = $s_p->fetch_assoc();
			$docdescr = $r_p["engdocdescr"];
			$docpath = $r_p["engdocpath"];
			$s_p->free();
			
			// Fetch and serve the document in docpath
			// Requires XSendFile to be installed and enabled in apache
			if (file_exists($docpath))
			{
				header("X-Sendfile: $docpath");
				header("Content-type: application/octet-stream");
				header('Content-Disposition: attachment; filename="'.basename($docpath).'"');
				$dbh->close();					
			}
			else
			{
				$dbh->close();
				$myparts->AlertMeTo("Error: file ".htmlentities($docpath)." does not exist.");
				$myparts->PopMeClose();
				die();
			}
		}
	}
}
else 
{
	$dbh->close();
	$myparts->AlertMeTo("Unspecified document ID");
	$myparts->PopMeClose();
	die();
}

?>