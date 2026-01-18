<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الصيانة - المناجاة</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #188781;
            --secondary-color: #1f9f97;
            --accent-color: #f6bd21;
        }
        body {
            background: linear-gradient(135deg, #188781 0%, #1f9f97 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cairo', sans-serif;
        }
        .maintenance-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .maintenance-icon {
            font-size: 80px;
            color: var(--accent-color);
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        .social-link {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 24px;
            transition: all 0.3s ease;
            background: var(--bg-tertiary, #f3f4f6);
            color: var(--text-primary, #1f2937);
        }
        .social-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .social-link.facebook:hover {
            background: #1877f2;
            color: white;
        }
        .social-link.twitter:hover {
            background: #1da1f2;
            color: white;
        }
        .social-link.instagram:hover {
            background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);
            color: white;
        }
        .social-link.youtube:hover {
            background: #ff0000;
            color: white;
        }
        .social-link.linkedin:hover {
            background: #0077b5;
            color: white;
        }
        .social-link.tiktok:hover {
            background: #000000;
            color: white;
        }
        .social-link.whatsapp:hover {
            background: #25d366;
            color: white;
        }
        .social-link.telegram:hover {
            background: #0088cc;
            color: white;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="المناجاة" style="max-width: 150px; height: auto; margin-bottom: 30px;" class="d-block mx-auto">
        </div>
        
        <div class="maintenance-icon">
            <i class="bi bi-tools"></i>
        </div>
        
        <h1 class="fw-bold mb-3" style="color: var(--primary-color);">الموقع قيد الصيانة</h1>
        
        <p class="text-muted mb-4" style="font-size: 1.1rem; line-height: 1.8;">
            نعتذر عن الإزعاج، نحن نقوم حاليًا بإجراء بعض التحديثات والصيانة على الموقع لتحسين تجربتك.
            <br><br>
            سنعود قريبًا!
        </p>
        
        @if(!empty(array_filter($socialLinks)))
        <div class="social-links">
            @if(!empty($socialLinks['facebook']))
                <a href="{{ $socialLinks['facebook'] }}" target="_blank" class="social-link facebook" title="Facebook">
                    <i class="bi bi-facebook"></i>
                </a>
            @endif
            @if(!empty($socialLinks['twitter']))
                <a href="{{ $socialLinks['twitter'] }}" target="_blank" class="social-link twitter" title="Twitter">
                    <i class="bi bi-twitter"></i>
                </a>
            @endif
            @if(!empty($socialLinks['instagram']))
                <a href="{{ $socialLinks['instagram'] }}" target="_blank" class="social-link instagram" title="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
            @endif
            @if(!empty($socialLinks['youtube']))
                <a href="{{ $socialLinks['youtube'] }}" target="_blank" class="social-link youtube" title="YouTube">
                    <i class="bi bi-youtube"></i>
                </a>
            @endif
            @if(!empty($socialLinks['linkedin']))
                <a href="{{ $socialLinks['linkedin'] }}" target="_blank" class="social-link linkedin" title="LinkedIn">
                    <i class="bi bi-linkedin"></i>
                </a>
            @endif
            @if(!empty($socialLinks['tiktok']))
                <a href="{{ $socialLinks['tiktok'] }}" target="_blank" class="social-link tiktok" title="TikTok">
                    <i class="bi bi-tiktok"></i>
                </a>
            @endif
            @if(!empty($socialLinks['whatsapp']))
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $socialLinks['whatsapp']) }}" target="_blank" class="social-link whatsapp" title="WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </a>
            @endif
            @if(!empty($socialLinks['telegram']))
                <a href="{{ str_starts_with($socialLinks['telegram'], 'http') ? $socialLinks['telegram'] : 'https://t.me/' . ltrim($socialLinks['telegram'], '@') }}" target="_blank" class="social-link telegram" title="Telegram">
                    <i class="bi bi-telegram"></i>
                </a>
            @endif
        </div>
        @endif
        
        <div class="mt-4 pt-4 border-top">
            <small class="text-muted">
                <i class="bi bi-clock me-1"></i>
                نعتذر عن أي إزعاج قد يسببه هذا
            </small>
        </div>
    </div>
</body>
</html>
