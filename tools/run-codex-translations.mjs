import fs from 'node:fs';
import path from 'node:path';
import {execFileSync} from 'node:child_process';

const root = path.resolve(import.meta.dirname, '..');
const sourcePath = path.join(root, 'tmp-translation-source.json');
const progressPath = path.join(root, 'tmp-translation-progress.json');
const outputPath = path.join(root, 'tmp-generated-translations.json');
const outFile = path.join(root, 'tmp-codex-translation.out.json');

const languageNames = {
  hi: 'Hindi',
  id: 'Indonesian',
  it: 'Italian',
  ja: 'Japanese',
  ms: 'Malay',
  fa: 'Persian',
  pl: 'Polish',
  pt: 'Portuguese',
  ru: 'Russian',
};

const locales = process.argv.slice(2);
const requestedLocales = locales.length ? locales : Object.keys(languageNames);
const batchSize = Number(process.env.DIASTOLES_CODEX_BATCH || 48);

function readJson(file, fallback) {
  return fs.existsSync(file) ? JSON.parse(fs.readFileSync(file, 'utf8')) : fallback;
}

function writeJson(file, value) {
  fs.writeFileSync(file, JSON.stringify(value, null, 2) + '\n');
}

function placeholders(value) {
  return [...String(value).matchAll(/\{[a-z_]+\}/g)].map((match) => match[0]).sort().join('|');
}

function itemsFromSource(source) {
  const items = [];
  for (const [key, text] of Object.entries(source.interface || {})) {
    if (text) items.push({ id: `i:${key}`, text, type: 'interface', key });
  }
  let questionIndex = 0;
  for (const [prompt, question] of Object.entries(source.questions || {})) {
    for (const field of ['prompt', 'short_label', 'followup']) {
      if (question[field]) {
        items.push({ id: `q:${questionIndex}:${field}`, text: question[field], type: 'question', prompt, field });
      }
    }
    questionIndex += 1;
  }
  return items;
}

function buildCatalog(source, progress, locale) {
  const result = { interface: {}, questions: {} };
  for (const [key, original] of Object.entries(source.interface || {})) {
    result.interface[key] = original ? progress[locale]?.[`i:${key}`] || '' : '';
  }
  let questionIndex = 0;
  for (const [prompt, question] of Object.entries(source.questions || {})) {
    result.questions[prompt] = {
      prompt: question.prompt ? progress[locale]?.[`q:${questionIndex}:prompt`] || '' : '',
      short_label: question.short_label ? progress[locale]?.[`q:${questionIndex}:short_label`] || '' : '',
      followup: question.followup ? progress[locale]?.[`q:${questionIndex}:followup`] || '' : '',
    };
    questionIndex += 1;
  }
  return result;
}

function validateBatch(itemsById, translations) {
  if (!Array.isArray(translations)) throw new Error('Response does not contain a translations array.');
  for (const translation of translations) {
    const original = itemsById.get(translation.id);
    if (!original) throw new Error(`Unknown id ${translation.id}`);
    if (!translation.text || typeof translation.text !== 'string') throw new Error(`Missing translation text for ${translation.id}`);
    if (placeholders(original.text) !== placeholders(translation.text)) throw new Error(`Placeholder mismatch for ${translation.id}`);
  }
}

function codexTranslate(locale, language, batch) {
  const prompt = `Translate the JSON payload below from English into ${language}.

Return only strict JSON in this exact shape:
{"translations":[{"id":"same id","text":"translation"}]}

Rules:
- Return exactly one item per input item, in the same order.
- Preserve Diástoles, Sístoles, names, URLs, punctuation, line breaks, symbols and every {placeholder} exactly.
- Preserve a warm, concise, poetic tone suitable for an artistic smell-memory experience.
- Do not explain, summarize, censor, embellish or add content.
- Do not use markdown.
- Do not run commands or inspect files.

Payload:
${JSON.stringify({locale, language, items: batch}, null, 2)}
`;

  try {
    execFileSync('codex', [
      'exec',
      '--ephemeral',
      '--sandbox',
      'read-only',
      '-C',
      root,
      '-o',
      outFile,
      '-',
    ], {
      cwd: root,
      input: prompt,
      encoding: 'utf8',
      stdio: ['pipe', 'pipe', 'pipe'],
      timeout: 300000,
    });
  } catch (error) {
    if (error.stdout) process.stderr.write(String(error.stdout));
    if (error.stderr) process.stderr.write(String(error.stderr));
    throw error;
  }

  const raw = fs.readFileSync(outFile, 'utf8').trim();
  return JSON.parse(raw).translations;
}

const source = readJson(sourcePath, {});
const items = itemsFromSource(source);
const itemsById = new Map(items.map((item) => [item.id, item]));
const progress = readJson(progressPath, {});
const existing = readJson(outputPath, {});

for (const locale of requestedLocales) {
  if (!languageNames[locale]) throw new Error(`Unsupported locale ${locale}`);
  progress[locale] ||= {};
  const missing = () => items.filter((item) => !progress[locale][item.id]);
  while (missing().length) {
    const batch = missing().slice(0, batchSize);
    process.stderr.write(`${locale}: translating ${batch.length}; done ${Object.keys(progress[locale]).length}/${items.length}\n`);
    const translations = codexTranslate(locale, languageNames[locale], batch);
    validateBatch(itemsById, translations);
    for (const translation of translations) {
      progress[locale][translation.id] = translation.text;
    }
    existing[locale] = buildCatalog(source, progress, locale);
    writeJson(progressPath, progress);
    writeJson(outputPath, existing);
    process.stderr.write(`${locale}: done ${Object.keys(progress[locale]).length}/${items.length}\n`);
  }
}
