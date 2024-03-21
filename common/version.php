<?php
$VERSION = "1.50";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>