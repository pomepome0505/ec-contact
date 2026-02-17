<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Services\InquiryService;
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

    public function show(Inquiry $inquiry): Response
    {
        return Inertia::render('Inquiry/Show', [
            'inquiry' => $this->inquiryService->getDetail($inquiry),
        ]);
    }
}
