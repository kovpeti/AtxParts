<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-logs.php 203 2016-07-17 06:16:46Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=logdate
//      1=user
//      2=logtype

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-logs.php";
$formname = "logs";
$formtitle= "Event Logs";
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
	
$sc = 2;
if (isset($_GET['sc']))
	$sc = trim(urldecode(($_GET["sc"])));
if (!is_numeric($sc))
	$sc = 2;
	
// Retrieve the log table for display
$dset = array();
$q_p = "select * from log "
	. "\n left join user on user.uid=log.uid "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by loginid asc, logdate desc ";
			break;
	case "1":
			$q_p .= "\n order by username asc, logdate desc ";
			break;
	case "2":
			$q_p .= "\n order by logdate desc, loginid asc ";
			break;
	case "3":
			$q_p .= "\n order by logmsg asc, logdate desc, loginid asc ";
			break;
	default:
			$q_p .= "\n order by logdate desc, loginid asc ";
			break;
}
if (MAXLOGS > 0)
	$q_p .= "\n limit ".MAXLOGS;

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
			$dset[$i]["logtype"] = $r_p["logtype"];
			$dset[$i]["logmsg"] = $r_p["logmsg"];
			$dset[$i]["logdate"] = $r_p["logdate"];
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
	<th class="pagebtn_c pagebtncell_on" width="13%"><span class="pagebtntext">Logs</span></th>
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
	<td width="10%" class="tablehead"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg) ?>" title="Sort by login ID">Login ID</a></td>
	<td width="20%" class="tablehead"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg) ?>" title="Sort by Name">Name</a></td>
	<td width="15%" class="tablehead"><a href="<?php print $formfile."?sc=2&pg=".urlencode($pg) ?>" title="Sort by log date">Log Date</a></td>
	<td width="55%" class="tablehead"><a href="<?php print $formfile."?sc=3&pg=".urlencode($pg) ?>" title="Sort by log message">Log Message</a></td>
	</tr>
	
<?php
$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	$trclass = "tableline".($i%2);
	print "<tr class=\"".$trclass."\" height=\"16\">\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["loginid"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["username"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["logdate"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["logmsg"])."</a></td>\n";
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
