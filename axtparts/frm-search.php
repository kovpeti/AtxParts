<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-search.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=part number
//      1=category
//      2=description
//      3=footprint
// $st: search text

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-search.php";
$formname = "search";
$formtitle= "Part & Component Search";
$rpp = 30;
$var_search = "vsearch";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_SEARCH) !== true)
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
	
$st = false;
if (isset($_GET['st']))
	$st = trim(urldecode(($_GET["st"])));
	
if (isset($_POST["btn_search"]))
{
	if (isset($_POST["searchtext"]))
	{
		$st = trim($_POST["searchtext"]);
		$myparts->SessionVarSave($var_search, $st);
	}
	$urlq = "?sc=".urlencode($sc)."&st=".urlencode($st)."&pg=".urlencode($pg);
	print "<script type=\"text/javascript\">top.location.href='".$formfile.$urlq."'</script>\n";
}

// If false, then read out the last value saved.
// Otherwise save the selected value
if ($st === false)
{
	$f = $myparts->SessionVarRead($var_search);
	if ($f === false)
	{
		$st = "";
		$myparts->SessionVarSave($var_search, $st);
	}
	else 
		$st = $f;
}
else 
	$myparts->SessionVarSave($var_search, $st);
	
$sr = array();
$n = 0;

if ($st != "")
{
	// Look for parts
	$q_search = "select * from parts "
			. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
			. "\n left join footprint on footprint.fprintid=parts.footprint "
			. "\n where partdescr like '%".$dbh->real_escape_string($st)."%' "
			;
	// Add sorting
	switch ($sc)
	{
		case "0":
				$q_search .= "\n order by partnumber asc ";
				break;
		case "1":
				$q_search .= "\n order by catdescr asc ";
				break;
		case "2":
				$q_search .= "\n order by partdescr asc ";
				break;
		case "3":
				$q_search .= "\n order by fprintdescr asc ";
				break;
		default:
				$q_search .= "\n order by partdescr asc ";
				break;
	}
	$s_search = $dbh->query($q_search);

	$startidx = $pg * $rpp;
	$endidx = ($pg + 1) * $rpp;
	$cp = 0;
	if ($s_search)
	{
		while (($r_search = $s_search->fetch_assoc()) && ($cp < $endidx))
		{
			if ($cp < $startidx)
				$cp++;
			else 
			{
				$sr[$n]["partid"] = $r_search["partid"];
				$sr[$n]["partnumber"] = $r_search["partnumber"];
				$sr[$n]["catdescr"] = $r_search["catdescr"];
				$sr[$n]["partdescr"] = $r_search["partdescr"];
				$sr[$n]["fprintdescr"] = $r_search["fprintdescr"];
				// Count the stock
				$q_s = "select sum(qty) as totalqty "
					. "\n from stock "
					. "\n where partid='".$r_search["partid"]."' "
					;
				$s_s = $dbh->query($q_s);	
				$sr[$n]["stockqty"] = 0;
				if ($s_s)
				{
					$r_s = $s_s->fetch_assoc();
					if (isset($r_s["totalqty"]))
						$sr[$n]["stockqty"] = $r_s["totalqty"];
					$s_s->free();
				}
					
				$n++;
				$cp++;
			}
		}
		$s_search->free();
	}
		
	// Look for components
	$q_search = "select * from components "
			. "\n left join parts on parts.partid=components.partid "
			. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
			. "\n left join footprint on footprint.fprintid=parts.footprint "
			. "\n where mfgcode like '%".$dbh->real_escape_string($st)."%' "
			;
	// Add sorting
	switch ($sc)
	{
		case "0":
				$q_search .= "\n order by partnumber asc ";
				break;
		case "1":
				$q_search .= "\n order by catdescr asc ";
				break;
		case "2":
				$q_search .= "\n order by partdescr asc ";
				break;
		case "3":
				$q_search .= "\n order by fprintdescr asc ";
				break;
		case "4":
				$q_search .= "\n order by mfgcode asc ";
				break;
		default:
				$q_search .= "\n order by mfgcode asc ";
				break;
	}
	$s_search = $dbh->query($q_search);

	if ($s_search)
	{
		while (($r_search = $s_search->fetch_assoc()) && ($cp < $endidx))
		{
			if ($cp < $startidx)
				$cp++;
			else 
			{
				$sr[$n]["partid"] = $r_search["partid"];
				$sr[$n]["compid"] = $r_search["compid"];
				$sr[$n]["partnumber"] = $r_search["partnumber"];
				$sr[$n]["catdescr"] = $r_search["catdescr"];
				$sr[$n]["partdescr"] = $r_search["partdescr"];
				$sr[$n]["mfgcode"] = $r_search["mfgcode"];
				$sr[$n]["fprintdescr"] = $r_search["fprintdescr"];
				// Count the stock
				$q_s = "select sum(qty) as totalqty "
					. "\n from stock "
					. "\n where partid='".$r_search["partid"]."' "
					;
				$s_s = $dbh->query($q_s);	
				$sr[$n]["stockqty"] = 0;
				if ($s_s)
				{
					$r_s = $s_s->fetch_assoc();
					if (isset($r_s["totalqty"]))
						$sr[$n]["stockqty"] = $r_s["totalqty"];
					$s_s->free();
				}
					
				$n++;
				$cp++;
			}
		}
		$s_search->free();
	}
}

$np = intval($n/$rpp);
if (($n % $rpp) > 0)
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
$tabparams["tabon"] = "Search";
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
	<span class="formheadtext">Search for Parts</span>
	</td>
	</tr>
	
	<form name="searchform" action="<?php print $formfile ?>" method="post">
	<tr class="contentrow_40">
	<td class="contentcell_lt" colspan="8">
	<span class="smlgrytext">Part or Component</span><br/>
	<input type="text" name="searchtext" tabindex="1" size="40" maxlength="40" value="<?php print htmlentities($st) ?>" />
	</td>
	<td class="contentcell_lt" colspan="8">
	<span class="smlgrytext"></span><br/>
	<input type="submit" name="btn_search" class="btntext" value="Search" />
	</td>
	<td class="contentcell_lt" colspan="8"></td>
	</tr>
	</form>	

	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>
	
	<tr>
	<td class="contentcell_lt" colspan="24">
<?php
print "<span class=\"pageon\">Page: </span>\n";
$urlq = "?sc=".urlencode($sc)."&st=".urlencode($st);
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
	<td width="10%" class="tablehead"><a href="<?php print $formfile."?sc=0&pg=".urlencode($pg)."&st=".urlencode($st) ?>" title="Sort by part number">Part Number</a></td>
	<td width="20%" class="tablehead"><a href="<?php print $formfile."?sc=1&pg=".urlencode($pg)."&st=".urlencode($st) ?>" title="Sort by part category">Category</a></td>
	<td width="30%" class="tablehead"><a href="<?php print $formfile."?sc=2&pg=".urlencode($pg)."&st=".urlencode($st) ?>" title="Sort by part description">Description</a></td>
	<td width="23%" class="tablehead"><a href="<?php print $formfile."?sc=4&pg=".urlencode($pg)."&st=".urlencode($st) ?>" title="Sort by component code">Component</a></td>
	<td width="12%" class="tablehead"><a href="<?php print $formfile."?sc=3&pg=".urlencode($pg)."&st=".urlencode($st) ?>" title="Sort by part footprint">Footprint</a></td>
	<td width="5%" class="tablehead">Stock</td>
	</tr>
	
<?php
for ($i = 0; $i < $n; $i++)
{
	$trclass = "tableline".($i%2);
	print "<tr class=\"".$trclass."\" height=\"16\">\n";
	print "<td class=\"tablelinktext\"><a href=\"javascript:popupOpener('pop-part.php?partid=".urlencode($sr[$i]["partid"]).($st === false ? "" : "&st=".urlencode($st))."','pop_part',300,400)\" title=\"View/Edit part detail\">".htmlentities($sr[$i]["partnumber"])."</a></td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($sr[$i]["catdescr"])."</td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($sr[$i]["partdescr"])."</td>\n";
	print "<td class=\"tablelinktext\">".(isset($sr[$i]["mfgcode"]) ? htmlentities($sr[$i]["mfgcode"]) : "&nbsp;")."</td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($sr[$i]["fprintdescr"])."</td>\n";
	print "<td class=\"tablelinktext\">".htmlentities($sr[$i]["stockqty"])."</td>\n";
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
