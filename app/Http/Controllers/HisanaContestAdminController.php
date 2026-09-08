<?php

namespace App\Http\Controllers;

use App\Models\HisanaContestEntry;
use App\Services\ResendMailer;
use Illuminate\Http\Request;

class HisanaContestAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = HisanaContestEntry::query()->latest();

        if ($search = trim((string) $request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(30)->withQueryString();
        $total = HisanaContestEntry::count();
        $emailed = HisanaContestEntry::whereNotNull('email_sent_at')->count();

        return view('hisana-contest.index', compact('entries', 'total', 'emailed', 'search'));
    }

    public function destroy(HisanaContestEntry $entry)
    {
        $entry->delete();

        return back()->with('success', 'تم حذف المشاركة.');
    }

    public function resendEmail(HisanaContestEntry $entry, ResendMailer $mailer)
    {
        $sent = $this->sendConfirmation($mailer, $entry);

        if ($sent) {
            $entry->forceFill(['email_sent_at' => now()])->save();

            return back()->with('success', 'تم إعادة إرسال الإيميل إلى '.$entry->email);
        }

        return back()->with('error', 'فشل إرسال الإيميل. تأكد من تفعيل الدومين على Resend.');
    }

    public function sendConfirmation(ResendMailer $mailer, HisanaContestEntry $entry): bool
    {
        return $mailer->sendView(
            $entry->email,
            'تم تسجيلك في مسابقة الحصانة — رابط التحميل من Google Play',
            'emails.hisana-contest-app',
            [
                'name' => $entry->name,
                'appStoreUrl' => config('services.hisana.app_store_url'),
                'contestUrl' => route('landing.hisana-contest'),
                'resultsDate' => '10 أكتوبر 2026',
                'prize' => '1000 جنيه مصري',
                'winnersCount' => 3,
            ]
        );
    }
}
