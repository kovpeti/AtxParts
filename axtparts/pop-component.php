<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-component.php 209 2017-04-06 21:48:59Z gswan $

// Parameters passed: 
// none: create new component
// $componentid: ID of component to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-component.php";
$formname = "popcomponent";
$formtitle= "Add/Edit Component";
$popx = 700;
$popy = 750;

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

$compid = false;
if (isset($_GET['compid']))
	$compid = trim(urldecode(($_GET["compid"])));
if (!is_numeric($compid))
	$compid = false;

$suppid = false;
if (isset($_GET['suppid']))
	$suppid = trim(urldecode(($_GET["suppid"])));
if (!is_numeric($suppid))
	$suppid = false;

if (($compid !== false) && ($suppid !== false))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_s = "delete from suppliers "
			. "\n where suppid='".$dbh->real_escape_string($suppid)."' "
			. "\n and compid='".$dbh->real_escape_string($compid)."' "
			. "\n limit 1"
			;
		$s_s = $dbh->query($q_s);
		if (!$s_s)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else 
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Supplier ".$suppid." removed from: ".$compid;
			$myparts->LogSave($dbh, LOGTYPE_SUPPLDELETE, $uid, $logmsg);
		}
	}
}

// Handle form submission here
if (isset($_POST["btn_supplier"]))
{
	if ($compid !== false)
	{
		if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
			$myparts->AlertMeTo("Insufficient privileges.");
		else
		{
			// Add a supplier for the component
			if (isset($_POST["suppid"]))
				$suppid = trim($_POST["suppid"]);
			if (isset($_POST["suppcatnum"]))
				$suppcatnum = trim($_POST["suppcatnum"]);
			
			$q_s = "insert into suppliers "
				. "\n set "
				. "\n compid='".$dbh->real_escape_string($compid)."', "
				. "\n suppid='".$dbh->real_escape_string($suppid)."', "
				. "\n suppcatno='".$dbh->real_escape_string($suppcatnum)."' "
				;
			$s_s = $dbh->query($q_s);
			if (!$s_s)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Supplier added: ".$suppid.", catnum: ".$suppcatnum;
				$myparts->LogSave($dbh, LOGTYPE_SUPPLNEW, $uid, $logmsg);
			}
		}
	}		
}

if (isset($_POST["btn_save"]))
{
	// Save the component data - must have a part
	if (isset($_POST["partid"]))
	{
		$partid = trim($_POST["partid"]);
		if (!is_numeric($partid))
			$partid = false;
	}
	else 
		$partid = false;
		
	if ($partid !== false)
	{
		if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
			$myparts->AlertMeTo("Insufficient privileges.");
		else
		{
			if (isset($_POST["mfgname"]))
				$mfgname = trim($_POST["mfgname"]);
			else 
				$mfgname = "";
			if (isset($_POST["mfgcode"]))
				$mfgcode = trim($_POST["mfgcode"]);
			else 
				$mfgcode = "";
			if (isset($_POST["dataid"]))
				$dataid = trim($_POST["dataid"]);
			else 
				$dataid = 0;
			if (isset($_POST["compstateid"]))
				$compstateid = trim($_POST["compstateid"]);
			else 
				$compstateid = 0;
				
			// Upload of datasheet - place it in the directory for the selected part category
			if ($dataid == 0)
			{
				if (isset($_FILES["dsheetfile"]))
				{
					if ($_FILES["dsheetfile"]["error"] == UPLOAD_ERR_OK)
					{
						// Find the datadir for the datasheet, as specified by the part category
						$q_p = "select * "
							. "\n from parts "
							. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
							. "\n where partid='".$dbh->real_escape_string($partid)."' "
							;
						$s_p =$dbh->query($q_p);
						if (!$s_p)
							$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
						else 
						{
							$r_p = $s_p->fetch_assoc();
							$partcatid = $r_p["partcatid"];
							$dsd = $r_p["datadir"];
							$datadir = DATASHEETS_DIR.$dsd;
							if (substr($datadir, -1) != "/")
								$datadir .= "/";
							if (!file_exists("../".$datadir))
							{
								$r = mkdir("../".$datadir, 0755, true);
								if ($r === false)
									$myparts->AlertMeTo("Error: Could not create directory for datasheet(../".htmlentities($datadir).").");
							}
							else 
								$r = true;
								
							if ($r === true)
							{
								$ftype = $_FILES["dsheetfile"]["type"];
								$fname = $_FILES["dsheetfile"]["tmp_name"];
								$frealname = $_FILES["dsheetfile"]["name"];
								$frealpath = $datadir.$frealname;
								$r = move_uploaded_file($fname, "../".$frealpath);
								if ($r !== true)
									$myparts->AlertMeTo("Error: Could not move uploaded file to ../".htmlentities($frealpath).".");
								else 
								{
									// Save the details in the database as a new datasheet
									if (isset($_POST["datadescr"]))
										$datadescr = trim($_POST["datadescr"]);
									else 
										$datadescr = "";
									
									$q_d = "insert into datasheets "
										. "\n set "
										. "\n datasheetpath='".$dbh->real_escape_string($frealname)."', "
										. "\n datadescr='".$dbh->real_escape_string($datadescr)."', "
										. "\n partcatid='".$dbh->real_escape_string($partcatid)."' "
										;
									$s_d = $dbh->query($q_d);
									if (!$s_d)
										$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
									else 
									{
										$dataid = $dbh->insert_id;
										$uid = $myparts->SessionMeUID();
										$logmsg = "Datasheet created: ".$datadescr;
										$myparts->LogSave($dbh, LOGTYPE_DSHEETNEW, $uid, $logmsg);
									}
								}
							}
							$s_p->free();
						}
					}
				}
			}
				
			if ($compid === false)
			{
				// new component - insert the values
				$q_p = "insert into components "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n datasheet='".$dbh->real_escape_string($dataid)."', "
					. "\n compstateid='".$dbh->real_escape_string($compstateid)."', "
					. "\n mfgname='".$dbh->real_escape_string($mfgname)."', "
					. "\n mfgcode='".$dbh->real_escape_string($mfgcode)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
					$myparts->UpdateParent();
			}
			else 
			{
				// existing part - update the values
				$q_p = "update components "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n datasheet='".$dbh->real_escape_string($dataid)."', "
					. "\n compstateid='".$dbh->real_escape_string($compstateid)."', "
					. "\n mfgname='".$dbh->real_escape_string($mfgname)."', "
					. "\n mfgcode='".$dbh->real_escape_string($mfgcode)."' "
					. "\n where compid='".$dbh->real_escape_string($compid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Component created for ".$partid.": ".$mfgcode;
					$myparts->LogSave($dbh, LOGTYPE_COMPNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
	else 
		$myparts->AlertMeTo("Part must be specified.");
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Delete the component
		$q_p = "delete from components "
			. "\n where compid='".$dbh->real_escape_string($compid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Component deleted: ".$compid;
			$myparts->LogSave($dbh, LOGTYPE_COMPDELETE, $uid, $logmsg);
			
			$myparts->AlertMeTo("Component deleted.");
			// remove the supplier links to the component
			$q_p = "delete from suppliers "
				. "\n where compid='".$dbh->real_escape_string($compid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		}
		$dbh->close();
		$myparts->UpdateParent();
		$myparts->PopMeClose();
		die();
	}
}


// Get the parts, states, datasheets and suppliers for the lists
$q_d = "select * "
	. "\n from parts "
	. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
	. "\n left join footprint on footprint.fprintid=parts.footprint "
	. "\n order by catdescr, partdescr "
	;
		
$s_d = $dbh->query($q_d);
$list_parts = array();
$i = 0;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_parts[$i][0] = $r_d["partid"];
		$list_parts[$i][1] = $r_d["catdescr"]." ".$r_d["partdescr"]." ".$r_d["fprintdescr"];
		$i++;
	}
	$s_d->free();
}

$q_d = "select compstateid, "
	. "\n statedescr "
	. "\n from compstates "
	. "\n order by statedescr "
	;
		
$s_d = $dbh->query($q_d);
$list_states = array();
$i = 0;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_states[$i][0] = $r_d["compstateid"];
		$list_states[$i][1] = $r_d["statedescr"];
		$i++;
	}
	$s_d->free();
}

$q_d = "select dataid, "
	. "\n datadescr "
	. "\n from datasheets "
	. "\n order by datadescr "
	;
		
$s_d = $dbh->query($q_d);
$list_datasheet = array();
$list_datasheet[0][0] = 0;
$list_datasheet[0][1] = "None or Upload";
$i = 1;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_datasheet[$i][0] = $r_d["dataid"];
		$list_datasheet[$i][1] = $r_d["datadescr"];
		$i++;
	}
	$s_d->free();
}

$q_d = "select cvid, "
	. "\n cvname "
	. "\n from custvend "
	. "\n where cvtype & ".CVTYPE_SUPPLIER
	. "\n order by cvname "
	;
		
$s_d = $dbh->query($q_d);
$list_supplier = array();
$i = 0;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_supplier[$i][0] = $r_d["cvid"];
		$list_supplier[$i][1] = $r_d["cvname"];
		$i++;
	}
	$s_d->free();
}

$dset_supp = array();
$ns = 0;

if ($compid !== false)
{
	$urlargs = "?compid=".urlencode($compid);
	
	$q_p = "select * "
		. "\n from components "
		. "\n left join datasheets on datasheets.dataid=components.datasheet "
		. "\n left join compstates on compstates.compstateid=components.compstateid "
		. "\n left join parts on parts.partid=components.partid "
		. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
		. "\n where compid='".$dbh->real_escape_string($compid)."' "
		;
			
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_p = $s_p->fetch_assoc();
		$partid = $r_p["partid"];
		$mfgname = $r_p["mfgname"];
		$mfgcode = $r_p["mfgcode"];
		$dataid = $r_p["dataid"];
		$datadescr = $r_p["datadescr"];
		$datasheetpath = $r_p["datasheetpath"];
		$compstateid = $r_p["compstateid"];
		$statedescr = $r_p["statedescr"];
		$partdescr = $r_p["partdescr"];
		$partcat = $r_p["catdescr"];
		$partnum = $r_p["partnumber"];
		$s_p->free();
	}
	
	// Suppliers
	$q_t = "select * "
		. "\n from suppliers "
		. "\n left join custvend on custvend.cvid=suppliers.suppid "
		. "\n where compid='".$dbh->real_escape_string($compid)."' "
		;
	$s_t = $dbh->query($q_t);
	if ($s_t)
	{
		while ($r_t = $s_t->fetch_assoc())
		{
			$dset_supp[$ns]["suppid"] = $r_t["suppid"];
			$dset_supp[$ns]["cvname"] = $r_t["cvname"];
			$dset_supp[$ns]["suppcatno"] = $r_t["suppcatno"];
			$ns++;
		}
		$s_t->free();
	}
}
else 
{
	$urlargs="";
	$partid = false;
	$mfgname = "";
	$mfgcode = "";
	$dataid = false;
	$datadescr = "";
	$datasheetpath = "";
	$compstateid = false;
	$statedescr = "";
	$partdescr = "";
	$partcat = "";
	$partnum = "";
}		

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Component Properties";
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
	<span class="formheadtext"><?php print ($compid === false ? "Add New Component" : "Edit Component") ?></span>
	<p/>

	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()' enctype="multipart/form-data">
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Part Description</td>
	<td class="propscell_lt propcell" width="70%">
	<select name="partid" style="width: 28em" tabindex="10">
	<?php $myparts->RenderOptionList($list_parts, $partid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Manufacturer</td>
	<td class="propscell_lt propcell">
	<input type="text" name="mfgname" size="48" maxlength="100" tabindex="20" value="<?php print htmlentities($mfgname) ?>" /></td>
	</tr>

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Device Number</td>
	<td class="propscell_lt propcell">
	<input type="text" name="mfgcode" size="48" maxlength="100" tabindex="30" value="<?php print htmlentities($mfgcode) ?>" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Existing Datasheet</td>
	<td class="propscell_lt propcell" width="70%">
	<select name="dataid" style="width: 28em" tabindex="40">
	<?php $myparts->RenderOptionList($list_datasheet, $dataid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Upload New Data Sheet</td>
	<td class="propscell_lt propcell">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php print MAX_DATASHEET_UPLOAD_SIZE ?>" />
	<input type="file" name="dsheetfile" value="" size="40" maxlength="100" tabindex="50" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">New Data Sheet Desc</td>
	<td class="propscell_lt propcell">
	<input type="text" name="datadescr" size="48" maxlength="100" tabindex="60" value="" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Component Status</td>
	<td class="propscell_lt propcell" width="70%">
	<select name="compstateid" style="width: 28em" tabindex="70">
	<?php $myparts->RenderOptionList($list_states, $compstateid, false); ?>
	</select></td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" tabindex="80" onclick="delClear()" />
	&nbsp;
<?php
	if ($compid !== false)
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
	<hr/>

<?php
if ($compid !== false)
{
	// Assign suppliers to this component
?>
	<span class="formheadtext">Add a supplier for this component</span>
	<p/>
	
	<form name="suppform" method="post" action="<?php print $formfile.$urlargs ?>" >
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Supplier</td>
	<td class="propscell_lt propcell" width="70%">
	<select name="suppid" style="width: 28em" tabindex="100">
	<?php $myparts->RenderOptionList($list_supplier, false, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Catalogue No</td>
	<td class="propscell_lt propcell">
	<input type="text" name="suppcatnum" size="48" maxlength="40" value="" tabindex="110" /></td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_supplier" class="btntext" value="Add" />
	</td>
	</tr>
	</table>
	</form>	
	
	<p/>
	<span class="formheadtext">Suppliers for this component</span>
	<p/>
	
	<table class="dataview_noborder">
	<tr>
	<td class="tablehead" width="60"></td>
	<td class="tablehead" width="330">Supplier</td>
	<td class="tablehead" width="140">Cat No</td>
	</tr>
	
<?php 
	for ($i = 0; $i < $ns; $i++)
	{
		print "<tr>\n";
		print "<td class=\"tabletext\"><a href=\"".$formfile.$urlargs."&suppid=".urlencode($dset_supp[$i]["suppid"])."\">Remove</a></td>\n";
		print "<td class=\"tabletext\">".htmlentities($dset_supp[$i]["cvname"])."</td>\n";
		print "<td class=\"tabletext\">".htmlentities($dset_supp[$i]["suppcatno"])."</td>\n";
		print "</tr>\n";
	}
?>	
	</table>
<?php 
}
?>
	</section>
</body></html>
