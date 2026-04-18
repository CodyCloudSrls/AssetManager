<?php

namespace App\Models;

use App\Http\Traits\UniqueUndeletedTrait;
use App\Models\Traits\TenantTemplateTrait;
use App\Models\Traits\Searchable;
use App\Presenters\Presentable;
use App\Presenters\StatusLabelPresenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Watson\Validating\ValidatingTrait;

class Statuslabel extends SnipeModel
{
    use HasFactory;
    use Presentable;
    use SoftDeletes;
    use TenantTemplateTrait;
    use UniqueUndeletedTrait;
    use ValidatingTrait;

    protected $injectUniqueIdentifier = true;

    protected $table = 'status_labels';

    protected $hidden = ['user_id', 'deleted_at'];

    protected $presenter = StatusLabelPresenter::class;

    protected $fillable = [
        'archived',
        'company_id',
        'visibility_type',
        'deployable',
        'name',
        'notes',
        'pending',
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
    protected $searchableAttributes = ['name', 'notes', 'visibility_type'];

    /**
     * The relations and their attributes that should be included when searching the model.
     *
     * @var array
     */
    protected $searchableRelations = [
        'company' => ['name'],
    ];

    public function getRules()
    {
        return [
            'name' => [
                'required',
                'max:255',
                'string',
                Rule::unique('status_labels', 'name')
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
            'notes' => 'string|nullable',
            'deployable' => 'required',
            'pending' => 'required',
            'archived' => 'required',
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility_type' => 'required|string|in:private,descendants,global',
        ];
    }

    public function isDeletable()
    {
        return Gate::allows('delete', $this)
            && (($this->assets_count ?? $this->assets()->count()) === 0)
            && ($this->deleted_at == '');
    }

    /**
     * Establishes the status label -> assets relationship
     *
     * @author A. Gianotto <snipe@snipe.net>
     *
     * @since  [v1.0]
     *
     * @return Relation
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'status_id');
    }

    public function adminuser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * Gets the status label type
     *
     * @author A. Gianotto <snipe@snipe.net>
     *
     * @since  [v1.0]
     *
     * @return string
     */
    public function getStatuslabelType()
    {
        if (($this->pending == '1') && ($this->archived == '0') && ($this->deployable == '0')) {
            return 'pending';
        } elseif (($this->pending == '0') && ($this->archived == '1') && ($this->deployable == '0')) {
            return 'archived';
        } elseif (($this->pending == '0') && ($this->archived == '0') && ($this->deployable == '0')) {
            return 'undeployable';
        }

        return 'deployable';
    }

    /**
     * Query builder scope to for pending status types
     *
     * @return Builder Modified query builder
     */
    public function scopePending()
    {
        return $this->where('pending', '=', 1)
            ->where('archived', '=', 0)
            ->where('deployable', '=', 0);
    }

    /**
     * Query builder scope for archived status types
     *
     * @return Builder Modified query builder
     */
    public function scopeArchived()
    {
        return $this->where('pending', '=', 0)
            ->where('archived', '=', 1)
            ->where('deployable', '=', 0);
    }

    /**
     * Query builder scope for deployable status types
     *
     * @return Builder Modified query builder
     */
    public function scopeDeployable()
    {
        return $this->where('pending', '=', 0)
            ->where('archived', '=', 0)
            ->where('deployable', '=', 1);
    }

    /**
     * Query builder scope for undeployable status types
     *
     * @return Builder Modified query builder
     */
    public function scopeUndeployable()
    {
        return $this->where('pending', '=', 0)
            ->where('archived', '=', 0)
            ->where('deployable', '=', 0);
    }

    /**
     * Helper function to determine type attributes
     *
     * @author A. Gianotto <snipe@snipe.net>
     *
     * @since  [v1.0]
     *
     * @return string
     */
    public static function getStatuslabelTypesForDB($type)
    {
        $statustype['pending'] = 0;
        $statustype['deployable'] = 0;
        $statustype['archived'] = 0;

        if ($type == 'pending') {
            $statustype['pending'] = 1;
            $statustype['deployable'] = 0;
            $statustype['archived'] = 0;
        } elseif ($type == 'deployable') {
            $statustype['pending'] = 0;
            $statustype['deployable'] = 1;
            $statustype['archived'] = 0;
        } elseif ($type == 'archived') {
            $statustype['pending'] = 0;
            $statustype['deployable'] = 0;
            $statustype['archived'] = 1;
        }

        return $statustype;
    }

    public function scopeOrderByCreatedBy($query, $order)
    {
        return $query->leftJoin('users as admin_sort', 'status_labels.created_by', '=', 'admin_sort.id')->select('status_labels.*')->orderBy('admin_sort.first_name', $order)->orderBy('admin_sort.last_name', $order);
    }
}
