<?php

namespace Modules\Chat\Services\Helpcenters;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;
use Spatie\Permission\Models\Role;

class HelpcenterCategoryService
{
    /**
     * Paginated list of top-level categories with section and article counts.
     */
    public function listCategories(?string $search): LengthAwarePaginator
    {
        return HelpcenterCategory::categories()
            ->with(['sections' => fn ($q) => $q->withCount('articles')->ordered()])
            ->withCount(['sections', 'articles'])
            ->when($search, fn ($q, $s) => $q->search($s))
            ->ordered()
            ->paginate(20);
    }

    /**
     * Roles for the visibility/management dropdowns.
     *
     * @return \Illuminate\Support\Collection<string, string>
     */
    public function roleOptions(): \Illuminate\Support\Collection
    {
        return Role::orderBy('name')->pluck('name', 'name');
    }

    /**
     * All top-level categories for section parent dropdowns.
     *
     * @return Collection<int, HelpcenterCategory>
     */
    public function allCategories(): Collection
    {
        return HelpcenterCategory::categories()->orderBy('name')->get();
    }

    /**
     * Create a new top-level category.
     */
    public function createCategory(array $data): HelpcenterCategory
    {
        $data['position'] = HelpcenterCategory::whereNull('parent_id')->max('position') + 1;
        $data['is_section'] = false;

        return HelpcenterCategory::create($data);
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(HelpcenterCategory $category, array $data): void
    {
        $category->update($data);
    }

    /**
     * Find a category with its sections (for show view).
     */
    public function findCategoryWithSections(int $id): HelpcenterCategory
    {
        return HelpcenterCategory::with(['sections' => fn ($q) => $q->withCount('articles')->orderBy('position')])
            ->withCount(['sections', 'articles'])
            ->findOrFail($id);
    }

    /**
     * Delete a category if it has no sections or articles.
     *
     * Returns null on success or an error message string.
     */
    public function deleteCategory(HelpcenterCategory $category): ?string
    {
        if ($category->sections()->count() > 0) {
            return 'No se puede eliminar una categoría que contiene secciones';
        }

        if ($category->articles()->count() > 0) {
            return 'No se puede eliminar una categoría que contiene artículos';
        }

        $category->delete();

        return null;
    }

    /**
     * Create a new section under a parent category.
     */
    public function createSection(array $data): HelpcenterCategory
    {
        $data['position'] = HelpcenterCategory::where('parent_id', $data['parent_id'])->max('position') + 1;
        $data['is_section'] = true;

        return HelpcenterCategory::create($data);
    }

    /**
     * Update an existing section.
     */
    public function updateSection(HelpcenterCategory $section, array $data): void
    {
        $section->update($data);
    }

    /**
     * Find a section with its parent and articles (for show view).
     */
    public function findSectionWithArticles(int $id): HelpcenterCategory
    {
        return HelpcenterCategory::with(['parent', 'articles.author'])
            ->withCount('articles')
            ->findOrFail($id);
    }

    /**
     * Delete a section if it has no articles.
     *
     * Returns null on success or an error message string.
     */
    public function deleteSection(HelpcenterCategory $section): ?string
    {
        if ($section->articles()->count() > 0) {
            return 'No se puede eliminar una sección que contiene artículos';
        }

        $section->delete();

        return null;
    }
}
