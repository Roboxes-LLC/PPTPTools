<?php
$VERSION = "1.45";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>