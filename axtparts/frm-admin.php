<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-admin.php 201 2016-07-17 05:49:39Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=loginID
//      1=name
//      2=status
//      3=lastlogin
//      4=role

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-admin.php";
$formname = "admin";
$formtitle= "User Admin";
$rpp = 40;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_ADMIN) !== true)
{
	$myparts->AlertMeTo("Insufficient tab privileges.");
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if (!$dbh)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

$pg = 0;
if (isset($_GET['pg']))
	$pg = trim(urldecode(($_GET["pg"])));
if (!is_numeric($pg))
	$pg = 0;
	
$sc = 0;
if (isset($_GET['sc']))
	$sc = trim(urldecode(($_GET["sc"])));
if (!is_numeric($sc))
	$sc = 0;
	
// Retrieve the user table for display
$dset = array();
$q_p = "select * from user "
	. "\n left join role on role.roleid=user.roleid "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by loginid asc ";
			break;
	case "1":
			$q_p .= "\n order by username asc ";
			break;
	case "2":
			$q_p .= "\n order by status asc ";
			break;
	case "3":
			$q_p .= "\n order by lastlogin desc ";
			break;
	case "4":
			$q_p .= "\n order by rolename asc ";
			break;
	default:
			$q_p .= "\n order by loginid asc ";
			break;
}

$s_p = $dbh->query($q_p);
$nu = 0;
		
$startidx = $pg * $rpp;
$endidx = ($pg + 1) * $rpp;
$cp = 0;
$i = 0;
if ($s_p)
{
	$nu = $s_p->num_rows;
	while (($r_p = $s_p->fetch_assoc()) && ($cp < $endidx))
	{
		if ($cp < $startidx)
			$cp++;
		else 
		{
			$dset[$i]["uid"] = $r_p["uid"];
			$dset[$i]["loginid"] = $r_p["loginid"];
			$dset[$i]["username"] = $r_p["username"];
			$dset[$i]["lastlogin"] = $r_p["lastlogin"];
			if ($r_p["status"] == USERSTATUS_ACTIVE)
				$dset[$i]["status"] = "active";
			else
				$dset[$i]["status"] = "inactive";
			$dset[$i]["rolename"] = $r_p["rolename"];
			$dset[$i]["logincount"] = $r_p["logincount"];
			$i++;
			$cp++;
		}
	}
}

$np = intval($nu/$rpp);
if (($nu % $rpp) > 0)
	$np++;

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = $_cfg_stdtitle;
$headparams["icon"] = $_cfg_stdicon;
$headparams["css"] = $_cfg_stdcss;
$headparams["jscript_file"] = $_cfg_stdjscript;
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

$tabparams = array();
$tabparams["tabon"] = "Admin";
$tabparams["tabs"] = $_cfg_tabs;
$myparts->FormRender_Tabs($tabparams);

print "<div class=\"formpanel\">\n";

$topparams = array();
$topparams["siteheading"] = SYSTEMHEADING;
$topparams["formtitle"] = $formtitle;
$topparams["username"] = $username;
$topparams["buttons"] = array(
		"logout" => $_cfg_btn_logout,
);
$myparts->FormRender_TopPanel($topparams);

$bottomparams = array();
$bottomparams["branding"] = SYSTEMBRANDING." ".ENGPARTSVERSION;

?>
<section class="contentpanel">
	<table class="contentpanel">
	<?php $myparts->FormRender_Grid(960, 24) ?>
	
	<tr class="contentrow_20">
	<td class="contentcell_lt" colspan="24">
	<table class="pagebtnset">
	<tr class="contentrow_30">
	<th class="dataview_c" width="24%"></th>
	<th class="pagebtn_c pagebtncell_on" width="13%"><span class="pagebtntext">Users</span></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-logs.php"><span class="pagebtntext" title="System Logs">Logs</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-roles.php"><span class="pagebtntext" title="Roles">Roles</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-system.php"><span class="pagebtntext" title="System">System</span></a></th>
	<th class="pagebtn_c" width="24%"></th>
	</tr>
	</table>
	</td>
	</tr>
	
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>
	
	<tr>
	<td class="contentcell_lt" colspan="24">
<?php
print "<span class=\"pageon\">Page: </span>\n";
$urlq = "?sc=".urlencode($sc);
for ($i = 0; $i < $np; $i++)
{
	if ($pg == $i)
		print "&nbsp;<span class=\"pageon\">".($i+1)."</span>&nbsp;\n";
	else
		print "<a href=\"".htmlentities($formfile).$urlq."&pg=".$i."\">&nbsp;<span class=\"pageoff\">".($i+1)."</span>&nbsp;</a>\n";
}
?>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td width="15%" class="tablehead"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg) ?>" title="Sort by login ID">Login ID</a></td>
	<td width="20%" class="tablehead"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg) ?>" title="Sort by name">Name</a></td>
	<td width="15%" class="tablehead"><a href="<?php print $formfile."?sc=2&pg=".urlencode($pg) ?>" title="Sort by status">Status</a></td>
	<td width="15%" class="tablehead"><a href="<?php print $formfile."?sc=4&pg=".urlencode($pg) ?>" title="Sort by role">Role</a></td>
	<td width="20%" class="tablehead"><a href="<?php print $formfile."?sc=3&pg=".urlencode($pg) ?>" title="Sort by last login">Last Login</a></td>
	<td width="15%" class="tablehead">Login Count</td>
	</tr>
	
	<tr class="tablelineadd" height="16">
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_USERADMIN))
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-user.php','pop_user',300,400)\" title=\"Add a new user\">Add User...</a></td>\n";
else
	print "<td class=\"tablelinktext\"></td>\n";

	print "<td class=\"tablelinktext\"></td>\n";
	print "<td class=\"tablelinktext\"></td>\n";
	print "<td class=\"tablelinktext\"></td>\n";
	print "<td class=\"tablelinktext\"></td>\n";
	print "<td class=\"tablelinktext\"></td>\n";
?>
	</tr>

<?php
$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	$trclass = "tableline".($i%2);
	print "<tr class=\"".$trclass."\" height=\"16\">\n";
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-user.php?uid=".urlencode($dset[$i]["uid"])."','pop_user',300,400)\" title=\"View/Edit user detail\">".htmlentities($dset[$i]["loginid"])."</a></td>\n";
	print "<td class=\"".$trclass."\">".htmlentities($dset[$i]["username"])."</td>\n";
	print "<td class=\"".$trclass."\">".htmlentities($dset[$i]["status"])."</td>\n";
	print "<td class=\"".$trclass."\">".htmlentities($dset[$i]["rolename"])."</td>\n";
	print "<td class=\"".$trclass."\">".htmlentities($dset[$i]["lastlogin"])."</td>\n";
	print "<td class=\"".$trclass."\">".htmlentities($dset[$i]["logincount"])."</td>\n";
	print "</tr>\n";
}
?>
	</table>
	</td>
	</tr>
	
	</table>
</section>
<p/>
<?php 
$myparts->FormRender_BottomPanel_Login($bottomparams);
?>
</div>
</body></html>
