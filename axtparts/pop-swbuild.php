<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-swbuild.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new swbuild
// $swbuildid: ID of sw build to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-swbuild.php";
$formname = "popswbuild";
$formtitle= "Add/Edit Software Build";
$popx = 700;
$popy = 500;

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

$swbuildid = false;
if (isset($_GET['swbuildid']))
	$swbuildid = trim(urldecode(($_GET["swbuildid"])));
if (!is_numeric($swbuildid))
	$swbuildid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_SWBUILD) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["swname"]))
			$swname = trim($_POST["swname"]);
		else 
			$swname = "";
		if (isset($_POST["buildhost"]))
			$buildhost = trim($_POST["buildhost"]);
		else 
			$buildhost = "";
		if (isset($_POST["buildimage"]))
			$buildimage = trim($_POST["buildimage"]);
		else 
			$buildimage = "";
		if (isset($_POST["releaserev"]))
			$releaserev = trim($_POST["releaserev"]);
		else 
			$releaserev = "";
		$rv = $myparts->GetDateFromPost("releasedate");
		if ($rv["value"] !== false)
			$releasedate = $rv["value"];
		else 
			$releasedate = "";
		
		if ($swname == "")
			$myparts->AlertMeTo("Require a software name.");
		else 
		{
			if ($swbuildid === false)
			{
				// new swbuild - insert the values
				$q_p = "insert into swbuild "
					. "\n set "
					. "\n swname='".$dbh->real_escape_string($swname)."', "
					. "\n buildhost='".$dbh->real_escape_string($buildhost)."', "
					. "\n buildimage='".$dbh->real_escape_string($buildimage)."', "
					. "\n releaserev='".$dbh->real_escape_string($releaserev)."', "
					. "\n releasedate='".$dbh->real_escape_string($releasedate)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Software build created: ".$swname.": ".$buildimage;
					$myparts->LogSave($dbh, LOGTYPE_SWBLDNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update swbuild "
					. "\n set "
					. "\n swname='".$dbh->real_escape_string($swname)."', "
					. "\n buildhost='".$dbh->real_escape_string($buildhost)."', "
					. "\n buildimage='".$dbh->real_escape_string($buildimage)."', "
					. "\n releaserev='".$dbh->real_escape_string($releaserev)."', "
					. "\n releasedate='".$dbh->real_escape_string($releasedate)."' "
					. "\n where swbuildid='".$dbh->real_escape_string($swbuildid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Software build updated: ".$swname.": ".$buildimage;
					$myparts->LogSave($dbh, LOGTYPE_SWBLDCHANGE, $uid, $logmsg);
				}
				$myparts->UpdateParent();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_SWBUILD) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete swbuild that is still in use by a Unit via a swlicence
		$ns = $myparts->ReturnCountOf($dbh, "swlicence", "swlid", "swbuildid", $swbuildid);
		if ($ns > 0)
			$myparts->AlertMeTo("Software still used by ".$ns." licenses.");
		else 
		{
			$q_p = "delete from swbuild "
				. "\n where swbuildid='".$dbh->real_escape_string($swbuildid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Software build deleted: ".$swbuildid;
				$myparts->LogSave($dbh, LOGTYPE_SWBLDDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("SW build deleted.");
			}
			$myparts->UpdateParent();
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

$nb = 0;
if ($swbuildid !== false)
{
	$urlargs = "?swbuildid=".urlencode($swbuildid);

	$q_p = "select swbuildid, "
		. "\n swname, "
		. "\n buildhost, "
		. "\n buildimage, "
		. "\n releaserev, "
		. "\n releasedate "
		. "\n from swbuild "
		. "\n where swbuildid='".$dbh->real_escape_string($swbuildid)."' "
		;
																		
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_p = $s_p->fetch_assoc();
		$swname = $r_p["swname"];
		$buildhost = $r_p["buildhost"];
		$buildimage = $r_p["buildimage"];
		$releaserev = $r_p["releaserev"];
		$releasedate = $r_p["releasedate"];
		if (($releasedate != "") && ($releasedate != "0000-00-00"))
		{
			$releasedate_yy = substr($r_p["releasedate"], 0, 4);
			$releasedate_mm = substr($r_p["releasedate"], 5, 2);
			$releasedate_dd = substr($r_p["releasedate"], 8, 2);
		}
		else
		{
			$releasedate_dd = "";
			$releasedate_mm = "";
			$releasedate_yy = "";
		}
		$s_p->free();
	}

	// Get some stats about its usage
	$nb = $myparts->ReturnCountOf($dbh, "swlicence", "swlid", "swbuildid", $swbuildid);
}
else
{
	$urlargs="";
	$swname = "";
	$buildhost = "";
	$buildimage = "";
	$releaserev = "";
	$releasedate = "";
	$releasedate_dd = "";
	$releasedate_mm = "";
	$releasedate_yy = "";
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Software Build Properties";
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
	<span class="formheadtext"><?php print ($swbuildid === false ? "Add New SW Build" : "Edit SW Build") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()' />
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="25%">Software Name</td>
	<td class="propscell_lt propcell" width="75%">
	<input type="text" name="swname" size="40" maxlength="40" tabindex="10" value="<?php print htmlentities($swname) ?>" />
	</td>
	</tr>

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Build Host</td>
	<td class="propscell_lt propcell">
	<input type="text" name="buildhost" size="40" maxlength="100" tabindex="20" value="<?php print htmlentities($buildhost) ?>" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Build Image</td>
	<td class="propscell_lt propcell">
	<input type="text" name="buildimage" size="40" maxlength="100" tabindex="30" value="<?php print htmlentities($buildimage) ?>" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Release Rev</td>
	<td class="propscell_lt propcell">
	<input type="text" name="releaserev" size="40" maxlength="40" tabindex="40" value="<?php print htmlentities($releaserev) ?>" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Release Date (y-m-d)</td>
	<td class="propscell_lt propcell">
	<input type="text" name="releasedate_yy" size="6" maxlength="4" tabindex="50" value="<?php print htmlentities($releasedate_yy) ?>" />
	-
	<input type="text" name="releasedate_mm" size="6" maxlength="2" tabindex="60" value="<?php print htmlentities($releasedate_mm) ?>" />
	-
	<input type="text" name="releasedate_dd" size="6" maxlength="2" tabindex="70" value="<?php print htmlentities($releasedate_dd) ?>" />
	</td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($swbuildid !== false)
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
if ($swbuildid !== false)
{
	print "<span class=\"formtext\">SW licenses referencing this software: ".$nb."</span><br/>\n";
}
?>
	</section>
</body></html>
