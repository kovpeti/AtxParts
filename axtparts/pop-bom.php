<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bom.php 215 2018-01-07 11:25:38Z gswan $

// Parameters passed: 
// none: create new BOM
// $assyid: ID of assembly to edit BOM
// $variantid: ID of the assembly variant

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-bom.php";
$formname = "popbom";
$formtitle= "Add/Edit Assembly BOM";

$popx = 1000;
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

$assyid = false;
$showstock = false;
$dset = array();

if (isset($_GET['assyid']))
	$assyid = trim(urldecode(($_GET["assyid"])));
if (!is_numeric($assyid))
	$assyid = false;

if ($assyid !== false)
{
	$variantid = false;
	if (isset($_GET['variantid']))
		$variantid = trim(urldecode(($_GET["variantid"])));
	if (!is_numeric($variantid))
		$variantid = false;
		
	if ($variantid === false)
	{
		$dbh->close();
		$myparts->AlertMeTo("A variant must be specified, or bad value ".trim(urldecode(($_GET["variantid"]))).".");
		$myparts->PopMeClose();
		die();
	}
	
	if (isset($_GET['showstock']))
		$showstock = trim(urldecode(($_GET["showstock"])));
	if (!is_numeric($showstock))
		$showstock = false;
}		
	
// Handle BOM Copy request
if (isset($_POST["btn_copybom"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMITEMS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["bomcopyvid"]))
		{
			$bcvariantid = $_POST["bomcopyvid"];
			if (is_numeric($bcvariantid))
			{
				// Use the bc variantid to read all bomvariant items matching and copy to the new variantid
				$q_bomsrc = "select * "
						. "\n from bomvariants "
						. "\n left join boms on boms.bomid=bomvariants.bomid "
						. "\n where variantid='".$dbh->real_escape_string($bcvariantid)."' "
						;
				$s_bomsrc = $dbh->query($q_bomsrc);
				$bcstop = false;
				if ($s_bomsrc)
				{
					while ($r_bomsrc = $s_bomsrc->fetch_assoc())
					{
						if (!$bcstop)
						{
							$dbom_partid = $r_bomsrc["partid"];
							$dbom_qty = $r_bomsrc["qty"];
							$dbom_ref = $r_bomsrc["ref"];
							$dbom_um = $r_bomsrc["um"];
							$dbom_alt = $r_bomsrc["alt"];
							
							// Insert a new bom line first
							$q_dbom = "insert into boms "
								. "\n set "
								. "\n partid='".$dbh->real_escape_string($dbom_partid)."', "
								. "\n assyid='".$dbh->real_escape_string($assyid)."', "
								. "\n qty='".$dbh->real_escape_string($dbom_qty)."', "
								. "\n um='".$dbh->real_escape_string($dbom_um)."', "
								. "\n ref='".$dbh->real_escape_string($dbom_ref)."', "
								. "\n alt='".$dbh->real_escape_string($dbom_alt)."' "
								;
							$s_dbom = $dbh->query($q_dbom);
							if ($s_dbom)
							{
								$dbom_bomid = $dbh->insert_id;
									
								// add the bomline to the variant table
								$q_v = "insert into bomvariants "
									. "\n set "
									. "\n variantid='".$dbh->real_escape_string($variantid)."', "
									. "\n bomid='".$dbh->real_escape_string($dbom_bomid)."' "
									;
								$s_v = $dbh->query($q_v);
							}
							else
							{
								$bcstop = true;
								$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
							}
						}
					}
					$s_bomsrc->free();
				}
			}
			else
				$myparts->AlertMeTo("Bad BOM variant specified for copy.");
		}
		else
			$myparts->AlertMeTo("BOM variantid for copy must be specified.");
	}
}


// Handle new BOM form submission here
if (isset($_POST["btn_newbom"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMITEMS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// A 'new' BOM simply consists of setting the assyid and variantid
		// Nothing is saved into the database until a part is allocated to the BOM
		if (isset($_POST["assyid"]))
		{
			$assyid = trim($_POST["assyid"]);
			if ($assyid == "")
				$assyid = false;
		}
		else 
			$assyid = false;
			
		if (isset($_POST["variantid"]))
		{
			$variantid = trim($_POST["variantid"]);
			if ($variantid == "")
				$variantid = false;
		}
		else 
			$variantid = false;

		if (($assyid === false) || ($variantid === false))
			$myparts->AlertMeTo("An assembly and a variant must be specified.");
		else
		{
			$urlargs = "?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid)."&showstock=".urlencode($showstock);
			$myparts->VectorMeTo($formfile.$urlargs);
		}
	}
}		

if ($assyid !== false)
{
	$urlargs = "?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid)."&showstock=".urlencode($showstock);
	
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
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
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
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
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
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
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
				$r_p = array();
				if ($s_p)
				{
					$r_p = $s_p->fetch_assoc();
					$s_p->free();
				}
				
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
			}
			$i++;
		}
		$s_b->free();
	}
	
	// Allow a copy from an existing BOM only if there are no components already assigned to this BOM.
	$allowcopy = false;
	$list_bomcopy = array();
	$b = 0;
	if (count($dset) == 0)	// no components
	{
		// Create a list of assemblies to copy a bom from
		$q_v = "select distinct bomvariants.variantid, "
			. "\n variantname, "
			. "\n variantdescr, "
			. "\n assyid "
			. "\n from bomvariants "
			. "\n left join variant on variant.variantid=bomvariants.variantid "
			. "\n left join boms on boms.bomid=bomvariants.bomid "
			. "\n order by variantdescr"
			;
		$s_v = $dbh->query($q_v);
		if ($s_v)
		{
			while ($r_v = $s_v->fetch_assoc())
			{
				$list_bomcopy[$b][0] = $r_v["variantid"];
				$list_bomcopy[$b][1] = $r_v["variantname"]." (".$r_v["variantdescr"].")";
				$b++;
			}
			$s_v->free();
		}
		if ($b> 0)
			$allowcopy = true;
	}
}
else 
{
	$urlargs="";
	// Get a list of assemblies and variants if we are adding a new BOM
	$q_assy = "select * "
			. "\n from assemblies "
			. "\n left join parts on parts.partid=assemblies.partid "
			. "\n order by partdescr, assyrev, assyaw "
			;
				
	$s_assy = $dbh->query($q_assy);
	$list_assy = array();
	$i = 0;
	if ($s_assy)
	{
		while ($r_assy = $s_assy->fetch_assoc())
		{
			$assyrev = str_pad($r_assy["assyrev"], 2, "0", STR_PAD_LEFT);
			$assyaw = $r_assy["assyaw"];
			$list_assy[$i][0] = $r_assy["assyid"];
			$list_assy[$i][1] = $r_assy["partdescr"]." - ".$assyrev.($assyaw == null ? "" : "-".$assyaw)." (".$r_assy["assydescr"].")";
			$i++;
		}
		$s_assy->free();
	}
	
	$q_var = "select variantid, "
			. "\n variantname, "
			. "\n variantdescr, "
			. "\n variantstate "
			. "\n from variant "
			. "\n order by variantname, variantdescr "
			;
				
	$s_var = $dbh->query($q_var);
	$list_var = array();
	$i = 0;
	if ($s_var)
	{
		while ($r_var = $s_var->fetch_assoc())
		{
			$list_var[$i][0] = $r_var["variantid"];
			$list_var[$i][1] = $r_var["variantname"]." (".$r_var["variantdescr"]." - ".$r_var["variantstate"].")";
			$i++;
		}
		$s_var->free();
	}
}

$myparts->UpdateParent();
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
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

if ($assyid === false)
{
	// New BOM
	$myparts->FormRender_PopClose();
?>
	<section class="contentpanel_popup">
	
	<span class="formheadtext">Add New BOM</span>
	<p/>

	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>">
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="200">Assembly</td>
	<td class="propscell_lt propcell" width="600">
	<select name="assyid" style="width: 40em" tabindex="10">
	<?php $myparts->RenderOptionList($list_assy, false, false); ?>
	</select>
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Variant</td>
	<td class="propscell_lt propcell">
	<select name="variantid" style="width: 40em" tabindex="20">
	<?php $myparts->RenderOptionList($list_var, false, false); ?>
	</select>
	</td>
	</tr>
	</table>

	<table class="contentpanel_popup">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_newbom" class="btntext" value="Save">
	</td>
	</tr>
	</table>
	</form>
<?php
}
else 
{
	// Edit an existing BOM
?>
	<section class="contentpanel_popup">
	
	<table class="contentpanel_popup">
	<tr class="contentrow_40">
	<td class="contentcell_lt" width="60%">
	<span class="formtitle">Bill of Materials</span>
	</td>
	<td class="contentcell_lt" width="10%"></td>
	<td class="contentcell_lt" width="30%">
<?php 
if ($showstock === false)
{
	print "<input type=\"button\" name=\"print\" class=\"btntext\" value=\"Show Stock\" title=\"Show stock levels\" onclick=\"javascript:top.location.href='".htmlentities($formfile)."?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid)."&showstock=1'\" />";
	print "&nbsp;&nbsp;";
	print "<input type=\"button\" name=\"print\" class=\"btntext\" value=\"Print\" title=\"View printable BOM\" onclick=\"javascript:popupOpenerMenus('pop-bomprintview.php?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid)."','pop_bomprint',300,400)\" />";
	print "&nbsp;&nbsp;";
}
else
{
	print "<input type=\"button\" name=\"print\" class=\"btntext\" value=\"Hide Stock\" title=\"Hide stock levels\" onclick=\"javascript:top.location.href='".htmlentities($formfile)."?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid)."'\" />";
	print "&nbsp;&nbsp;";
	print "<input type=\"button\" name=\"print\" class=\"btntext\" value=\"Print\" title=\"View printable BOM\" onclick=\"javascript:popupOpenerMenus('pop-bomprintview.php?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid)."&showstock=1','pop_bomprint',300,400)\" />";
	print "&nbsp;&nbsp;";
}
?>
	<input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" />
	</td>
	</tr>
	</table>
<?php 
	// If there are no components already entered into this BOM them allow a copy of an existing BOM 
	if ($allowcopy === true)
	{
		print "<form name=\"bomcopy\" method=\"post\" action=\"".$formfile.$urlargs."\" >";
		print "<table class=\"contentpanel\">";
		print "<tr class=\"contentrow_10\">";
		print "<td class=\"contentcell_lt\">";
		print "<span class=\"smlgrytext\">Copy a BOM from an existing assembly/variant</span>";
		print "</td>";
		print "</tr>";
		
		print "<tr class=\"contentrow_30\">";
		print "<td valign=\"top\">";
		print "<select name=\"bomcopyvid\" style=\"width: 60em\">";
		$myparts->RenderOptionList($list_bomcopy, false, false);
		print "</select>";
		print "&nbsp;&nbsp;";
		print "<input type=\"submit\" name=\"btn_copybom\" class=\"btntext\" value=\"Copy\" >";
		print "</td>";
		print "</tr>";
		print "</form>";
	}
?>
	
	<hr/><p/>
	
	<table class="contentpanel">
	<tr class="contentrow_10">
	<td class="contentcell_lt" width="60%"><span class="smlgrytext">Assembly</span></td>
	<td class="contentcell_lt" width="20%"><span class="smlgrytext">Revision</span></td>
	<td class="contentcell_lt" width="20%"><span class="smlgrytext">Artwork</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td valign="top"><span class="formheadtext"><?php print $assy["assyname"]." ".$assy["assydescr"] ?></span></td>
	<td valign="top"><span class="formheadtext"><?php print $assy["assyrev"] ?></span></td>
	<td valign="top"><span class="formheadtext"><?php print $assy["assyaw"] ?></span></td>
	</tr>
	
	<tr class="contentrow_10">
	<td valign="top"><span class="smlgrytext">Variant</span></td>
	<td colspan="2" valign="top"><span class="smlgrytext">Status</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td valign="top"><span class="formheadtext"><?php print $variant["variantname"]." ".$variant["variantdescr"] ?></span></td>
	<td valign="top"><span class="formheadtext"><?php print $variant["variantstate"] ?></span></td>
	</tr>
	</table>
	
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<tr>
	<td width="11%" class="tablehead">Part Number</td>
	<td width="42%" class="tablehead">Part</td>
	<td width="5%" class="tablehead">Qty</td>
	<td width="5%" class="tablehead">UM</td>
	<td width="20%" class="tablehead">Ref</td>
<?php
if ($showstock === false)
	print "<td width=\"17%\" class=\"tablehead\">Alt Part</td>\n";
else
{
	print "<td width=\"6%\" class=\"tablehead\">Stock</td>\n";
	print "<td width=\"11%\" class=\"tablehead\">Locn</td>\n";
}
?>
	</tr>
	
	<tr class="tablelineadd" height="16">
	<td class="tablelinktext"><a href="javascript:popupOpener('pop-bomline.php<?php print "?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid) ?>','pop_bomline',300,400)" title="Add a line to the BOM">New BOM line...</a></td>
	<td class="tablelinktext"></td>
	<td class="tablelinktext"></td>
	<td class="tablelinktext"></td>
	<td class="tablelinktext"></td>
<?php
if ($showstock === false)
	print "<td class=\"tablelinktext\"></td>\n";
else 
{
	print "<td class=\"tablelinktext\"></td>\n";
	print "<td class=\"tablelinktext\"></td>\n";
}
?>
	</tr>
<?php
$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	$trclass = "tableline".($i%2);
	print "<tr class=\"".$trclass."\" height=\"16\">\n";
	print "<td class=\"tablelinktext\" valign=\"top\"><a href=\"javascript:popupOpener('pop-bomline.php?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid)."&bomid=".urlencode($dset[$i]["bomid"])."','pop_bomline',300,400)\" title=\"View/Edit BOM line detail\">".htmlentities($dset[$i]["partnumber"])."</a></td>\n";
	print "<td class=\"tablelinktext\" valign=\"top\">".htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])." ".htmlentities($dset[$i]["footprint"])."</td>\n";
	print "<td class=\"tablelinktext\" valign=\"top\">".htmlentities($dset[$i]["qty"])."</td>\n";
	print "<td class=\"tablelinktext\" valign=\"top\">".htmlentities($dset[$i]["um"])."</td>\n";
	print "<td class=\"tablelinktext\" valign=\"top\">".htmlentities($dset[$i]["ref"])."</td>\n";
	if ($showstock === false)
		print "<td class=\"tablelinktext\" valign=\"top\">".htmlentities($dset[$i]["altcatdescr"])." ".htmlentities($dset[$i]["altpartdescr"])." ".htmlentities($dset[$i]["altfprint"])."</td>\n";
	else 
	{
		print "<td class=\"tablelinktext\" valign=\"top\">".htmlentities($dset[$i]["stockqty"])."</td>\n";
		print "<td class=\"tablelinktext\" valign=\"top\">".htmlentities($dset[$i]["stockloc"])."</td>\n";
	}
	print "</tr>\n";
}
?>
	</table>
<?php
}
?>
	</section>
</body></html>
