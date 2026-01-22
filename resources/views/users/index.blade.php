@extends('layouts.app')

@section('title', 'المستخدمون')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="bi bi-people me-2"></i>المستخدمون في النظام
    </h2>
    <div>
        <span class="badge bg-primary me-2">إجمالي: {{ $users->count() }}</span>
    </div>
</div>

@if($users->count() > 0)
    <div class="row g-3">
        @foreach($users as $user)
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 user-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="user-avatar me-3">
                            <div class="avatar-circle">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold">{{ $user->name }}</h6>
                            <small class="text-muted">{{ $user->email }}</small>
                        </div>
                    </div>
                    <div class="user-stats">
                        <div class="stat-item">
                            <i class="bi bi-heart text-danger"></i>
                            <span>{{ $user->likes_count }}</span>
                            <small>إعجاب</small>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-bookmark text-warning"></i>
                            <span>{{ $user->favorites_count }}</span>
                            <small>مفضلة</small>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-chat-dots text-info"></i>
                            <span>{{ $user->comments_count }}</span>
                            <small>تعليق</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0 small">الدور:</label>
                            @if(auth()->user()->isAdmin() && $user->id !== auth()->id())
                                @php
                                    $currentRole = $user->role ?? 'user';
                                @endphp
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="role_{{ $user->id }}" id="role_user_{{ $user->id }}" 
                                           value="user" {{ $currentRole === 'user' ? 'checked' : '' }}
                                           onchange="updateUserRole({{ $user->id }}, 'user', '{{ $currentRole }}')">
                                    <label class="btn btn-outline-secondary" for="role_user_{{ $user->id }}" title="مستخدم">
                                        <i class="bi bi-person"></i>
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="role_{{ $user->id }}" id="role_editor_{{ $user->id }}" 
                                           value="editor" {{ $currentRole === 'editor' ? 'checked' : '' }}
                                           onchange="updateUserRole({{ $user->id }}, 'editor', '{{ $currentRole }}')">
                                    <label class="btn btn-outline-warning" for="role_editor_{{ $user->id }}" title="محرر">
                                        <i class="bi bi-pencil"></i>
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="role_{{ $user->id }}" id="role_admin_{{ $user->id }}" 
                                           value="admin" {{ $currentRole === 'admin' ? 'checked' : '' }}
                                           onchange="updateUserRole({{ $user->id }}, 'admin', '{{ $currentRole }}')">
                                    <label class="btn btn-outline-danger" for="role_admin_{{ $user->id }}" title="مدير">
                                        <i class="bi bi-shield-check"></i>
                                    </label>
                                </div>
                            @else
                                @if($user->role)
                                    <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'editor' ? 'warning' : 'secondary') }}">
                                        {{ $user->role === 'admin' ? 'مدير' : ($user->role === 'editor' ? 'محرر' : 'مستخدم') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">مستخدم</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="bi bi-calendar3 me-1"></i>
                            انضم: {{ $user->created_at->format('Y-m-d') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-people fs-1 text-muted"></i>
            <p class="text-muted mt-3">لا يوجد مستخدمون في النظام</p>
        </div>
    </div>
@endif
@endsection

@push('styles')
<style>
.user-card {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
}

.user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
}

.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.user-stats {
    display: flex;
    gap: 1rem;
    padding: 0.75rem 0;
    border-top: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    margin: 0.75rem 0;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    flex: 1;
}

.stat-item i {
    font-size: 1.25rem;
}

.stat-item span {
    font-weight: 600;
    font-size: 1.125rem;
    color: var(--bs-dark);
}

.stat-item small {
    font-size: 0.75rem;
    color: var(--bs-secondary);
}

.role-buttons {
    display: flex;
    gap: 0.5rem;
}

.role-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.role-btn:hover {
    transform: scale(1.05);
}

.role-btn.active {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}
</style>
@endpush

@push('scripts')
<script>
async function updateUserRole(userId, role, currentRole) {
    const roleLabels = {
        'admin': 'مدير',
        'editor': 'محرر',
        'user': 'مستخدم'
    };

    if (!confirm(`هل أنت متأكد من تغيير دور المستخدم إلى ${roleLabels[role]}؟`)) {
        // إعادة تعيين الـ radio button
        document.querySelector(`input[name="role_${userId}"][value="${currentRole}"]`).checked = true;
        return;
    }

    const updateUrl = '{{ route("users.update-role", ":id") }}'.replace(':id', userId);
    const updateUrlRelative = updateUrl.replace(/^https?:\/\/[^\/]+/, '');

    try {
        const response = await fetch(updateUrlRelative, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                role: role
            })
        });

        const data = await response.json();

        if (data.success) {
            // إظهار رسالة نجاح
            showToast('تم تحديث الدور بنجاح', 'success');
            // تحديث الصفحة بعد ثانية واحدة
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message || 'حدث خطأ أثناء تحديث الدور', 'error');
            // إعادة تعيين الـ radio button
            document.querySelector(`input[name="role_${userId}"][value="${currentRole}"]`).checked = true;
        }
    } catch (error) {
        console.error('Error updating role:', error);
        showToast('حدث خطأ أثناء تحديث الدور', 'error');
        // إعادة تعيين الـ radio button
        document.querySelector(`input[name="role_${userId}"][value="${currentRole}"]`).checked = true;
    }
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>
@endpush
