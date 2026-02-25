<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplyInquiryRequest;
use App\Http\Requests\StoreCustomerMessageRequest;
use App\Http\Requests\StoreInquiryByStaffRequest;
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
        $selectOptions = $this->inquiryService->getSelectOptions();

        return Inertia::render('Inquiry/Index', [
            'inquiries' => $this->inquiryService->getList(),
            ...$selectOptions,
        ]);
    }

    public function create(): Response
    {
        $selectOptions = $this->inquiryService->getSelectOptions();

        return Inertia::render('Inquiry/Create', [
            ...$selectOptions,
            'staffs' => $this->inquiryService->getStaffList(),
        ]);
    }

    public function store(StoreInquiryByStaffRequest $request): RedirectResponse
    {
        $inquiry = $this->inquiryService->storeByStaff($request->validated());

        return redirect()->route('inquiries.show', $inquiry->id);
    }

    public function show(int $inquiry_id): Response
    {
        $inquiry = Inquiry::findOrFail($inquiry_id);
        $selectOptions = $this->inquiryService->getSelectOptions();

        return Inertia::render('Inquiry/Show', [
            'inquiry' => $this->inquiryService->getDetail($inquiry),
            'staffs' => $this->inquiryService->getStaffList(),
            ...$selectOptions,
        ]);
    }

    public function update(UpdateInquiryStatusRequest $request, int $inquiry_id): RedirectResponse
    {
        $inquiry = Inquiry::findOrFail($inquiry_id);
        $this->inquiryService->updateStatus($inquiry, $request->validated());

        return redirect()->route('inquiries.show', $inquiry->id);
    }

    public function reply(ReplyInquiryRequest $request, int $inquiry_id): RedirectResponse
    {
        $this->inquiryService->reply($inquiry_id, $request->validated(), (int) $request->user()->id);

        return redirect()->route('inquiries.show', $inquiry_id);
    }

    public function storeCustomerMessage(StoreCustomerMessageRequest $request, int $inquiry_id): RedirectResponse
    {
        $this->inquiryService->storeCustomerMessage($inquiry_id, $request->validated());

        return redirect()->route('inquiries.show', $inquiry_id);
    }
}
