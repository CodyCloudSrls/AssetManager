<?php

namespace Tests\Unit;

use Tests\TestCase;

class SnipeTranslatorTest extends TestCase
{
    // the 'meatiest' of these tests will explicitly choose non-English as the language, because otherwise
    // the fallback-logic (which is to fall-back to 'en-US') will be conflated in with the translation logic.
    // The product ships Italian + English only, so Italian (it-IT) is the sample non-English locale here.

    // WARNING: If these translation strings are updated, these tests will start to fail. Update them as appropriate.

    public function test_basic()
    {
        $this->assertEquals('This user has admin privileges', trans('general.admin_tooltip', [], 'en-US'));
    }

    public function test_italian()
    {
        $this->assertEquals('Accessorio', trans('general.accessory', [], 'it-IT'));
    }

    public function test_fallback()
    {
        $this->assertEquals(
            'This user has admin privileges',
            trans('general.admin_tooltip', [], 'xx-ZZ'),
            'Nonexistent locale should fall-back to en-US'
        );
    }

    public function test_backup_string()
    {
        $this->assertEquals(
            'Non sono stati ancora effettuati backup',
            trans('backup::notifications.no_backups_info', [], 'it-IT'),
            "Italian 'no backups info' message should be here"
        );
    }

    public function test_backup_fallback()
    {
        $this->assertEquals(
            'No backups were made yet',
            trans('backup::notifications.no_backups_info', [], 'xx-ZZ'),
            "'no backups info' string should fallback to 'en'"
        );

    }

    public function test_trans_choice_singular()
    {
        $this->assertEquals(
            '1 Consumabile',
            trans_choice('general.countable.consumables', 1, [], 'it-IT')
        );
    }

    public function test_trans_choice_plural()
    {
        $this->assertEquals(
            '2 Consumabili',
            trans_choice('general.countable.consumables', 2, [], 'it-IT')
        );
    }

    public function test_totally_bogus_key()
    {
        $this->assertEquals(
            'bogus_key',
            trans('bogus_key', [], 'it-IT'),
            'Translating a completely bogus key should at least just return back that key'
        );
    }

    public function test_replacements()
    {
        $this->assertEquals(
            'Bene assegnato a Some Name Here',
            trans('admin/users/general.assets_user', ['name' => 'Some Name Here'], 'it-IT'),
            'Text should get replaced in translations when given'
        );
    }

    public function test_legacy_backup_locale_mapping()
    {
        $this->assertEquals(
            'Messaggio dell\'eccezione: MESSAGE',
            trans('backup::notifications.exception_message', ['message' => 'MESSAGE'], 'it-IT')
        );
    }
}
