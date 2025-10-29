<?php
$VERSION = "1.73";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>