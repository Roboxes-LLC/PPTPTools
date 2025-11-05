<?php
$VERSION = "1.74";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>