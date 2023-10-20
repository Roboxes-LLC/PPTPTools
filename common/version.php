<?php
$VERSION = "1.3E";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>