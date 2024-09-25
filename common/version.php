<?php
$VERSION = "1.64";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>