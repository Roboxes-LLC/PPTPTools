<?php
$VERSION = "1.61";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>