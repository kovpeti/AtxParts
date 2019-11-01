<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-categories.php 201 2016-07-17 05:49:39Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=description
//		1=datadir

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-categories.php";
$formname = "categories";
$formtitle= "Part Categories";
$rpp = 30;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_PARTS) !== true)
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
	
// Retrieve the states for display
$dset = array();
$q_p = "select * from pgroups ";

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by catdescr asc ";
			break;
	case "1":
			$q_p .= "\n order by datadir asc ";
			break;
	default:
			$q_p .= "\n order by catdescr asc ";
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
			$dset[$i]["partcatid"] = $r_p["partcatid"];
			$dset[$i]["catdescr"] = $r_p["catdescr"];
			$dset[$i]["datadir"] = $r_p["datadir"];
			$q_n = "select partid from parts "
					. "\n where partcatid='".$r_p["partcatid"]."' "
					;
			$s_n = $dbh->query($q_n);
			$dset[$i]["numusing"] = 0;
			if ($s_n)
			{
				$dset[$i]["numusing"] = $s_n->num_rows;
				$s_n->free();
			}
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
$tabparams["tabon"] = "Parts";
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
	<th class="dataview_c" width="4%"></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-parts.php"><span class="pagebtntext" title="Parts">Parts</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-components.php"><span class="pagebtntext" title="Component Detail">Components</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-compstates.php"><span class="pagebtntext" title="Component States">States</span></a></th>
	<th class="pagebtn_c pagebtncell_on" width="13%"><span class="pagebtntext">Categories</span></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-datasheets.php"><span class="pagebtntext" title="Component Datasheets">Datasheets</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-footprints.php"><span class="pagebtntext" title="Part Footprints">Footprints</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-stock.php"><span class="pagebtntext" title="Part Stock">Stock</span></a></th>
	<th class="pagebtn_c" width="5%"></th>
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
	<td width="40%" class="tablehead"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg) ?>" title="Sort by part category">Part Category</a></td>
	<td width="40%" class="tablehead"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg) ?>" title="Sort by part data directory">Data Directory</a></td>
	<td width="20%" class="tablehead">Parts Using</td>
	</tr>

	<tr class="tablelineadd" height="16">
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_PARTCATS))
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-category.php','pop_cat',300,400)\" title=\"Add a new part category\">Add Category...</a></td>\n";
else
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
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-category.php?catid=".urlencode($dset[$i]["partcatid"])."','pop_cat',300,400)\" title=\"View/Edit part category detail\">".htmlentities($dset[$i]["catdescr"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["datadir"])."</td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["numusing"])."</td>\n";
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
