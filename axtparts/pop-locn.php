<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-locn.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $locid: The location entry to edit. Create a new location if this is not present.

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-locn.php";
$formname = "poplocn";
$formtitle= "Add/Edit Location Detail";
$popx = 700;
$popy = 300;

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

$locid = false;
if (isset($_GET['locid']))
	$locid = trim(urldecode(($_GET["locid"])));
if (!is_numeric($locid))
	$locid = false;
	
// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["locref"]))
			$locref = trim($_POST["locref"]);
		else 
			$locref = "";
			
		if (isset($_POST["locdescr"]))
			$locdescr = trim($_POST["locdescr"]);
		else 
			$locdescr = "";
			
		if ($locref == "")
			$myparts->AlertMeTo("Require a location reference.");
		else 
		{
			if ($locid === false)
			{
				// new location - insert the values
				$q_p = "insert into locn "
					. "\n set "
					. "\n locref='".$dbh->real_escape_string($locref)."', "
					. "\n locdescr='".$dbh->real_escape_string($locdescr)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Location created: ".$locdescr;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update locn "
					. "\n set "
					. "\n locref='".$dbh->real_escape_string($locref)."', "
					. "\n locdescr='".$dbh->real_escape_string($locdescr)."' "
					. "\n where locid='".$dbh->real_escape_string($locid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Location updated: ".$locdescr;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
				$dbh->close();
				$myparts->PopMeClose();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete location if still used by a stock item
		$nt = $myparts->ReturnCountOf($dbh, "stock", "stockid", "locid", $locid);
		if ($nt > 0)
			$myparts->AlertMeTo("Location still used by ".$nt." parts.");
		else 
		{
			$q_p = "delete from locn "
				. "\n where locid='".$dbh->real_escape_string($locid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Location deleted: ".$locid;
				$myparts->LogSave($dbh, LOGTYPE_PARTLOCNDELETE, $uid, $logmsg);
				$myparts->UpdateParent();
			}
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($locid !== false)
{
	$urlargs = "?locid=".urlencode($locid);

	$q_l = "select locid, "
		. "\n locref, "
		. "\n locdescr "
		. "\n from locn "
		. "\n where locid='".$dbh->real_escape_string($locid)."' "
		;
	$s_l = $dbh->query($q_l);

	if (!$s_l)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_l = $s_l->fetch_assoc();
		$locref = $r_l["locref"];
		$locdescr = $r_l["locdescr"];
		$s_l->free();
	}
}
else
{
	$urlargs = "";
	$locref = "";
	$locdescr = "";
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Location Properties";
$headparams["icon"] = $_cfg_stdicon;
$headparams["css"] = $_cfg_stdcss;
$headparams["jscript_file"] = $_cfg_stdjscript;
$headparams["jscript_local"][] = "window.resizeTo(".$popx.",".$popy.");";
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

$myparts->FormRender_PopClose();

?>
<section class="contentpanel_popup">
	<span class="formheadtext"><?php print ($locid === false ? "Add New Location Detail" : "Edit Location Detail") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Location Ref</td>
	<td class="propscell_lt propcell" width="70%">
	<input type="text" name="locref" size="40" maxlength="40" tabindex="10" value="<?php print htmlentities($locref) ?>" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Location Description</td>
	<td class="propscell_lt propcell">
	<input type="text" name="locdescr" size="40" maxlength="100" tabindex="20" value="<?php print htmlentities($locdescr) ?>" /></td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($locid !== false)
	{
?>
	<input type="submit" name="btn_delete" class="btntext" value="Delete" onclick="delSet()" />
<?php
	}
?>
	</td>
	</tr>
	</table>
	</form>

	</section>
</body></html>
