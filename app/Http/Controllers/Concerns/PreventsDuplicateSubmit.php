<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Guards create/update actions against duplicate form submissions (double/triple clicks,
 * or a back-forward-cache re-post). Each edit form renders a fresh one-time "_submit_nonce"
 * hidden field (see layouts/edit-form.blade.php); the first request to arrive consumes it and
 * proceeds, any later request carrying the same nonce is recognised as a duplicate.
 *
 * The client-side disable-on-submit (jQuery-validate submitHandler) stops the extra requests
 * in the browser; this server guard is the authority — it also covers scripted clients, retries
 * and a page restored from bfcache, where the client guard cannot help.
 */
trait PreventsDuplicateSubmit
{
    /**
     * Returns true when this exact submission has already been processed. Uses Cache::add,
     * which is atomic (only the first caller within the TTL stores the key and gets true), and
     * self-expiring, so it needs no cleanup job. A missing/oversized nonce never blocks — that
     * keeps API clients and any form without the hidden field working exactly as before.
     */
    protected function isDuplicateSubmit(Request $request): bool
    {
        $nonce = trim((string) $request->input('_submit_nonce'));

        if ($nonce === '' || strlen($nonce) > 100) {
            return false;
        }

        // 60s comfortably outlives a burst of duplicate clicks / an in-flight upload without
        // ever blocking a deliberate later resubmit (every GET render mints a new nonce).
        return ! Cache::add('submit-once:'.$nonce, 1, now()->addSeconds(60));
    }
}
