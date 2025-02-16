<?php
$VERSION = "1.6C";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>