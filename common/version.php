<?php
$VERSION = "1.40";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>