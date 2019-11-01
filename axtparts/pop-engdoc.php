<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-engdoc.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new documents
// engdocid: ID of document to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-engdoc.php";
$formname = "popengdoc";
$formtitle= "Add/Edit Document Detail";
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

$docid = false;
$assyid = false;
if (isset($_GET['engdocid']))
	$docid = trim(urldecode(($_GET["engdocid"])));
if (!is_numeric($docid))
	$docid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ENGDOCS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["docdescr"]))
			$docdescr = trim($_POST["docdescr"]);
		else 
			$docdescr = "";
		
		if ($docdescr == "")
			$myparts->AlertMeTo("Require a document description.");
		else
		{
			if (isset($_POST["assyid"]))
				$assyid = trim($_POST["assyid"]);
			else 
				$assyid = "";
		
			if (($assyid == "") && ($docid === false))
				$myparts->AlertMeTo("Require an assembly selection.");
			else 
			{
				if ($docid === false)
				{
					// Upload of new document - place it in the directory under the selected assembly
					if (isset($_FILES["docfile"]))
					{
						if ($_FILES["docfile"]["error"] == UPLOAD_ERR_OK)
						{
							// Find the docpath for the document, as specified by the assembly part number
							$q_p = "select partnumber "
								. "\n from assemblies "
								. "\n left join parts on parts.partid=assemblies.partid "
								. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
								;
								
							$s_p =$dbh->query($q_p);
							if (!$s_p)
								$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
							else 
							{
								$r_p = $s_p->fetch_assoc();
								$partnum = $r_p["partnumber"];
								$s_p->free();
								
								$docdir = ENGDOC_DIR.$partnum."/";
								if (!file_exists($docdir))
								{
									$r = mkdir($docdir, 0755, true);
									if ($r === false)
										$myparts->AlertMeTo("Error: Could not create directory for document(".htmlentities($docdir).").");
								}
								else 
									$r = true;
									
								if ($r === true)
								{
									$ftype = $_FILES["docfile"]["type"];
									$fname = $_FILES["docfile"]["tmp_name"];
									$frealname = $_FILES["docfile"]["name"];
									$frealpath = $docdir.$frealname;
									$r = move_uploaded_file($fname, $frealpath);
									if ($r !== true)
										$myparts->AlertMeTo("Error: Could not move uploaded file to ".htmlentities($frealpath).".");
									else 
									{
										$q_d = "insert into engdocs "
											. "\n set "
											. "\n engdocpath='".$dbh->real_escape_string($frealpath)."', "
											. "\n engdocdescr='".$dbh->real_escape_string($docdescr)."', "
											. "\n assyid='".$dbh->real_escape_string($assyid)."' "
											;
											
										$s_d = $dbh->query($q_d);
										if (!$s_d)
											$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
										else 
										{
											$uid = $myparts->SessionMeUID();
											$logmsg = "Document created: ".$frealpath;
											$myparts->LogSave($dbh, LOGTYPE_EDOCNEW, $uid, $logmsg);
											$myparts->AlertMeTo("Document saved to ".htmlentities($frealpath).".");
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
					// existing - update the values only - description only, as assembly is part of the file path
					$q_p = "update engdocs "
						. "\n set "
						. "\n engdocdescr='".$dbh->real_escape_string($docdescr)."' "
						. "\n where engdocid='".$dbh->real_escape_string($docid)."' "
						;
						
					$s_p = $dbh->query($q_p);
					if (!$s_p)
						$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
					else
					{
						$uid = $myparts->SessionMeUID();
						$logmsg = "Document metadata updated: ".$docdescr;
						$myparts->LogSave($dbh, LOGTYPE_EDOCCHANGE, $uid, $logmsg);
						$myparts->UpdateParent();
					}
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ENGDOCS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Remove the reference, not the file?
		$q_p = "delete from engdocs "
			. "\n where engdocid='".$dbh->real_escape_string($docid)."' "
			. "\n limit 1 "
			;
			
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Document removed: ".$docid;
			$myparts->LogSave($dbh, LOGTYPE_EDOCDELETE, $uid, $logmsg);
			$myparts->AlertMeTo("Document deleted.");
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

if ($docid !== false)
{
	$urlargs = "?engdocid=".urlencode($docid);
	
	$q_p = "select engdocid, "
		. "\n engdocdescr, "
		. "\n assyid, "
		. "\n engdocpath "
		. "\n from engdocs "
		. "\n where engdocid='".$dbh->real_escape_string($docid)."' "
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
		$docdescr = $r_p["engdocdescr"];
		$assyid = $r_p["assyid"];
		$docpath = $r_p["engdocpath"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$docdescr = "";
	$assyid = 0;
	$docpath = "";
}

$q_assy = "select assyid, "
		. "\n assydescr, "
		. "\n assyrev, "
		. "\n assyaw, "
		. "\n partdescr, "
		. "\n partnumber "
		. "\n from assemblies "
		. "\n left join parts on parts.partid=assemblies.partid "
		. "\n order by assydescr "
		;
		
$s_assy = $dbh->query($q_assy);
$list_assy = array();
$i = 0;
if ($s_assy)
{
	while ($r_assy = $s_assy->fetch_assoc())
	{
		$list_assy[$i][0] = $r_assy["assyid"];
		$list_assy[$i][1] = $r_assy["partdescr"]." - ".$r_assy["partnumber"].": (R".str_pad($r_assy["assyrev"], 2, "0", STR_PAD_LEFT)."/".$r_assy["assyaw"].")";
		$i++;
	}
	$s_assy->free();
}

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Engineering Document Properties";
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
	<span class="formheadtext"><?php print ($docid === false ? "Add New Document" : "Edit Document Description") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()' enctype="multipart/form-data">
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Document Description</td>
	<td class="propscell_lt propcell" width="70%">
	<input type="text" name="docdescr" size="50" maxlength="100" tabindex="10" value="<?php print htmlentities($docdescr) ?>"  /></td>
	</tr>
	
<?php
if ($docid === false)
{
	// Allow for setting of assembly and document upload
?>
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Assembly</td>
	<td class="propscell_lt propcell">
	<select name="assyid" style="width: 34em" tabindex="20">
	<?php $myparts->RenderOptionList($list_assy, $assyid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Upload New Document</td>
	<td class="propscell_lt propcell">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php print MAX_DATASHEET_UPLOAD_SIZE ?>" />
	<input type="file" name="docfile" value="" size="50" maxlength="100" tabindex="50" />
	</td>
	</tr>

<?php
}
else
{
	// Show assembly and document file path
?>
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Assembly</td>
	<td class="propscell_lt propcell">
	<select name="assyid" style="width: 34em" tabindex="20" disabled>
	<?php $myparts->RenderOptionList($list_assy, $assyid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Path</td>
	<td class="propscell_lt propcell">
	<input type="text" name="docpath" size="50" maxlength="200" tabindex="50" value="<?php print htmlentities($docpath) ?>" readonly />
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
	if ($docid !== false)
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
