<?php
$VERSION = "1.14";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>