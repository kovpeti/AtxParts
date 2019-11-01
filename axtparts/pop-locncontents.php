<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-locncontents.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: 0 = location
//      1 = locref
// $locid: location ID

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-locncontents.php";
$formname = "poplocncontents";
$formtitle= "Show Location Contents";
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
	
$locid = false;
if (isset($_GET['locid']))
	$locid = trim(urldecode(($_GET["locid"])));
if (!is_numeric($locid))
	$locid = false;

if ($locid === false)
{
	$myparts->AlertMeTo("Require a location.");
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

$dset = array();
$q_l = "select locid, "
	. "\n locdescr, "
	. "\n locref "
	. "\n from locn "
	. "\n where locid='".$dbh->real_escape_string($locid)."' "
	;
$s_l = $dbh->query($q_l);
if ($s_l)
{
	$r_l = $s_l->fetch_assoc();
	$locref = $r_l["locref"];
	$locdescr = $r_l["locdescr"];
	$s_l->free();
}

$q_p = "select * "
	. "\n  from stock "
	. "\n left join parts on parts.partid=stock.partid "
	. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
	. "\n where locid='".$dbh->real_escape_string($locid)."' "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by partnumber asc ";
			break;
	case "1":
			$q_p .= "\n order by catdescr asc, partdescr asc ";
			break;
	default:
			$q_p .= "\n order by catdescr asc, partdesc asc ";
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
			$dset[$i]["stockid"] = $r_p["stockid"];
			$dset[$i]["partid"] = $r_p["partid"];
			$dset[$i]["partnumber"] = $r_p["partnumber"];
			$dset[$i]["partdescr"] = $r_p["partdescr"];
			$dset[$i]["catdescr"] = $r_p["catdescr"];
			$dset[$i]["qty"] = $r_p["qty"];
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
$headparams["title"] = "Location Contents";
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
	<span class="formheadtext"><?php print htmlentities($locref)." (".htmlentities($locdescr).") Contents" ?></span>
	<p/>
<?php
print "<span class=\"pageon\">Page: </span>\n";
$urlq = "?sc=".urlencode($sc)."&locid=".urlencode($locid);
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
	<td class="tablehead" width="140"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg) ?>" title="Sort by part number">Part Number</a></td>
	<td class="tablehead" width="400"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg) ?>" title="Sort by part descr">Description</a></td>
	<td class="tablehead" width="60">Qty</td>
	</tr>

<?php
$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	$trclass = "tableline".($i%2);
	print "<tr class=\"".$trclass."\" height=\"16\">\n";
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-part.php?partid=".urlencode($dset[$i]["partid"])."','pop_part',300,400)\" title=\"View/Edit part detail\">".htmlentities($dset[$i]["partnumber"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["qty"])."</td>\n";
	print "</tr>\n";
}
?>
	</table>
</section>
</body></html>
