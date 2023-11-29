<?php
$VERSION = "1.43";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>