<?php
$VERSION = "1.60";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>