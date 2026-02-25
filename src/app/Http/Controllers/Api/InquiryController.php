<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInquiryRequest;
use App\Services\InquiryService;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    public function __construct(
        private readonly InquiryService $inquiryService,
    ) {}

    public function store(StoreInquiryRequest $request): JsonResponse
    {
        $inquiry = $this->inquiryService->store($request->validated());

        return response()->json([
            'message' => 'お問い合わせを受け付けました。',
            'inquiry_number' => $inquiry->inquiry_number,
        ], 201);
    }
}
