<?php
$VERSION = "1.63";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>