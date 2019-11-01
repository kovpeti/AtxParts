<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-footprint.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new footprint
// $fprintid: ID of footprint to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-footprint.php";
$formname = "popfootprint";
$formtitle= "Add/Edit Part Footprint";
$popx = 700;
$popy = 400;

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

$fprintid = false;
if (isset($_GET['fprintid']))
	$fprintid = trim(urldecode(($_GET["fprintid"])));
if (!is_numeric($fprintid))
	$fprintid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_FOOTPRINTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["fprintdescr"]))
			$fprintdescr = trim($_POST["fprintdescr"]);
		else 
			$fprintdescr = "";
			
		if ($fprintdescr == "")
			$myparts->AlertMeTo("Require a footprint description.");
		else 
		{
			if ($fprintid === false)
			{
				// new footprint - insert the values
				$q_p = "insert into footprint "
					. "\n set "
					. "\n fprintdescr='".$dbh->real_escape_string($fprintdescr)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Part footprint created: ".$fprintdescr;
					$myparts->LogSave($dbh, LOGTYPE_FPRINTNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update footprint "
					. "\n set "
					. "\n fprintdescr='".$dbh->real_escape_string($fprintdescr)."' "
					. "\n where fprintid='".$dbh->real_escape_string($fprintid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Part footprint updated: ".$fprintdescr;
					$myparts->LogSave($dbh, LOGTYPE_FPRINTCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_FOOTPRINTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete footprint if still used by parts
		$nt = $myparts->ReturnCountOf($dbh, "parts", "footprint", "footprint", $fprintid);
		if ($nt > 0)
			$myparts->AlertMeTo("Footprint still used by ".$nt." parts.");
		else 
		{
			$q_p = "delete from footprint "
				. "\n where fprintid='".$dbh->real_escape_string($fprintid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Part footprint deleted: ".$fprintid;
				$myparts->LogSave($dbh, LOGTYPE_FPRINTDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("Part footprint deleted.");
			}
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($fprintid !== false)
{
	$urlargs = "?fprintid=".urlencode($fprintid);

	$q_p = "select fprintid, "
		. "\n fprintdescr "
		. "\n from footprint "
		. "\n where fprintid='".$dbh->real_escape_string($fprintid)."' "
		;
										
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_p = $s_p->fetch_assoc();
		$fprintdescr = $r_p["fprintdescr"];
		$s_p->free();
	}
}
else
{
	$urlargs="";
	$fprintdescr = "";
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Footprint Properties";
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
	<span class="formheadtext"><?php print ($fprintid === false ? "Add New Part Footprint" : "Edit Part Footprint") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Footprint</td>
	<td class="propscell_lt propcell" width="70%">
	<input type="text" name="fprintdescr" size="40" maxlength="100" tabindex="10" value="<?php print htmlentities($fprintdescr) ?>" />
	</td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($fprintid !== false)
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
