<?php
$VERSION = "1.1D";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>