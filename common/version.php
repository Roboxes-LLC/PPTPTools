<?php
$VERSION = "1.72";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>