<?php
$VERSION = "1.6E";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>