<?php

use App\Helpers\Helper;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $supportedLocales = Helper::availableLanguageLocales();
        $fallbackLocale = Helper::normalizeSupportedLocale(config('app.fallback_locale', 'en-US'));

        Setting::whereNotNull('locale')
            ->whereNotIn('locale', $supportedLocales)
            ->update(['locale' => $fallbackLocale]);

        User::whereNotNull('locale')
            ->whereNotIn('locale', $supportedLocales)
            ->update(['locale' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
