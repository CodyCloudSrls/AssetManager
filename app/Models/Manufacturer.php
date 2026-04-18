<?php

namespace App\Models;

use App\Models\Traits\Searchable;
use App\Models\Traits\TenantTemplateTrait;
use App\Presenters\ManufacturerPresenter;
use App\Presenters\Presentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Watson\Validating\ValidatingTrait;

class Manufacturer extends SnipeModel
{
    use HasFactory;
    use TenantTemplateTrait;

    protected $presenter = ManufacturerPresenter::class;

    use Presentable;
    use SoftDeletes;

    protected $table = 'manufacturers';

    protected $hidden = ['user_id'];

    /**
     * Whether the model should inject it's identifier to the unique
     * validation rules before attempting validation. If this property
     * is not set in the model it will default to true.
     *
     * @var bool
     */
    protected $injectUniqueIdentifier = true;

    use ValidatingTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'image',
        'support_email',
        'support_phone',
        'support_url',
        'url',
        'warranty_lookup_url',
        'company_id',
        'visibility_type',
        'tag_color',
        'notes',
    ];

    protected $casts = [
        'company_id' => 'integer',
    ];

    use Searchable;

    /**
     * The attributes that should be included when searching the model.
     *
     * @var array
     */
    protected $searchableAttributes = [
        'name',
        'created_at',
        'notes',
        'visibility_type',
    ];

    /**
     * The relations and their attributes that should be included when searching the model.
     *
     * @var array
     */
    protected $searchableRelations = [
        'company' => ['name'],
        'adminuser' => ['first_name', 'last_name', 'display_name'],
    ];

    public function getRules()
    {
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique('manufacturers', 'name')
                    ->ignore($this->getKey())
                    ->where(function ($query) {
                        $query->whereNull('deleted_at');

                        if (is_null($this->company_id)) {
                            $query->whereNull('company_id');
                        } else {
                            $query->where('company_id', $this->company_id);
                        }
                    }),
            ],
            'url' => 'nullable|starts_with:http://,https://,afp://,facetime://,file://,irc://',
            'support_email' => 'email|nullable',
            'support_url' => 'nullable|starts_with:http://,https://,afp://,facetime://,file://,irc://',
            'warranty_lookup_url' => 'nullable|starts_with:http://,https://,afp://,facetime://,file://,irc://',
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility_type' => 'required|string|in:private,descendants,global',
        ];
    }

    public function isDeletable()
    {
        return Gate::allows('delete', $this)
            && (($this->assets_count ?? $this->assets()->count()) === 0)
            && (($this->licenses_count ?? $this->licenses()->count()) === 0)
            && (($this->consumables_count ?? $this->consumables()->count()) === 0)
            && (($this->accessories_count ?? $this->accessories()->count()) === 0)
            && (($this->components_count ?? $this->components()->count()) === 0)
            && ($this->deleted_at == '');
    }

    public function assets()
    {
        return $this->hasManyThrough(Asset::class, AssetModel::class, 'manufacturer_id', 'model_id');
    }

    public function models()
    {
        return $this->hasMany(AssetModel::class, 'manufacturer_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'manufacturer_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'manufacturer_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'manufacturer_id');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'manufacturer_id');
    }

    /**
     * Query builder scope to order on the user that created it
     */
    public function scopeOrderByCreatedBy($query, $order)
    {
        return $query->leftJoin('users as admin_sort', 'manufacturers.created_by', '=', 'admin_sort.id')->select('manufacturers.*')->orderBy('admin_sort.first_name', $order)->orderBy('admin_sort.last_name', $order);
    }
}
