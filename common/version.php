<?php
$VERSION = "1.11";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>