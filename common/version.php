<?php
$VERSION = "1.33";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>