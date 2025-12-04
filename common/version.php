<?php
$VERSION = "1.77";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>