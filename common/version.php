<?php
$VERSION = "1.38";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>