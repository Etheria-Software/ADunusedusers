<?php
////////////////////////////////////////////////
//              LGPL notice                   //
////////////////////////////////////////////////
/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.
    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
////////////////////////////////////////////////
//         used libraries/code modules        //
////////////////////////////////////////////////
/*
adLDAP 4.0.4 -- released under GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1 by  http://adldap.sourceforge.net/
etheria config loader (custom embedded build) -- released under GNU LESSER GENERAL PUBLIC LICENSE
*/
////////////////////////////////////////////////
// 	       notes/requirements                 //
////////////////////////////////////////////////
/*
php7 with ldap support must be installed
*/
////////////////////////////////////////////////
//               Version info                 //
////////////////////////////////////////////////
/*
version 1
*/
////////////////////////////////////////////////
//               Developer Info               //
////////////////////////////////////////////////
/*
Name : James
Alias : Shadow AKA ShadowGauardian507-IRL
Contact : shadow@shadowguardian507-irl.uk
Alternate contact : shadow@etheria-software.uk
Note as an Anti-spam Measure I run graylisting on my mail servers, so new senders email will be held for some time before it
arrives in my mail box,
please ensure that the service you are sending from tolerates graylisting on target address (most normal mail systems are
perfectly happy with this)
This software is provided WITHOUT any SUPPORT or WARRANTY but bug reports and feature requests are welcome.
*/
?>
<?php
/*.
    require_module 'standard';
    require_module 'ldap';
.*/
?>
<?php
$debugenable = false;
error_reporting(E_ERROR | E_WARNING | E_PARSE);
?>

<?php
function htmldebugprint(string $stringtoprint, bool $printenable)
{
  if($printenable)
  {
    print $stringtoprint;
  }
}

function htmldebugprint_r(array $arraytoprint, bool $printenable)
{
  if($printenable)
  {
    print "<pre>";
    print_r($arraytoprint);
    print "</pre>";
  }
}
function mslogintimestamptodatecellformated($mstimestamp)
{
  $mstimestampsec   = (int)($mstimestamp / 10000000); // divide by 10 000 000 to get seconds
  $unixTimestamp = ($mstimestampsec - 11644473600); // 1.1.1600 -> 1.1.1970 difference in seconds

  if ( (isset($mstimestamp)) && ($mstimestamp != "") && ($mstimestamp != 0) ){
      print "<th>". date('Y-m-d h:i:s A', $unixTimestamp ) ." </th>";
    }
  else
    {
      print "<th> never logged in </th>";
    }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>AD never logged in accounts</title>
  <meta charset="UTF-8">
  <meta name="description" content="list of users who have never logged in to AD but have accounts">
<?php
if (!file_exists ( "./config.d/active/ldap.conf.php"))
{
  echo "ldap config file mising please check that ./config.d/active/ldap.conf.php exists exists template can be found in ./config.d/template/ldap.conf.php";
  die;
}
if (!file_exists ( "./config.d/active/theme.conf.php"))
{
  echo "theme config file mising please check that ./config.d/active/theme.conf.php exists template can be found in ./config.d/template/theme.conf.php ";
  die;
}

// config module loader
foreach (glob("./config.d/active/*.conf.php") as $conffilename)
{
    include $conffilename;
}
?>

</head>
<body>
  <h1>AD never logged in accounts</h1>
<?php
require_once(dirname(__FILE__) . '/adLDAP-4.0.4/adLDAP.php');

$bdn = $ldapconf['basedn'];
$acctsif = $ldapconf['accountsuffix'];
$lun = $ldapconf['linkaccountname'];
$lup = $ldapconf['linkaccountpassword'];
$rpg = $ldapconf['realprimarygroup'];
$rg = $ldapconf['recursivegroups'];
$dcs = $ldapconf['dcarray'];

$rand_DC_key = array_rand($dcs,1);
$dctouse = $dcs[$rand_DC_key];

htmldebugprint($dctouse . "-- <br/>",$debugenable);

print'<div id="dccon">';
print 'connected domain controller = ' . $dctouse;
print'</div>';

$dclinkarry = array($dctouse);

$LdapConOptArry = array('base_dn'=>$bdn, 'domain_controllers'=>$dclinkarry, 'account_suffix'=>$acctsif, 'admin_username'=>$lun, 'admin_password'=>$lup, 'real_primarygroup'=>$rpg, 'recursive_groups'=>$rg );

$adldap = new adLDAP($LdapConOptArry);
try {
    $adldap->connect();
}
catch (adLDAPException $e) {
    echo $e; exit();
}

$sr = ldap_search($adldap->getLdapConnection(), $ldapconf['basedn'] , '(&(!(lastlogon=*))(objectClass=user)(!(objectClass=computer)))', array('objectclass', 'distinguishedname', 'samaccountname'));
$userentries = @ldap_get_entries($adldap->getLdapConnection(), $sr);

foreach ($userentries as $auserobject) {
  htmldebugprint($auserobject['samaccountname'][0] . " --- <br/>",$debugenable);

  if( $auserobject['samaccountname'][0] != '' )
    {
      $usernamesarry[] = $auserobject['samaccountname'][0];
    }
}

?>
<table style="width:100%">
  <tr>
    <th style="font-weight: bold;">User Name</th>
    <th style="font-weight: bold;">Display Name</th>
    <th style="font-weight: bold;">When Created (year-month-day time)</th>
    <th style="font-weight: bold;">Last Logon (year-month-day time)<br/> from connected DC</th>
    <th style="font-weight: bold;">Last Logon Timestamp (year-month-day time)<br/> synced every 15 days between DC's</th>
  </tr>

<?php

htmldebugprint_r($usernamesarry,$debugenable);

foreach ($usernamesarry as $username)
  {
    print "<tr>";
      print "<th>". $username ."</th>";
      $userinfo = $adldap->user()->info($username, array("displayname","lastLogonTimestamp","lastLogon","whenCreated"));
      print "<th>". $userinfo[0]["displayname"][0] ."</th>";
      $wcsrc = $userinfo[0]["whencreated"][0];
      $wcconvd = substr($wcsrc,0,4)."-".substr($wcsrc,4,2)."-".substr($wcsrc,6,2)." ".substr($wcsrc,8,2).":".substr($wcsrc,10,2);
      print "<th>". $wcconvd  ."</th>";
      mslogintimestamptodatecellformated($userinfo[0]["lastlogon"][0]);
      mslogintimestamptodatecellformated($userinfo[0]["lastlogontimestamp"][0]);
    print "</tr>";
  }

?>

</table>
</body>
<?php

//close ldap connection
$adldap->close();

?>
