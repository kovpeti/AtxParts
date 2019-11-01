<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bomline.php 205 2016-11-28 22:04:10Z gswan $

// Parameters passed: 
// $assyid: ID of assembly
// $variantid: ID of variant
// $bomid: ID of bomline to edit, none to add a new bomline to an assy/variant

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-bomline.php";
$formname = "popbomline";
$formtitle= "Add/Edit BOM Item";
$popx = 700;
$popy = 600;

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

$variantid = false;
if (isset($_GET['variantid']))
	$variantid = trim(urldecode(($_GET["variantid"])));
if (!is_numeric($variantid))
	$variantid = false;

if (($assyid === false) || ($variantid === false))
{
	$myparts->AlertMeTo("An assembly and variant must be specified.");
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

$bomid = false;
if (isset($_GET['bomid']))
	$bomid = trim(urldecode(($_GET["bomid"])));
if (!is_numeric($bomid))
	$bomid = false;
	
// Handle part form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMITEMS) === true)
	{
		if (isset($_POST["partid"]))
		{
			$partid = trim($_POST["partid"]);
			if ($partid == "")
				$partid = false;
		}
		else 
			$partid = false;

		if ($partid === false)
			$myparts->AlertMeTo("A part must be specified.");
		else 
		{
			if (isset($_POST["qty"]))
				$qty = trim($_POST["qty"]);
			else 
				$qty = 0;
			if (isset($_POST["um"]))
				$um = trim($_POST["um"]);
			else 	
				$um = "";
			if (isset($_POST["ref"]))
				$ref = trim($_POST["ref"]);
			else 	
				$ref = "";
			if (isset($_POST["altpartid"]))
				$altpartid = trim($_POST["altpartid"]);
			else 	
				$altpartid = 0;
			
			if ($bomid === false)
			{
				// new bomline - insert the values
				$q_p = "insert into boms "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n assyid='".$dbh->real_escape_string($assyid)."', "
					. "\n qty='".$dbh->real_escape_string($qty)."', "
					. "\n um='".$dbh->real_escape_string($um)."', "
					. "\n ref='".$dbh->real_escape_string($ref)."', "
					. "\n alt='".$dbh->real_escape_string($altpartid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
				else 
				{
					// Get the bomid for the new entry
					$newbomid = $dbh->insert_id;
					
					// add the bomline to the variant table
					$q_v = "insert into bomvariants "
						. "\n set variantid='".$dbh->real_escape_string($variantid)."', "
						. "\n bomid='".$dbh->real_escape_string($newbomid)."' "
						;
					$s_v = $dbh->query($q_v);
					
					// Read the assydescr and partdescr for the log
					$q_p = "select assydescr, "
						. "\n partdescr "
						. "\n from boms "
						. "\n left join assemblies on assemblies.assyid=boms.assyid "
						. "\n left join parts on parts.partid=boms.partid "
						. "\n where bomid='".$dbh->real_escape_string($newbomid)."' "
						;
					$s_p = $dbh->query($q_p);
					if ($s_p)
					{
						$r_p = $s_p->fetch_assoc();
						$d_assydescr = $r_p["assydescr"];
						$d_partdescr = $r_p["partdescr"];
						$s_p->free();
					}
					else 
					{
						$d_assydescr = "not found"; 
						$d_partdescr = "not found"; 
					}
						
					$uid = $myparts->SessionMeUID();
					$logmsg = "BOM: ".$d_assydescr." line ".$d_partdescr." added.";
					$myparts->LogSave($dbh, LOGTYPE_BOMNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing bomline - update the values
				$q_p = "update boms "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n assyid='".$dbh->real_escape_string($assyid)."', "
					. "\n qty='".$dbh->real_escape_string($qty)."', "
					. "\n um='".$dbh->real_escape_string($um)."', "
					. "\n ref='".$dbh->real_escape_string($ref)."', "
					. "\n alt='".$dbh->real_escape_string($altpartid)."' "
					. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
				else 
				{
					// Read the assydescr and partdescr for the log
					$q_p = "select assydescr, "
						. "\n partdescr "
						. "\n from boms "
						. "\n left join assemblies on assemblies.assyid=boms.assyid "
						. "\n left join parts on parts.partid=boms.partid "
						. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
						;
					$s_p = $dbh->query($q_p);
					if ($s_p)
					{
						$r_p = $s_p->fetch_assoc();
						$d_assydescr = $r_p["assydescr"];
						$d_partdescr = $r_p["partdescr"];
						$s_p->free();
					}
					else 
					{
						$d_assydescr = "not found"; 
						$d_partdescr = "not found"; 
					}
						
					$uid = $myparts->SessionMeUID();
					$logmsg = "BOM: ".$d_assydescr." line ".$d_partdescr." updated.";
					$myparts->LogSave($dbh, LOGTYPE_BOMCHANGE, $uid, $logmsg);
					$dbh->close();
					$myparts->UpdateParent();
					$myparts->PopMeClose();
					die();
				}
			}
		}
	}
	else 
		$myparts->AlertMeTo("Insufficient privileges.");
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMITEMS) === true)
	{
		// Delete bom item from the selected variant only.
		// If this is the only variant then delete the bomitem completely.
		$q_v = "select count(*) as count "
			. "\n from bomvariants "
			. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
			;
		$s_v = $dbh->query($q_v);
		if ($s_v)
		{
			$r_v = $s_v->fetch_assoc();
			$nv = $r_v["count"];
			
			if ($nv < 2)
			{
				// remove the bomid from the bomvariants table
				$q_vx = "delete from bomvariants "
					. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
					;
				$s_vx = $dbh->query($q_vx);
				if (!$s_vx)
					$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
				else 
				{
					// Read the assyname for the log
					$q_p = "select assydescr, "
						. "\n partdescr "
						. "\n from boms "
						. "\n left join assemblies on assemblies.assyid=boms.assyid "
						. "\n left join parts on parts.partid=boms.partid "
						. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
						;
					$s_p = $dbh->query($q_p);
					if ($s_p)
					{
						$r_p = $s_p->fetch_assoc();
						$d_assydescr = $r_p["assydescr"];
						$d_partdescr = $r_p["partdescr"];
						$s_p->free();
					}
					else 
					{
						$d_assydescr = "not found"; 
						$d_partdescr = "not found"; 
					}
							
					// remove the bomid from the boms table
					$q_b = "delete from boms "
						. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
						. "\n limit 1 "
						;
					$s_b = $dbh->query($q_b);
					if (!$s_b)
						$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
					else 
					{
						$uid = $myparts->SessionMeUID();
						$logmsg = "BOM: ".$d_assydescr." line ".$d_partdescr." deleted.";
						$myparts->LogSave($dbh, LOGTYPE_BOMDELETE, $uid, $logmsg);
						$dbh->close();
						$myparts->UpdateParent();
						$myparts->PopMeClose();
						die();
					}
				}
			}
			else 
			{
				// Read the assyname for the log
				$q_p = "select assydescr, "
					. "\n partdescr "
					. "\n from boms "
					. "\n left join assemblies on assemblies.assyid=boms.assyid "
					. "\n left join parts on parts.partid=boms.partid "
					. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
					;
				$s_p = $dbh->query($q_p);
				if ($s_p)
				{
					$r_p = $s_p->fetch_assoc();
					$d_assydescr = $r_p["assydescr"];
					$d_partdescr = $r_p["partdescr"];
					$s_p->free();
				}
				else 
				{
					$d_assydescr = "not found"; 
					$d_partdescr = "not found"; 
				}
					
				// remove from the bomvariants table for this variant only
				$q_vx = "delete from bomvariants "
					. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
					. "\n and variantid='".$dbh->real_escape_string($variantid)."' "
					. "\n limit 1 "
					;
				$s_vx = $dbh->query($q_vx);
				if (!$s_vx)
					$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "BOM: ".$d_assydescr." line ".$d_partdescr." deleted.";
					$myparts->LogSave($dbh, LOGTYPE_BOMDELETE, $uid, $logmsg);
					$dbh->close();
					$myparts->UpdateParent();
					$myparts->PopMeClose();
					die();
				}
			}
			$s_v->free();
		}
	}
	else 
		$myparts->AlertMeTo("Insufficient privileges.");
}

// Get the parts for the lists
$q_parts = "select * from parts "
		. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
		. "\n left join footprint on footprint.fprintid=parts.footprint "
		. "\n order by catdescr, partdescr "
		;
		
$s_parts = $dbh->query($q_parts);
$list_parts = array();
$i = 0;
if ($s_parts)
{
	while ($r_parts = $s_parts->fetch_assoc())
	{
		$list_parts[$i][0] = $r_parts["partid"];
		if ($r_parts["fprintdescr"] != "")
			$list_parts[$i][1] = $r_parts["catdescr"]." ".$r_parts["partdescr"].", ".$r_parts["fprintdescr"].", (".$r_parts["partnumber"].")";
		else
			$list_parts[$i][1] = $r_parts["catdescr"]." ".$r_parts["partdescr"]." (".$r_parts["partnumber"].")";
		$i++;
	}
	$s_parts->free();
}

// Create a copy for the altparts, however the first item should be NULL in case there is no alt part
$npl = count($list_parts);
$list_altparts = array();
$list_altparts[0][0] = 0;
$list_altparts[0][1] = "None";
$i = 1;
for ($j = 0; $j < $npl; $j++)
{
	$list_altparts[$i][0] = $list_parts[$j][0];
	$list_altparts[$i][1] = $list_parts[$j][1];
	$i++;
}

$urlargs = "?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid);

if ($bomid !== false)
{
	$urlargs .= "&bomid=".urlencode($bomid);
	
	// Get the details of this BOM line
	$q_bl = "select * from boms "
		. "\n left join parts on parts.partid=boms.partid "
		. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
		. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
		;
	$s_bl = $dbh->query($q_bl);
	if (!$s_bl)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_bl = $s_bl->fetch_assoc();
		$partid = $r_bl["partid"];
		$qty = $r_bl["qty"];
		$um = $r_bl["um"];
		$ref = $r_bl["ref"];
		$altid = $r_bl["alt"];
		$s_bl->free();
	}
	
	// Find out what variants this bom line is used on
	$q_bvp = "select partid "
		. "\n from boms "
		. "\n where bomid='".$dbh->real_escape_string($bomid)."' "
		;
	$s_bvp = $dbh->query($q_bvp);
	if (!$s_bvp)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_bvp = $s_bvp->fetch_assoc();
		$b_partid = $r_bvp["partid"];
		$s_bvp->free();
		
		$q_bv = "select * from bomvariants "
			. "\n left join variant on variant.variantid=bomvariants.variantid "
			. "\n left join boms on boms.bomid=bomvariants.bomid "
			. "\n where boms.assyid='".$dbh->real_escape_string($assyid)."' "
			. "\n and boms.partid='".$b_partid."' "
			;
				
		$s_bv = $dbh->query($q_bv);
		if (!$s_bv)
		{
			$dbh->close();
			$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
			$myparts->PopMeClose();
			die();
		}
		else 
		{
			$bvset = array();
			$i = 0;
			while ($r_bv = $s_bv->fetch_assoc())
			{
				$bvset[$i]["bvid"] = $r_bv["bomvid"];
				$bvset[$i]["variantid"] = $r_bv["variantid"];
				$bvset[$i]["bomid"] = $r_bv["bomid"];
				$bvset[$i]["variantname"] = $r_bv["variantname"];
				$bvset[$i]["variantdescr"] = $r_bv["variantdescr"];
				$bvset[$i]["variantstate"] = $r_bv["variantstate"];
				$i++;
			}
			$s_bv->free();
		}
	}
}
else 
{
	$bvset = array();
	$partid = 0;
	$qty = "";
	$um = "ea";
	$ref = "";
	$altid = 0;
}		

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "BOM Line";
$headparams["icon"] = $_cfg_stdicon;
$headparams["css"] = $_cfg_stdcss;
$headparams["jscript_file"] = $_cfg_stdjscript;
$headparams["jscript_local"][] = "window.resizeTo(".$popx.",".$popy.");";
$headparams["jscript_local"][] = "window.opener.location.href=window.opener.location.href;";
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

$myparts->FormRender_PopClose();

?>
<section class="contentpanel_popup">
	<span class="formheadtext"><?php print ($assyid === false ? "Add New BOM Item" : "Edit BOM Item") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Part</td>
	<td class="propscell_lt propcell" width="70%">
	<select name="partid" style="width: 34em" tabindex="10">
	<?php $myparts->RenderOptionList($list_parts, $partid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Quantity</td>
	<td class="propscell_lt propcell"><input type="text" name="qty" size="50" maxlength="20" tabindex="20" value="<?php print htmlentities($qty) ?>" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Unit of Measure</td>
	<td class="propscell_lt propcell"><input type="text" name="um" size="50" maxlength="20" tabindex="30" value="<?php print htmlentities($um) ?>" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Circuit Ref</td>
	<td class="propscell_lt propcell"><input type="text" name="ref" size="50" maxlength="100" tabindex="40" value="<?php print htmlentities($ref) ?>" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Alternate Part</td>
	<td class="propscell_lt propcell" width="70%">
	<select name="altpartid" style="width: 34em" tabindex="50">
	<?php $myparts->RenderOptionList($list_altparts, $altid, false); ?>
	</select>
	</select></td>
	</tr>
	</table>
	
	<table class="contentpanel_popup">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()">
	&nbsp;
<?php
	if ($bomid !== false)
	{
?>
	<input type="submit" name="btn_delete" class="btntext" value="Delete" onclick="delSet()" title="Delete this item from the selected BOM">
<?php
	}
?>
	</td>
	</tr>
	</table>
	</form>
	<p/>

<?php
if ($bomid !== false)
{
?>
	<span class="formheadtext">Assembly variants using this BOM item</span>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td class="tablehead" width="140">Variant</td>
	<td class="tablehead" width="320">Description</td>
	<td class="tablehead" width="140">Status</td>
	</tr>

	<tr>
	<td class="tablelineadd"><a href="javascript:popupOpener('pop-bomlineaddvar.php<?php print $urlargs ?>','pop_bomlineaddvar',300,400)" title="Add this BOM line to another variant">Add...</a></td>
	<td class="tablelineadd"></td>
	<td class="tablelineadd"></td>
	</tr>

<?php
	$nbv = count($bvset);
	for ($i = 0; $i < $nbv; $i++)
	{
		print "<tr>\n";
		print "<td class=\"tabletext\">".$bvset[$i]["variantname"]."</td>\n";
		print "<td class=\"tabletext\">".$bvset[$i]["variantdescr"]."</td>\n";
		print "<td class=\"tabletext\">".$bvset[$i]["variantstate"]."</td>\n";
		print "</tr>\n";
	}
	print "</table>\n";
}
?>
	</section>
</body></html>
