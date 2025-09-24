<?php
$VERSION = "1.71";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>