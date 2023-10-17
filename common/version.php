<?php
$VERSION = "1.3C";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>