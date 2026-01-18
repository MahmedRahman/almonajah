@extends('layouts.app')

@section('title', 'الإعدادات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">الإعدادات</h2>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-gear me-2"></i>إعدادات النظام
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="mb-3">معلومات النظام</h6>
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">اسم التطبيق:</span>
                                <strong>{{ config('app.name') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">البيئة:</span>
                                <strong>{{ config('app.env') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">إصدار Laravel:</span>
                                <strong>{{ app()->version() }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">المنطقة الزمنية:</span>
                                <strong>{{ config('app.timezone') }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="mb-3">معلومات المستخدم</h6>
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">الاسم:</span>
                                <strong>{{ auth()->user()->name }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">البريد الإلكتروني:</span>
                                <strong>{{ auth()->user()->email }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">تاريخ التسجيل:</span>
                                <strong>{{ auth()->user()->created_at->format('Y-m-d') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mt-4">
                    <div class="col-md-12 mb-4">
                        <h6 class="mb-3">
                            <i class="bi bi-tools me-2"></i>وضع الصيانة
                        </h6>
                        <form method="POST" action="{{ route('settings.maintenance-mode') }}" class="mb-4">
                            @csrf
                            <div class="card border">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                               name="maintenance_mode" {{ $maintenanceMode ? 'checked' : '' }}
                                               onchange="this.form.submit()">
                                        <label class="form-check-label" for="maintenance_mode">
                                            <strong>تفعيل وضع الصيانة</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        عند تفعيل وضع الصيانة، سيتم توجيه الزوار إلى صفحة الصيانة. المستخدمون المسجلون يمكنهم الدخول بشكل طبيعي.
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <hr>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6 class="mb-3">
                            <i class="bi bi-share me-2"></i>روابط السوشيال ميديا
                        </h6>
                        <form method="POST" action="{{ route('settings.social-links') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="facebook" class="form-label">
                                        <i class="bi bi-facebook me-2 text-primary"></i>Facebook
                                    </label>
                                    <input type="url" class="form-control" id="facebook" name="facebook" 
                                           value="{{ old('facebook', $socialLinks['facebook'] ?? '') }}" 
                                           placeholder="https://facebook.com/yourpage">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="twitter" class="form-label">
                                        <i class="bi bi-twitter me-2 text-info"></i>Twitter / X
                                    </label>
                                    <input type="url" class="form-control" id="twitter" name="twitter" 
                                           value="{{ old('twitter', $socialLinks['twitter'] ?? '') }}" 
                                           placeholder="https://twitter.com/yourhandle">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="instagram" class="form-label">
                                        <i class="bi bi-instagram me-2 text-danger"></i>Instagram
                                    </label>
                                    <input type="url" class="form-control" id="instagram" name="instagram" 
                                           value="{{ old('instagram', $socialLinks['instagram'] ?? '') }}" 
                                           placeholder="https://instagram.com/yourhandle">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="youtube" class="form-label">
                                        <i class="bi bi-youtube me-2 text-danger"></i>YouTube
                                    </label>
                                    <input type="url" class="form-control" id="youtube" name="youtube" 
                                           value="{{ old('youtube', $socialLinks['youtube'] ?? '') }}" 
                                           placeholder="https://youtube.com/@yourchannel">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="linkedin" class="form-label">
                                        <i class="bi bi-linkedin me-2 text-primary"></i>LinkedIn
                                    </label>
                                    <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                           value="{{ old('linkedin', $socialLinks['linkedin'] ?? '') }}" 
                                           placeholder="https://linkedin.com/company/yourcompany">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tiktok" class="form-label">
                                        <i class="bi bi-tiktok me-2"></i>TikTok
                                    </label>
                                    <input type="url" class="form-control" id="tiktok" name="tiktok" 
                                           value="{{ old('tiktok', $socialLinks['tiktok'] ?? '') }}" 
                                           placeholder="https://tiktok.com/@yourhandle">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="whatsapp" class="form-label">
                                        <i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp
                                    </label>
                                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" 
                                           value="{{ old('whatsapp', $socialLinks['whatsapp'] ?? '') }}" 
                                           placeholder="+201234567890">
                                    <small class="text-muted">رقم الهاتف مع رمز الدولة</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telegram" class="form-label">
                                        <i class="bi bi-telegram me-2 text-info"></i>Telegram
                                    </label>
                                    <input type="text" class="form-control" id="telegram" name="telegram" 
                                           value="{{ old('telegram', $socialLinks['telegram'] ?? '') }}" 
                                           placeholder="@yourhandle">
                                    <small class="text-muted">اسم المستخدم أو رابط القناة</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>حفظ الروابط
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
