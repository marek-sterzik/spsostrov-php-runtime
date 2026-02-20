<?php

$plugins = [];
if (AdminerLoginPasswordLess::isConfigured()) {
    $plugins[] = new AdminerLoginPasswordLess();
}

return $plugins;
