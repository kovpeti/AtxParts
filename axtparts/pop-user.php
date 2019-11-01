<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-user.php 191 2016-07-17 02:03:13Z gswan $

// Parameters passed: 
// none: create new user
// $uid: ID of user to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-user.php";
$formname = "popuser";
$formtitle= "Add/Edit User";
$popx = 700;
$popy = 500;

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

$uid = false;
if (isset($_GET['uid']))
	$uid = trim(urldecode(($_GET["uid"])));
if (!is_numeric($uid))
	$uid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERADMIN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["loginid"]))
			$loginid = trim($_POST["loginid"]);
		else 
			$loginid = "";
			
		if ($loginid == "")
			$myparts->AlertMeTo("Require a login ID.");
		else 
		{
			if (isset($_POST["username"]))
				$username = trim($_POST["username"]);
			else 
				$username = "";
				
			if (isset($_POST["roleid"]))
				$roleid = trim($_POST["roleid"]);
			else 
				$roleid = 0;
				
			if (isset($_POST["status"]))
				$status = trim($_POST["status"]);
			else 
				$status = "";
				
			if (isset($_POST["passwd"]))
				$passwd = trim($_POST["passwd"]);
			else 
				$passwd = "";
				
			if ($passwd != "")
				$hpasswd = $myparts->Passwd_ssha1($passwd);
			else 
				$hpasswd = false;
			
			if ($uid === false)
			{
				// new user - insert the values
				$q_p = "insert into user "
					. "\n set "
					. "\n loginid='".$dbh->real_escape_string($loginid)."', "
					. "\n username='".$dbh->real_escape_string($username)."', "
					. "\n roleid='".$dbh->real_escape_string($roleid)."', "
					. "\n status='".$dbh->real_escape_string($status)."'"
					;
				if ($hpasswd !== false)
					$q_p .= ", \n passwd='".$dbh->real_escape_string($hpasswd)."' ";
					
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "User created: ".$loginid.": ".$username;
					$myparts->LogSave($dbh, LOGTYPE_USERNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update user "
					. "\n set "
					. "\n loginid='".$dbh->real_escape_string($loginid)."', "
					. "\n username='".$dbh->real_escape_string($username)."', "
					. "\n roleid='".$dbh->real_escape_string($roleid)."', "
					. "\n status='".$dbh->real_escape_string($status)."'"
					;
				if ($hpasswd !== false)
					$q_p .= ", \n passwd='".$dbh->real_escape_string($hpasswd)."' ";
					
				$q_p .= "\n where uid='".$dbh->real_escape_string($uid)."' ";
				
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "User updated: ".$loginid.": ".$username;
					$myparts->LogSave($dbh, LOGTYPE_USERCHANGE, $uid, $logmsg);
				}
				$myparts->UpdateParent();
			}
		
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERADMIN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from user "
			. "\n where uid='".$dbh->real_escape_string($uid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "User deleted: ".$uid;
			$myparts->LogSave($dbh, LOGTYPE_USERDELETE, $uid, $logmsg);
			$myparts->AlertMeTo("User deleted.");
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

if ($uid !== false)
{
	$urlargs = "?uid=".urlencode($uid);

	$q_p = "select * "
		. "\n from user "
		. "\n left join role on role.roleid=user.roleid "
		. "\n where uid='".$dbh->real_escape_string($uid)."' "
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
			$loginid = $r_p["loginid"];
			$username = $r_p["username"];
			$lastlogin = $r_p["lastlogin"];
			$logincount = $r_p["logincount"];
			$status = $r_p["status"];
			$roleid = $r_p["roleid"];
			$s_p->free();
		}
		else
		{
			$myparts->AlertMeTo(htmlentities("Error: User record not found", ENT_COMPAT));
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}
else
{
	$loginid = "";
	$username = "";
	$lastlogin = "";
	$logincount = 0;
	$status = 0;
	$roleid = 0;
}

// Get a list of roles for the selector
$q_r = "select roleid, "
	. "\n rolename "
	. "\n from role "
	. "\n order by rolename "
	;
$s_r = $dbh->query($q_r);

$list_role = array();
$nrole = 0;
if ($s_r)
{
	while ($r_r = $s_r->fetch_assoc())
	{
		$list_role[$nrole][0] = $r_r["roleid"];
		$list_role[$nrole][1] = $r_r["rolename"];
	}
	$s_r->free();
}

$list_status = array();
$nstat = 0;
foreach ($_ustat_text as $k => $v)
	$list_status[$nstat++] = array ($k, $v);

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "User Properties";
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
	<span class="formheadtext"><?php print ($uid === false ? "Add New User" : "Edit User") ?></span>
	<p/>

	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="25%">Login ID</td>
	<td class="propscell_lt propcell" width="75%">
	<input type="text" name="loginid" size="40" maxlength="100" tabindex="10" value="<?php print htmlentities($loginid) ?>" /></td>
	</tr>

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Password</td>
	<td class="propscell_lt propcell">
	<input type="password" name="passwd" size="40" maxlength="100" tabindex="15" value="" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">User name</td>
	<td class="propscell_lt propcell">
	<input type="text" name="username" size="40" maxlength="100" tabindex="20" value="<?php print htmlentities($username) ?>" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Role</td>
	<td class="propscell_lt propcell">
	<select name="roleid" style="width: 18em" tabindex="30">
	<?php $myparts->RenderOptionList($list_role, $roleid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Status</td>
	<td class="propscell_lt propcell">
	<select name="status" style="width: 18em" tabindex="40">
	<?php $myparts->RenderOptionList($list_status, $status, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Last login</td>
	<td class="propscell_lt propcell">
	<input type="text" readonly name="lastlogin" size="40" maxlength="40" tabindex="50" value="<?php print htmlentities($lastlogin) ?>" />
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Login count</td>
	<td class="propscell_lt propcell">
	<input type="text" readonly name="logincount" size="40" maxlength="40" tabindex="60" value="<?php print htmlentities($logincount) ?>" />
	</td>
	</tr>
	</table>	
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($uid !== false)
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
