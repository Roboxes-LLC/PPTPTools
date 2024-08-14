<?php
$VERSION = "1.62";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>