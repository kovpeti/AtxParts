<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-part.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new part
// $partid: ID of part to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-part.php";
$formname = "poppart";
$formtitle= "Add/Edit Part";
$popx = 700;
$popy = 950;

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

$partid = false;
if (isset($_GET['partid']))
	$partid = trim(urldecode(($_GET["partid"])));
if (!is_numeric($partid))
	$partid = false;

$fc = false;
if (isset($_GET['fc']))
	$fc = trim(urldecode(($_GET["fc"])));
if (!is_numeric($fc))
	$fc = false;
	
// Handle part form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["partdescr"]))
			$partdescr = trim($_POST["partdescr"]);
		else 
			$partdescr = "";
		if (isset($_POST["partcat"]))
			$partcatid = trim($_POST["partcat"]);
		else 
			$partcatid = 0;
		if (isset($_POST["fprint"]))
			$fprintid = trim($_POST["fprint"]);
		else 	
			$fprintid = 0;
			
		if ($partid === false)
		{
			// new part - insert the values and generate the part number
			$q_p = "insert into parts "
				. "\n set "
				. "\n partdescr='".$dbh->real_escape_string($partdescr)."', "
				. "\n partcatid='".$dbh->real_escape_string($partcatid)."', "
				. "\n footprint='".$dbh->real_escape_string($fprintid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else 
			{
				$newpartid = $dbh->insert_id;
				$partnum = $myparts->CalcPartNumber(str_pad($newpartid, 6, "0", STR_PAD_LEFT), PARTPREFIX);
			
				$q_p = "update parts "
					. "\n set "
					. "\n partnumber='".$dbh->real_escape_string($partnum)."' "
					. "\n where partid='".$dbh->real_escape_string($newpartid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Part created: ".$partdescr;
					$myparts->LogSave($dbh, LOGTYPE_PARTNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
		else 
		{
			// existing part - update the values
			$q_p = "update parts "
				. "\n set "
				. "\n partdescr='".$dbh->real_escape_string($partdescr)."', "
				. "\n partcatid='".$dbh->real_escape_string($partcatid)."', "
				. "\n footprint='".$dbh->real_escape_string($fprintid)."' "
				. "\n where partid='".$dbh->real_escape_string($partid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Part updated: ".$partdescr;
				$myparts->LogSave($dbh, LOGTYPE_PARTCHANGE, $uid, $logmsg);
				$myparts->UpdateParent();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete parts if still attached to boms or components
		$nb = $myparts->ReturnCountOf($dbh, "boms", "bomid", "partid", $partid);
		if ($nb > 0)
			$myparts->AlertMeTo("Part still used in ".$nb." BOMs.");
		else 
		{
			$nc = $myparts->ReturnCountOf($dbh, "components", "compid", "partid", $partid);
			if ($nc > 0)
				$myparts->AlertMeTo("Part still used by ".$nc." components.");
			else 
			{
				$na = $myparts->ReturnCountOf($dbh, "assemblies", "assyid", "partid", $partid);
				if ($na > 0)
					$myparts->AlertMeTo("Part still allocated to ".$na." assemblies.");
				else 
				{
					// Unattached part can be deleted
					$q_p = "delete from parts "
						. "\n where partid='".$dbh->real_escape_string($partid)."' "
						. "\n limit 1 "
						;
					$s_p = $dbh->query($q_p);
					if (!$s_p)
						$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
					else
					{
						$uid = $myparts->SessionMeUID();
						$logmsg = "Part deleted: ".$assydescr;
						$myparts->LogSave($dbh, LOGTYPE_PARTDELETE, $uid, $logmsg);
						$myparts->AlertMeTo("Part deleted.");
					}
					$dbh->close();
					$myparts->PopMeClose();
					die();
				}
			}
		}
	}
}

// Get the footprints and part categories for the lists
$q_fprint = "select fprintid, "
		. "\n fprintdescr "
		. "\n from footprint "
		. "\n order by fprintdescr "
		;
		
$s_fprint = $dbh->query($q_fprint);
$list_fprint = array();
$list_fprint[0][0] = 0;
$list_fprint[0][1] = "None";
$i = 1;
if ($s_fprint)
{
	while ($r_fprint = $s_fprint->fetch_assoc())
	{
		$list_fprint[$i][0] = $r_fprint["fprintid"];
		$list_fprint[$i][1] = $r_fprint["fprintdescr"];
		$i++;
	}
	$s_fprint->free();
}

$q_partcat = "select partcatid, "
		. "\n catdescr "
		. "\n from pgroups "
		. "\n order by catdescr "
		;
		
$s_partcat = $dbh->query($q_partcat);
$list_partcat = array();
$i = 0;
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

if ($partid !== false)
{
	$urlargs = "?partid=".urlencode($partid);
	if ($fc !== false)
		$urlargs .= "&fc=".urlencode($fc);

	$q_p = "select partnumber, "
		. "\n partdescr, "
		. "\n partcatid, "
		. "\n footprint "
		. "\n from parts "
		. "\n where partid='".$dbh->real_escape_string($partid)."' "
		;
														
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_p = $s_p->fetch_assoc();
		$partnum = $r_p["partnumber"];
		$partdescr = $r_p["partdescr"];
		$partcatid = $r_p["partcatid"];
		$partfprint = $r_p["footprint"];
		$s_p->free();
	}
}
else
{
	$urlargs = ($fc === false ? "" : "?fc=".urlencode($fc));
	$partnum = "";
	$partdescr = "";
	$partcatid = $fc;
	$partfprint = "";
}

$q_t = "select * from components "
	. "\n left join compstates on compstates.compstateid=components.compstateid "
	. "\n left join datasheets on datasheets.dataid=components.datasheet "
	. "\n left join pgroups on pgroups.partcatid=datasheets.partcatid "
	. "\n where partid='".$dbh->real_escape_string($partid)."' "
	. "\n order by mfgname "
	;
$s_t = $dbh->query($q_t);
$cc = 0;
$dset_comps = array();
if ($s_t)
{
	while ($r_t = $s_t->fetch_assoc())
	{
		$dset_comps[$cc] = $r_t;
		if ($r_t["datadescr"] != NULL)
		{
			$dset_comps[$cc]["dsheetpath"] = "../".DATASHEETS_DIR.$r_t["datadir"];
			if (substr($dset_comps[$cc]["dsheetpath"], -1) != "/")
				$dset_comps[$cc]["dsheetpath"] .= "/";
			$dset_comps[$cc]["dsheetpath"] .= $r_t["datasheetpath"];
		}
		else
			$dset_comps[$cc]["dsheetpath"] = false;
		$cc++;
	}
	$s_t->free();
}

$q_t = "select * from boms "
	. "\n left join assemblies on assemblies.assyid=boms.assyid "
	. "\n where boms.partid='".$dbh->real_escape_string($partid)."' "
	. "\n order by assydescr, assyrev "
	;
$s_t = $dbh->query($q_t);
$ca = 0;
$dset_assy = array();
if ($s_t)
{
	while ($r_t = $s_t->fetch_assoc())
	{
		$q_a = "select partdescr "
			. "\n from parts "
			. "\n where partid='".$r_t["partid"]."' "
			;
		$s_a = $dbh->query($q_a);
		if ($s_a)
		{
			$r_a = $s_a->fetch_assoc();
			$dset_assy[$ca] = $r_t;
			$dset_assy[$ca]["partdescr"] = $r_a["partdescr"];
			$s_a->free();
		}
		$ca++;
	}
	$s_t->free();
}

$q_stk = "select * from stock "
	. "\n left join locn on locn.locid=stock.locid "
	. "\n where partid='".$dbh->real_escape_string($partid)."' "
	. "\n order by locref "
	;
$s_stk = $dbh->query($q_stk);
$cs = 0;
$dset_stock = array();
if ($s_stk)
{
	while ($r_stk = $s_stk->fetch_assoc())
	{
		$dset_stock[$cs] = $r_stk;
		$cs++;
	}
	$s_stk->free();
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Part Properties";
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
	<span class="formheadtext"><?php print ($partid === false ? "Add New Part" : "Edit Part") ?></span>
	<p/>

	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Part Number</td>
	<td class="propscell_lt propcell" width="70%"><?php print htmlentities($partnum) ?></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Part Description</td>
	<td class="propscell_lt propcell">
	<input type="text" name="partdescr" size="40" maxlength="100" tabindex="10" value="<?php print htmlentities($partdescr) ?>" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Part Category</td>
	<td class="propscell_lt propcell">
	<select name="partcat" style="width: 24em" tabindex="20">
	<?php $myparts->RenderOptionList($list_partcat, $partcatid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Part Footprint</td>
	<td class="propscell_lt propcell">
	<select name="fprint" style="width: 24em" tabindex="30">
	<?php $myparts->RenderOptionList($list_fprint, $partfprint, false); ?>
	</select></td>
	</tr>
	</table>

	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($partid !== false)
	{
?>
	<input type="submit" name="btn_delete" class="btntext" value="Delete" onclick="delSet()" />
<?php
	}
?>
	</td>
	</tr>
	</table>
	</form>
	<p/>

	<span class="formheadtext">Components referencing this part</span>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td class="tablehead" width="400">Manufacturer Part</td>
	<td class="tablehead" width="50">Data</td>
	<td class="tablehead" width="150">Status</td>
	</tr>
<?php
for ($i = 0; $i < $cc; $i++)
{
	print "<tr>\n";
	print "<td class=\"tabletext\">".$dset_comps[$i]["mfgname"]." ".$dset_comps[$i]["mfgcode"]."</td>\n";
	print "<td class=\"tabletext\">";
	if ($dset_comps[$i]["dsheetpath"] !== false)
		print "<a href=\"javascript:popupOpener('".$dset_comps[$i]["dsheetpath"]."','pop_showdatasheet',800,800)\" title=\"View component datasheet\"><img src=\"../images/icon-pdf.png\" width=\"16\" height=\"16\" border=\"0\">&nbsp;".htmlentities($dset_comps[$i]["datasheetpath"])."</a>\n";
	else
		print "&nbsp;";
	print "</td>\n";
	print "<td class=\"tabletext\" title=\"".$dset_comps[$i]["statedescr"]."\">".(substr($dset_comps[$i]["statedescr"], 0, 15))."...</td>\n";
	print "</tr>\n";
	
}
?>
	</table>
	<p/>
	
	<span class="formheadtext">Assembly BOMs using this part</span>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td class="tablehead" width="400">Assembly</td>
	<td class="tablehead" width="100">Rev/AW</td>
	<td class="tablehead" width="100">Qty</td>
	</tr>
<?php 
for ($i = 0; $i < $ca; $i++)
{
	print "<tr>\n";
	print "<td class=\"tabletext\">".$dset_assy[$i]["partdescr"]." (".$dset_assy[$i]["assydescr"].")</td>\n";
	print "<td class=\"tabletext\">".(str_pad($dset_assy[$i]["assyrev"], 2, "0", STR_PAD_LEFT))." - ".$dset_assy[$i]["assyaw"]."</td>\n";
	print "<td class=\"tabletext\">".$dset_assy[$i]["qty"]."</td>\n";
	print "</tr>\n";
}
?>	
	</table>
	<p/>
	
	<span class="formheadtext">Part Stock</span>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td class="tablehead" width="350">Location</td>
	<td class="tablehead" width="100">Loc Ref</td>
	<td class="tablehead" width="50">Qty</td>
	<td class="tablehead" width="100">Note</td>
	</tr>

	<tr>
	<td class="tablelineadd"><a href="javascript:popupOpener('pop-locnconfig.php','pop_locnconfig',300,400)" title="Configure locations">Configure...</a></td>
	<td class="tablelineadd"><a href="javascript:popupOpener('pop-locn.php','pop_locn',300,400)" title="Add location detail">Add...</a></td>
	<td class="tablelineadd"><a href="javascript:popupOpener('pop-stock.php<?php print $urlargs ?>','pop_stock',300,400)" title="Add stock detail">Add...</a></td>
	<td class="tablelineadd"></td>
	</tr>
	
<?php 
for ($i = 0; $i < $cs; $i++)
{
	print "<tr>\n";
	print "<td class=\"tabletext\">".htmlentities($dset_stock[$i]["locdescr"])."</td>\n";
	print "<td class=\"tabletext\"><a href=\"javascript:popupOpener('pop-locn.php?locid=".$dset_stock[$i]["locid"]."','pop_locn',300,400)\" title=\"Edit location detail\">".htmlentities($dset_stock[$i]["locref"])."</a></td>\n";
	print "<td class=\"tabletext\"><a href=\"javascript:popupOpener('pop-stock.php".$urlargs."&stockid=".$dset_stock[$i]["stockid"]."','pop_stock',300,400)\" title=\"Edit stock detail\">".htmlentities($dset_stock[$i]["qty"])."</a></td>\n";
	print "<td class=\"tabletext\">".htmlentities($dset_stock[$i]["note"])."</td>\n";
	print "</tr>\n";
}
?>
	</table>

	</section>
</body></html>
