<?php

namespace App\Http\Controllers;

use App\Models\HisanaContestEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HisanaContestController extends Controller
{
    public function index()
    {
        return view('landing.hisana-contest', [
            'resultsDate' => '10 أكتوبر 2026',
            'prize' => '1000 جنيه مصري',
            'winnersCount' => 3,
            'duration' => 'شهر واحد',
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'answer' => trim((string) $request->input('answer', '')),
            'name' => ($name = trim((string) $request->input('name', ''))) !== '' ? $name : null,
        ]);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('hisana_contest_entries', 'email'),
            ],
            'answer' => ['required', 'string', 'min:10', 'max:2000'],
            'name' => ['nullable', 'string', 'max:120'],
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد مسجّل مسبقًا في المسابقة.',
            'answer.required' => 'يرجى كتابة إجابة دعاء سيد الاستغفار.',
            'answer.min' => 'الإجابة قصيرة جدًا. اكتب الدعاء كاملًا إن أمكن.',
        ]);

        HisanaContestEntry::create([
            'email' => $validated['email'],
            'answer' => $validated['answer'],
            'name' => $validated['name'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $successMessage = 'سيتم إرسال رابط التطبيق إلى بريدك الإلكتروني خلال الأيام القادمة. من شروط المسابقة تحميل التطبيق وإبقاؤه شهرًا كاملًا.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
            ]);
        }

        return redirect()
            ->route('landing.hisana-contest')
            ->with('success', $successMessage);
    }
}
