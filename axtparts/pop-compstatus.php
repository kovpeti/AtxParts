<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-compstatus.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new compstate
// $compstateid: ID of compstate to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-compstatus.php";
$formname = "popcompstatus";
$formtitle= "Add/Edit Component Status";
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

$compstateid = false;
if (isset($_GET['compstateid']))
	$compstateid = trim(urldecode(($_GET["compstateid"])));
if (!is_numeric($compstateid))
	$compstateid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPSTATES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	{
		if (isset($_POST["statedescr"]))
			$statedescr = trim($_POST["statedescr"]);
		else 
			$statedescr = "";
			
		if ($statedescr == "")
			$myparts->AlertMeTo("Require a state description.");
		else 
		{
			if ($compstateid === false)
			{
				// new state - insert the values
				$q_p = "insert into compstates "
					. "\n set "
					. "\n statedescr='".$dbh->real_escape_string($statedescr)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Component state created: ".$statedescr;
					$myparts->LogSave($dbh, LOGTYPE_CSTATENEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update compstates "
					. "\n set "
					. "\n statedescr='".$dbh->real_escape_string($statedescr)."' "
					. "\n where compstateid='".$dbh->real_escape_string($compstateid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Component state updated: ".$statedescr;
					$myparts->LogSave($dbh, LOGTYPE_CSTATECHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPSTATES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete state if still used by components
		$nt = $myparts->ReturnCountOf($dbh, "components", "compid", "compstateid", $compstateid);
		if ($nt > 0)
			$myparts->AlertMeTo("State still used by ".$nt." components.");
		else 
		{
			$q_p = "delete from compstates "
				. "\n where compstateid='".$dbh->real_escape_string($compstateid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Component state deleted: ".$compstateid;
				$myparts->LogSave($dbh, LOGTYPE_CSTATEDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("Component state deleted.");
			}
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($compstateid !== false)
{
	$urlargs = "?compstateid=".urlencode($compstateid);
	
	$q_p = "select compstateid, "
		. "\n statedescr "
		. "\n from compstates "
		. "\n where compstateid='".$dbh->real_escape_string($compstateid)."' "
		;
			
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_p = $s_p->fetch_assoc();
		$statedescr = $r_p["statedescr"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$statedescr = "";
}		

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Component Status";
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
	<span class="formheadtext"><?php print ($compstateid === false ? "Add New Component State" : "Edit Component State") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Component State</td>
	<td class="propscell_lt propcell" width="70%">
	<input type="text" name="statedescr" size="40" maxlength="100" tabindex="10" value="<?php print htmlentities($statedescr) ?>" /></td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($compstateid !== false)
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
