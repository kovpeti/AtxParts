<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-datasheets.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=description
//		1=datasheetpath
//		2=category

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-datasheets.php";
$formname = "datasheets";
$formtitle= "Component Datasheets";
$rpp = 30;
$var_fc = "filter_fc";

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
	
$fc = false;
if (isset($_GET['fc']))
	$fc = trim(urldecode(($_GET["fc"])));
	
if (isset($_POST["btn_filter"]))
{
	if (isset($_POST["partcat"]))
	{
		$fc = trim($_POST["partcat"]);
		$myparts->SessionVarSave($var_fc, $fc);
	}
	$urlq = "?sc=".urlencode($sc)."&fc=".urlencode($fc)."&pg=".urlencode($pg);
	print "<script type=\"text/javascript\">top.location.href='".$formfile.$urlq."'</script>\n";
}

// If false, then read out the last value saved.
// Otherwise save the selected value
if ($fc === false)
{
	$f = $myparts->SessionVarRead($var_fc);
	if ($f === false)
	{
		$fc = "";
		$myparts->SessionVarSave($var_fc, $fc);
	}
	else 
		$fc = $f;
}
else 
	$myparts->SessionVarSave($var_fc, $fc);

// Retrieve the states for display
$dset = array();
$q_p = "select * from datasheets "
	." \n left join pgroups on pgroups.partcatid=datasheets.partcatid "
	;

// filter by category if selected
if (($fc !== false) && ($fc != ""))
	$q_p .= "\n where datasheets.partcatid='".$dbh->real_escape_string($fc)."' ";
	
// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by datadescr asc ";
			break;
	case "1":
			$q_p .= "\n order by datasheetpath asc ";
			break;
	case "2":
			$q_p .= "\n order by catdescr asc ";
			break;
	default:
			$q_p .= "\n order by datadescr asc ";
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
			$dset[$i]["dataid"] = $r_p["dataid"];
			$dset[$i]["datadescr"] = $r_p["datadescr"];
			$dset[$i]["datasheetpath"] = $r_p["datasheetpath"];
			$dset[$i]["datadir"] = $r_p["datadir"];
			$dset[$i]["catid"] = $r_p["partcatid"];
			$dset[$i]["category"] = $r_p["catdescr"];
			$q_n = "select compid from components "
				. "\n where datasheet='".$r_p["dataid"]."' "
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

// Get a list of categories for the filter
$q_partcat = "select * from pgroups "
		. "\n order by catdescr "
		;
		
$s_partcat = $dbh->query($q_partcat);
$list_partcat = array();
$list_partcat[0][0] = "";
$list_partcat[0][1] = "All";
$i = 1;
if ($s_partcat)
{
	while ($r_partcat = $s_partcat->fetch_assoc())
	{
		$list_partcat[$i][0] = $r_partcat["partcatid"];
		$list_partcat[$i][1] = $r_partcat["catdescr"];
		$i++;
	}
	$s_partcat->free();
}

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
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-categories.php"><span class="pagebtntext" title="Part Categories">Categories</span></a></th>
	<th class="pagebtn_c pagebtncell_on" width="13%"><span class="pagebtntext">Datasheets</span></th>
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
$urlq = "?sc=".urlencode($sc)."&fc=".urlencode($fc);
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
	<form name="filterform" method="post" action="<?php print $formfile.$urlq ?>">
	<td class="tablelinefilter"><input type="submit" name="btn_filter" class="btntext" value="Apply Filter"></td>
	<td class="tablelinefilter">
	<select name="partcat" style="width: 20em">
	<?php $myparts->RenderOptionList($list_partcat, $fc, false); ?>
	</select>
	</td>
	<td class="tablelinefilter"></td>
	<td class="tablelinefilter"></td>
	</form>
	</tr>
	
	<tr>
	<td width="45%" class="tablehead"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg)."&fc=".urlencode($fc) ?>" title="Sort by datasheet description">Datasheet Description</a></td>
	<td width="25%" class="tablehead"><a href="<?php print $formfile."?sc=2&pg=".urlencode($pg)."&fc=".urlencode($fc) ?>" title="Sort by category">Category</a></td>
	<td width="25%" class="tablehead"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg)."&fc=".urlencode($fc) ?>" title="Sort by datasheet location">Datasheet File</a></td>
	<td width="5%" class="tablehead" title="Number of components referring to this datasheet">Using</td>
	</tr>

	<tr class="tablelineadd" height="16">
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_DATASHEEETS))
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-datasheet.php','pop_datasheet',300,400)\" title=\"Add a new component datasheet\">Add Datasheet...</a></td>\n";
else
	print "<td class=\"tablelinktext\"></td>\n";
	
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
	if ($dset[$i]["datasheetpath"] != NULL)
	{
		$dsheetpath = "../".DATASHEETS_DIR.$dset[$i]["datadir"];
		if (substr($dsheetpath, -1) != "/")
			$dsheetpath .= "/";
		$dsheetpath .= $dset[$i]["datasheetpath"];
	}
	else 
		$dsheetpath = false;
	$trclass = "tableline".($i%2);
	print "<tr class=\"".$trclass."\" height=\"16\">\n";
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-datasheet.php?dataid=".urlencode($dset[$i]["dataid"])."','pop_datasheet',300,400)\" title=\"View/Edit component datasheet detail\">".htmlentities($dset[$i]["datadescr"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["category"])."</td>\n";
	print "<td class=\"tablelinktext\">";
	if ($dsheetpath !== false)
		print "<a href=\"javascript:popupOpener('".$dsheetpath."','pop_showdatasheet',800,800)\" title=\"View component datasheet\"><img src=\"../images/icon-pdf.png\" width=\"16\" height=\"16\" border=\"0\">&nbsp;".htmlentities($dset[$i]["datasheetpath"])."</a>\n";
	else 
		print "&nbsp;";
	print "</td>\n";
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
