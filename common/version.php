<?php
$VERSION = "1.15";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>