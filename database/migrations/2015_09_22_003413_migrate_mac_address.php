<?php

use App\Models\AssetModel;
use App\Models\CustomField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class MigrateMacAddress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $fieldsetId = DB::table('custom_fieldsets')->insertGetId(['name' => 'Asset with MAC Address']);
        if (! $fieldsetId) {
            throw new Exception("couldn't save customfieldset");
        }
        $macid = DB::table('custom_fields')->insertGetId([
            'name' => 'MAC Address',
            'format' => CustomField::PREDEFINED_FORMATS['MAC'],
            'element' => 'text', ]);
        if (! $macid) {
            throw new Exception("Can't save MAC Custom field: $macid");
        }

        DB::table('custom_field_custom_fieldset')->insert([
            'custom_field_id' => $macid,
            'custom_fieldset_id' => $fieldsetId,
            'required' => false,
            'order' => 1,
        ]);
        AssetModel::where(['show_mac_address' => true])->update(['fieldset_id' => $fieldsetId]);

        Schema::table('assets', function (Blueprint $table) {
            $table->renameColumn('mac_address', '_snipeit_mac_address');
        });

        // DB::statement("ALTER TABLE assets CHANGE mac_address _snipeit_mac_address varchar(255)");

        $ans = Schema::table('models', function (Blueprint $table) {
            $table->renameColumn('show_mac_address', 'deprecated_mac_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $f = DB::table('custom_fieldsets')->where(['name' => 'Asset with MAC Address'])->first();

        if ($f) {
            $fieldIds = DB::table('custom_field_custom_fieldset')
                ->where('custom_fieldset_id', $f->id)
                ->pluck('custom_field_id');

            DB::table('custom_field_custom_fieldset')->where('custom_fieldset_id', $f->id)->delete();
            DB::table('custom_fields')->whereIn('id', $fieldIds)->delete();
            DB::table('custom_fieldsets')->where('id', $f->id)->delete();
        }

        Schema::table('models', function (Blueprint $table) {
            $table->renameColumn('deprecated_mac_address', 'show_mac_address');
        });

        if (Schema::hasColumn('assets', '_snipeit_mac_address')) {
            DB::statement('ALTER TABLE assets CHANGE _snipeit_mac_address mac_address varchar(255)');
        }
    }
}
