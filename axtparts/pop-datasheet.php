<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-datasheet.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new datasheet
// $dataid: ID of datasheet to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-datasheet.php";
$formname = "popdatasheet";
$formtitle= "Add/Edit Datasheet Detail";
$popx = 700;
$popy = 400;

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

$partcatid = false;
$dataid = false;
if (isset($_GET['dataid']))
	$dataid = trim(urldecode(($_GET["dataid"])));
if (!is_numeric($dataid))
	$dataid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_DATASHEEETS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["datadescr"]))
			$datadescr = trim($_POST["datadescr"]);
		else 
			$datadescr = "";
		
		if ($datadescr == "")
			$myparts->AlertMeTo("Require a datasheet description.");
			
		if (isset($_POST["partcatid"]))
			$partcatid = trim($_POST["partcatid"]);
		else 
			$partcatid = "";
	
		if ($partcatid == "")
			$myparts->AlertMeTo("Require a part category selection.");
		else 
		{
			if ($dataid === false)
			{
				// Upload of datasheet - place it in the directory for the selected part category
				if (isset($_FILES["dsheetfile"]))
				{
					if ($_FILES["dsheetfile"]["error"] == UPLOAD_ERR_OK)
					{
						// Find the datadir for the datasheet, as specified by the part category
						$q_p = "select partcatid, "
							. "\n datadir "
							. "\n from pgroups "
							. "\n where partcatid='".$dbh->real_escape_string($partcatid)."' "
							;
						$s_p =$dbh->query($q_p);
						if (!$s_p)
							$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
						else 
						{
							$r_p = $s_p->fetch_assoc();
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
										$uid = $myparts->SessionMeUID();
										$logmsg = "Datasheet added: ".$datadescr;
										$myparts->LogSave($dbh, LOGTYPE_DSHEETNEW, $uid, $logmsg);
										$myparts->AlertMeTo("Datasheet saved to ".htmlentities($frealpath).".");
										$myparts->UpdateParent();
									}
								}
							}
						}
					}
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update datasheets "
					. "\n set "
					. "\n datadescr='".$dbh->real_escape_string($datadescr)."', "
					. "\n partcatid='".$dbh->real_escape_string($partcatid)."' "
					. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Datasheet metadata updated: ".$datadescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_DATASHEEETS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from datasheets "
			. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			// Remove the references from components to this datasheet
			$q_p = "update components "
				. "\n set "
				. "\n dataid='0' "
				. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
				;
			$s_p = $dbh->query($q_p);
			
			$uid = $myparts->SessionMeUID();
			$logmsg = "Datasheet removed: ".$dataid;
			$myparts->LogSave($dbh, LOGTYPE_DSHEETDELETE, $uid, $logmsg);
			$myparts->AlertMeTo("Datasheet deleted.");
		}
		$myparts->PopMeClose();
		$dbh->close();
		die();
	}
}

if ($dataid !== false)
{
	$urlargs = "?dataid=".urlencode($dataid);
	
	$q_p = "select dataid, "
		. "\n datadescr, "
		. "\n partcatid "
		. "\n from datasheets "
		. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
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
		$datadescr = $r_p["datadescr"];
		$partcatid = $r_p["partcatid"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$datadescr = "";
	$partcatid = 0;
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

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Datasheet Properties";
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
	<span class="formheadtext"><?php print ($dataid === false ? "Add New Datasheet" : "Edit Datasheet Detail") ?></span>
	<p/>

	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()' enctype="multipart/form-data">
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Datasheet Description</td>
	<td class="propscell_lt propcell" width="70%">
	<input type="text" name="datadescr" size="40" maxlength="100" tabindex="10" value="<?php print htmlentities($datadescr) ?>" /></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Part Category</td>
	<td class="propscell_lt propcell">
	<select name="partcatid" style="width: 24em" tabindex="20">
	<?php $myparts->RenderOptionList($list_partcat, $partcatid, false); ?>
	</select></td>
	</tr>

<?php
if ($dataid === false)
{
?>
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Upload New Data Sheet</td>
	<td class="propscell_lt propcell">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php print MAX_DATASHEET_UPLOAD_SIZE ?>" />
	<input type="file" name="dsheetfile" value="" size="40" maxlength="100" tabindex="50" />
	</td>
	</tr>
<?php
}
?>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($dataid !== false)
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

	</section>
</body></html>
