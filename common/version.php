<?php
$VERSION = "1.79";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>