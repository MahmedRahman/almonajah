// ─── DOM refs ────────────────────────────────────────────────────────────────
const srtInput             = document.getElementById("srtFile");
const downloadBtn          = document.getElementById("downloadBtn");
const downloadAllBtn       = document.getElementById("downloadAllBtn");
const translateAllBtn      = document.getElementById("translateAllBtn");
const statusText           = document.getElementById("status");
const languagePanel        = document.getElementById("languagePanel");
const selectedLanguageText = document.getElementById("selectedLanguage");
const fileMetaText         = document.getElementById("fileMeta");
const previewSection       = document.getElementById("previewSection");
const previewBody          = document.getElementById("previewBody");
const previewCount         = document.getElementById("previewCount");
const sectionsSection      = document.getElementById("sectionsSection");
const sectionsPanelTitle   = document.getElementById("sectionsPanelTitle");
const sectionsTotal        = document.getElementById("sectionsTotal");
const sectionsBody         = document.getElementById("sectionsBody");

// ─── Config ───────────────────────────────────────────────────────────────────
const SECTION_BLOCKS_SIZE = 15;

// ─── Languages ────────────────────────────────────────────────────────────────
const languages = [
  { code: "id", label: "Bahasa Indonesia", apiName: "Bahasa Indonesia" },
  { code: "ur", label: "اردو",             apiName: "Urdu"             },
  { code: "fr", label: "Français",         apiName: "French"           },
  { code: "en", label: "English",          apiName: "English"          },
  { code: "ar", label: "العربية",          apiName: "Arabic"           },
  { code: "la", label: "Latina",           apiName: "Latin"            },
  { code: "ha", label: "Hausa",            apiName: "Hausa"            },
];

// Languages that need the translation API (Arabic is the original file).
const translatableLanguages = () => languages.filter((l) => l.code !== "ar");

// ─── State ────────────────────────────────────────────────────────────────────
let originalFileName   = "";
let selectedLanguage   = null;
let parsedBlocks       = [];
let currentSections    = [];
let isTranslating      = false;

const translatedByLanguage    = {};  // langCode → SRT string
const sectionStatusByLanguage = {};  // langCode → [{status, error}]
const languageNodes           = {};  // langCode → {button, fill}
let   sectionRowElements      = [];

const TIMESTAMP_REGEX =
  /^\d{2}:\d{2}:\d{2},\d{3}\s+-->\s+\d{2}:\d{2}:\d{2},\d{3}(?:\s+.*)?$/;

// ─── Status badges ────────────────────────────────────────────────────────────
const STATUS_CONFIG = {
  pending:     { badge: "badge-pending",     label: "⏳ في الانتظار",  row: ""                       },
  translating: { badge: "badge-translating", label: "⟳ جارٍ الترجمة", row: "section-row-translating" },
  done:        { badge: "badge-done",        label: "✓ مكتمل",         row: "section-row-done"        },
  error:       { badge: "badge-error",       label: "✗ فشل",           row: "section-row-error"       },
};

// ─── Generic helpers ──────────────────────────────────────────────────────────
function setStatus(msg) {
  statusText.textContent = msg;
}

function splitIntoSections(blocks) {
  const out = [];
  for (let i = 0; i < blocks.length; i += SECTION_BLOCKS_SIZE) {
    out.push(blocks.slice(i, i + SECTION_BLOCKS_SIZE));
  }
  return out;
}

function collectTextLines(blocks) {
  return blocks.flatMap((b) => b.textLines);
}

function remapLinesToBlocks(blocks, translatedLines) {
  let cursor = 0;
  return blocks.map((block) => {
    const textLines = translatedLines.slice(cursor, cursor + block.textLines.length);
    cursor += block.textLines.length;
    return { ...block, textLines };
  });
}

function rebuildSrt(blocks) {
  return (
    blocks
      .map((b) => [b.indexLine, b.timestampLine, ...b.textLines].join("\n"))
      .join("\n\n") + "\n"
  );
}

function readyLanguageCount() {
  return languages.filter((l) => !!translatedByLanguage[l.code]).length;
}

function ensureArabicCached() {
  if (!parsedBlocks.length) return;
  translatedByLanguage.ar = rebuildSrt(parsedBlocks);
  sectionStatusByLanguage.ar = currentSections.map(() => ({ status: "done", error: null }));
  setLanguageProgress("ar", 100);
}

function updateActionButtons() {
  const hasFile = parsedBlocks.length > 0;
  const count   = readyLanguageCount();

  if (translateAllBtn) {
    translateAllBtn.disabled = !hasFile || isTranslating;
  }
  downloadBtn.disabled = !selectedLanguage || !translatedByLanguage[selectedLanguage?.code];
  if (downloadAllBtn) {
    downloadAllBtn.disabled = count === 0 || isTranslating;
  }
}

function setUiBusy(busy) {
  isTranslating = busy;
  setButtonsDisabled(busy);
  updateActionButtons();
}

// ─── SRT parser ───────────────────────────────────────────────────────────────
function parseSrt(content) {
  const normalized = content.replace(/\r\n/g, "\n").trim();
  if (!normalized) throw new Error("الملف فارغ.");

  const blocks = normalized.split(/\n{2,}/).map((raw) => {
    const lines = raw.split("\n");
    if (lines.length < 3) {
      throw new Error("مقطع SRT غير صالح: كل مقطع يحتاج رقم + توقيت + نص.");
    }
    const [indexLine, timestampLine, ...textLines] = lines;
    if (!/^\d+$/.test(indexLine.trim())) {
      throw new Error(`رقم مقطع غير صالح: ${indexLine}`);
    }
    if (!TIMESTAMP_REGEX.test(timestampLine.trim())) {
      throw new Error(`سطر توقيت غير صالح: ${timestampLine}`);
    }
    return { indexLine, timestampLine, textLines };
  });

  if (!blocks.length) throw new Error("لم يُعثر على مقاطع في الملف.");
  return blocks;
}

// ─── Preview table ────────────────────────────────────────────────────────────
function renderPreview(blocks) {
  const frag = document.createDocumentFragment();
  blocks.forEach(({ indexLine, timestampLine, textLines }) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td class="col-index">${indexLine.trim()}</td>
      <td class="col-time">${timestampLine.trim()}</td>
      <td class="col-text">${textLines.join("\n")}</td>`;
    frag.appendChild(tr);
  });
  previewBody.innerHTML = "";
  previewBody.appendChild(frag);
  previewCount.textContent = `${blocks.length} مقطع`;
  previewSection.classList.remove("hidden");
}

function clearPreview() {
  previewBody.innerHTML = "";
  previewSection.classList.add("hidden");
}

// ─── Language buttons ─────────────────────────────────────────────────────────
function setLanguageActive(code) {
  languages.forEach(({ code: c }) => {
    const node = languageNodes[c];
    if (!node) return;
    node.button.classList.toggle("active", c === code);
  });
  const found = languages.find((l) => l.code === code);
  selectedLanguageText.textContent = found ? found.label : "لا يوجد";
}

function setLanguageProgress(code, pct) {
  const node = languageNodes[code];
  if (!node) return;
  node.fill.style.width = `${Math.max(0, Math.min(100, pct))}%`;
}

function setButtonsDisabled(disabled) {
  languages.forEach(({ code }) => {
    const node = languageNodes[code];
    if (node) node.button.disabled = disabled;
  });
}

function initLanguageButtons() {
  const frag = document.createDocumentFragment();
  languages.forEach((lang) => {
    const item   = document.createElement("div");
    item.className = "language-item";

    const button = document.createElement("button");
    button.type      = "button";
    button.className = "language-btn";
    button.textContent = lang.label;
    button.addEventListener("click", () => handleTranslate(lang));

    const track = document.createElement("div");
    track.className = "progress-track";
    const fill  = document.createElement("div");
    fill.className  = "progress-fill";
    track.appendChild(fill);

    item.appendChild(button);
    item.appendChild(track);
    frag.appendChild(item);
    languageNodes[lang.code] = { button, fill };
  });
  languagePanel.appendChild(frag);
}

// ─── Sections panel ───────────────────────────────────────────────────────────
function renderSectionsPanel(sections, languageLabel) {
  sectionsPanelTitle.textContent = `تقدم الترجمة · ${languageLabel}`;
  sectionsTotal.textContent      = `${sections.length} قسم`;
  sectionRowElements = [];

  const frag = document.createDocumentFragment();
  sections.forEach((section, i) => {
    const first     = section[0].indexLine.trim();
    const last      = section[section.length - 1].indexLine.trim();
    const lineCount = collectTextLines(section).length;

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td class="section-td-num">${i + 1}</td>
      <td class="section-td-range">${first} – ${last}</td>
      <td class="section-td-lines">${lineCount}</td>
      <td class="section-td-status">
        <span class="badge badge-pending">⏳ في الانتظار</span>
      </td>`;
    frag.appendChild(tr);
    sectionRowElements.push(tr);
  });

  sectionsBody.innerHTML = "";
  sectionsBody.appendChild(frag);
  sectionsSection.classList.remove("hidden");
}

function updateSectionRow(index, status, errorMsg) {
  const tr = sectionRowElements[index];
  if (!tr) return;

  const cfg   = STATUS_CONFIG[status] || STATUS_CONFIG.pending;
  const badge = tr.querySelector(".badge");
  badge.className   = `badge ${cfg.badge}`;
  badge.textContent = errorMsg
    ? `✗ ${errorMsg.slice(0, 35)}`
    : cfg.label;

  tr.className = cfg.row;

  if (status === "translating") {
    tr.scrollIntoView({ block: "nearest", behavior: "smooth" });
  }
}

function clearSectionsPanel() {
  sectionsBody.innerHTML = "";
  sectionRowElements     = [];
  sectionsSection.classList.add("hidden");
}

// ─── API layer ────────────────────────────────────────────────────────────────
async function callTranslateAPI(lines, targetLanguageName) {
  const res  = await fetch("translate-lines", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ lines, targetLanguage: targetLanguageName }),
  });
  const data = await res.json();

  if (!res.ok) {
    throw new Error(data?.error || "فشل طلب الترجمة.");
  }
  if (!Array.isArray(data.translated) || data.translated.length !== lines.length) {
    throw new Error(
      `الموديل رجّع ${data.translated?.length ?? "؟"} سطر بدل ${lines.length}.`
    );
  }
  return data.translated;
}

async function translateSectionWithRetry(lines, targetLanguageName) {
  // Server already repairs count mismatches (split / fill-by-id / line-by-line).
  // Keep a light client fallback for network / rare failures.
  try {
    return await callTranslateAPI(lines, targetLanguageName);
  } catch (firstErr) {
    const retryable =
      firstErr.message.includes("رجّع") ||
      firstErr.message.includes("JSON") ||
      firstErr.message.includes("parse") ||
      firstErr.message.includes("line count") ||
      firstErr.message.includes("guarantee") ||
      firstErr.message.includes("Model returned") ||
      firstErr.message.includes("Incomplete");

    if (!retryable) throw firstErr;

    if (lines.length <= 1) {
      // one more attempt
      return await callTranslateAPI(lines, targetLanguageName);
    }

    const mid = Math.ceil(lines.length / 2);
    const left = await callTranslateAPI(lines.slice(0, mid), targetLanguageName);
    const right = await callTranslateAPI(lines.slice(mid), targetLanguageName);
    return [...left, ...right];
  }
}

/**
 * Translate one language. Updates cache + UI progress.
 * @returns {Promise<'cached'|'done'>}
 */
async function runTranslateLanguage(language, { batchLabel } = {}) {
  selectedLanguage = language;
  setLanguageActive(language.code);

  if (translatedByLanguage[language.code]) {
    renderSectionsPanel(currentSections, language.label);
    (sectionStatusByLanguage[language.code] || []).forEach(({ status, error }, i) => {
      updateSectionRow(i, status, error);
    });
    setLanguageProgress(language.code, 100);
    return "cached";
  }

  if (language.code === "ar") {
    ensureArabicCached();
    renderSectionsPanel(currentSections, language.label);
    sectionStatusByLanguage.ar.forEach(({ status }, i) => updateSectionRow(i, status));
    return "done";
  }

  setLanguageProgress(language.code, 0);
  sectionStatusByLanguage[language.code] =
    currentSections.map(() => ({ status: "pending", error: null }));
  renderSectionsPanel(currentSections, language.label);

  const translatedSections = [];
  const prefix = batchLabel ? `${batchLabel} · ` : "";

  for (let i = 0; i < currentSections.length; i++) {
    const section   = currentSections[i];
    const textLines = collectTextLines(section);

    sectionStatusByLanguage[language.code][i] = { status: "translating", error: null };
    updateSectionRow(i, "translating");
    setStatus(
      `${prefix}ترجمة ${language.label}: القسم ${i + 1} / ${currentSections.length}` +
      ` · ${textLines.length} سطر...`
    );

    try {
      const translatedLines  = await translateSectionWithRetry(textLines, language.apiName);
      const translatedBlocks = remapLinesToBlocks(section, translatedLines);
      translatedSections.push(translatedBlocks);

      sectionStatusByLanguage[language.code][i] = { status: "done", error: null };
      updateSectionRow(i, "done");
    } catch (sectionErr) {
      const msg = sectionErr.message.slice(0, 60);
      sectionStatusByLanguage[language.code][i] = { status: "error", error: msg };
      updateSectionRow(i, "error", msg);
      throw sectionErr;
    }

    setLanguageProgress(
      language.code,
      Math.round(((i + 1) / currentSections.length) * 100)
    );
  }

  translatedByLanguage[language.code] = rebuildSrt(translatedSections.flat());
  return "done";
}

// ─── Translation orchestrator ─────────────────────────────────────────────────
async function handleTranslate(language) {
  if (!parsedBlocks.length) {
    setStatus("ارفع ملف SRT أولًا.");
    return;
  }
  if (isTranslating) return;

  setUiBusy(true);
  try {
    const result = await runTranslateLanguage(language);
    if (result === "cached") {
      setStatus(`ترجمة ${language.label} جاهزة — يمكنك التنزيل.`);
    } else if (language.code === "ar") {
      setStatus("النص العربي الأصلي جاهز للتنزيل.");
    } else {
      setStatus(`اكتملت الترجمة إلى ${language.label} بنجاح ✓ — جاهز للتنزيل.`);
    }
  } catch (err) {
    setStatus(`خطأ في ترجمة ${language.label}: ${err.message}`);
  } finally {
    setUiBusy(false);
  }
}

async function handleTranslateAll() {
  if (!parsedBlocks.length) {
    setStatus("ارفع ملف SRT أولًا.");
    return;
  }
  if (isTranslating) return;

  const targets = languages;
  const toDo = targets.filter((l) => !translatedByLanguage[l.code]);
  if (toDo.length === 0) {
    ensureArabicCached();
    updateActionButtons();
    setStatus("كل اللغات مترجمة بالفعل. يمكنك تنزيل الملف المضغوط.");
    return;
  }

  setUiBusy(true);
  const total    = targets.length;
  let   done     = readyLanguageCount();
  let   failed   = [];

  try {
    ensureArabicCached();
    done = readyLanguageCount();

    for (const language of targets) {
      if (translatedByLanguage[language.code]) {
        setLanguageProgress(language.code, 100);
        continue;
      }

      const batchLabel = `كل اللغات ${done + 1}/${total}`;
      try {
        await runTranslateLanguage(language, { batchLabel });
        done = readyLanguageCount();
      } catch (err) {
        failed.push(`${language.label}: ${err.message}`);
        // continue with remaining languages
      }
    }

    if (failed.length === 0) {
      setStatus(
        `اكتملت ترجمة كل اللغات (${readyLanguageCount()}/${total}) ✓ — نزّل الملف المضغوط.`
      );
    } else {
      setStatus(
        `انتهت الدفعة: ${readyLanguageCount()}/${total} ناجحة · فشل: ${failed.length}. ` +
        `يمكنك إعادة المحاولة أو تنزيل ما اكتمل.`
      );
    }
  } finally {
    setUiBusy(false);
  }
}

// ─── File upload ──────────────────────────────────────────────────────────────
srtInput.addEventListener("change", async (event) => {
  parsedBlocks     = [];
  currentSections  = [];
  originalFileName = "";
  selectedLanguage = null;
  selectedLanguageText.textContent = "لا يوجد";
  clearPreview();
  clearSectionsPanel();

  languages.forEach(({ code }) => {
    translatedByLanguage[code]    = "";
    sectionStatusByLanguage[code] = [];
    setLanguageProgress(code, 0);
    const node = languageNodes[code];
    if (node) node.button.classList.remove("active");
  });
  updateActionButtons();

  const file = event.target.files?.[0];
  if (!file) {
    fileMetaText.textContent = "لم يتم اختيار ملف بعد.";
    setStatus("في انتظار رفع ملف...");
    return;
  }

  if (!file.name.toLowerCase().endsWith(".srt")) {
    fileMetaText.textContent = "صيغة الملف غير صحيحة، لازم تكون .srt";
    setStatus("من فضلك اختَر ملف بصيغة .srt فقط.");
    srtInput.value = "";
    return;
  }

  originalFileName = file.name;
  try {
    const content = await file.text();
    parsedBlocks    = parseSrt(content);
    currentSections = splitIntoSections(parsedBlocks);
    const lineCount = collectTextLines(parsedBlocks).length;
    fileMetaText.textContent =
      `${file.name} · ${parsedBlocks.length} مقطع · ${currentSections.length} قسم · ${lineCount} سطر نصي`;
    setStatus("تم تحميل الملف. اختَر لغة، أو اضغط «ترجم كل اللغات».");
    renderPreview(parsedBlocks);
    updateActionButtons();
  } catch (err) {
    fileMetaText.textContent = "فشل قراءة الملف، تأكد من صيغة SRT.";
    setStatus(`خطأ في قراءة الملف: ${err.message}`);
    parsedBlocks = [];
    updateActionButtons();
  }
});

// ─── Download ─────────────────────────────────────────────────────────────────
function triggerDownload(blob, filename) {
  const url  = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href     = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

downloadBtn.addEventListener("click", () => {
  if (!selectedLanguage || !translatedByLanguage[selectedLanguage.code]) {
    setStatus("اختَر لغة وأكمل الترجمة أولًا.");
    return;
  }

  const blob = new Blob([translatedByLanguage[selectedLanguage.code]], {
    type: "text/plain;charset=utf-8",
  });
  const base = originalFileName.replace(/\.srt$/i, "");
  triggerDownload(blob, `${base || "translated"}_${selectedLanguage.code}.srt`);
  setStatus(`تم تنزيل ملف الترجمة (${selectedLanguage.label}).`);
});

downloadAllBtn.addEventListener("click", async () => {
  const files = languages.filter((l) => !!translatedByLanguage[l.code]);
  if (files.length === 0) {
    setStatus("لا توجد ملفات مترجمة للتنزيل. شغّل الترجمة أولًا.");
    return;
  }
  if (typeof JSZip === "undefined") {
    setStatus("مكتبة الضغط غير محمّلة. حدّث الصفحة وحاول مرة أخرى.");
    return;
  }

  downloadAllBtn.disabled = true;
  setStatus(`جارٍ تجهيز ملف ZIP (${files.length} لغة)...`);

  try {
    const zip  = new JSZip();
    const base = originalFileName.replace(/\.srt$/i, "") || "translated";

    files.forEach((lang) => {
      zip.file(`${base}_${lang.code}.srt`, translatedByLanguage[lang.code]);
    });

    const blob = await zip.generateAsync({
      type: "blob",
      compression: "DEFLATE",
      compressionOptions: { level: 6 },
    });

    triggerDownload(blob, `${base}_all_languages.zip`);
    setStatus(`تم تنزيل ${files.length} لغة في ملف مضغوط ✓`);
  } catch (err) {
    setStatus(`فشل إنشاء الملف المضغوط: ${err.message}`);
  } finally {
    updateActionButtons();
  }
});

translateAllBtn.addEventListener("click", () => handleTranslateAll());

// ─── Init ─────────────────────────────────────────────────────────────────────
initLanguageButtons();
selectedLanguageText.textContent = "لا يوجد";
updateActionButtons();
