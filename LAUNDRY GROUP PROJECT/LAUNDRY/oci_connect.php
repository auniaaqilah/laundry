<?php
if (function_exists('oci_connect')) {
    echo "✅ OCI8 is installed!";
} else {
    echo "❌ OCI8 is NOT installed.";
}
?>
