<?php
$VERSION = "1.1C";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>