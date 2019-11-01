<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bomlineaddvar.php 202 2016-07-17 06:08:05Z gswan $

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

$bomid = false;
if (isset($_GET['bomid']))
	$bomid = trim(urldecode(($_GET["bomid"])));
if (!is_numeric($bomid))
	$bomid = false;
	
if (($assyid === false) || ($bomid === false))
{
	$myparts->AlertMeTo("A bom line and assembly must be specified.");
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

$urlargs = "?assyid=".urlencode($assyid)."&bomid=".urlencode($bomid);

// Handle part form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMVARIANT) === true)
	{
		if (isset($_POST["variantid"]))
		{
			$variantid = trim($_POST["variantid"]);
			if ($variantid == "")
				$variantid = false;
		}
		else 
			$variantid = false;

		if ($variantid === false)
			$myparts->AlertMeTo("A variant must be specified.");
		else 
		{
			$q_p = "insert into bomvariants "
				. "\n set "
				. "\n variantid='".$dbh->real_escape_string($variantid)."', "
				. "\n bomid='".$dbh->real_escape_string($bomid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else 
				$myparts->UpdateParent();
		}
	}
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

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Add BOM Line to Variant";
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
	<span class="formheadtext">Add selected BOM item to variant BOM</span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" >
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="30%">Variant</td>
	<td class="propscell_lt propcell" width="70%">
	<select name="variantid" style="width: 35em" tabindex="10">
	<?php $myparts->RenderOptionList($list_var, false, false); ?>
	</select></td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save">

	</td>
	</tr>
	</table>
	</form>

	</section>
</body></html>
