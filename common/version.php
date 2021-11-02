<?php
$VERSION = "1.10";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>