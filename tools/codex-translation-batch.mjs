import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const sourcePath = path.join(root, 'tmp-translation-source.json');
const progressPath = path.join(root, 'tmp-translation-progress.json');

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

function itemsFromSource(source) {
  const items = [];
  for (const [key, text] of Object.entries(source.interface || {})) {
    if (text) items.push({ id: `i:${key}`, text });
  }
  let questionIndex = 0;
  for (const [prompt, question] of Object.entries(source.questions || {})) {
    for (const field of ['prompt', 'short_label', 'followup']) {
      if (question[field]) {
        items.push({ id: `q:${questionIndex}:${field}`, text: question[field] });
      }
    }
    questionIndex += 1;
  }
  return items;
}

function placeholders(value) {
  return [...String(value).matchAll(/\{[a-z_]+\}/g)].map((match) => match[0]).sort().join('|');
}

function readJson(file, fallback) {
  return fs.existsSync(file) ? JSON.parse(fs.readFileSync(file, 'utf8')) : fallback;
}

function writeJson(file, value) {
  fs.writeFileSync(file, JSON.stringify(value, null, 2) + '\n');
}

const [command, locale, rawLimit, rawInput] = process.argv.slice(2);
if (!command || !locale || !languageNames[locale]) {
  console.error('Usage: node tools/codex-translation-batch.mjs prepare|merge <locale> [limit|input-file] [input-file]');
  process.exit(1);
}

const source = readJson(sourcePath, {});
const progress = readJson(progressPath, {});
const items = itemsFromSource(source);
progress[locale] ||= {};

if (command === 'prepare') {
  const limit = Number(rawLimit || 30);
  const batch = items.filter((item) => !progress[locale][item.id]).slice(0, limit);
  const payload = {
    locale,
    language: languageNames[locale],
    count: batch.length,
    items: batch,
    instructions: [
      `Translate each text from English into ${languageNames[locale]}.`,
      'Return strict JSON only: {"translations":[{"id":"same id","text":"translation"}]}',
      'Return exactly one item per input item, in the same order.',
      'Preserve Diástoles, Sístoles, names, URLs, punctuation, line breaks, symbols and every {placeholder} exactly.',
      'Do not explain, summarize, censor, embellish or add content.',
    ],
  };
  const serialized = JSON.stringify(payload, null, 2) + '\n';
  if (rawInput) {
    fs.writeFileSync(rawInput, serialized);
    console.log(`${locale}: prepared ${batch.length} items in ${rawInput}`);
  } else {
    process.stdout.write(serialized);
  }
  process.exit(0);
}

if (command === 'merge') {
  const inputFile = rawInput || rawLimit;
  if (!inputFile || !fs.existsSync(inputFile)) {
    console.error('Missing translation JSON file.');
    process.exit(1);
  }
  const payload = readJson(inputFile, {});
  const batchById = new Map(items.map((item) => [item.id, item]));
  const translations = payload.translations || [];
  for (const translation of translations) {
    const original = batchById.get(translation.id);
    if (!original) throw new Error(`Unknown id ${translation.id}`);
    if (!translation.text || typeof translation.text !== 'string') throw new Error(`Missing text for ${translation.id}`);
    if (placeholders(original.text) !== placeholders(translation.text)) throw new Error(`Placeholder mismatch for ${translation.id}`);
    progress[locale][translation.id] = translation.text;
  }
  writeJson(progressPath, progress);
  console.log(`${locale}: merged ${translations.length}; total ${Object.keys(progress[locale]).length}/${items.length}`);
  process.exit(0);
}

console.error(`Unknown command: ${command}`);
process.exit(1);
