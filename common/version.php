<?php
$VERSION = "1.44";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>