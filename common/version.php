<?php
$VERSION = "1.42";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>