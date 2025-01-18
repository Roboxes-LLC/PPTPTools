<?php
$VERSION = "1.6B";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>