<?php
$VERSION = "1.68";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>