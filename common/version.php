<?php
$VERSION = "1.13";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>