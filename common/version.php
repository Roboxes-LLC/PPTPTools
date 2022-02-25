<?php
$VERSION = "1.18";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>