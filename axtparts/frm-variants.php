<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-variants.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=description
//		1=name
//		2=state

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-variants.php";
$formname = "variants";
$formtitle= "Assembly Variants";
$rpp = 30;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_ASSY) !== true)
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
$q_p = "select variantid, "
	. "\n variantname, "
	. "\n variantdescr, "
	. "\n variantstate "
	. "\n from variant "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by variantdescr asc ";
			break;
	case "1":
			$q_p .= "\n order by variantname asc ";
			break;
	case "2":
			$q_p .= "\n order by variantstate asc ";
			break;
	default:
			$q_p .= "\n order by variantdescr asc ";
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
			$dset[$i]["variantid"] = $r_p["variantid"];
			$dset[$i]["variantdescr"] = $r_p["variantdescr"];
			$dset[$i]["variantname"] = $r_p["variantname"];
			$dset[$i]["variantstate"] = $r_p["variantstate"];
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
$tabparams["tabon"] = "Assembly";
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
	<th class="dataview_c" width="17%"></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-assembly.php"><span class="pagebtntext" title="Assemblies">Assembly</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-boms.php"><span class="pagebtntext" title="BOM Detail">BOM</span></a></th>
	<th class="pagebtn_c pagebtncell_on" width="13%"><span class="pagebtntext">Variant</span></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-engdocs.php"><span class="pagebtntext" title="Engineering Documents">Docs</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-swbuild.php"><span class="pagebtntext" title="Software Builds">SW Build</span></a></th>
	<th class="pagebtn_c" width="18%"></th>
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
	<td width="20%" class="tablehead"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg) ?>" title="Sort by variant name">Variant Name</a></td>
	<td width="60%" class="tablehead"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg) ?>" title="Sort by variant description">Variant Description</a></td>
	<td width="20%" class="tablehead"><a href="<?php print $formfile."?sc=2&pg=".urlencode($pg) ?>" title="Sort by variant state">Variant State</a></td>
	</tr>

	<tr class="tablelineadd" height="16">
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_BOMVARIANT))
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-variant.php','pop_variant',300,400)\" title=\"Add a new assembly variant\">Add Variant...</a></td>\n";
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
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-variant.php?variantid=".urlencode($dset[$i]["variantid"])."','pop_variant',300,400)\" title=\"View/Edit assembly variant detail\">".htmlentities($dset[$i]["variantname"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["variantdescr"])."</td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($dset[$i]["variantstate"])."</td>\n";
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
