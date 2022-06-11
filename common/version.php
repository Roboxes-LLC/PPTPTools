<?php
$VERSION = "1.1E";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>