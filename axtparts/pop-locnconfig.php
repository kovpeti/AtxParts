<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-locnconfig.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: 0 = location
//      1 = locref

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-locnconfig.php";
$formname = "poplocnconfig";
$formtitle= "Configure Part Locations";
$rpp = 25;

$popx = 700;
$popy = 900;

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
	
$dset = array();
$q_p = "select locid, "
	. "\n locref, "
	. "\n locdescr "
	. "\n from locn "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by locdescr asc ";
			break;
	case "1":
			$q_p .= "\n order by locref asc ";
			break;
	default:
			$q_p .= "\n order by locdescr asc ";
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
			$dset[$i]["locid"] = $r_p["locid"];
			$dset[$i]["locref"] = $r_p["locref"];
			$dset[$i]["locdescr"] = $r_p["locdescr"];
			$q_n = "select partid "
				. "\n from stock "
				. "\n where locid='".$r_p["locid"]."' "
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
$headparams["title"] = "Location Configuration";
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
	<span class="formheadtext">Stock Locations</span>
	<p/>
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
	<td class="tablehead" width="360"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg) ?>" title="Sort by location">Location</a></td>
	<td class="tablehead" width="180"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg) ?>" title="Sort by location Reference">Loc Ref</a></td>
	<td class="tablehead" width="60">Using</td>
	</tr>
	
	<tr>
	<td class="tablelineadd"></td>
	<td class="tablelineadd"><a href="javascript:popupOpener('pop-locn.php','pop_locn',300,400)" title="Add location detail">Add...</a></td>
	<td class="tablelineadd"></td>
	</tr>
	
<?php
$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	$trclass = "tableline".($i%2);
	print "<tr class=\"".$trclass."\" height=\"16\">\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["locdescr"])."</td>\n";
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-locn.php?locid=".urlencode($dset[$i]["locid"])."','pop_locn',300,400)\" title=\"View/Edit part location detail\">".htmlentities($dset[$i]["locref"])."</a></td>\n";
	print "<td class=\"tablelinktext\">";
	if ($dset[$i]["numusing"] > 0)
		print "<a href=\"javascript:popupOpener('pop-locncontents.php?locid=".urlencode($dset[$i]["locid"])."','pop_locncontents',300,400)\" title=\"Show contents of location\">".htmlentities($dset[$i]["numusing"])."</a>";
	else
		print htmlentities($dset[$i]["numusing"]);
	print "</td>\n";
	print "</tr>\n";
}
?>
	</table>
	</section>
</body></html>
