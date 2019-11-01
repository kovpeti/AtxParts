<?php
// ********************************************
// Copyright 2003-2015 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-category.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new category
// $catid: ID of category to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-category.php";
$formname = "popcategory";
$formtitle= "Add/Edit Category";
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

$catid = false;
if (isset($_GET['catid']))
	$catid = trim(urldecode(($_GET["catid"])));
if (!is_numeric($catid))
	$catid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTCATS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["catdescr"]))
			$catdescr = trim($_POST["catdescr"]);
		else 
			$catdescr = "";
		if (isset($_POST["datadir"]))
			$datadir = trim($_POST["datadir"]);
		else 
			$datadir = "";
		
		if ($catdescr == "")
			$myparts->AlertMeTo("Require a category description.");
		else 
		{
			if ($catid === false)
			{
				// new category - insert the values
				$q_p = "insert into pgroups "
					. "\n set "
					. "\n catdescr='".$dbh->real_escape_string($catdescr)."', "
					. "\n datadir='".$dbh->real_escape_string($datadir)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Category created: ".$catdescr;
					$myparts->LogSave($dbh, LOGTYPE_PGRPNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update pgroups "
					. "\n set "
					. "\n catdescr='".$dbh->real_escape_string($catdescr)."', "
					. "\n datadir='".$dbh->real_escape_string($datadir)."' "
					. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Category updated: ".$catdescr;
					$myparts->LogSave($dbh, LOGTYPE_PGRPCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTCATS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete category is still used by a datasheet
		$nd = $myparts->ReturnCountOf($dbh, "datasheets", "dataid", "partcatid", $catid);
		if ($nd > 0)
			$myparts->AlertMeTo("Category still used by ".$nd." datasheets.");
		else 
		{
			$q_p = "delete from pgroups "
				. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				// Remove the references from parts to this category
				$q_p = "update parts "
					. "\n set "
					. "\n partcatid='0' "
					. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
					;
				$s_p = $dbh->query($q_p);
				
				$uid = $myparts->SessionMeUID();
				$logmsg = "Category deleted: ".$catid;
				$myparts->LogSave($dbh, LOGTYPE_PGRPDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("Part category deleted.");
			}
			$dbh->close();
			$myparts->UpdateParent();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($catid !== false)
{
	$urlargs = "?catid=".urlencode($catid);
	
	$q_p = "select partcatid, "
		. "\n catdescr, "
		. "\n datadir "
		. "\n from pgroups "
		. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
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
		$catdescr = $r_p["catdescr"];
		$datadir = $r_p["datadir"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$catdescr = "";
	$datadir = "";
}		

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Category Properties";
$headparams["icon"] = $_cfg_stdicon;
$headparams["css"] = $_cfg_stdcss;
$headparams["jscript_file"] = $_cfg_stdjscript;
$headparams["jscript_local"][] = "window.resizeTo(".$popx.",".$popy.");";
$headparams["jscript_local"][] = "window.opener.location.href=window.opener.location.href;";
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

$myparts->FormRender_PopClose();

?>
<section class="contentpanel_popup">
	<span class="formheadtext"><?php print ($catid === false ? "Add New Part Category" : "Edit Part Category") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Part Category</td>
	<td class="propscell_lt propcell" width="70%">
	<input type="text" name="catdescr" size="40" maxlength="100" tabindex="10" value="<?php print htmlentities($catdescr) ?>" /></td>
	</tr>

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Datasheet Directory</td>
	<td class="propscell_lt propcell">
	<input type="text" name="datadir" size="40" maxlength="100" tabindex="20" value="<?php print htmlentities($datadir) ?>" /></td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()">
	&nbsp;
<?php
if ($catid !== false)
{
?>
	<input type="submit" name="btn_delete" class="btntext" value="Delete" onclick="delSet()">
<?php
}
?>
	</td>
	</tr>
	</table>
	</form>

	</section>
</body></html>
