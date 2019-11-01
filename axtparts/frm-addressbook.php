<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-addressbook.php 201 2016-07-17 05:49:39Z gswan $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "frm-addressbook.php";
$formname = "addressbook";
$formtitle= "Address Book";

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

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if (!$dbh)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

// if a 'show' value is sent with the URL then we need that page, otherwise
// default to 'a'
$showpage = "all";
if (isset($_GET['show']))
	$showpage = trim(urldecode(($_GET["show"])));

// if a 'type' value is sent with the URL then we need to filter
// default to 'all'
$showtype = "all";
if (isset($_GET['type']))
	$showtype = trim(urldecode(($_GET["type"])));

if (isset($_POST["btn_newcv"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if (isset($_POST['newaddrname']))
		{
			$editform = "frm-address.php";
			$name = trim($_POST['newaddrname']);
			if ($name != "")
			{
				$c = $myparts->AddNewCV($dbh, $cvname);
				if (isset($c["rowid"]))
				{
					$cvid = $c["rowid"];
					$dbh->close();
					$myparts->VectorMeTo($editform."?cvid=".$cvid);
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
			if (isset($_POST['cvid']))
			{
				$cvid = trim($_POST['cvid']);
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
				$myparts->AlertMeTo("No address ID specified");
		}
		else
			$myparts->AlertMeTo("Name must be specified");
	}
}

// Get the form data
$q_addr =  "select "
	. "\n cvid, "
	. "\n cvname, "
	. "\n cvaddr1, "
	. "\n cvaddr2, "
	. "\n cvcity, "
	. "\n cvstate, "
	. "\n cvpcode, "
	. "\n cvcountry, "
	. "\n cvtel, "
	. "\n cvfax, "
	. "\n cvtype "
	. "\n from custvend ";

if ($showpage == "other")
	$q_addr .= "\n where cvname not rlike '^[A-Z,a-z].*' ";
else if ($showpage == "all")
	$q_addr .= "\n where (cvname IS NOT NULL AND cvname <>'')";
else
	$q_addr .= "\n where lower(substring(cvname,1,1))='".($dbh->real_escape_string($showpage))."' ";
//if ($showpage == "other")
//	$q_addr .= "\n where cvname not rlike '^[A-Z,a-z].*' ";
//else
//	$q_addr .= "\n where lower(substring(cvname,1,1))='".($dbh->real_escape_string($showpage))."' ";

$q_addr .= "\n order by cvname";
$s_addr = $dbh->query($q_addr);
$dataset_addr = array();
$na = 0;
if ($s_addr)
{
	while ($r_addr = $s_addr->fetch_assoc())
	{
		//Filter by type
		$proceed =0;
		switch ($showtype){
			case "all":
					$proceed=1;
					break;
			case "manufacturer":
					if (intval($r_addr['cvtype'])&CVTYPE_MANUFACTURER) $proceed=1;
					break;
			case "supplier":
					if (intval($r_addr['cvtype'])&CVTYPE_SUPPLIER) $proceed=1;
					break;
			case "client":
					if (intval($r_addr['cvtype'])&CVTYPE_CLIENT) $proceed=1;
					break;
			case "employee":
					if (intval($r_addr['cvtype'])&CVTYPE_EMPLOYEE) $proceed=1;
					break;
		}
		if ($proceed==1){
		
		$dataset_addr[$na]["address"] = $r_addr;
		
		$q_contacts = "select "
				. "\n cvid, "
				. "\n contname "
				. "\n from contacts "
				. "\n where cvid=".$r_addr['cvid']
				. "\n order by contname"
				;
													
		$s_contacts = $dbh->query($q_contacts);
		$dataset_addr[$na]["contacts"] = array();
		$nc = 0;
		if ($s_contacts)
		{
			while ($r_contacts = $s_contacts->fetch_assoc())
			{
				$dataset_addr[$na]["contacts"][$nc] = $r_contacts;
				$nc++;
			}
			$s_contacts->free();
		}
		$na++;
		}
	}
	$s_addr->free();
}

// List of clients
$q_clients = "select "
		. "\n cvname, "
		. "\n cvid "
		. "\n from custvend "
		. "\n order by cvname"
		;

$s_clients = $dbh->query($q_clients);
$dataset_clients = array();
$nx = 0;
if ($s_clients)
{
	while ($r_clients = $s_clients->fetch_assoc())
	{
		$dataset_clients[$nx][0] = $r_clients['cvid'];
		$dataset_clients[$nx][1] = $r_clients['cvname'];
		$nx++;
	}
	$s_clients->free();
}

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
	<table class="pagebtnset">
	<tr class="contentrow_30">
	<th class="dataview_c" width="17%"></th>
	<th class="pagebtn_c pagebtncell_on" width="13%"><a href="frm-addressbook.php?show=all&type=all"><span class="pagebtntext" title="Address">All Address</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-addressbook.php?show=all&type=supplier"><span class="pagebtntext" title="Suppliers">Suppliers</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-addressbook.php?show=all&type=manufacturer"><span class="pagebtntext" title="Manufacturers">Manufacturers</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-addressbook.php?show=all&type=client"><span class="pagebtntext" title="Clients">Clients</span></a></th>
	<th class="pagebtn_c pagebtncell_off" width="13%"><a href="frm-addressbook.php?show=all&type=employee"><span class="pagebtntext" title="Employees">Employees</span></a></th>
	<th class="pagebtn_c" width="18%"></th>
	</tr>
	</table>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>

	<table class="contentpanel">
	<tr class="contentrow_30">
	<td class="contentcell_lt" width="9%"></td>
<?php
// Print the alphabet buttons
for ($c = ord("a"); $c <= ord("z"); $c++)
{
	print "<td class=\"contentcell_cc\" width=\"3%\">";
	if (($showpage != "other") and ($showpage != "all"))
	{
		if ($c == ord($showpage))
		{
			$img = "../images/btn-alphaon-".chr($c).".png";
			print "<img src=\"".$img."\" height=\"20\" width=\"20\" border=\"0\">";
		}
		else 
		{
			$img = "../images/btn-alphaoff-".chr($c).".png";
			print "<a href=\"".$formfile."?show=".chr($c)."&type=".$showtype."\">";
			print "<img src=\"".$img."\" height=\"20\" width=\"20\" border=\"0\">";
			print "</a>";
		}
	}
	else 
	{
		$img = "../images/btn-alphaoff-".chr($c).".png";
		print "<a href=\"".$formfile."?show=".chr($c)."&type=".$showtype."\">";
		print "<img src=\"".$img."\" height=\"20\" width=\"20\" border=\"0\">";
		print "</a>";
	}
	print "</td>\n";
}

print "<td class=\"contentcell_cc\" width=\"3%\">";
if ($showpage == "other")
	print "<img src=\"../images/btn-alphaon-other.png\" height=\"20\" width=\"20\" border=\"0\">";
else
	print "<a href=\"".$formfile."?show=other\"><img src=\"../images/btn-alphaoff-other.png\" height=\"20\" width=\"20\" border=\"0\"></a>";
print "</td>\n";
?>
	<td class="contentcell_lt" width="10%"></td>
	</tr>
	</table>
	</td>
	</tr>

	<tr>
	<td class="contentcell_lt" colspan="24">
	<table class="dataview_noborder">
	<tr>
	<th class="dataview_l" width="23%">Name</th>
	<th class="dataview_l" width="38%">Address</th>
	<th class="dataview_l" width="12%">Phone</th>
	<th class="dataview_l" width="12%">Fax</th>
	<th class="dataview_l" width="15%">Contacts</th>
	</tr>
<?php
for ($i = 0; $i < $na; $i++)
{
	print "<tr>\n";
	print "<td class=\"tablelinktext\" valign=\"top\"><a href=\"frm-address.php?cvid=".urlencode($dataset_addr[$i]["address"]['cvid'])."\">".($dataset_addr[$i]["address"]['cvname'] == "" ? "&nbsp;" : htmlentities($dataset_addr[$i]["address"]['cvname']))."</a></td>\n";
	print "<td class=\"tabletext\" valign=\"top\">";
	print ($dataset_addr[$i]["address"]['cvaddr1'] == "" ? "" : htmlentities($dataset_addr[$i]["address"]['cvaddr1']).", ");
	print ($dataset_addr[$i]["address"]['cvaddr2'] == "" ? "" : htmlentities($dataset_addr[$i]["address"]['cvaddr2']).", ");
	print ($dataset_addr[$i]["address"]['cvcity'] == "" ? "" : htmlentities($dataset_addr[$i]["address"]['cvcity']).", ");
	print ($dataset_addr[$i]["address"]['cvstate'] == "" ? "" : htmlentities($dataset_addr[$i]["address"]['cvstate'])." ");
	print ($dataset_addr[$i]["address"]['cvpcode'] == "" ? "" : htmlentities($dataset_addr[$i]["address"]['cvpcode']).", ");
	print ($dataset_addr[$i]["address"]['cvcountry'] == "" ? "" : htmlentities($dataset_addr[$i]["address"]['cvcountry']).", ");
	print "</td>\n";
	print "<td valign=\"top\" class=\"tabletext\">".htmlentities($dataset_addr[$i]["address"]['cvtel'])."</td>\n";
	print "<td valign=\"top\" class=\"tabletext\">".htmlentities($dataset_addr[$i]["address"]['cvfax'])."</td>\n";
	print "<td valign=\"top\" class=\"tabletext\">";

	$nc = count($dataset_addr[$i]["contacts"]);
	for ($j = 0; $j < $nc; $j++)
	{
		print ($dataset_addr[$i]["contacts"][$j]["contname"] == "" ? "" : htmlentities($dataset_addr[$i]["contacts"][$j]["contname"]));
		print "<br/>\n";
	}
	print "</td>\n";
	print "</tr>\n";
}
?>
	</table>
	</td>
	</tr>

<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) === true)
{
?>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><span class="formheadtext">New Address Entry</span></td></tr>

	<form name="newaddr" method="post" action="<?php print $formfile."?show=".urlencode($showpage) ?>">
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Organisation Name</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	</tr>
	
	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8"><input type="text" name="newaddrname" size="40" maxlength="90" /></td>
	<td class="contentcell_lt" colspan="8"><input class="btntext" type="submit" name="btn_newcv" value="Add" title="Add the organisation specified to the address book" /></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	</tr>
	</form>

<?php
}
if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) === true)
{
?>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><hr/></td></tr>
	<tr class="contentrow_20"><td class="contentcell_lt" colspan="24"><span class="formheadtext">New Contact Entry</span></td></tr>

	<form name="newcont" method="post" action="<?php print $formfile."?show=".urlencode($showpage) ?>">
	<tr class="contentrow_10">
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Select Organisation</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext">Contact Name</span></td>
	<td class="contentcell_lt" colspan="8"><span class="smlgrytext"></span></td>
	</tr>

	<tr class="contentrow_30">
	<td class="contentcell_lt" colspan="8"><select name="cvid" style="width:24em;">
<?php
	for ($i = 0; $i  < $nx; $i++)
		print "<option value=".$dataset_clients[$i][0].">".htmlentities($dataset_clients[$i][1])."</option>\n";
?>
	</select>
	</td>
	<td class="contentcell_lt" colspan="8"><input type="text" name="newcontactname" size="40" maxlength="90" /></td>
	<td class="contentcell_lt" colspan="8"><input class="btntext" type="submit" name="btn_newcontact" value="Add" title="Add the contact name to the organisation specified" /></td>
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
