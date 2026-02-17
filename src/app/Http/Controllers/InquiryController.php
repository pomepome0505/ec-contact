<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInquiryStatusRequest;
use App\Models\Inquiry;
use App\Services\InquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function __construct(
        private readonly InquiryService $inquiryService,
    ) {}

    public function index(Request $request): Response
    {
        $enumOptions = $this->inquiryService->getEnumOptions();

        return Inertia::render('Inquiry/Index', [
            'inquiries' => $this->inquiryService->getList(),
            ...$enumOptions,
        ]);
    }

    public function show(int $inquiry_id): Response
    {
        $inquiry = Inquiry::findOrFail($inquiry_id);
        $enumOptions = $this->inquiryService->getEnumOptions();

        return Inertia::render('Inquiry/Show', [
            'inquiry' => $this->inquiryService->getDetail($inquiry),
            'staffs' => $this->inquiryService->getStaffList(),
            ...$enumOptions,
        ]);
    }

    public function update(UpdateInquiryStatusRequest $request, int $inquiry_id): RedirectResponse
    {
        $inquiry = Inquiry::findOrFail($inquiry_id);
        $this->inquiryService->updateStatus($inquiry, $request->validated());

        return redirect()->route('inquiries.show', $inquiry->id);
    }
}
