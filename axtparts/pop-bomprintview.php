<?php
// ********************************************
// Copyright 2003-2015 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bomprintview.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $assyid: ID of assembly to edit BOM
// $variantid: ID of the assembly variant

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-bomprintview.php";
$formname = "popbomprintview";
$formtitle= "Printable View of Assembly BOM";

$popx = 1200;
$popy = 900;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

$assyid = false;
if (isset($_GET['assyid']))
	$assyid = trim(urldecode(($_GET["assyid"])));
if (!is_numeric($assyid))
	$assyid = false;

if ($assyid === false)
{
		$myparts->AlertMeTo("An assembly must be specified.");
		$myparts->PopMeClose();
		die();
}

$variantid = false;
if (isset($_GET['variantid']))
	$variantid = trim(urldecode(($_GET["variantid"])));
if (!is_numeric($variantid))
	$variantid = false;
		
if ($variantid === false)
{
	$myparts->AlertMeTo("A variant must be specified.");
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

$showstock = false;
if (isset($_GET['showstock']))
	$showstock = trim(urldecode(($_GET["showstock"])));
if (!is_numeric($showstock))
	$showstock = false;
	
if ($assyid !== false)
{
	// Get the details for the assembly
	$q_a = "select * "
		. "\n from assemblies "
		. "\n left join parts on parts.partid=assemblies.partid "
		. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
		;
	$s_a = $dbh->query($q_a);
	if (!$s_a)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_a = $s_a->fetch_assoc();
		$assy["partnumber"] = $r_a["partnumber"];
		$assy["assyname"] = $r_a["partdescr"];
		$assy["assydescr"] = $r_a["assydescr"];
		$assy["assyrev"] = str_pad($r_a["assyrev"], 2, "0", STR_PAD_LEFT);
		$assy["assyaw"] = $r_a["assyaw"];
		$s_a->free();
	}
	
	// Get the details for the variant
	$q_v = "select variantid, "
		. "\n variantname, "
		. "\n variantdescr, "
		. "\n variantstate "
		. "\n from variant "
		. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
		;
	$s_v = $dbh->query($q_v);
	if (!$s_v)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_v = $s_v->fetch_assoc();
		$variant["variantname"] = $r_v["variantname"];
		$variant["variantdescr"] = $r_v["variantdescr"];
		$variant["variantstate"] = $r_v["variantstate"];
		$s_v->free();
	}
	
	// Get the BOM items for the assembly/variant
	$q_b = "select * "
		. "\n from boms "
		. "\n left join parts on parts.partid=boms.partid "
		. "\n left join assemblies on assemblies.assyid=boms.assyid "
		. "\n left join bomvariants on bomvariants.bomid=boms.bomid "
		. "\n left join variant on variant.variantid=bomvariants.variantid "
		. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
		. "\n left join footprint on footprint.fprintid=parts.footprint "
		. "\n where boms.assyid='".$dbh->real_escape_string($assyid)."' "
		. "\n and bomvariants.variantid='".$dbh->real_escape_string($variantid)."' "
		. "\n order by catdescr, partdescr "
		;
		
	$s_b = $dbh->query($q_b);
	if (!$s_b)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$i = 0;
		while ($r_b = $s_b->fetch_assoc())
		{
			$dset[$i]["partnumber"] = $r_b["partnumber"];
			$dset[$i]["catdescr"] = $r_b["catdescr"];
			$dset[$i]["partdescr"] = $r_b["partdescr"];
			$dset[$i]["footprint"] = $r_b["fprintdescr"];
			$dset[$i]["qty"] = $r_b["qty"];
			$dset[$i]["um"] = $r_b["um"];
			$dset[$i]["ref"] = $r_b["ref"];
			$dset[$i]["altid"] = $r_b["alt"];
			$dset[$i]["bomid"] = $r_b["bomid"];
			
			// If alt is > 0 then find the part detail for it.
			$altid = $r_b["alt"];
			$dset[$i]["altpartnumber"] = "";
			$dset[$i]["altcatdescr"] = "";
			$dset[$i]["altpartdescr"] = "";
			$dset[$i]["altfprint"] = "";
			if (($altid != null) && ($altid > 0))
			{
				$q_alt = "select * "
					. "\n from parts "
					. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
					. "\n left join footprint on footprint.fprintid=parts.footprint "
					. "\n where partid='".$dbh->real_escape_string($altid)."' "
					;
				$s_alt = $dbh->query($q_alt);
				if ($s_alt)
				{
					$r_alt = $s_alt->fetch_assoc();
					$dset[$i]["altpartnumber"] = $r_alt["partnumber"];
					$dset[$i]["altcatdescr"] = $r_alt["catdescr"];
					$dset[$i]["altpartdescr"] = $r_alt["partdescr"];
					$dset[$i]["altfprint"] = $r_alt["fprintdescr"];
					$s_alt->free();
				}
			}
			
			// If we need to show stock levels for the part
			if ($showstock !== false)
			{
				$q_p = "select partid "
					. "\n from parts "
					. "\n where partnumber='".$r_b["partnumber"]."' "
					;
				$s_p = $dbh->query($q_p);
				if ($s_p)
				{
					$r_p = $s_p->fetch_assoc();
				
					$q_stk = "select sum(qty) as stockqty "
						. "\n from stock "
						. "\n where partid='".$r_p["partid"]."' "
						;
					$s_stk = $dbh->query($q_stk);
					$dset[$i]["stockqty"] = 0;
					$dset[$i]["stockloc"] = "";
					if ($s_stk)
					{
						$r_stk = $s_stk->fetch_assoc();
						if ($r_stk["stockqty"] !== null)
						{
							$dset[$i]["stockqty"] = $r_stk["stockqty"];
					
							$q_sl = "select * "
								. "\n from stock "
								. "\n left join locn on locn.locid=stock.locid "
								. "\n where partid='".$r_p["partid"]."' "
								;
							$s_sl = $dbh->query($q_sl);
							if ($s_sl)
							{
								while ($r_sl = $s_sl->fetch_assoc())
									$dset[$i]["stockloc"] .= $r_sl["locref"].", ";
								$dset[$i]["stockloc"] = substr($dset[$i]["stockloc"], 0, -2);
								$s_sl->free();
							}
						}
						$s_stk->free();
					}
					$s_p->free();
				}
			}
			$i++;
		}
		$s_b->free();
	}
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Assembly Properties";
$headparams["icon"] = $_cfg_stdicon;
$headparams["css"] = $_cfg_stdcss;
$headparams["jscript_file"] = $_cfg_stdjscript;
$headparams["jscript_local"][] = "window.resizeTo(".$popx.",".$popy.");";
$headparams["jscript_local"][] = "window.opener.location.href=window.opener.location.href;";
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

?>
	<section class="contentpanel_popup">
	<span class="print_title">Bill Of Materials</span>
	<p/>

	<table class="print_table" width="100%">
	<tr class="contentrow_10">
	<td width="60%" class="contentcell_lt print_labelsmall">Assembly</td>
	<td width="20%" class="contentcell_lt print_labelsmall">Revision</td>
	<td width="20%" class="contentcell_lt print_labelsmall">Artwork</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt print_heading"><?php print $assy["assyname"]." ".$assy["assydescr"] ?></td>
	<td class="contentcell_lt print_heading"><?php print $assy["assyrev"] ?></td>
	<td class="contentcell_lt print_heading"><?php print $assy["assyaw"] ?></td>
	</tr>
	
	<tr class="contentrow_10">
	<td class="contentcell_lt print_labelsmall">Variant</td>
	<td colspan="2" class="contentcell_lt print_labelsmall">Status</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt print_heading"><?php print $variant["variantname"]." ".$variant["variantdescr"] ?></td>
	<td class="contentcell_lt print_heading"><?php print $variant["variantstate"] ?></td>
	</tr>
	</table>
	<p/>
	
	<table class="print_table" width="100%">
	<tr>
	<td width="11%" class="print_tablehead">Part Number</td>
	<td width="42%" class="print_tablehead">Part</td>
	<td width="5%" class="print_tablehead">Qty</td>
	<td width="5%" class="print_tablehead">UM</td>
	<td width="20%" class="print_tablehead">Ref</td>
<?php
if ($showstock === false)
	print "<td width=\"17%\" class=\"print_tablehead\">Alt Part</td>\n";
else
{
	print "<td width=\"5%\" class=\"print_tablehead\">Stock</td>\n";
	print "<td width=\"12%\" class=\"print_tablehead\">Locn</td>\n";
}
?>
	</tr>
	
<?php
$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	print "<tr>\n";
	print "<td class=\"contentcell_lt print_tablecell\">".htmlentities($dset[$i]["partnumber"])."</td>\n";
	print "<td class=\"contentcell_lt print_tablecell\">".htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])." ".htmlentities($dset[$i]["footprint"])."</td>\n";
	print "<td class=\"contentcell_lt print_tablecellnumber\">".htmlentities($dset[$i]["qty"])."</td>\n";
	print "<td class=\"contentcell_lt print_tablecell\">".htmlentities($dset[$i]["um"])."</td>\n";
	print "<td class=\"contentcell_lt print_tablecell\">".htmlentities($dset[$i]["ref"])."</td>\n";
	if ($showstock === false)
		print "<td class=\"contentcell_lt print_tablecell\">".htmlentities($dset[$i]["altcatdescr"])." ".htmlentities($dset[$i]["altpartdescr"])." ".htmlentities($dset[$i]["altfprint"])."</td>\n";
	else 
	{
		print "<td class=\"contentcell_lt print_tablecell\">".htmlentities($dset[$i]["stockqty"])."</td>\n";
		print "<td class=\"contentcell_lt print_tablecell\">".htmlentities($dset[$i]["stockloc"])."</td>\n";
	}
	print "</tr>\n";
}
?>
	</table>
	
	</section>
</body></html>
