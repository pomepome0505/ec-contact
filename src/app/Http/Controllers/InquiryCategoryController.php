<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryCategoryRequest;
use App\Http\Requests\UpdateInquiryCategoryRequest;
use App\Models\InquiryCategory;
use App\Services\InquiryCategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InquiryCategoryController extends Controller
{
    public function __construct(
        private readonly InquiryCategoryService $categoryService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Category/Index', [
            'categories' => $this->categoryService->getList(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Category/Create');
    }

    public function store(StoreInquiryCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->store($request->validated());

        return redirect()->route('categories.index');
    }

    public function edit(int $categoryId): Response
    {
        $category = InquiryCategory::findOrFail($categoryId);

        return Inertia::render('Category/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateInquiryCategoryRequest $request, int $categoryId): RedirectResponse
    {
        $category = InquiryCategory::findOrFail($categoryId);
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('categories.index');
    }

    public function destroy(int $categoryId): RedirectResponse
    {
        $category = InquiryCategory::findOrFail($categoryId);

        try {
            $this->categoryService->delete($category);
        } catch (\LogicException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()->route('categories.index');
    }

    public function toggleActive(int $categoryId): RedirectResponse
    {
        $category = InquiryCategory::findOrFail($categoryId);
        $this->categoryService->toggleActive($category);

        return redirect()->route('categories.index');
    }
}
