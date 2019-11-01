<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-variant.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new variant
// $variantid: ID of variant to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-variant.php";
$formname = "popvariant";
$formtitle= "Add/Edit Variant";
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

$variantid = false;
if (isset($_GET['variantid']))
	$variantid = trim(urldecode(($_GET["variantid"])));
if (!is_numeric($variantid))
	$variantid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMVARIANT) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["variantname"]))
			$variantname = trim($_POST["variantname"]);
		else 
			$variantname = "";
		if (isset($_POST["variantdescr"]))
			$variantdescr = trim($_POST["variantdescr"]);
		else 
			$variantdescr = "";
		if (isset($_POST["variantstate"]))
			$variantstate = trim($_POST["variantstate"]);
		else 
			$variantstate = "";
		
		if ($variantname == "")
			$myparts->AlertMeTo("Require a variant name.");
		else 
		{
			if ($variantid === false)
			{
				// new variant - insert the values
				$q_p = "insert into variant "
					. "\n set "
					. "\n variantname='".$dbh->real_escape_string($variantname)."', "
					. "\n variantdescr='".$dbh->real_escape_string($variantdescr)."', "
					. "\n variantstate='".$dbh->real_escape_string($variantstate)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Variant created: ".$variantname.": ".$variantdescr;
					$myparts->LogSave($dbh, LOGTYPE_VARIANTNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update variant "
					. "\n set "
					. "\n variantname='".$dbh->real_escape_string($variantname)."', "
					. "\n variantdescr='".$dbh->real_escape_string($variantdescr)."', "
					. "\n variantstate='".$dbh->real_escape_string($variantstate)."' "
					. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Variant updated: ".$variantname.": ".$variantdescr;
					$myparts->LogSave($dbh, LOGTYPE_VARIANTCHANGE, $uid, $logmsg);
				}
				$myparts->UpdateParent();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMVARIANT) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete variant that is still in use by a BOM
		$nb = $myparts->ReturnCountOf($dbh, "bomvariants", "bomvid", "variantid", $variantid);
		if ($nb > 0)
			$myparts->AlertMeTo("Variant still used by ".$nb." BOMs.");
		else 
		{
			// Cannot delete a variant that is still in use by manufactured units
			$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "variantid", $variantid);
			if ($nu > 0)
				$myparts->AlertMeTo("Variant still used by ".$nu." manufactured units.");
			else 
			{
				$q_p = "delete from variant "
					. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
					. "\n limit 1 "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Variant deleted: ".$variantid;
					$myparts->LogSave($dbh, LOGTYPE_VARIANTDELETE, $uid, $logmsg);
					$myparts->AlertMeTo("Variant deleted.");
				}
				
				$myparts->UpdateParent();
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
		}
	}
}

$nb = 0;
$nu = 0;
if ($variantid !== false)
{
	$urlargs = "?variantid=".urlencode($variantid);

	$q_p = "select * "
		. "\n from variant "
		. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
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
		$variantname = $r_p["variantname"];
		$variantdescr = $r_p["variantdescr"];
		$variantstate = $r_p["variantstate"];
		$s_p->free();
	}

	// Get some stats about its usage
	$nb = $myparts->ReturnCountOf($dbh, "bomvariants", "bomvid", "variantid", $variantid);
	$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "variantid", $variantid);

}
else
{
	$urlargs="";
	$variantname = "";
	$variantdescr = "";
	$variantstate = "";
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Variant Properties";
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
	<span class="formheadtext"><?php print ($variantid === false ? "Add New Assembly Variant" : "Edit Assembly Variant") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="20%">Variant Name</td>
	<td class="propscell_lt propcell" width="80%">
	<input type="text" name="variantname" size="55" maxlength="40" tabindex="10" value="<?php print htmlentities($variantname) ?>" />
	</td>
	</tr>

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Variant Description</td>
	<td class="propscell_lt propcell">
	<input type="text" name="variantdescr" size="55" maxlength="100" tabindex="20" value="<?php print htmlentities($variantdescr) ?>" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Variant Status</td>
	<td class="propscell_lt propcell">
	<input type="text" name="variantstate" size="55" maxlength="40" tabindex="30" value="<?php print htmlentities($variantstate) ?>" />
	</td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($variantid !== false)
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
<?php
if ($variantid !== false)
{
	print "<span class=\"formtext\">BOM items referencing this variant: ".$nb."</span><br>\n";
	print "<span class=\"formtext\">Units referencing this variant: ".$nu."</span><br>\n";
}
?>
	</section>
</body></html>
