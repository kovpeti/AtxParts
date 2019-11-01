<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: index.php 89 2016-07-12 11:50:11Z gswan $

session_start();
include("../config/config-axtparts.php");
require_once("../core/cl-axtparts.php");
$formfile = "index.php";
$loginid = "";
$myparts = new axtparts();

// Process a login request
if (isset($_POST["btn_login"]))
{
	$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
	
	if (isset($_POST["loginid"]))
		$loginid = trim($_POST["loginid"]);
	else 
		$loginid = false;
	
	if (isset($_POST["passwd"]))
		$passwd = trim($_POST["passwd"]);
	else 
		$passwd = false;
		
	if ($dbh)
	{
		if (($loginid !== false) && ($passwd !== false))
		{
			$t = $myparts->UserLogin($dbh, $loginid, $passwd);
			if ($t["status"] === true)
				$myparts->VectorMeTo(PAGE_LOGIN);
			else 
			{
				$err = $t["error"];
				$myparts->AlertMeTo($err);
			}
		}
		$dbh->close();
	}
}

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

print "<div class=\"loginpanel\">\n";

$topparams = array();
$topparams["logoimgurl"] = $_cfg_logoimgurl;
$myparts->FormRender_TopPanel_Login($topparams);

$bottomparams = array();
$bottomparams["siteheading"] = SYSTEMHEADING;
$bottomparams["branding"] = SYSTEMBRANDING." ".ENGPARTSVERSION;

?>
<section class="contentpanel_login">
	
	<table class="contentpanel_login">
	<form method="post" action="<?php print $formfile ?>" id="loginform" autocomplete="off" >
		<tr class="contentrow_25">
		<td class="contentcell_rt" width="40%"><span class="blacktext">&nbsp;</span></td>
		<td class="contentcell_lt" width="60%"></td>
		</tr>
		
		<tr class="contentrow_25">
		<td class="contentcell_rt"><span class="blacktext">User ID:</span></td>
		<td class="contentcell_lt"><input type="text" name="loginid" value="" size="25" maxlength="25" tabindex="10"></td>
		</tr>
	
		<tr class="contentrow_25">
		<td class="contentcell_rt"><span class="blacktext">Password:</span></td>
		<td class="contentcell_lt"><input type="password" name="passwd" value="" size="25" maxlength="25" tabindex="20" autocomplete="off"></td>
		</tr>

		<tr class="contentrow_25">
		<td class="contentcell_rt"><span class="blacktext">&nbsp;</span></td>
		<td class="contentcell_lt"></td>
		</tr>
	
		<tr class="contentrow_40">
		<td class="contentcell_rt"></td>
		<td class="contentcell_lt"><input type="submit" name="btn_login" class="btntext" value="Login" tabindex="40"></td>
		</tr>
	</form>
	</table>

</section>
<p/>
<?php 
	$myparts->FormRender_BottomPanel_Login($bottomparams);
?>
</div>
</body></html>
