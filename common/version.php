<?php
$VERSION = "1.0C";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>