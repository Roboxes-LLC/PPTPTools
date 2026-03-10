<?php
$VERSION = "1.80";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>