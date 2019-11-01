<?php
// ********************************************
// Copyright 2003-2015 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-assy.php 209 2017-04-06 21:48:59Z gswan $

// Parameters passed: 
// none: create new part
// $assyid: ID of assembly to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-assy.php";
$formname = "popassy";
$formtitle= "Add/Edit Assembly";
$popx = 700;
$popy = 700;

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
if (isset($_GET['assyid']))
	$assyid = trim(urldecode(($_GET["assyid"])));
if (!is_numeric($assyid))
	$assyid = false;

// Handle part form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) === true)
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
			if (isset($_POST["assyname"]))
				$assyname = trim($_POST["assyname"]);
			else 
				$assyname = "";
			if (isset($_POST["assydescr"]))
				$assydescr = trim($_POST["assydescr"]);
			else 	
				$assydescr = "";
			if (isset($_POST["assyaw"]))
				$assyaw = trim($_POST["assyaw"]);
			else 	
				$assyaw = "";
			if (isset($_POST["assyrev"]))
				$assyrev = trim($_POST["assyrev"]);
			else 	
				$assyrev = "";
			
			if ($assyid === false)
			{
				// new assy - insert the values
				$q_p = "insert into assemblies "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n assydescr='".$dbh->real_escape_string($assydescr)."', "
					. "\n assyrev='".$dbh->real_escape_string($assyrev)."', "
					. "\n assyaw='".$dbh->real_escape_string($assyaw)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Assembly created: ".$assydescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing assy - update the values
				$q_p = "update assemblies "
					. "\n set "
					. "\n assydescr='".$dbh->real_escape_string($assydescr)."', "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n assyrev='".$dbh->real_escape_string($assyrev)."', "
					. "\n assyaw='".$dbh->real_escape_string($assyaw)."' "
					. "\n where partid='".$dbh->real_escape_string($partid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Assembly updated: ".$assydescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
	else 
		$myparts->AlertMeTo("Insufficient privileges.");
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) === true)
	{
		// Cannot delete assy if still attached to boms or units
		$nb = $myparts->ReturnCountOf($dbh, "boms", "bomid", "assyid", $assyid);
		if ($nb > 0)
			$myparts->AlertMeTo("Assembly still referenced in ".$nb." BOMs.");
		else 
		{
			$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "assyid", $assyid);
			if ($nu > 0)
				$myparts->AlertMeTo("Assembly is used in ".$nu." manufactured units.");
			else 
			{
				// Read the assyname for the log
				$q_p = "select assydescr "
					. "\n from assemblies "
					. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
					;
				$s_p = $dbh->query($q_p);
				if ($s_p)
				{
					$r_p = $s_p->fetch_assoc();
					$d_assydescr = $r_p["assydescr"];
					$s_p->free();
				}
				else 
					$d_assydescr = $assyid;
					
				// Unattached assembly can be deleted
				$q_p = "delete from assemblies "
					. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
					. "\n limit 1 "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$myparts->AlertMeTo("Assembly deleted.");
					$uid = $myparts->SessionMeUID();
					$logmsg = "Assembly deleted: ".$d_assydescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYDELETE, $uid, $logmsg);
				}
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
		}
	}
	else 
		$myparts->AlertMeTo("Insufficient privileges.");
}

// Get the parts (only assemblies) for the lists
$q_parts = "select partid, "
		. "\n partcatid, "
		. "\n partdescr, "
		. "\n partnumber "
		. "\n from parts "
		. "\n where partcatid='1' or partcatid='2' "
		. "\n order by partdescr "
		;
		
$s_parts = $dbh->query($q_parts);
$list_parts = array();
$i = 0;
if ($s_parts)
{
	while ($r_parts = $s_parts->fetch_assoc())
	{
		$list_parts[$i][0] = $r_parts["partid"];
		$list_parts[$i][1] = $r_parts["partdescr"]." (".$r_parts["partnumber"].")";
		$i++;
	}
	$s_parts->free();
}

if ($assyid !== false)
{
	$urlargs = "?assyid=".urlencode($assyid);
	
	$q_p = "select * from assemblies "
		. "\n left join parts on parts.partid=assemblies.partid "
		. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
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
		$assyname = $r_p["partdescr"];
		$assyid = $r_p["assyid"];
		$partid = $r_p["partid"];
		$assydescr = $r_p["assydescr"];
		$assyrev = str_pad($r_p["assyrev"], 2, "0", STR_PAD_LEFT);
		$assyaw = $r_p["assyaw"];
		$s_p->free();
	}
	
	// Get some statistical detail about the assembly
	$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "assyid", $assyid);
	$nb = $myparts->ReturnCountOf($dbh, "boms", "bomid", "assyid", $assyid);
	
	// Engineering and Manufacturing docs
	$q_ed = "select engdocpath, "
		. "\n engdocid, "
		. "\n engdocdescr "
		. "\n from engdocs "
		. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
		;
	$s_ed = $dbh->query($q_ed);
	$ed_set = array();
	$ned = 0;
	if ($s_ed)
	{
		while ($r_ed = $s_ed->fetch_assoc())
			$ed_set[$ned++] = $r_ed;
	}
}
else 
{
	$urlargs="";
	$partnum = "";
	$assyname = "";
	$partid = 0;
	$assydescr = "";
	$assyrev = "";
	$assyaw = "";
	$ed_set = array();
	$ned = 0;
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
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

$myparts->FormRender_PopClose();

?>
<section class="contentpanel_popup">
	<span class="formheadtext"><?php print ($assyid === false ? "Add New Assembly" : "Edit Assembly") ?></span>
	<p/>
	
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES))
	print "<form name=\"mainform\" method=\"post\" action=\"".$formfile.$urlargs."\" onsubmit='return deleteCheck()'>\n";
?>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Part Number</td>
	<td class="propscell_lt propcell" width="70%"><?php print htmlentities($partnum) ?></td>
	</tr>

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Part</td>
	<td class="propscell_lt propcell">
<?php
print "<select name=\"partid\" style=\"width: 24em\" tabindex=\"10\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) !== true)
	print "readonly";
print " >\n";
$myparts->RenderOptionList($list_parts, $partid, false); 
?>
	</select>
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Assembly Name</td>
	<td class="propscell_lt propcell">
	<input type="text" name="assyname" size="40" maxlength="20" tabindex="20" value="<?php print htmlentities($assyname) ?>" readonly />
	</td>
	</tr>
		
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Assy Description</td>
	<td class="propscell_lt propcell">
<?php
print "<input type=\"text\" name=\"assydescr\" size=\"40\" maxlength=\"100\" tabindex=\"30\" value=\"".htmlentities($assydescr)."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) !== true)
	print "readonly";
print " >\n";
?>
	</td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Assy Revision</td>
	<td class="propscell_lt propcell">
<?php
print "<input type=\"text\" name=\"assyrev\" size=\"40\" maxlength=\"2\" tabindex=\"40\" value=\"".htmlentities($assyrev)."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) !== true)
	print "readonly";
print " >\n";
?>
	</td>
	</tr>	

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Assy Artwork</td>
	<td class="propscell_lt propcell">
<?php
print "<input type=\"text\" name=\"assyaw\" size=\"40\" maxlength=\"2\" tabindex=\"50\" value=\"".htmlentities($assyaw)."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) !== true)
	print "readonly";
print " >\n";
?>
	</td>
	</tr>	
	</table>	

<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES))
{
?>
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($assyid !== false)
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
<?php 
}
?>
	<p/>
<?php
if ($assyid !== false)
{
?>
	<span class="formheadtext">Engineering Documents</span>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td class="tablehead">Document</td>
	</tr>
	
<?php 
	if ($ned == 0)
	{
		print "<tr>";
		print "<td class=\"tabletext\">No Documents</td>";
		print "</tr>\n";
	}
	else
	{	
		for ($i = 0; $i < $ned; $i++)
		{
			print "<tr>";
			print "<td class=\"tabletext\">"
				. "<a href=\"javascript:popupOpener('pop-engdocdl.php?engdocid=".urlencode($ed_set[$i]["engdocid"])."','pop_engdocdl',800,800)\">"
				. htmlentities($ed_set[$i]["engdocdescr"])
				. "</a></td>";
			print "</tr>\n";
		}
	}
?>
	</table>
	<p/>

	<span class="formtext">BOM items referencing this assembly: <?php print $nb ?></span><br/>
	<span class="formtext">Units referencing this assembly: <?php print $nu ?></span><br/>
<?php 
}
?>
	</section>
</body></html>
