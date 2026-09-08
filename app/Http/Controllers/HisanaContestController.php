<?php

namespace App\Http\Controllers;

use App\Models\HisanaContestEntry;
use App\Services\ResendMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function store(Request $request, ResendMailer $mailer, HisanaContestAdminController $admin)
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

        $entry = HisanaContestEntry::create([
            'email' => $validated['email'],
            'answer' => $validated['answer'],
            'name' => $validated['name'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $emailSent = false;
        try {
            $emailSent = $admin->sendConfirmation($mailer, $entry);
            if ($emailSent) {
                $entry->forceFill(['email_sent_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            Log::error('Hisana contest email failed: '.$e->getMessage(), [
                'email' => $entry->email,
            ]);
        }

        $successMessage = $emailSent
            ? 'تم تسجيلك بنجاح، وتم إرسال رسالة التأكيد ورابط التحميل إلى إيميلك. النتائج بإذن الله يوم 10 أكتوبر.'
            : 'تم تسجيلك بنجاح في المسابقة. سنرسل لك رسالة التأكيد ورابط التحميل على إيميلك قريبًا.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'email_sent' => $emailSent,
            ]);
        }

        return redirect()
            ->route('landing.hisana-contest')
            ->with('success', $successMessage);
    }
}
