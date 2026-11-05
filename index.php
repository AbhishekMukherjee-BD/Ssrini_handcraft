<?php
/**
 * Root Canonical Redirect
 * Sends an instant HTTP 301 Moved Permanently response to /ecom/
 */

header("Location: /ecom/", true, 301);
exit;
