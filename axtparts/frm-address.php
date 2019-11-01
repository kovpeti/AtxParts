<?php
// ********************************************
// Copyright 2003-2015 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-address.php 204 2016-07-17 06:22:10Z gswan $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-address.php";
$returnformfile = "frm-addressbook.php";
$formname = "address";
$formtitle= "Address Details";

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

// This form required an addrid to be sent with the URL
if (isset($_GET['cvid']))
	$cvid = trim(urldecode($_GET["cvid"]));
else
{
	$myparts->AlertMeTo("No address ID specified.");
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
	if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($cvid != "")
		{
			if (isset($_POST['cvname']))
			{
				$cvname = trim($_POST['cvname']);
				if (isset($_POST['cvtel']))
					$cvtel = trim($_POST['cvtel']);
				if (isset($_POST['cvfax']))
					$cvfax = trim($_POST['cvfax']);
				if (isset($_POST['cvweb']))
					$cvweb = trim($_POST['cvweb']);
				if (isset($_POST['cvcomment']))
					$cvcomment = trim($_POST['cvcomment']);
				if (isset($_POST['cvaddr1']))
					$cvaddr1 = trim($_POST['cvaddr1']);
				if (isset($_POST['cvaddr2']))
					$cvaddr2 = trim($_POST['cvaddr2']);
				if (isset($_POST['cvcity']))
					$cvcity = trim($_POST['cvcity']);
				if (isset($_POST['cvstate']))
					$cvstate = trim($_POST['cvstate']);
				if (isset($_POST['cvpcode']))
					$cvpcode = trim($_POST['cvpcode']);
				if (isset($_POST['cvcountry']))
					$cvcountry = trim($_POST['cvcountry']);
				if (isset($_POST['cvctype']))
					$ctype = $_POST['cvctype'];
				$cvtype = 0x00;
				if ($ctype == 'c')
					$cvtype |= CVTYPE_COMPANY;
				if (isset($_POST['cvtypesupplier']))
					$cvtype |= CVTYPE_SUPPLIER;
				if (isset($_POST['cvtypeclient']))
					$cvtype |= CVTYPE_CLIENT;
				if (isset($_POST['cvtypeemployee']))
					$cvtype |= CVTYPE_EMPLOYEE;
				if (isset($_POST['cvtypepersonal']))
					$cvtype |= CVTYPE_PERSONAL;
				if (isset($_POST['cvtypemanufacturer']))
					$cvtype |= CVTYPE_MANUFACTURER;

				// now we can update the record
				$q_cv = "update custvend "
					. "\n set "
					. "\n cvname='".$dbh->real_escape_string($cvname)."', "
					. "\n cvaddr1='".$dbh->real_escape_string($cvaddr1)."', "
					. "\n cvaddr2='".$dbh->real_escape_string($cvaddr2)."', "
					. "\n cvcity='".$dbh->real_escape_string($cvcity)."', "
					. "\n cvstate='".$dbh->real_escape_string($cvstate)."', "
					. "\n cvpcode='".$dbh->real_escape_string($cvpcode)."', "
					. "\n cvcountry='".$dbh->real_escape_string($cvcountry)."', "
					. "\n cvcomment='".$dbh->real_escape_string($cvcomment)."', "
					. "\n cvtel='".$dbh->real_escape_string($cvtel)."', "
					. "\n cvfax='".$dbh->real_escape_string($cvfax)."', "
					. "\n cvweb='".$dbh->real_escape_string($cvweb)."', "
					. "\n cvtype=".$dbh->real_escape_string($cvtype)." "
					. "\n where cvid='".$dbh->real_escape_string($cvid)."' "
					;
					
				$s_cv = $dbh->query($q_cv);
				if (!$s_cv)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			}
			else
				$myparts->AlertMeTo("Name must be specified.");
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No address ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($cvid != "")
		{
			// Check to see if the address is a supplier, has contacts, is a manufacturer or customer.
			// If it does, then don't delete until all links are removed by the user
			$nc = $myparts->ReturnCountOf($dbh, "contacts", "contid", "cvid", $cvid);
			if ($nc > 0)
				$myparts->AlertMeTo("There are ".$nc." contacts still associated with this organisation.");
			else 
			{
				$ns = $myparts->ReturnCountOf($dbh, "suppliers", "suppid", "suppid", $cvid);
				if ($ns > 0)
					$myparts->AlertMeTo("This organisation is still being used as a supplier.");
				else 
				{
					$nm = $myparts->ReturnCountOf($dbh, "unit", "mfgid", "mfgid", $cvid);
					if ($nm > 0)
						$myparts->AlertMeTo("This organisation is a manufacturer of ".$nm." production units.");
					else 
					{
						$nu = $myparts->ReturnCountOf($dbh, "unit", "custid", "custid", $cvid);
						if ($nu > 0)
							$myparts->AlertMeTo("This organisation is a customer of production units.");
						else 
						{
							// delete the organisation
							$q_c = "delete from custvend "
								. "\n where cvid='".$dbh->real_escape_string($cvid)."' "
								. "\n limit 1 "
								;
								
							$s_c = $dbh->query($q_c);
							if (!$s_c)
								$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				
							$dbh->close();
							$myparts->AlertMeTo("Organisation deleted.");
							$myparts->VectorMeTo($returnformfile);
							die();
						}
					}
				}
			}
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No address ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}
elseif (isset($_POST["btn_newcontact"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if (isset($_POST['newcontactname']))
		{
			$editform = "frm-contact.php";
			$name = trim($_POST['newcontactname']);
			if ($name != "")
			{
				$c = $myparts->AddContactToCV($dbh, $cvid, $name);
				if (isset($c["rowid"]))
				{
					$contid = $c["rowid"];
					$dbh->close();
					$myparts->VectorMeTo($editform."?contid=".$contid);
					die();
				}
				else 
					$myparts->AlertMeTo("Error: ".htmlentities($c["error"], ENT_COMPAT));
			}
			else 
				$myparts->AlertMeTo("Name must be specified");
		}
		else
			$myparts->AlertMeTo("Name must be specified");
	}
}

$r_cv = $myparts->GetAddressRow($dbh, $cvid);
$contactlist = $myparts->GetContacts($dbh, $cvid);
$nc = count($contactlist);
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
	<span class="formheadtext">Address Detail</span>
	</td>
	</tr>

<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) === true)
	print "<form name=\"cvedit\" method=\"post\" action=\"".$formfile."?cvid=".urlencode($cvid)."\" onsubmit='return deleteCheck()'>\n";
?>
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Name</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Phone</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Website</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
<?php
print "<input type=\"text\" name=\"cvname\" size=\"40\" tabindex=\"10\"  maxlength=\"90\" value=\"".htmlentities($r_cv['cvname'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly";
print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8">
<?php 
print "<input type=\"text\" name=\"cvtel\" size=\"40\" tabindex=\"80\" maxlength=\"60\" value=\"".htmlentities($r_cv['cvtel'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly ";
print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8">
<?php 
print "<input type=\"text\" name=\"cvweb\" size=\"40\" tabindex=\"100\" maxlength=\"90\" value=\"".htmlentities($r_cv['cvweb'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly ";
print ">";
?>
	</td>
	</tr>

	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Address Line 1</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Fax</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">&nbsp;</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
<?php
print "<input type=\"text\" name=\"cvaddr1\" tabindex=\"20\" size=\"40\" maxlength=\"250\" value=\"".htmlentities($r_cv['cvaddr1'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly";
print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8">
<?php 	
print "<input type=\"text\" name=\"cvfax\" tabindex=\"90\" size=\"40\" maxlength=\"60\" value=\"".htmlentities($r_cv['cvfax'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly ";
print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="8"></td>
	</tr>
	
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Address Line 2</span></td>
	<td class="contentcell_lt" colspan="16"><span class="smlgrytext">Comment</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
<?php
print "<input type=\"text\" name=\"cvaddr2\" tabindex=\"30\" size=\"40\" maxlength=\"250\" value=\"".htmlentities($r_cv['cvaddr2'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly";
print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="16">
<?php 
print "<input type=\"text\" name=\"cvcomment\" tabindex=\"110\" size=\"85\" maxlength=\"250\" value=\"".htmlentities($r_cv['cvcomment'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly ";
print ">";
?>
	</td>
	</tr>

	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">City</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Type</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">&nbsp;</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
<?php
print "<input type=\"text\" name=\"cvcity\" tabindex=\"40\" size=\"40\" maxlength=\"60\" value=\"".htmlentities($r_cv['cvcity'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled";
print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="4" rowspan="5">
<?php 
print "<input type=\"checkbox\" name=\"cvtypesupplier\" tabindex=\"120\" value=\"1\" ";
if ($r_cv['cvtype'] & CVTYPE_SUPPLIER)
	print "checked ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled ";
print ">";
?>		
	<span class="formsmltext">Supplier</span><br/>
<?php 
print "<input type=\"checkbox\" name=\"cvtypemanufacturer\" tabindex=\"180\" value=\"1\" ";
if ($r_cv['cvtype'] & CVTYPE_MANUFACTURER)
	print "checked ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled ";
print ">";
?>		
	<span class="formsmltext">Manufacturer</span><br/>
<?php 
print "<input type=\"checkbox\" name=\"cvtypeclient\" tabindex=\"130\" value=\"1\" ";
if ($r_cv['cvtype'] & CVTYPE_CLIENT)
	print "checked ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled ";
print ">";
?>
	<span class="formsmltext">Client</span><br/>
<?php 
print "<input type=\"checkbox\" name=\"cvtypeemployee\" tabindex=\"140\" value=\"1\" ";
if ($r_cv['cvtype'] & CVTYPE_EMPLOYEE)
	print "checked ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled ";
print ">";
?>
	<span class="formsmltext">Employee</span><br/>
<?php
print "<input type=\"checkbox\" name=\"cvtypepersonal\" tabindex=\"150\" value=\"1\" ";
if ($r_cv['cvtype'] & CVTYPE_PERSONAL)
	print "checked ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled ";
print ">";
?>
	<span class="formsmltext">Personal</span><br/>
	</td>
	<td class="contentcell_lt" colspan="4" rowspan="5">
<?php 	
print "<input type=\"radio\" name=\"cvctype\" tabindex=\"160\" value=\"c\" ";
if ($r_cv['cvtype'] & CVTYPE_COMPANY)
	print "checked ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled ";
print ">";
?>
	<span class="formsmltext">Company</span><br/>
<?php 
print "<input type=\"radio\" name=\"cvctype\" tabindex=\"170\" value=\"i\" ";
if (!($r_cv['cvtype'] & CVTYPE_COMPANY))
	print "checked ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "disabled ";
print ">";
?>
	<span class="formsmltext">Individual</span>
	</td>
	<td class="contentcell_lt" colspan="8" rowspan="5"></td>
	</tr>
	
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="4"><span class="smlgrytext">State</span></td>
	<td class="contentcell_lt" colspan="4"><span class="smlgrytext">Postcode</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="4">
<?php
print "<input type=\"text\" name=\"cvstate\" tabindex=\"50\" size=\"15\" maxlength=\"30\" value=\"".htmlentities($r_cv['cvstate'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly";
print ">";
?>
	</td>
	<td class="contentcell_lt" colspan="4">
<?php 
print "<input type=\"text\" name=\"cvpcode\" tabindex=\"60\" size=\"15\" maxlength=\"15\" value=\"".htmlentities($r_cv['cvpcode'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly ";
print ">";
?>
	</td>
	</tr>

	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Country</span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
<?php
print "<input type=\"text\" name=\"cvcountry\" tabindex=\"70\" size=\"40\" maxlength=\"60\" value=\"".htmlentities($r_cv['cvcountry'])."\" ";
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
	print "readonly";
print ">";
?>
	</td>
	</tr>
	
<?php 
// update and delete buttons
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) === true)
{
?>
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8">
	<input type="submit" name="btn_update" class="btntext" value="Update" onclick="delClear()" title="Update the details entered in the database">
	<input type="submit" name="btn_delete" class="btnredtext" value="Delete" onclick="delSet()" disabled="true" title="Delete this organisation from the database">
	<input type="button" name="addreditenable" class="btngrntext" value="EN" onclick="javascript:document.cvedit.btn_delete.disabled=false" title="Enable the delete button">
	</td>
	<td class="contentcell_lt" colspan="8"></td>
	<td class="contentcell_lt" colspan="8"></td>
	</tr>
	
	</form>
<?php
}
?>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><span class="formheadtext">Contacts</span></td></tr>

	<tr>
	<td class="contentcell_lt" colspan="24">
	<table class="dataview_noborder">
	<tr>
	<th class="dataview_l" width="20%">Name</th>
	<th class="dataview_l" width="15%">Phone</th>
	<th class="dataview_l" width="15%">Mobile</th>
	<th class="dataview_l" width="20%">Email</th>
	<th class="dataview_l" width="30%">Position</th>
	</tr>
	
<?php
for ($i = 0; $i < $nc; $i++)
{
	$r_contact = $contactlist[$i];
	print "<tr>\n";
	print "<td class=\"dataview_lt\"><a href=\"frm-contact.php?contid=".$r_contact['contid']."\">".htmlentities($r_contact['contname'])."</a></td>\n";
	print "<td class=\"dataview_lt\">".htmlentities($r_contact['conttel'])."</td>\n";
	print "<td class=\"dataview_lt\">".htmlentities($r_contact['contmob'])."</td>\n";
	print "<td class=\"dataview_lt\">".htmlentities($r_contact['contemail'])."</td>\n";
	print "<td class=\"dataview_lt\">".htmlentities($r_contact['contposn'])."</td>\n";
	print "</tr>\n";
}
?>
	</table>
	</td>
	</tr>

<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) === true)
{
?>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><span class="formheadtext">New Contact</span></td></tr>
	
	<form name="newcont" method="post" action="<?php print $formfile."?cvid=".urlencode($cvid) ?>" >
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Contact Name</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	</tr>

	<tr>
	<td class="contentcell_lt" colspan="8">
	<input type="text" name="newcontactname" size="40" maxlength="90" />
	</td>
	<td class="contentcell_lt" colspan="8">
	<input class="btntext" type="submit" name="btn_newcontact" value="Add" title="Add the contact to this organisation" />
	</td>
	<td class="contentcell_lt" colspan="8"></td>
	</tr>
	</form>
<?php
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
