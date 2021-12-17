<?php
$VERSION = "1.12";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>