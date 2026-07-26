<script>
(function() {
        const browseUrl = @json(route('assets.import.browse'));
        const uploadUrl = @json(route('assets.import.upload'));
        const importUrl = @json(route('assets.import'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const breadcrumbEl = document.getElementById('importVideoBreadcrumb');
        const loadingEl = document.getElementById('importVideoLoading');
        const errorEl = document.getElementById('importVideoError');
        const contentEl = document.getElementById('importVideoBrowseContent');
        const foldersEl = document.getElementById('importVideoFolders');
        const filesEl = document.getElementById('importVideoFiles');
        const emptyEl = document.getElementById('importVideoEmpty');
        const resultEl = document.getElementById('importVideoResult');
        const submitBtn = document.getElementById('importVideoSubmitBtn');
        const progressPanel = document.getElementById('importVideoProgress');
        const progressLabel = document.getElementById('importVideoProgressLabel');
        const progressPercent = document.getElementById('importVideoProgressPercent');
        const progressBar = document.getElementById('importVideoProgressBar');
        const progressDetail = document.getElementById('importVideoProgressDetail');
        const uploadTargetEl = document.getElementById('importVideoUploadTarget');
        const fileInput = document.getElementById('importVideoFileInput');
        const uploadBtn = document.getElementById('importVideoUploadBtn');
        const uploadImportBtn = document.getElementById('importVideoUploadImportBtn');
        const uploadCard = document.getElementById('importVideoUploadCard');
        const selectBar = document.getElementById('importVideoSelectBar');
        const selectedCountEl = document.getElementById('importVideoSelectedCount');
        const selectAllBtn = document.getElementById('importVideoSelectAllBtn');
        const clearSelectionBtn = document.getElementById('importVideoClearSelectionBtn');
        const submitBtnLabel = document.getElementById('importVideoSubmitBtnLabel');

        if (!resultEl || !fileInput || !uploadImportBtn) return;

        const LAST_BROWSE_PATH_KEY = 'almonajah_import_video_last_browse_path';
        const DEFAULT_UPLOAD_PATH = document.getElementById('importVideoDefaultFolder')?.value || 'videos/uploads';
        const selectedFileNameEl = document.getElementById('importVideoSelectedFileName');

        let currentPath = '';
        let selectedPaths = [];
        let busy = false;
        function resolveUploadFolder() {
            return canUploadToPath(currentPath) ? currentPath : DEFAULT_UPLOAD_PATH;
        }

        function updateSelectedFileLabel() {
            const file = fileInput?.files?.[0];
            if (!selectedFileNameEl) return;
            if (!file) {
                selectedFileNameEl.textContent = 'لم يتم اختيار ملف بعد';
                selectedFileNameEl.classList.add('text-muted');
                return;
            }
            selectedFileNameEl.classList.remove('text-muted');
            selectedFileNameEl.innerHTML = '<strong>' + escapeHtml(file.name) + '</strong> · ' + formatBytes(file.size);
        }

        function saveLastBrowsePath(path) {
            if (path === null || path === undefined) return;
            try {
                localStorage.setItem(LAST_BROWSE_PATH_KEY, String(path));
            } catch (e) { /* private mode / quota */ }
        }

        function getLastBrowsePath() {
            try {
                return localStorage.getItem(LAST_BROWSE_PATH_KEY) || '';
            } catch (e) {
                return '';
            }
        }

        function getInitialBrowsePath() {
            const saved = getLastBrowsePath();
            if (saved) return saved;
            return '';
        }

        function formatBytes(bytes) {
            if (!bytes || bytes < 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            let n = bytes;
            while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
            return (i === 0 ? n : n.toFixed(1)) + ' ' + units[i];
        }

        function canUploadToPath(path) {
            return path && path !== '2025' && path !== 'videos';
        }

        function updateUploadTarget() {
            if (uploadTargetEl) {
                if (canUploadToPath(currentPath)) {
                    uploadTargetEl.innerHTML = 'المجلد الحالي: <code dir="ltr">' + escapeHtml(currentPath) + '</code>';
                } else {
                    uploadTargetEl.textContent = 'تصفح مجلدات السيرفر ثم حدّد الملفات.';
                }
            }
            const hasFile = !!(fileInput?.files?.length);
            if (uploadBtn) uploadBtn.disabled = busy || !hasFile;
            if (uploadImportBtn) uploadImportBtn.disabled = busy || !hasFile;
            updateSelectedFileLabel();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function getSelectedPaths() {
            return Array.from(filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]:checked:not(:disabled)'))
                .map(function(cb) { return cb.value; });
        }

        function updateSelectionUi() {
            selectedPaths = getSelectedPaths();
            const n = selectedPaths.length;
            if (selectedCountEl) selectedCountEl.textContent = n + ' محدد';
            if (submitBtnLabel) {
                submitBtnLabel.textContent = n > 1
                    ? ('تسجيل ونقل المحدد (' + n + ')')
                    : 'تسجيل ونقل المحدد';
            }
            if (submitBtn) submitBtn.disabled = busy || n === 0;
            if (selectBar) {
                const selectable = filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]:not(:disabled)');
                selectBar.classList.toggle('d-none', selectable.length === 0);
            }
        }

        function setBusy(on) {
            busy = on;
            updateSelectionUi();
            const hasFile = !!(fileInput?.files?.length);
            if (uploadBtn) uploadBtn.disabled = on || !hasFile;
            if (uploadImportBtn) uploadImportBtn.disabled = on || !hasFile;
            if (fileInput) fileInput.disabled = on;
            document.body.classList.toggle('import-video-busy', on);
        }

        function showProgress(label, percent, detail) {
            progressPanel.classList.remove('d-none');
            progressLabel.textContent = label;
            if (percent === null || percent === undefined) {
                progressPercent.textContent = '...';
                progressBar.style.width = '100%';
                progressBar.textContent = '';
                progressBar.classList.add('progress-bar-animated', 'progress-bar-striped');
            } else {
                const p = Math.max(0, Math.min(100, Math.round(percent)));
                progressPercent.textContent = p + '%';
                progressBar.style.width = p + '%';
                progressBar.textContent = p >= 8 ? p + '%' : '';
                progressBar.classList.toggle('progress-bar-animated', p < 100);
                progressBar.classList.toggle('progress-bar-striped', p < 100);
            }
            progressDetail.textContent = detail || '';
            if (uploadCard) uploadCard.classList.add('opacity-50');
            contentEl.classList.add('d-none');
        }

        function hideProgress() {
            progressPanel.classList.add('d-none');
            if (uploadCard) uploadCard.classList.remove('opacity-50');
        }

        function setLoading(on) {
            loadingEl.classList.toggle('d-none', !on);
            if (on) {
                contentEl.classList.add('d-none');
                errorEl.classList.add('d-none');
            }
        }

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.classList.remove('d-none');
            contentEl.classList.add('d-none');
        }

        function formatSize(mb) {
            if (mb >= 1024) return (mb / 1024).toFixed(2) + ' GB';
            return mb + ' MB';
        }

        function renderBreadcrumb(segments) {
            breadcrumbEl.innerHTML = '';
            const homeLi = document.createElement('li');
            homeLi.className = 'breadcrumb-item';
            const homeA = document.createElement('a');
            homeA.href = '#';
            homeA.textContent = 'الرئيسية';
            homeA.addEventListener('click', function(e) {
                e.preventDefault();
                loadBrowse('');
            });
            homeLi.appendChild(homeA);
            breadcrumbEl.appendChild(homeLi);

            segments.forEach(function(seg, i) {
                const li = document.createElement('li');
                const isLast = i === segments.length - 1;
                li.className = 'breadcrumb-item' + (isLast ? ' active' : '');
                if (isLast) {
                    li.textContent = seg;
                } else {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.textContent = seg;
                    const path = segments.slice(0, i + 1).join('/');
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        loadBrowse(path);
                    });
                    li.appendChild(a);
                }
                breadcrumbEl.appendChild(li);
            });
        }

        function renderBrowse(data) {
            currentPath = data.path_prefix || '';
            saveLastBrowsePath(currentPath);
            selectedPaths = [];
            renderBreadcrumb(data.breadcrumb_segments || []);

            foldersEl.innerHTML = '';
            (data.folders || []).forEach(function(folder) {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-lg-3';
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'btn btn-outline-primary w-100 text-start py-3';
                card.innerHTML = '<i class="bi bi-folder-fill me-2"></i>' + folder;
                card.addEventListener('click', function() {
                    const next = currentPath ? currentPath + '/' + folder : folder;
                    loadBrowse(next);
                });
                col.appendChild(card);
                foldersEl.appendChild(col);
            });

            filesEl.innerHTML = '';
            (data.files || []).forEach(function(file) {
                const item = document.createElement('label');
                item.className = 'list-group-item list-group-item-action d-flex align-items-start gap-2' + (file.already_in_site ? ' disabled opacity-75' : '');
                const disabled = file.already_in_site;
                const badge = file.already_in_site
                    ? '<span class="badge bg-success mt-1">منقول للموقع مسبقاً</span>'
                    : (file.in_database ? '<span class="badge bg-info text-dark mt-1">مسجل — بانتظار النقل</span>' : '<span class="badge bg-secondary mt-1">جديد</span>');
                item.innerHTML =
                    '<input type="checkbox" name="import_video_file" class="form-check-input mt-1 flex-shrink-0" value="' + escapeHtml(file.relative_path) + '"' + (disabled ? ' disabled' : '') + '>' +
                    '<div class="flex-grow-1 min-width-0">' +
                    '<div class="fw-semibold text-truncate">' + escapeHtml(file.file_name) + '</div>' +
                    '<small class="text-muted d-block" dir="ltr">' + escapeHtml(file.relative_path) + ' · ' + formatSize(file.size_mb) + '</small>' +
                    badge +
                    '</div>';
                if (!disabled) {
                    const checkbox = item.querySelector('input');
                    checkbox.addEventListener('change', updateSelectionUi);
                }
                filesEl.appendChild(item);
            });

            const hasFolders = (data.folders || []).length > 0;
            const hasFiles = (data.files || []).length > 0;
            emptyEl.classList.toggle('d-none', hasFolders || hasFiles);
            contentEl.classList.remove('d-none');
            updateUploadTarget();
            updateSelectionUi();
        }

        function uploadVideoFile(file) {
            return new Promise(function(resolve, reject) {
                const fd = new FormData();
                fd.append('video', file);
                fd.append('folder_path', resolveUploadFolder());
                fd.append('_token', csrfToken);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const pct = (e.loaded / e.total) * 100;
                        showProgress(
                            'جاري رفع الملف...',
                            pct,
                            formatBytes(e.loaded) + ' من ' + formatBytes(e.total) + ' · ' + file.name
                        );
                    } else {
                        showProgress('جاري رفع الملف...', null, file.name + ' — جاري الإرسال...');
                    }
                });

                xhr.onload = function() {
                    let data = {};
                    try { data = JSON.parse(xhr.responseText); } catch (err) {}
                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        resolve(data);
                    } else {
                        const msg = data.error || data.message
                            || (data.errors && data.errors.video && data.errors.video[0])
                            || 'فشل رفع الملف';
                        reject(new Error(msg));
                    }
                };
                xhr.onerror = function() { reject(new Error('انقطع الاتصال أثناء الرفع')); };
                xhr.send(fd);
            });
        }

        function runImport(sourcePath, options) {
            const opts = options || {};
            if (!opts.silentProgress) {
                showProgress('جاري التسجيل ونقل الفيديو...', null, 'قد تستغرق العملية دقائق للملفات الكبيرة — لا تغلق النافذة');
            }

            return fetch(importUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ source_path: sourcePath })
            }).then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); });
        }

        function runImportBatchSequential(paths) {
            const total = paths.length;
            const results = [];
            let imported = 0;
            let skipped = 0;
            let failed = 0;
            let chain = Promise.resolve();

            paths.forEach(function(sourcePath, index) {
                chain = chain.then(function() {
                    const pct = Math.round(((index) / total) * 100);
                    const fileName = sourcePath.split('/').pop();
                    showProgress(
                        'جاري التسجيل والنقل...',
                        pct,
                        (index + 1) + ' / ' + total + ' — ' + fileName
                    );
                    return runImport(sourcePath, { silentProgress: true }).then(function(res) {
                        const d = res.data || {};
                        const row = {
                            source_path: sourcePath,
                            success: !!d.success,
                            message: d.message || d.error || '',
                            asset_id: d.asset_id || null,
                            asset_url: d.asset_url || null,
                            already_imported: !!d.already_imported,
                            error: d.error || null
                        };
                        results.push(row);
                        if (row.success) {
                            if (row.already_imported) skipped++;
                            else imported++;
                        } else {
                            failed++;
                        }
                    }).catch(function() {
                        results.push({
                            source_path: sourcePath,
                            success: false,
                            message: '',
                            error: 'خطأ في الاتصال'
                        });
                        failed++;
                    });
                });
            });

            return chain.then(function() {
                const message = 'اكتملت المعالجة: نجح ' + imported
                    + (skipped > 0 ? ' · موجود مسبقاً ' + skipped : '')
                    + (failed > 0 ? ' · فشل ' + failed : '')
                    + ' من ' + total;
                return {
                    success: failed === 0,
                    message: message,
                    imported: imported,
                    skipped: skipped,
                    failed: failed,
                    total: total,
                    results: results
                };
            });
        }

        function showResultSuccess(data) {
            setBusy(false);
            showProgress('تم بنجاح', 100, data.message || 'اكتملت العملية');
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
            contentEl.classList.remove('d-none');
            resultEl.classList.remove('d-none');
            resultEl.className = 'alert alert-success mb-0 mt-3';
            const link = data.asset_url ? ' <a href="' + data.asset_url + '" class="alert-link" target="_blank" rel="noopener">فتح صفحة الفيديو</a>' : '';
            resultEl.innerHTML = (data.message || 'تم بنجاح') + link;
            if (!data.already_imported) {
                setTimeout(function() { window.location.href = data.asset_url || @json(route('assets.index')); }, 1500);
            }
        }

        function showBatchResultSuccess(data) {
            setBusy(false);
            const imported = data.imported || 0;
            const failed = data.failed || 0;
            const skipped = data.skipped || 0;
            showProgress('اكتملت الدفعة', 100, data.message || '');
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
            contentEl.classList.remove('d-none');
            resultEl.classList.remove('d-none');
            resultEl.className = 'alert ' + (failed > 0 ? 'alert-warning' : 'alert-success') + ' mb-0 mt-3';
            let html = '<strong>' + escapeHtml(data.message || 'اكتملت المعالجة') + '</strong>';
            if (Array.isArray(data.results) && data.results.length) {
                html += '<ul class="mb-0 mt-2 small" style="max-height: 200px; overflow-y: auto;">';
                data.results.forEach(function(row) {
                    const name = row.source_path ? row.source_path.split('/').pop() : '';
                    const icon = row.success ? (row.already_imported ? '○' : '✓') : '✗';
                    const cls = row.success ? (row.already_imported ? 'text-muted' : 'text-success') : 'text-danger';
                    html += '<li class="' + cls + '">' + icon + ' ' + escapeHtml(name);
                    if (row.error) html += ' — ' + escapeHtml(row.error);
                    else if (row.message && !row.success) html += ' — ' + escapeHtml(row.message);
                    if (row.asset_url && row.success && !row.already_imported) {
                        html += ' <a href="' + escapeHtml(row.asset_url) + '" target="_blank" rel="noopener">فتح</a>';
                    }
                    html += '</li>';
                });
                html += '</ul>';
            }
            resultEl.innerHTML = html;
            if (imported > 0 || skipped > 0) {
                setTimeout(function() { window.location.href = @json(route('assets.index')); }, failed > 0 ? 3500 : 2000);
            } else {
                loadBrowse(currentPath);
            }
        }

        function showResultError(msg) {
            hideProgress();
            setBusy(false);
            resultEl.classList.remove('d-none');
            resultEl.className = 'alert alert-danger mb-0 mt-3';
            resultEl.textContent = msg;
        }

        function loadBrowse(path, allowFallbackToRoot) {
            setLoading(true);
            errorEl.classList.add('d-none');
            resultEl.classList.add('d-none');

            const url = browseUrl + (path ? '?path=' + encodeURIComponent(path) : '');
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    setLoading(false);
                    if (!data.success) {
                        if (allowFallbackToRoot && path) {
                            saveLastBrowsePath('');
                            loadBrowse('', false);
                            return;
                        }
                        showError(data.error || 'تعذر تحميل المجلد');
                        return;
                    }
                    renderBrowse(data);
                })
                .catch(function() {
                    setLoading(false);
                    if (allowFallbackToRoot && path) {
                        saveLastBrowsePath('');
                        loadBrowse('', false);
                        return;
                    }
                    showError('تعذر الاتصال بالخادم');
                });
        }

        // تهيئة الصفحة
        resultEl.classList.add('d-none');
        resultEl.innerHTML = '';
        hideProgress();
        setBusy(false);
        if (fileInput) fileInput.value = '';
        updateUploadTarget();

        // تحميل التصفح عند فتح قسم السيرفر فقط
        const serverCollapse = document.getElementById('importVideoServerCollapse');
        if (serverCollapse) {
            serverCollapse.addEventListener('shown.bs.collapse', function() {
                loadBrowse(getInitialBrowsePath(), true);
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', updateUploadTarget);
        }

        if (uploadBtn) {
            uploadBtn.addEventListener('click', function() {
                const file = fileInput?.files?.[0];
                if (!file) return;
                setBusy(true);
                resultEl.classList.add('d-none');
                uploadVideoFile(file)
                    .then(function(data) {
                        showProgress('اكتمل الرفع', 100, data.file_name);
                        progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                        setBusy(false);
                        fileInput.value = '';
                        updateUploadTarget();
                        loadBrowse(data.folder_path || resolveUploadFolder());
                    })
                    .catch(function(err) {
                        showResultError(err.message || 'فشل الرفع');
                    });
            });
        }

        if (uploadImportBtn) {
            uploadImportBtn.addEventListener('click', function() {
                const file = fileInput?.files?.[0];
                if (!file || busy) return;
                setBusy(true);
                resultEl.classList.add('d-none');
                uploadVideoFile(file)
                    .then(function(data) {
                        showProgress('اكتمل الرفع — جاري إضافة الفيديو للمكتبة...', 100, data.file_name);
                        return runImport(data.relative_path);
                    })
                    .then(function(res) {
                        if (res.data && res.data.success) {
                            showResultSuccess(res.data);
                        } else {
                            showResultError((res.data && (res.data.error || res.data.message)) || 'فشل التسجيل والنقل');
                        }
                    })
                    .catch(function(err) {
                        showResultError(err.message || 'حدث خطأ');
                    });
            });
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]:not(:disabled)').forEach(function(cb) {
                    cb.checked = true;
                });
                updateSelectionUi();
            });
        }

        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function() {
                filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]').forEach(function(cb) {
                    cb.checked = false;
                });
                updateSelectionUi();
            });
        }

        if (submitBtn) submitBtn.addEventListener('click', function() {
            const paths = getSelectedPaths();
            if (!paths.length || busy) return;

            if (!confirm('تسجيل ونقل ' + paths.length + ' فيديو إلى الموقع؟\nقد تستغرق العملية وقتاً طويلاً.')) {
                return;
            }

            setBusy(true);
            resultEl.classList.add('d-none');

            if (paths.length === 1) {
                runImport(paths[0])
                    .then(function(res) {
                        if (res.data.success) {
                            showResultSuccess(res.data);
                        } else {
                            showResultError(res.data.error || 'فشل الاستيراد');
                        }
                    })
                    .catch(function() {
                        showResultError('حدث خطأ أثناء التسجيل والنقل');
                    });
                return;
            }

            runImportBatchSequential(paths)
                .then(function(summary) {
                    showBatchResultSuccess(summary);
                })
                .catch(function() {
                    showResultError('حدث خطأ أثناء التسجيل والنقل');
                });
        });
    })();
</script>
