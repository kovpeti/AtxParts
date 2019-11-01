<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-contact.php 202 2016-07-17 06:08:05Z gswan $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-contact.php";
$returnformfile = "frm-addressbook.php";
$formname = "contact";
$formtitle= "Contact Details";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_ADDRESS) !== true)
{
	$myparts->AlertMeTo("Insufficient tab privileges.");
	die();
}

// This form required a contact id to be sent with the URL
if (isset($_GET['contid']))
	$contid = trim(urldecode($_GET["contid"]));
else
{
	$myparts->AlertMeTo("No contact ID specified.");
	$myparts->VectorMeTo($returnformfile);
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if (!$dbh)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

if (isset($_POST["btn_update"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($contid != "")
		{
			if (isset($_POST['contname']))
			{
				$contname = trim($_POST['contname']);
				if (isset($_POST['conttel']))
					$conttel = trim($_POST['conttel']);
				if (isset($_POST['contemail']))
					$contemail = trim($_POST['contemail']);
				if (isset($_POST['contposn']))
					$contposn = trim($_POST['contposn']);
				if (isset($_POST['contcomment']))
					$contcomment = trim($_POST['contcomment']);
				if (isset($_POST['contmob']))
					$contmob = trim($_POST['contmob']);

				// now we can update the record
				$q_c = "update contacts "
					. "\n set "
					. "\n contname='".$dbh->real_escape_string($contname)."', "
					. "\n conttel='".$dbh->real_escape_string($conttel)."', "
					. "\n contemail='".$dbh->real_escape_string($contemail)."', "
					. "\n contposn='".$dbh->real_escape_string($contposn)."', "
					. "\n contmob='".$dbh->real_escape_string($contmob)."', "
					. "\n contcomment='".$dbh->real_escape_string($contcomment)."' "
					. "\n where contid='".$dbh->real_escape_string($contid)."' "
					;
					
				$s_c = $dbh->query($q_c);
				if (!$s_c)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			}
			else
				$myparts->AlertMeTo("Name must be specified.");
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No contact ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($contid != "")
		{
			// delete the contact
			$q_c = "delete from contacts "
				. "\n where contid='".$dbh->real_escape_string($contid)."' "
				. "\n limit 1 "
				;
					
			$s_c = $dbh->query($q_c);
			if (!$s_c)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
	
			$dbh->close();
			$myparts->AlertMeTo("Contact deleted.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No contact ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}

$r_c = $myparts->GetContact($dbh, $contid);
$cvid = $r_c["cvid"];
$r_cv = $myparts->GetAddressRow($dbh, $cvid);

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
$tabparams["tabon"] = "Address";
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
	<span class="formheadtext">Contact Detail</span>
	</td>
	</tr>
	
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Organisation</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
	<a href="<?php print "frm-address.php?cvid=".$cvid ?>">
	<span class="formlinktext"><?php print htmlentities($r_cv['cvname']) ?></span>
	</a>
	</td>
	<td class="contentcell_lt" colspan="8"></td>
	<td class="contentcell_lt" colspan="8"></td>
	</tr>

<?php
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) === true)
		print "<form name=\"contedit\" method=\"post\" action=\"".$formfile."?contid=".urlencode($contid)."\" onsubmit='return deleteCheck()'>\n";
?>
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Name</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Phone</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Email</span></td>
	</tr>

	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
<?php
	print "<input type=\"text\" name=\"contname\" tabindex=\"1\" size=\"40\" maxlength=\"90\" value=\"".htmlentities($r_c['contname'])."\" ";
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		print "readonly";
	print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8">
<?php 
	print "<input type=\"text\" name=\"conttel\" size=\"40\" tabindex=\"8\" maxlength=\"60\" value=\"".htmlentities($r_c['conttel'])."\" ";
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		print "readonly ";
	print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8">
<?php 
	print "<input type=\"text\" name=\"contemail\" size=\"40\" tabindex=\"10\" maxlength=\"90\" value=\"".htmlentities($r_c['contemail'])."\" ";
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		print "readonly ";
	print ">";
?>
	</td>
	</tr>

	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Position</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Mobile</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Comment</span></td>
	</tr>

	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
<?php
	print "<input type=\"text\" name=\"contposn\" tabindex=\"1\" size=\"40\" maxlength=\"90\" value=\"".htmlentities($r_c['contposn'])."\" ";
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		print "readonly";
	print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8">
<?php 
	print "<input type=\"text\" name=\"contmob\" size=\"40\" tabindex=\"8\" maxlength=\"60\" value=\"".htmlentities($r_c['contmob'])."\" ";
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		print "readonly ";
	print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8">
<?php 
	print "<input type=\"text\" name=\"contcomment\" size=\"40\" tabindex=\"10\" maxlength=\"90\" value=\"".htmlentities($r_c['contcomment'])."\" ";
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		print "readonly ";
	print ">";
?>
	</td>
	</tr>

<?php
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) === true)
	{
?>
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
	<input type="submit" name="btn_update" class="btntext" value="Update" onclick="delClear()" title="Update the details entered in the database" />
	<input type="submit" name="btn_delete" class="btnredtext" value="Delete" onclick="delSet()" disabled="true" title="Delete this contact from the database" />
	<input type="button" name="editenable" class="btngrntext" value="EN" onclick="javascript:document.contedit.btn_delete.disabled=false" title="Enable the delete button" />
	</td>
	<td class="contentcell_lt" colspan="8"></td>
	<td class="contentcell_lt" colspan="8"></td>
	</tr>
	
<?php
		print "</form>\n";
	}
?>
	</table>
</section>
<p/>
<?php
$myparts->FormRender_BottomPanel_Login($bottomparams);
?>
</div>
</body></html>
