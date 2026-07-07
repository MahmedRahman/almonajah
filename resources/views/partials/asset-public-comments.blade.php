{{-- تعليقات الصفحة العامة للأصل (فيديو/صوت) — يُستدعى مرة واحدة لكل صفحة --}}
@if($asset->show_comments ?? true)
<div class="comments-section {{ $commentsSectionClass ?? '' }}" id="commentsSection">
    <div class="comments-header">
        <h2 class="comments-title">
            <i class="bi bi-chat-left-text"></i>
            التعليقات
            <span class="comments-count" id="commentsCount">0</span>
        </h2>
    </div>
    @auth
    <div class="comment-form-container">
        <form class="comment-form" id="commentForm">
            @csrf
            <div class="comment-input-wrapper">
                <textarea class="comment-input" id="commentInput" name="content" rows="3" placeholder="أضف تعليقاً..." maxlength="2000" required></textarea>
                <small class="text-muted d-block mt-1"><span id="commentCharCount">0</span> / 2000</small>
            </div>
            <div class="comment-form-actions">
                <div class="comment-emoji-wrapper">
                    <button type="button" class="comment-emoji-btn" id="commentEmojiBtn" title="إضافة إيموجي" aria-label="إضافة إيموجي" aria-expanded="false">
                        <i class="bi bi-emoji-smile"></i>
                    </button>
                    <div class="comment-emoji-panel" id="commentEmojiPanel" role="menu" aria-hidden="true"></div>
                </div>
                <button type="submit" class="btn btn-primary comment-submit-btn" id="commentSubmitBtn">
                    <i class="bi bi-send me-1"></i>إرسال
                </button>
            </div>
        </form>
    </div>
    @else
    <div class="comment-login-prompt">
        <a href="#" onclick="event.preventDefault(); showLoginModal();">سجّل الدخول</a> لكتابة تعليق.
    </div>
    @endauth
    <div class="comments-list" id="commentsList">
        <!-- تُحمّل التعليقات عبر JavaScript -->
    </div>
    <div class="empty-comments d-none" id="emptyComments">
        <i class="bi bi-chat-dots"></i>
        <p>لا توجد تعليقات بعد. كن أول من يعلق.</p>
    </div>
</div>
@endif
