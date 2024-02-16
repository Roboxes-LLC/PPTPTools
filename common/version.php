<?php
$VERSION = "1.47";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>