<?php
$VERSION = "1.6F";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>