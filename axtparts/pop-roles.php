<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-roles.php 188 2016-07-16 23:16:57Z gswan $

// Parameters passed: 
// none: create new role
// $rid: roleid to add/edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-roles.php";
$formname = "poproles";
$formtitle= "Add/Edit Roles";
$popx = 700;
$popy = 800;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

if (!$myparts->SessionMePrivilegeBit(UPRIV_USERADMIN))
{
	$myparts->AlertMeTo("Insufficient privileges.");
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

$rid = false;
if (isset($_GET['rid']))
	$rid = trim(urldecode(($_GET["rid"])));
if (!is_numeric($rid))
	$rid = false;
	
// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERROLES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["rolename"]))
			$rolename = trim($_POST["rolename"]);
		else
			$rolename = "";
			
		if ($rolename == "")
			$myparts->AlertMeTo("Require a role name.");
		else
		{
			if (isset($_POST["rolemask"]))
				$rolemask = hexdec(trim($_POST["rolemask"]));
			else
				$rolemask = 0;
				
			if ($rid === false)
			{
				// new role - insert the values
				$q_p = "insert into role "
					. "\n set "
					. "\n rolename='".$dbh->real_escape_string($rolename)."', "
					. "\n privilege='".$dbh->real_escape_string($rolemask)."' "
					;
																	
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Role created: ".$rolename;
					$myparts->LogSave($dbh, LOGTYPE_ROLENEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else
			{
				// existing - update the values
				$q_p = "update role "
						. "\n set "
						. "\n rolename='".$dbh->real_escape_string($rolename)."', "
						. "\n privilege='".$dbh->real_escape_string($rolemask)."' "
						. "\n where roleid='".$dbh->real_escape_string($rid)."' "
						;

				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Role updated: ".$rolename;
					$myparts->LogSave($dbh, LOGTYPE_ROLECHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERROLES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from role "
			. "\n where roleid='".$dbh->real_escape_string($rid)."' "
			. "\n limit 1 "
			;

		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Role deleted: ".$rid;
			$myparts->LogSave($dbh, LOGTYPE_ROLEDELETE, $uid, $logmsg);
			$myparts->AlertMeTo("Role deleted.");
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

if ($rid !== false)
{
	$urlargs = "?rid=".urlencode($rid);

	$q_p = "select * "
		. "\n from role "
		. "\n where roleid='".$dbh->real_escape_string($rid)."' "
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
		if ($r_p = $s_p->fetch_assoc())
		{
			$rolename = $r_p["rolename"];
			$rolemask = "0x".dechex($r_p["privilege"]);
			$s_p->free();
		}
		else
		{
			$myparts->AlertMeTo(htmlentities("Error: Role record not found", ENT_COMPAT));
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}
else
{
	$urlargs = "";
	$rolename = "";
	$rolemask = 0;
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Role Properties";
$headparams["icon"] = $_cfg_stdicon;
$headparams["css"] = $_cfg_stdcss;
$headparams["jscript_file"] = $_cfg_stdjscript;
$headparams["jscript_file"][] = "../core/js-roles.js";
$headparams["jscript_local"][] = "window.resizeTo(".$popx.",".$popy.");";
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$bodyparams["onload"][] = "updateRolebits()";
$myparts->FormRender_BodyTag($bodyparams);

$myparts->FormRender_PopClose();

?>
<section class="contentpanel_popup">
	<span class="formheadtext"><?php print ($rid === false ? "Add New Role" : "Edit Role") ?></span>
	<p/>

	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="16%">Role Name</td>
	<td class="propscell_lt propcell" width="84%">
	<input type="text" name="rolename" size="40" maxlength="100" tabindex="10" value="<?php print htmlentities($rolename) ?>" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Privileges</td>
	<td class="propscell_lt propcell">
	<input type="text" name="rolemask" id="rolemask" value="<?php print $rolemask ?>" size="30" maxlength="10" tabindex="30" onchange="updateRolebits()" />
	<table class="contentpanel_popup">
	<tr>
	<td>
<?php
// print the checkbox rows
for ($i = 0; $i < 16; $i++)
{
	print "<tr class=\"contentrow_30\">\n";
	print "<td class=\"propscell_lt\" width=\"50%\">";
	print "<input type=\"checkbox\" name=\"rb".$i."\" id=\"rb".$i."\" value=\"".$i."\" onchange=\"updateRolemask()\" />";
	if (isset($_upriv_text[(1 << $i)]))
		print "<span class=\"formsmltext\">".$_upriv_text[(1 << $i)]."</span>";
	else
		print "<span class=\"formsmltext\">unassigned</span>";
	print "</td>\n";
	print "<td class=\"propscell_lt\" width=\"50%\">";
	print "<input type=\"checkbox\" name=\"rb".($i+16)."\" id=\"rb".($i+16)."\" value=\"".($i+16)."\" onchange=\"updateRolemask()\">";
	if (isset($_upriv_text[(1 << ($i+16))]))
		print "<span class=\"formsmltext\">".$_upriv_text[(1 << ($i+16))]."</span>";
	else
		print "<span class=\"formsmltext\">unassigned</span>";
	print "</td>\n";
	print "</tr>\n";
}
?>
	</td>
	</tr>
	</table>
	
	</td>
	</tr>
	</table>

	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($rid !== false)
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
