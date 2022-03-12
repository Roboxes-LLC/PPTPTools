<?php
$VERSION = "1.19";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>