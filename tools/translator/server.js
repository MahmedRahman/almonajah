const express = require("express");
const path = require("path");
require("dotenv").config();

const app = express();
const PORT = process.env.PORT || 3000;
const HOST = process.env.HOST || "127.0.0.1";
const DEEPSEEK_API_URL = "https://api.deepseek.com/chat/completions";

app.use(express.json({ limit: "1mb" }));
app.use(express.static(path.join(__dirname, "public")));

function parseTranslatedContent(rawContent) {
  if (typeof rawContent !== "string") {
    throw new Error("Invalid model response format.");
  }

  const cleaned = rawContent
    .trim()
    .replace(/^```(?:json)?/i, "")
    .replace(/```$/, "")
    .trim();

  try {
    const parsed = JSON.parse(cleaned);
    if (Array.isArray(parsed?.translations)) {
      return parsed.translations;
    }
    if (Array.isArray(parsed?.items)) {
      return parsed.items;
    }
    return parsed;
  } catch (_error) {
    const firstBracket = cleaned.indexOf("[");
    const lastBracket = cleaned.lastIndexOf("]");
    if (firstBracket !== -1 && lastBracket !== -1 && lastBracket > firstBracket) {
      return JSON.parse(cleaned.slice(firstBracket, lastBracket + 1));
    }

    const firstBrace = cleaned.indexOf("{");
    const lastBrace = cleaned.lastIndexOf("}");
    if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
      const parsedObject = JSON.parse(cleaned.slice(firstBrace, lastBrace + 1));
      if (Array.isArray(parsedObject?.items)) {
        return parsedObject.items;
      }
      if (Array.isArray(parsedObject?.translations)) {
        return parsedObject.translations;
      }
      return parsedObject;
    }

    throw new Error("Model returned non-JSON content.");
  }
}

/**
 * Convert model payload into a string array aligned by id when possible.
 * Missing slots become null so the caller can refill them.
 */
function normalizeModelItems(items, expectedLength) {
  if (!Array.isArray(items)) {
    throw new Error("Model response is not a JSON array.");
  }

  if (items.length === 0) {
    return expectedLength === 0 ? [] : null;
  }

  // Plain string array
  if (items.every((item) => typeof item === "string")) {
    if (items.length === expectedLength) {
      return items;
    }
    // Too many → trim; too few → mark incomplete with nulls so recursive path can fix
    if (items.length > expectedLength) {
      return items.slice(0, expectedLength);
    }
    return [...items, ...Array(expectedLength - items.length).fill(null)];
  }

  // {id, text} objects — align by id (most robust against dropped items)
  if (
    items.every(
      (item) =>
        item &&
        typeof item === "object" &&
        Number.isInteger(item.id) &&
        typeof item.text === "string"
    )
  ) {
    const byId = new Map();
    for (const item of items) {
      if (item.id >= 0 && item.id < expectedLength && !byId.has(item.id)) {
        byId.set(item.id, item.text);
      }
    }
    const out = [];
    for (let i = 0; i < expectedLength; i += 1) {
      out.push(byId.has(i) ? byId.get(i) : null);
    }
    return out;
  }

  throw new Error("Model array format is invalid. Expected strings or {id,text} objects.");
}

async function requestTranslation(prompt, apiKey, { temperature = 0.1 } = {}) {
  const response = await fetch(DEEPSEEK_API_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${apiKey}`,
    },
    body: JSON.stringify({
      model: "deepseek-chat",
      temperature,
      messages: [
        {
          role: "system",
          content:
            "You are a precise subtitle translation engine. " +
            "Return strict JSON only. Never merge, drop, or invent subtitle lines. " +
            "Output length must equal input length with matching ids.",
        },
        {
          role: "user",
          content: prompt,
        },
      ],
      response_format: { type: "json_object" },
    }),
  });

  const data = await response.json();
  if (!response.ok) {
    const message = data?.error?.message || "DeepSeek request failed.";
    const requestError = new Error(message);
    requestError.statusCode = response.status;
    throw requestError;
  }

  return data?.choices?.[0]?.message?.content;
}

function buildPrompt(indexedLines, targetLanguage, { strictCount = false } = {}) {
  const n = indexedLines.length;
  const rules = [
    `Translate each Arabic subtitle line to natural ${targetLanguage}.`,
    "Return JSON ONLY with key translations containing an array of objects.",
    'Output schema: {"translations":[{"id":0,"text":"..."},{"id":1,"text":"..."}]}',
    "Rules:",
    "- Keep the exact same ids as input (0-based contiguous).",
    `- CRITICAL: translations.length MUST be exactly ${n}.`,
    "- Do not merge, split, skip, or add lines.",
    "- Keep empty lines empty (return empty string).",
    `- Input count to preserve: ${n}`,
  ];

  if (strictCount) {
    rules.push(
      `- Your previous attempt had a wrong item count. This time return EXACTLY ${n} objects with ids 0..${n - 1}.`
    );
  }

  rules.push("", JSON.stringify(indexedLines));
  return rules.join("\n");
}

/**
 * One model call for a batch. Returns string[] of length expected (null gaps allowed from normalize).
 */
async function translateBatchOnce(lines, targetLanguage, apiKey, strictCount) {
  const indexedLines = lines.map((text, index) => ({ id: index, text }));
  const prompt = buildPrompt(indexedLines, targetLanguage, { strictCount });
  const content = await requestTranslation(prompt, apiKey, {
    temperature: strictCount ? 0 : 0.1,
  });
  const parsed = parseTranslatedContent(content);
  return normalizeModelItems(parsed, lines.length);
}

/**
 * Guarantee exact line count: align-by-id → refill gaps → split batch → single line.
 */
async function translateLinesGuaranteed(lines, targetLanguage, apiKey, depth = 0) {
  if (lines.length === 0) {
    return [];
  }

  // Ultimate fallback: translate line-by-line
  if (lines.length === 1 || depth >= 4) {
    const out = [];
    for (let i = 0; i < lines.length; i += 1) {
      if (lines[i].trim() === "") {
        out.push("");
        continue;
      }
      let text = null;
      for (let attempt = 0; attempt < 3 && text == null; attempt += 1) {
        const batch = await translateBatchOnce([lines[i]], targetLanguage, apiKey, attempt > 0);
        if (Array.isArray(batch) && typeof batch[0] === "string") {
          text = batch[0];
        }
      }
      if (text == null) {
        throw new Error(`Failed to translate line after retries: "${lines[i].slice(0, 40)}"`);
      }
      out.push(text);
    }
    return out;
  }

  // Attempt full batch (normal + strict)
  let partial = null;
  for (let attempt = 0; attempt < 2; attempt += 1) {
    try {
      const batch = await translateBatchOnce(lines, targetLanguage, apiKey, attempt > 0);
      if (!Array.isArray(batch) || batch.length !== lines.length) {
        continue;
      }
      if (batch.every((t) => typeof t === "string")) {
        return batch;
      }
      // Partial success with null gaps
      if (batch.some((t) => typeof t === "string")) {
        partial = batch;
        break;
      }
    } catch (error) {
      if (error.statusCode) {
        throw error;
      }
      // parse errors → retry / split
    }
  }

  // Fill any null gaps individually while keeping good items
  if (Array.isArray(partial)) {
    const filled = [...partial];
    const missingIndexes = [];
    for (let i = 0; i < filled.length; i += 1) {
      if (typeof filled[i] !== "string") {
        missingIndexes.push(i);
      }
    }
    if (missingIndexes.length === 0) {
      return filled;
    }
    if (missingIndexes.length <= Math.ceil(lines.length / 2)) {
      for (const idx of missingIndexes) {
        const [one] = await translateLinesGuaranteed([lines[idx]], targetLanguage, apiKey, depth + 1);
        filled[idx] = one;
      }
      if (filled.every((t) => typeof t === "string")) {
        return filled;
      }
    }
  }

  // Split in half and recurse
  const mid = Math.ceil(lines.length / 2);
  const left = await translateLinesGuaranteed(
    lines.slice(0, mid),
    targetLanguage,
    apiKey,
    depth + 1
  );
  const right = await translateLinesGuaranteed(
    lines.slice(mid),
    targetLanguage,
    apiKey,
    depth + 1
  );
  return [...left, ...right];
}

app.post("/translate-lines", async (req, res) => {
  try {
    const { lines, targetLanguage } = req.body || {};

    if (!Array.isArray(lines) || lines.length === 0) {
      return res.status(400).json({ error: "Request must include a non-empty lines array." });
    }

    if (lines.some((line) => typeof line !== "string")) {
      return res.status(400).json({ error: "All lines must be strings." });
    }

    if (lines.length > 80) {
      return res.status(400).json({
        error: "Too many lines in one request (max 80). Split the file into smaller sections.",
      });
    }

    if (targetLanguage !== undefined && typeof targetLanguage !== "string") {
      return res.status(400).json({ error: "targetLanguage must be a string." });
    }

    const normalizedTargetLanguage = (targetLanguage || "English").trim();
    if (!normalizedTargetLanguage) {
      return res.status(400).json({ error: "targetLanguage cannot be empty." });
    }

    if (!process.env.DEEPSEEK_API_KEY) {
      return res.status(500).json({
        error: "DEEPSEEK_API_KEY is missing. Add it to .env before translating.",
      });
    }

    const translated = await translateLinesGuaranteed(
      lines,
      normalizedTargetLanguage,
      process.env.DEEPSEEK_API_KEY
    );

    if (!Array.isArray(translated) || translated.length !== lines.length) {
      return res.status(502).json({
        error:
          `Could not guarantee line count after repairs ` +
          `(got ${translated?.length ?? 0}, expected ${lines.length}). Please retry.`,
      });
    }

    if (translated.some((line) => typeof line !== "string")) {
      return res.status(502).json({
        error: "Incomplete translation after repairs. Please retry this section.",
      });
    }

    return res.json({ translated });
  } catch (error) {
    if (error.statusCode) {
      return res.status(error.statusCode).json({ error: error.message });
    }
    return res.status(500).json({ error: error.message || "Unexpected server error." });
  }
});

app.use((_req, res) => {
  res.sendFile(path.join(__dirname, "public", "index.html"));
});

app.listen(PORT, HOST, () => {
  console.log(`SRT translator running on http://${HOST}:${PORT}`);
});
