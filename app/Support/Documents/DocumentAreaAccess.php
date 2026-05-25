<?php

namespace App\Support\Documents;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class DocumentAreaAccess
{
    public static function can(User $user, ?string $area, string $ability): bool
    {
        $area = trim((string) $area);

        if ($user->isSuperAdmin() || $user->isSuperUser() || $user->hasAccess('admin')) {
            return true;
        }

        if ($area === '') {
            return self::canAccessUnassignedArea($user, $ability);
        }

        return match ($ability) {
            'view', 'index', 'history', 'journal' => self::canView($user, $area),
            'update', 'edit', 'delete', 'restore', 'forceDelete' => $user->hasAccess("documents.area.{$area}.edit"),
            'files' => $user->hasAccess("documents.area.{$area}.files"),
            'viewFiles' => $user->hasAccess("documents.area.{$area}.files.view")
                || $user->hasAccess("documents.area.{$area}.files"),
            default => self::canView($user, $area),
        };
    }

    public static function canSet(User $user, ?string $area): bool
    {
        $area = trim((string) $area);

        if ($user->isSuperAdmin() || $user->isSuperUser() || $user->hasAccess('admin')) {
            return true;
        }

        if ($area === '') {
            return ! self::hasAreaRestriction($user) || self::canPerformAcrossAllAreas($user, 'update');
        }

        return $user->hasAccess("documents.area.{$area}.edit");
    }

    public static function applyDocumentScope($query, ?User $user = null)
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! self::documentsAreaColumnExists()) {
            return $query;
        }

        if ($user->isSuperAdmin() || $user->isSuperUser() || $user->hasAccess('admin')) {
            return $query;
        }

        $allowedAreas = collect(array_keys(Document::documentAreaOptions()))
            ->filter(fn (string $area) => self::canView($user, $area))
            ->values()
            ->all();

        $hasAreaRestriction = self::hasAreaRestriction($user);
        $canViewUnassigned = ! $hasAreaRestriction || self::canPerformAcrossAllAreas($user, 'view');

        if ($hasAreaRestriction && $allowedAreas === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $areaQuery) use ($allowedAreas, $canViewUnassigned) {
            if ($canViewUnassigned) {
                $areaQuery->whereNull('documents.document_area')
                    ->orWhere('documents.document_area', '');
            }

            if ($allowedAreas !== []) {
                $method = $canViewUnassigned ? 'orWhereIn' : 'whereIn';
                $areaQuery->{$method}('documents.document_area', $allowedAreas);
            }
        });
    }

    private static function canAccessUnassignedArea(User $user, string $ability): bool
    {
        if (! self::hasAreaRestriction($user)) {
            return true;
        }

        return match ($ability) {
            'view', 'index', 'history', 'journal' => self::canPerformAcrossAllAreas($user, 'view'),
            'update', 'edit', 'delete', 'restore', 'forceDelete' => self::canPerformAcrossAllAreas($user, 'update'),
            'files' => self::canPerformAcrossAllAreas($user, 'files'),
            'viewFiles' => self::canPerformAcrossAllAreas($user, 'viewFiles'),
            default => self::canPerformAcrossAllAreas($user, 'view'),
        };
    }

    private static function canView(User $user, string $area): bool
    {
        return $user->hasAccess("documents.area.{$area}.view")
            || $user->hasAccess("documents.area.{$area}.edit")
            || $user->hasAccess("documents.area.{$area}.files.view")
            || $user->hasAccess("documents.area.{$area}.files");
    }

    private static function canPerformAcrossAllAreas(User $user, string $ability): bool
    {
        foreach (array_keys(Document::documentAreaOptions()) as $area) {
            $allowed = match ($ability) {
                'view' => self::canView($user, $area),
                'update' => $user->hasAccess("documents.area.{$area}.edit"),
                'files' => $user->hasAccess("documents.area.{$area}.files"),
                'viewFiles' => $user->hasAccess("documents.area.{$area}.files.view")
                    || $user->hasAccess("documents.area.{$area}.files"),
                default => self::canView($user, $area),
            };

            if (! $allowed) {
                return false;
            }
        }

        return true;
    }

    private static function hasAreaRestriction(User $user): bool
    {
        foreach (array_keys(Document::documentAreaOptions()) as $area) {
            if (
                $user->hasAccess("documents.area.{$area}.view")
                || $user->hasAccess("documents.area.{$area}.edit")
                || $user->hasAccess("documents.area.{$area}.files.view")
                || $user->hasAccess("documents.area.{$area}.files")
            ) {
                return true;
            }
        }

        return false;
    }

    private static function documentsAreaColumnExists(): bool
    {
        try {
            return Schema::hasColumn('documents', 'document_area');
        } catch (\Throwable) {
            return false;
        }
    }
}
