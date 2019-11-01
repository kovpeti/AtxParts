<?php
// ********************************************
// Copyright 2003-2015 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-stock.php 206 2017-01-16 07:49:49Z gswan $

// Parameters passed: 
// $partid: The part associated with this stock line
// $stockid: The stockid entry to edit. Create a new stock line if this is not present.

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "pop-stock.php";
$formname = "popstock";
$formtitle= "Add/Edit Stock Item";
$popx = 700;
$popy = 400;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

$partid = false;
if (isset($_GET['partid']))
	$partid = trim(urldecode(($_GET["partid"])));
if (!is_numeric($partid))
	$partid = false;
	
$stockid = false;
if (isset($_GET['stockid']))
	$stockid = trim(urldecode(($_GET["stockid"])));
if (!is_numeric($stockid))
	$stockid = false;

if ($partid === false)
{
	$myparts->AlertMeTo("A part must be specified.");
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

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["qty"]))
			$qty = trim($_POST["qty"]);
		else 
			$qty = 0;
		if (isset($_POST["note"]))
			$note = trim($_POST["note"]);
		else 
			$note = "";
		if (isset($_POST["locid"]))
			$locid = trim($_POST["locid"]);
		else 
			$locid = 0;
		
		if ($locid == 0)
			$myparts->AlertMeTo("Require a location.");
		else 
		{
			if ($stockid === false)
			{
				// new stock item - insert the values
				$q_p = "insert into stock "
					. "\n set "
					. "\n qty='".$dbh->real_escape_string($qty)."', "
					. "\n note='".$dbh->real_escape_string($note)."', "
					. "\n locid='".$dbh->real_escape_string($locid)."', "
					. "\n partid='".$dbh->real_escape_string($partid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Stock location ".$locid." assigned to ".$partid;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNASSIGN, $uid, $logmsg);
					$myparts->UpdateParent();
				}
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
			else 
			{
				// existing - update the values
				$q_p = "update stock "
					. "\n set "
					. "\n qty='".$dbh->real_escape_string($qty)."', "
					. "\n note='".$dbh->real_escape_string($note)."', "
					. "\n locid='".$dbh->real_escape_string($locid)."', "
					. "\n partid='".$dbh->real_escape_string($partid)."' "
					. "\n where stockid='".$dbh->real_escape_string($stockid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Stock location ".$locid." updated for ".$partid;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNASSIGN, $uid, $logmsg);
					$myparts->UpdateParent();
				}
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCK) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from stock "
			. "\n where stockid='".$dbh->real_escape_string($stockid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Stock deleted: ".$stockid;
			$myparts->LogSave($dbh, LOGTYPE_PARTLOCNUNASSIGN, $uid, $logmsg);
			$myparts->AlertMeTo("Stock deleted.");
			$myparts->UpdateParent();
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

// Get the locations for the list
$q_loc = "select locid, "
		. "\n locref, "
		. "\n locdescr "
		. "\n from locn "
		. "\n order by locref "
		;
		
$s_loc = $dbh->query($q_loc);
$list_locn = array();
$i = 0;
if ($s_loc)
{
	while ($r_loc = $s_loc->fetch_assoc())
	{
		$list_locn[$i][0] = $r_loc["locid"];
		$list_locn[$i][1] = $r_loc["locref"]." (".$r_loc["locdescr"].")";
		$i++;
	}
	$s_loc->free();
}

if ($stockid !== false)
{
	$q_stk = "select stockid, "
		. "\n qty, "
		. "\n note, "
		. "\n locid, "
		. "\n partid "
		. "\n from stock "
		. "\n where stockid='".$dbh->real_escape_string($stockid)."' "
		;
	$s_stk = $dbh->query($q_stk);

	if (!$s_stk)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_stk = $s_stk->fetch_assoc();
		$locid = $r_stk["locid"];
		$qty = $r_stk["qty"];
		$note = $r_stk["note"];
		$s_stk->free();
	}
}
else
{
	$locid = 0;
	$qty = 0;
	$note = "";
}

$urlargs = "?partid=".urlencode($partid);

if ($stockid !== false)
	$urlargs .= "&stockid=".urlencode($stockid);

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = "Stock Properties";
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
	<span class="formheadtext"><?php print ($stockid === false ? "Add New Stock Detail" : "Edit Stock Detail") ?></span>
	<p/>
	
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" onsubmit='return deleteCheck()'>
	<table class="contentpanel_props_600">
	<tr class="contentrow_30">
	<td class="propscell_lt propcell" width="25%">Qty</td>
	<td class="propscell_lt propcell" width="75%">
	<input type="text" name="qty" size="40" maxlength="10" tabindex="10" value="<?php print htmlentities($qty) ?>" />
	</td>
	</tr>

	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Location</td>
	<td class="propscell_lt propcell">
	<select name="locid" style="width: 24em" tabindex="20">
	<?php $myparts->RenderOptionList($list_locn, $locid, false); ?>
	</select></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="propscell_lt propcell">Note</td>
	<td class="propscell_lt propcell">
	<input type="text" name="note" size="40" maxlength="60" tabindex="30" value="<?php print htmlentities($note) ?>" />
	</td>
	</tr>
	</table>
	
	<table class="contentpanel_popup_600">
	<tr class="contentrow_40">
	<td class="contentcell_lt">
	<input type="submit" name="btn_save" class="btntext" value="Save" onclick="delClear()" />
	&nbsp;
<?php
	if ($stockid !== false)
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

