<?php

namespace App\Services;

use App\Models\InquiryCategory;
use Illuminate\Database\Eloquent\Collection;

class InquiryCategoryService
{
    /**
     * @return Collection<int, InquiryCategory>
     */
    public function getList(): Collection
    {
        return InquiryCategory::ordered()->get();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function getActiveList(): array
    {
        return InquiryCategory::active()->ordered()
            ->get(['id', 'name'])
            ->map(fn (InquiryCategory $c) => ['value' => $c->id, 'label' => $c->name])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function store(array $validated): InquiryCategory
    {
        return InquiryCategory::create($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(InquiryCategory $category, array $validated): InquiryCategory
    {
        $category->update($validated);

        return $category->refresh();
    }

    public function delete(InquiryCategory $category): void
    {
        if ($category->inquiries()->exists()) {
            throw new \LogicException('関連する問い合わせデータがあるため削除できません。');
        }

        $category->delete();
    }

    public function toggleActive(InquiryCategory $category): InquiryCategory
    {
        $category->update(['is_active' => ! $category->is_active]);

        return $category->refresh();
    }
}
