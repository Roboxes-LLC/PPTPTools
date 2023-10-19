<?php
$VERSION = "1.3D";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>