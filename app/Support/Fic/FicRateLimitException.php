<?php

namespace App\Support\Fic;

/**
 * Thrown when the FiC connector is deliberately backing off to stay within the API's
 * hourly (1000) / monthly (40000) call quotas. It is an EXPECTED, self-healing condition
 * (not a fault), so schedulers treat it as "skip this run", not as an error to alert on.
 */
class FicRateLimitException extends \RuntimeException
{
}
