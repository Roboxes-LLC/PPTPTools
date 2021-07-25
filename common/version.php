<?php
$VERSION = "1.0D";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>