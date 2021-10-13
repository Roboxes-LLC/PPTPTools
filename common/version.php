<?php
$VERSION = "1.0F";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>