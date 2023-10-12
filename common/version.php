<?php
$VERSION = "1.3A";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>