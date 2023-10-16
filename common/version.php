<?php
$VERSION = "1.3B";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>