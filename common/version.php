<?php
$VERSION = "1.90";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>