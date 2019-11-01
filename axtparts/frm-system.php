<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-system.php 202 2016-07-17 06:08:05Z gswan $

// Displays system parameters and settings confirmation
// Parameters passed: 
// none

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-system.php";
$formname = "system";
$formtitle= "System Settings";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_ADMIN) !== true)
{
	$myparts->AlertMeTo("Insufficient tab privileges.");
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);

// Test various system parameters
$dset = array();

if (file_exists(ENGDOC_DIR))
	$dset["engdoc"]["exists"] = true;
else
	$dset["engdoc"]["exists"] = false;

if (is_readable(ENGDOC_DIR) === true)
	$dset["engdoc"]["read"] = true;
else
	$dset["engdoc"]["read"] = false;

if (is_writeable(ENGDOC_DIR) === true)
	$dset["engdoc"]["write"] = true;
else
	$dset["engdoc"]["write"] = false;

if (file_exists("../".DATASHEETS_DIR))
	$dset["datasheet"]["exists"] = true;
else
	$dset["datasheet"]["exists"] = false;

if (is_readable("../".DATASHEETS_DIR) === true)
	$dset["datasheet"]["read"] = true;
else
	$dset["datasheet"]["read"] = false;

if (is_writeable("../".DATASHEETS_DIR) === true)
	$dset["datasheet"]["write"] = true;
else
	$dset["datasheet"]["write"] = false;

if (file_exists(SWIMAGE_DIR))
	$dset["swimage"]["exists"] = true;
else
	$dset["swimage"]["exists"] = false;

if (is_readable(SWIMAGE_DIR) === true)
	$dset["swimage"]["read"] = true;
else
	$dset["swimage"]["read"] = false;

if (is_writeable(SWIMAGE_DIR) === true)
	$dset["swimage"]["write"] = true;
else
	$dset["swimage"]["write"] = false;

if ($dbh)
	$dset["db"]["access"] = true;
else
	$dset["db"]["access"] = false;

$dbh->close();

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $_cfg_stdmeta;
$headparams["title"] = $_cfg_stdtitle;
$headparams["icon"] = $_cfg_stdicon;
$headparams["css"] = $_cfg_stdcss;
$headparams["jscript_file"] = $_cfg_stdjscript;
$myparts->FormRender_Head($headparams);

$bodyparams = array();
$myparts->FormRender_BodyTag($bodyparams);

$tabparams = array();
$tabparams["tabon"] = "Admin";
$tabparams["tabs"] = $_cfg_tabs;
$myparts->FormRender_Tabs($tabparams);

print "<div class=\"formpanel\">\n";

$topparams = array();
$topparams["siteheading"] = SYSTEMHEADING;
$topparams["formtitle"] = $formtitle;
$topparams["username"] = $username;
$topparams["buttons"] = array(
		"logout" => $_cfg_btn_logout,
);
$myparts->FormRender_TopPanel($topparams);

$bottomparams = array();
$bottomparams["branding"] = SYSTEMBRANDING." ".ENGPARTSVERSION;

?>
<section class="contentpanel">
	<table class="contentpanel">
	<?php $myparts->FormRender_Grid(960, 24) ?>
	
	<tr class="contentrow_20">
	<td class="contentcell_lt" colspan="24">
	<table class="pagebtnset">
	<tr class="contentrow_30">
	<th class="dataview_c" width="24%"></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-admin.php"><span class="pagebtntext">Users</span></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-logs.php"><span class="pagebtntext" title="System Logs">Logs</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-roles.php"><span class="pagebtntext" title="Roles">Roles</span></a></th>
	<th class="pagebtn_c pagebtncell_on" width="13%"><span class="pagebtntext">System</span></a></th>
	<th class="pagebtn_c" width="24%"></th>
	</tr>
	</table>
	</td>
	</tr>
	
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>
	
	<tr>
	<td class="contentcell_lt" colspan="24">
	
	<table class="dataview_noborder">
	<tr>
	<td width="40%" class="tablehead">Parameter</td>
	<td width="60%" class="tablehead">Tests</td>
	</tr>

	<tr>
	<td class="tabletext"><?php print "Directory: ".ENGDOC_DIR ?></td>
	<td class="tabletext">
<?php 
if ($dset["engdoc"]["exists"] === true)
	print "<span class=\"greentext\">Exists [OK]</span>";
else
	print "<span class=\"redtext\">Exists [FAIL]</span>";
print "&nbsp;&nbsp;";
		
if ($dset["engdoc"]["read"] === true)
	print "<span class=\"greentext\">Readable [OK]</span>";
else
	print "<span class=\"redtext\">Readable [FAIL]</span>";
print "&nbsp;&nbsp;";

if ($dset["engdoc"]["write"] === true)
	print "<span class=\"greentext\">Writeable [OK]</span>";
else
	print "<span class=\"redtext\">Writeable [FAIL]</span>";
?>
	</td>
	</tr>

	<tr>
	<td class="tabletext"><?php print "Directory: ".DATASHEETS_DIR ?></td>
	<td class="tabletext">
<?php 
if ($dset["datasheet"]["exists"] === true)
	print "<span class=\"greentext\">Exists [OK]</span>";
else
	print "<span class=\"redtext\">Exists [FAIL]</span>";
print "&nbsp;&nbsp;";
		
if ($dset["datasheet"]["read"] === true)
	print "<span class=\"greentext\">Readable [OK]</span>";
else
	print "<span class=\"redtext\">Readable [FAIL]</span>";
print "&nbsp;&nbsp;";

if ($dset["datasheet"]["write"] === true)
	print "<span class=\"greentext\">Writeable [OK]</span>";
else
	print "<span class=\"redtext\">Writeable [FAIL]</span>";
?>
	</td>
	</tr>

	<tr>
	<td class="tabletext"><?php print "Directory: ".SWIMAGE_DIR ?></td>
	<td class="tabletext">
<?php 
if ($dset["swimage"]["exists"] === true)
	print "<span class=\"greentext\">Exists [OK]</span>";
else
	print "<span class=\"redtext\">Exists [FAIL]</span>";
print "&nbsp;&nbsp;";
		
if ($dset["swimage"]["read"] === true)
	print "<span class=\"greentext\">Readable [OK]</span>";
else
	print "<span class=\"redtext\">Readable [FAIL]</span>";
print "&nbsp;&nbsp;";

if ($dset["swimage"]["write"] === true)
	print "<span class=\"greentext\">Writeable [OK]</span>";
else
	print "<span class=\"redtext\">Writeable [FAIL]</span>";
?>
	</td>
	</tr>

	<tr>
	<td class="tabletext"><?php print "Database: ".PARTSDBASE ?></td>
	<td class="tabletext">
<?php 
if ($dset["db"]["access"] === true)
	print "<span class=\"greentext\">Access [OK]</span>";
else
	print "<span class=\"redtext\">Access [FAIL]</span>";
?>
	</td>
	</tr>

	</table>
	</td>
	</tr>
	
	</table>
</section>
<p/>
<?php 
$myparts->FormRender_BottomPanel_Login($bottomparams);
?>
</div>
</body></html>
