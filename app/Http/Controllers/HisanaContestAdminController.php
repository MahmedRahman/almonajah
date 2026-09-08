<?php

namespace App\Http\Controllers;

use App\Models\HisanaContestEntry;
use App\Models\Setting;
use App\Services\ResendMailer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HisanaContestAdminController extends Controller
{
    public const SETTING_OPEN = 'hisana_contest_open';

    public static function isContestOpen(): bool
    {
        return Setting::getValue(self::SETTING_OPEN, '1') !== '0';
    }

    public function index(Request $request)
    {
        $query = HisanaContestEntry::query()->latest();
        $search = trim((string) $request->get('q', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(30)->withQueryString();

        $today = Carbon::today();
        $weekAgo = Carbon::now()->subDays(7);

        $stats = [
            'total' => HisanaContestEntry::count(),
            'emailed' => HisanaContestEntry::whereNotNull('email_sent_at')->count(),
            'pending' => HisanaContestEntry::whereNull('email_sent_at')->count(),
            'today' => HisanaContestEntry::whereDate('created_at', $today)->count(),
            'week' => HisanaContestEntry::where('created_at', '>=', $weekAgo)->count(),
            'with_name' => HisanaContestEntry::whereNotNull('name')->where('name', '!=', '')->count(),
        ];

        $contestOpen = self::isContestOpen();

        return view('hisana-contest.index', [
            'entries' => $entries,
            'stats' => $stats,
            'search' => $search,
            'contestOpen' => $contestOpen,
        ]);
    }

    public function destroy(HisanaContestEntry $entry)
    {
        $entry->delete();

        return back()->with('success', 'تم حذف المشاركة.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:hisana_contest_entries,id'],
        ], [
            'ids.required' => 'اختر مشاركة واحدة على الأقل للحذف.',
        ]);

        $deleted = HisanaContestEntry::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', "تم حذف {$deleted} مشاركة.");
    }

    public function toggleOpen(Request $request)
    {
        $open = $request->boolean('open');
        Setting::setValue(
            self::SETTING_OPEN,
            $open ? '1' : '0',
            'boolean',
            'فتح أو قفل رابط مسابقة الحصانة'
        );

        return back()->with(
            'success',
            $open ? 'تم فتح رابط المسابقة. يمكن التسجيل الآن.' : 'تم قفل رابط المسابقة. التسجيل متوقف.'
        );
    }

    public function resendEmail(HisanaContestEntry $entry, ResendMailer $mailer)
    {
        $sent = $this->sendConfirmation($mailer, $entry);

        if ($sent) {
            $entry->forceFill(['email_sent_at' => now()])->save();

            $extra = $mailer->lastEmailId ? ' (Ref: '.$mailer->lastEmailId.')' : '';

            return back()->with('success', 'تم إرسال الإيميل إلى '.$entry->email.$extra.' — راجع الوارد والـ Spam.');
        }

        $detail = $mailer->lastError ? ' ('.$mailer->lastError.')' : '';

        return back()->with('error', 'فشل إرسال الإيميل إلى '.$entry->email.$detail);
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
