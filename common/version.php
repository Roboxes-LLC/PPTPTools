<?php
$VERSION = "1.16";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>