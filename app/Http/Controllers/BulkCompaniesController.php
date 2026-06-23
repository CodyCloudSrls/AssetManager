<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkCompaniesController extends Controller
{
    /** Contact fields that are safe to set across many companies at once. */
    private const BULK_FIELDS = ['phone', 'fax', 'email', 'notes'];

    /**
     * Show the group-edit form for the selected companies. The bulk-actions
     * dropdown POSTs here with bulk_actions=edit.
     */
    public function edit(Request $request)
    {
        $this->authorize('update', Company::class);

        if (! $request->filled('ids')) {
            return redirect()->route('companies.index')->with('error', trans('admin/companies/message.no_companies_selected'));
        }

        $companies = Company::whereIn('id', (array) $request->input('ids'))->orderBy('name')->get();

        if ($companies->isEmpty()) {
            return redirect()->route('companies.index')->with('error', trans('admin/companies/message.no_companies_selected'));
        }

        return view('companies.bulk-edit', [
            'companies' => $companies,
            'ids' => $companies->pluck('id')->all(),
        ]);
    }

    /**
     * Apply the group edit. Only the fields whose "apply_*" checkbox is set are
     * written on every selected company; everything else is left untouched.
     */
    public function update(Request $request)
    {
        $this->authorize('update', Company::class);

        $companies = Company::whereIn('id', (array) $request->input('ids'))->get();

        if ($companies->isEmpty()) {
            return redirect()->route('companies.index')->with('error', trans('admin/companies/message.no_companies_selected'));
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|min:3|max:35',
            'fax' => 'nullable|min:7|max:35',
            'email' => 'nullable|email|max:150',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $updates = [];
        foreach (self::BULK_FIELDS as $field) {
            if ($request->boolean('apply_'.$field)) {
                $value = $request->input($field);
                $updates[$field] = ($value === '' ? null : $value);
            }
        }

        if ($updates === []) {
            return redirect()->route('companies.index')->with('warning', trans('admin/hardware/message.update.nothing_updated'));
        }

        DB::transaction(function () use ($companies, $updates) {
            foreach ($companies as $company) {
                $company->fill($updates);
                $company->save();
            }
        });

        return redirect()->route('companies.index')->with('success', trans('admin/companies/message.update.success'));
    }
}
