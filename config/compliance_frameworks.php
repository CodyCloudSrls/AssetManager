<?php

if (! class_exists(App\Support\Compliance\ComplianceFrameworkPackCatalog::class)) {
    require_once __DIR__.'/../app/Support/Compliance/ComplianceFrameworkPackCatalog.php';
}

return App\Support\Compliance\ComplianceFrameworkPackCatalog::make();
