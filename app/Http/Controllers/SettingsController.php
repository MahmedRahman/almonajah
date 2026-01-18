<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $socialLinks = $this->getSocialLinks();
        return view('settings.index', compact('socialLinks'));
    }

    public function updateSocialLinks(Request $request)
    {
        $validated = $request->validate([
            'facebook' => 'nullable|url',
            'twitter' => 'nullable|url',
            'instagram' => 'nullable|url',
            'youtube' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'tiktok' => 'nullable|url',
            'whatsapp' => 'nullable|string',
            'telegram' => 'nullable|string',
        ]);

        // حفظ كل رابط في قاعدة البيانات
        Setting::setValue('social_facebook', $validated['facebook'] ?? '', 'url', 'رابط Facebook');
        Setting::setValue('social_twitter', $validated['twitter'] ?? '', 'url', 'رابط Twitter/X');
        Setting::setValue('social_instagram', $validated['instagram'] ?? '', 'url', 'رابط Instagram');
        Setting::setValue('social_youtube', $validated['youtube'] ?? '', 'url', 'رابط YouTube');
        Setting::setValue('social_linkedin', $validated['linkedin'] ?? '', 'url', 'رابط LinkedIn');
        Setting::setValue('social_tiktok', $validated['tiktok'] ?? '', 'url', 'رابط TikTok');
        Setting::setValue('social_whatsapp', $validated['whatsapp'] ?? '', 'text', 'رقم WhatsApp');
        Setting::setValue('social_telegram', $validated['telegram'] ?? '', 'text', 'رابط أو اسم Telegram');

        return redirect()->route('settings.index')
            ->with('success', 'تم حفظ روابط السوشيال ميديا بنجاح');
    }

    private function getSocialLinks()
    {
        return [
            'facebook' => Setting::getValue('social_facebook', ''),
            'twitter' => Setting::getValue('social_twitter', ''),
            'instagram' => Setting::getValue('social_instagram', ''),
            'youtube' => Setting::getValue('social_youtube', ''),
            'linkedin' => Setting::getValue('social_linkedin', ''),
            'tiktok' => Setting::getValue('social_tiktok', ''),
            'whatsapp' => Setting::getValue('social_whatsapp', ''),
            'telegram' => Setting::getValue('social_telegram', ''),
        ];
    }
}
