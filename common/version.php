<?php
$VERSION = "1.30";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>