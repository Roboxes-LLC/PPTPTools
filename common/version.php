<?php
$VERSION = "1.32";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>