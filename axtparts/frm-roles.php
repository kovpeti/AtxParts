<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-roles.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-roles.php";
$formname = "roles";
$formtitle= "User Roles";
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
	
// Retrieve the role table for display
$dset = array();
$q_p = "select * from role "
	. "\n order by rolename "
	;

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
			$dset[$i]["roleid"] = $r_p["roleid"];
			$dset[$i]["rolename"] = $r_p["rolename"];
			$dset[$i]["privilege"] = $r_p["privilege"];
			$i++;
			$cp++;
		}
	}
	$s_p->free();
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
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-admin.php"><span class="pagebtntext">Users</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-logs.php"><span class="pagebtntext" title="System Logs">Logs</span></a></th>
	<th class="pagebtn_c pagebtncell_on" width="13%"><span class="pagebtntext">Roles</span></th>
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
for ($i = 0; $i < $np; $i++)
{
	if ($pg == $i)
		print "&nbsp;<span class=\"pageon\">".($i+1)."</span>&nbsp;\n";
	else
		print "<a href=\"".htmlentities($formfile).$urlq."?pg=".$i."\">&nbsp;<span class=\"pageoff\">".($i+1)."</span>&nbsp;</a>\n";
}
?>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td width="10%" class="tablehead">Role</td>
	<td width="90%" class="tablehead">Privileges</td>
	</tr>
	
	<tr class="tablelineadd" height="16">
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_USERADMIN))
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-roles.php','pop_roles',300,400)\" title=\"Add a new role\">Add Role...</a></td>\n";
else
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
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-roles.php?rid=".urlencode($dset[$i]["roleid"])."','pop_roles',300,400)\" title=\"View/Edit role detail\">".htmlentities($dset[$i]["rolename"])."</a></td>\n";
	print "<td class=\"".$trclass."\">";
	print "0x".dechex($dset[$i]["privilege"]).": ";
	for ($j = 0; $j < 32; $j++)
	{
		$b = (1 << $j);
		if ($dset[$i]["privilege"] & $b)
		{
			if (isset($_upriv_text[$b]))
				print $_upriv_text[$b].", ";
		}
	}
	print "</td>\n";
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
