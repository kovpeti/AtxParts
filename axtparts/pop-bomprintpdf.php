<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bomprintpdf.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $assyid: ID of assembly to edit BOM
// $variantid: ID of the assembly variant

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
require_once("../core/fpdf/fpdf.pdf");
$formfile = "pop-bomprintpdf.php";
$formname = "popbomprintpdf";
$formtitle= "PDF of Assembly BOM";

$popx = 1200;
$popy = 900;

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

if ($assyid === false)
{
	$myparts->AlertMeTo("An assembly must be specified.");
	$myparts->PopMeClose();
	die();
}

$variantid = false;
if (isset($_GET['variantid']))
	$variantid = trim(urldecode(($_GET["variantid"])));
if (!is_numeric($variantid))
	$variantid = false;
		
if ($variantid === false)
{
	$myparts->AlertMeTo("A variant must be specified.");
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

if ($assyid !== false)
{
	// Get the details for the assembly
	$q_a = "select * "
		. "\n from assemblies "
		. "\n left join parts on parts.partid=assemblies.partid "
		. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
		;
	$s_a = $dbh->query($q_a);
	if (!$s_a)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_a = $s_a->fetch_assoc();
		$assy["partnumber"] = $r_a["partnumber"];
		$assy["assyname"] = $r_a["partdescr"];
		$assy["assydescr"] = $r_a["assydescr"];
		$assy["assyrev"] = str_pad($r_a["assyrev"], 2, "0", STR_PAD_LEFT);
		$assy["assyaw"] = $r_a["assyaw"];
		$s_a->free();
	}
	
	// Get the details for the variant
	$q_v = "select variantid, "
		. "\n variantname, "
		. "\n variantdescr, "
		. "\n variantstate "
		. "\n from variant "
		. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
		;
	$s_v = $dbh->query($q_v);
	if (!$s_v)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_v = $s_v->fetch_assoc();
		$variant["variantname"] = $r_v["variantname"];
		$variant["variantdescr"] = $r_v["variantdescr"];
		$variant["variantstate"] = $r_v["variantstate"];
		$s_v->free();
	}
	
	// Get the BOM items for the assembly/variant
	$q_b = "select * "
		. "\n from boms "
		. "\n left join parts on parts.partid=boms.partid "
		. "\n left join assemblies on assemblies.assyid=boms.assyid "
		. "\n left join bomvariants on bomvariants.bomid=boms.bomid "
		. "\n left join variant on variant.variantid=bomvariants.variantid "
		. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
		. "\n left join footprint on footprint.fprintid=parts.footprint "
		. "\n where boms.assyid='".$dbh->real_escape_string($assyid)."' "
		. "\n and bomvariants.variantid='".$dbh->real_escape_string($variantid)."' "
		. "\n order by catdescr, partdescr "
		;
		
	$s_b = $dbh->query($q_b);
	if (!$s_b)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$i = 0;
		while ($r_b = $s_b->fetch_assoc())
		{
			$dset[$i]["partnumber"] = $r_b["partnumber"];
			$dset[$i]["catdescr"] = $r_b["catdescr"];
			$dset[$i]["partdescr"] = $r_b["partdescr"];
			$dset[$i]["footprint"] = $r_b["fprintdescr"];
			$dset[$i]["qty"] = $r_b["qty"];
			$dset[$i]["um"] = $r_b["um"];
			$dset[$i]["ref"] = $r_b["ref"];
			$dset[$i]["altid"] = $r_b["alt"];
			$dset[$i]["bomid"] = $r_b["bomid"];
			
			// If alt is > 0 then find the part detail for it.
			$altid = $r_b["alt"];
			$dset[$i]["altpartnumber"] = "";
			$dset[$i]["altcatdescr"] = "";
			$dset[$i]["altpartdescr"] = "";
			$dset[$i]["altfprint"] = "";
			if (($altid != null) && ($altid > 0))
			{
				$q_alt = "select * "
					. "\n from parts "
					. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
					. "\n left join footprint on footprint.fprintid=parts.footprint "
					. "\n where partid='".$dbh->real_escape_string($altid)."' "
					;
				$s_alt = $dbh->query($q_alt);
				if ($s_alt)
				{
					$r_alt = $s_alt->fetch_assoc();
					$dset[$i]["altpartnumber"] = $r_alt["partnumber"];
					$dset[$i]["altcatdescr"] = $r_alt["catdescr"];
					$dset[$i]["altpartdescr"] = $r_alt["partdescr"];
					$dset[$i]["altfprint"] = $r_alt["fprintdescr"];
					$s_alt->free();
				}
			}
			$i++;
		}
		$s_b->free();
	}
	
	// PDF generation stuff
	class PDF extends FPDF
	{
		// Page header
		function 
		Header()
		{
		    // Logo, upperleftX, upperleftY, width
		    $this->Image('../images/bomlogo_pdf.png', 10, 8, 33);
		    // Arial bold 14
		    $this->SetFont('Arial', 'B', 14);
		    // Move to the right 8cm
		    $this->Cell(80);
		    // Title cell: width, height, text, border, next position, align, (fill, link)
		    $this->Cell(0, 10, $assy["assyname"]." BOM", 0, 0, 'C');
		    // Line break
		    $this->Ln(10);
		}
		
		// Page footer
		function 
		Footer()
		{
		    // Position at 1.5 cm from bottom
		    $this->SetY(-15);
		    // Arial italic 8
		    $this->SetFont('Arial', 'I', 8);
		    // Company name LHS
		    $this->Cell(100, 10, ENG_RPT_CNAME, 'T', 0, 'L');
		    // Page number RHS
		    $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 'T', 0, 'R');
		}
	}
	
	// Landscape A4 page in mm
	$mypdf = new PDF('L', 'mm', 'A4');
	$mypdf->AliasNbPages();

	// Output the BOM front page detail
	$mypdf->AddPage();
	
	// Description, variant, Rev, AW, Status etc
	
	
	// Output BOM table title row
	
	
	// Output BOM table cells
	
	
	
	
	
	
}



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>Parts System</title>
<link rel="shortcut icon" type="image/x-icon" href="../images/icon-axtparts.ico">
<link rel=stylesheet type="text/css" href="../core/css/axtparts.css">
<script language="javascript" src="../core/js-forms.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$popx.",".$popy.")</script>\n"; ?>
</head>
<body>
<div class="print_title">Bill Of Materials</div><p>

<table class="print_table" width="100%">
<tr height="10">
<td width="60%" valign="top" class="print_labelsmall">Assembly</td>
<td width="20%" valign="top" class="print_labelsmall">Revision</td>
<td width="20%" valign="top" class="print_labelsmall">Artwork</td>
</tr>
<tr height="30">
<td valign="top" class="print_heading"><?php print $assy["assyname"]." ".$assy["assydescr"] ?></td>
<td valign="top" class="print_heading"><?php print $assy["assyrev"] ?></td>
<td valign="top" class="print_heading"><?php print $assy["assyaw"] ?></td>
</tr>
<tr height="10">
<td valign="top" class="print_labelsmall">Variant</td>
<td colspan="2" valign="top" class="print_labelsmall">Status</td>
</tr>
<tr height="30">
<td valign="top" class="print_heading"><?php print $variant["variantname"]." ".$variant["variantdescr"] ?></td>
<td valign="top" class="print_heading"><?php print $variant["variantstate"] ?></td>
</tr>
</table><p>

<table class="print_table" width="100%">
<tr>
<td width="11%" class="print_tablehead">Part Number</td>
<td width="42%" class="print_tablehead">Part</td>
<td width="5%" class="print_tablehead">Qty</td>
<td width="5%" class="print_tablehead">UM</td>
<td width="20%" class="print_tablehead">Ref</td>
<td width="17%" class="print_tablehead">Alt Part</td>
</tr>
<?php
$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	print "<tr>\n";
	print "<td class=\"print_tablecell\" valign=\"top\">".htmlentities($dset[$i]["partnumber"])."</td>\n";
	print "<td class=\"print_tablecell\" valign=\"top\">".htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])." ".htmlentities($dset[$i]["footprint"])."</td>\n";
	print "<td class=\"print_tablecellnumber\" valign=\"top\">".htmlentities($dset[$i]["qty"])."</td>\n";
	print "<td class=\"print_tablecell\" valign=\"top\">".htmlentities($dset[$i]["um"])."</td>\n";
	print "<td class=\"print_tablecell\" valign=\"top\">".htmlentities($dset[$i]["ref"])."</td>\n";
	print "<td class=\"print_tablecell\" valign=\"top\">".htmlentities($dset[$i]["altcatdescr"])." ".htmlentities($dset[$i]["altpartdescr"])." ".htmlentities($dset[$i]["altfprint"])."</td>\n";
	print "</tr>\n";
}
?>
</table>
	

</body></html>