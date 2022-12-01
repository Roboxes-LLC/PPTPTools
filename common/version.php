<?php
$VERSION = "1.20";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>