<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Services\ContactNotificationService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactNotificationService $contactNotificationService
    ) {
    }

    public function index()
    {
        return view('contact');
    }

    /**
     * 通知処理を Service に逃がし、コントローラは送信結果だけを扱う。
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        try {
            $this->contactNotificationService->notify(
                $request->validated('title'),
                $request->validated('message')
            );

            return redirect('contact')->with('success', 'ご意見ありがとうございます。後ほどグループラインで返信します。');
        } catch (Throwable $e) {
            return redirect('contact')->withInput()->withErrors(['message' => 'お問い合わせ送信中にエラーが発生しました。']);
        }
    }
}
