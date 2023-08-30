<?php
$VERSION = "1.34";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>