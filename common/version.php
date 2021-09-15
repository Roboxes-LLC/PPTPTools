<?php
$VERSION = "1.0E";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>