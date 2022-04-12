<?php
$VERSION = "1.1A";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>