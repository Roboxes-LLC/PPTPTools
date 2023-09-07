<?php
$VERSION = "1.36";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>