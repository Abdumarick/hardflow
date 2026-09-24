<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCategoryAction
{
    public function execute(User $actor, Category $category, string $name, ?Category $parent, ?string $description = null): Category
    {
        if (! $actor->hasPermissionInBusiness(PermissionName::ProductsUpdate, $category->business)) {
            throw new AuthorizationException;
        }
        if ($parent && $parent->business_id !== $category->business_id) {
            throw new AuthorizationException('The parent category belongs to another business.');
        }
        if ($parent && ($parent->is($category) || $this->isDescendant($parent, $category))) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent or a child of one of its descendants.']);
        }
        $duplicate = Category::query()->where('business_id', $category->business_id)->where('name', trim($name))->whereKeyNot($category->id)
            ->where(fn ($query) => $parent ? $query->where('parent_id', $parent->id) : $query->whereNull('parent_id'))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This category name already exists at the selected level.']);
        }

        return DB::transaction(function () use ($actor, $category, $name, $parent, $description): Category {
            $old = $category->only(['name', 'parent_id', 'description']);
            $category->update(['name' => trim($name), 'parent_id' => $parent?->id, 'description' => $description]);
            AuditLog::query()->create(['user_id' => $actor->id, 'business_id' => $category->business_id, 'action' => 'category.updated', 'subject_type' => Category::class, 'subject_id' => $category->id, 'old_values' => $old, 'new_values' => $category->only(['name', 'parent_id', 'description'])]);

            return $category;
        });
    }

    private function isDescendant(Category $candidate, Category $category): bool
    {
        $cursor = $candidate;
        while ($cursor->parent_id !== null) {
            if ($cursor->parent_id === $category->id) {
                return true;
            }
            $cursor = Category::query()->where('business_id', $category->business_id)->findOrFail($cursor->parent_id);
        }

        return false;
    }
}
