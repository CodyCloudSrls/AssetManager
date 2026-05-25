<?php

namespace App\Http\Controllers\Api;

use App\Actions\Permissions\NormalizePermissionsPayloadAction;
use App\Actions\Permissions\PreserveUnauthorizedPrivilegedPermissionsAction;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Transformers\GroupsTransformer;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v4.0]
     */
    public function index(FilterRequest $request): JsonResponse|array
    {
        $this->authorize('view', Group::class);

        $groups = Group::select(['id', 'name', 'system_key', 'permissions', 'notes', 'created_at', 'updated_at', 'created_by'])->with('adminuser')->withCount('users as users_count');

        // This invokes the Searchable model trait scopeTextSearch and will handle input by search or by advanced search filter
        if ($request->filled('filter') || $request->filled('search')) {
            $groups->TextSearch($request->input('filter') ? $request->input('filter') : $request->input('search'));
        }

        if ($request->filled('name')) {
            $groups->where('name', '=', $request->input('name'));
        }

        $limit = app('api_limit_value');
        $offset = Helper::clampPaginationOffset($request->input('offset'), $groups->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';

        switch ($request->input('sort')) {
            case 'created_by':
                $groups = $groups->OrderByCreatedBy($order);
                break;
            default:
                // This array is what determines which fields should be allowed to be sorted on ON the table itself.
                // These must match a column on the consumables table directly.
                $allowed_columns = [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                    'users_count',
                ];

                $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';
                $groups = $groups->orderBy($sort, $order);
                break;
        }

        $total = $groups->count();
        $groups = $groups->skip($offset)->take($limit)->get();

        return (new GroupsTransformer)->transformGroups($groups, $total);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v4.0]
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Group::class);
        $group = new Group;
        $defaultPermissions = Helper::selectedPermissionsArray(config('permissions'), config('permissions'));

        $requestedPermissions = $request->has('permissions')
            ? NormalizePermissionsPayloadAction::run($request->input('permissions'))
            : $defaultPermissions;
        $requestedPermissions = PreserveUnauthorizedPrivilegedPermissionsAction::run(
            requestedPermissions: $requestedPermissions,
            authenticatedUser: auth()->user()
        );

        $group->fill($request->only(['name', 'notes']));
        $group->created_by = auth()->id();
        $group->permissions = json_encode(
            Helper::selectedPermissionsArray(config('permissions'), $requestedPermissions)
        );

        if ($group->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new GroupsTransformer)->transformGroup($group), trans('admin/groups/message.success.create')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $group->getErrors()));
    }

    /**
     * Display the specified resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v4.0]
     *
     * @param  int  $id
     */
    public function show($id): array
    {
        $group = Group::findOrFail($id);
        $this->authorize('view', $group);

        return (new GroupsTransformer)->transformGroup($group);
    }

    /**
     * Update the specified resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v4.0]
     *
     * @param  int  $id
     */
    public function update(Request $request, $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $this->authorize('update', $group);

        // Fill only the keys present in the request, so PATCH skips absent fields naturally.
        $group->fill($request->only(['name', 'notes']));

        // Preserve existing permissions when omitted from PATCH/PUT payload.
        if ($request->has('permissions')) {
            $originalPermissions = NormalizePermissionsPayloadAction::run($group->permissions);
            $group->permissions = json_encode(
                Helper::selectedPermissionsArray(
                    config('permissions'),
                    PreserveUnauthorizedPrivilegedPermissionsAction::run(
                        requestedPermissions: NormalizePermissionsPayloadAction::run($request->input('permissions')),
                        authenticatedUser: auth()->user(),
                        originalPermissions: $originalPermissions
                    )
                )
            );
        }

        if ($group->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new GroupsTransformer)->transformGroup($group), trans('admin/groups/message.success.update')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $group->getErrors()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v4.0]
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResponse
    {
        $this->authorize('delete', Group::class);
        $group = Group::findOrFail($id);
        if ($group->isSystemGroup()) {
            return response()
                ->json(Helper::formatStandardApiResponse('error', null, trans('admin/groups/message.delete.delete')));
        }

        if (! $group->isDeletable()) {
            return response()
                ->json(Helper::formatStandardApiResponse('error', null, trans('admin/groups/message.assoc_users')));
        }
        $group->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/groups/message.success.delete')));
    }
}
