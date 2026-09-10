import fs from 'node:fs';
import path from 'node:path';
import {execFileSync} from 'node:child_process';

const root = path.resolve(import.meta.dirname, '..');
const sourcePath = path.join(root, 'tmp-translation-source.json');
const outputPath = path.join(root, 'tmp-generated-translations.json');
const progressPath = path.join(root, 'tmp-translation-progress.json');
if (!fs.existsSync(sourcePath)) {
  const php = `$interface = Diastoles_Texts::all(); global $wpdb; $rows = $wpdb->get_results("SELECT prompt, short_label, followup FROM " . Diastoles_DB::table("questions") . " ORDER BY id", ARRAY_A); $questions = array(); foreach ($rows as $row) { $questions[$row["prompt"]] = $row; } echo wp_json_encode(array("interface" => $interface, "questions" => $questions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);`;
  const raw = execFileSync('docker', ['compose', 'run', '--rm', 'wpcli', 'eval', php], {cwd: root, encoding: 'utf8'});
  const start = raw.indexOf('{');
  if (start < 0) throw new Error('Could not extract the translation source from WordPress.');
  const extracted = JSON.parse(raw.slice(start));
  fs.writeFileSync(sourcePath, JSON.stringify(extracted, null, 2) + '\n');
}
const source = JSON.parse(fs.readFileSync(sourcePath, 'utf8'));
const existing = fs.existsSync(outputPath) ? JSON.parse(fs.readFileSync(outputPath, 'utf8')) : {};
const progress = fs.existsSync(progressPath) ? JSON.parse(fs.readFileSync(progressPath, 'utf8')) : {};

const languageNames = {
  ar: 'Arabic', eu: 'Basque', bn: 'Bengali', ca: 'Catalan', 'zh-cn': 'Simplified Chinese',
  fr: 'French', gl: 'Galician', de: 'German', hi: 'Hindi', id: 'Indonesian', it: 'Italian',
  ja: 'Japanese', ms: 'Malay', fa: 'Persian', pl: 'Polish', pt: 'Portuguese', ru: 'Russian', es: 'Spanish',
};

const requested = process.argv.slice(2);
const locales = requested.length ? requested : Object.keys(languageNames);
const batchSize = 12;
const maxAttempts = 3;

const items = [];
for (const [key, text] of Object.entries(source.interface)) if (text) items.push({id: `i:${key}`, text});
let questionIndex = 0;
for (const [prompt, question] of Object.entries(source.questions)) {
  for (const field of ['prompt', 'short_label', 'followup']) {
    if (question[field]) items.push({id: `q:${questionIndex}:${field}`, text: question[field], sourcePrompt: prompt, field});
  }
  questionIndex += 1;
}

const outputSchema = {
  type: 'object',
  properties: {
    translations: {
      type: 'array',
      items: {
        type: 'object',
        properties: {id: {type: 'string'}, text: {type: 'string'}},
        required: ['id', 'text'],
        additionalProperties: false,
      },
    },
  },
  required: ['translations'],
  additionalProperties: false,
};

function placeholders(value) {
  return [...String(value).matchAll(/\{[a-z_]+\}/g)].map((match) => match[0]).sort();
}

function validate(locale, result) {
  if (!result?.interface || !result?.questions) throw new Error(`${locale}: missing result sections`);
  for (const [key, original] of Object.entries(source.interface)) {
	if (!original && result.interface[key] !== '') throw new Error(`${locale}: invented interface.${key}`);
	if (original && (typeof result.interface[key] !== 'string' || !result.interface[key].trim())) throw new Error(`${locale}: missing interface.${key}`);
    if (placeholders(original).join('|') !== placeholders(result.interface[key]).join('|')) throw new Error(`${locale}: placeholders changed in ${key}`);
  }
  for (const [prompt, question] of Object.entries(source.questions)) {
    const translated = result.questions[prompt];
    if (!translated || typeof translated.prompt !== 'string' || !translated.prompt.trim()) throw new Error(`${locale}: missing question ${prompt}`);
    for (const field of ['short_label', 'followup']) {
      if (question[field] && (typeof translated[field] !== 'string' || !translated[field].trim())) throw new Error(`${locale}: missing ${field} for ${prompt}`);
      if (!question[field] && translated[field] !== '') throw new Error(`${locale}: invented ${field} for ${prompt}`);
    }
  }
}

async function translateBatch(locale, target, batch) {
  const prompt = `Translate every text value in the input array faithfully from English into ${target} for an artistic experience about smell, memory and human connection.

Return only {"translations":[{"id":"same id","text":"translation"},...]}. Return exactly one item for every input id, in the same order.
Preserve Diástoles, Sístoles, names, URLs, symbols, punctuation, line breaks and every {placeholder} exactly. Preserve poetic ambiguity and a warm concise tone. Never explain, summarize, censor, embellish or add content. Use standard contemporary ${target} and formal-neutral address where relevant. Never translate ids.

INPUT:
${JSON.stringify(batch)}`;
  let lastError;
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      const response = await fetch('http://127.0.0.1:11434/api/chat', {
        method: 'POST', headers: {'content-type': 'application/json'},
        body: JSON.stringify({
          model: 'gpt-oss:20b', messages: [{role: 'user', content: prompt}], stream: false,
          think: 'low', format: outputSchema,
          options: {temperature: 0.05, num_ctx: 32768, num_predict: 6000},
        }),
      });
      if (!response.ok) throw new Error(`Ollama ${response.status} ${await response.text()}`);
      const payload = await response.json();
      const content = payload.message?.content || '';
      if (!content) throw new Error(`Ollama returned no final JSON (${JSON.stringify({done_reason: payload.done_reason, eval_count: payload.eval_count, thinking_length: payload.message?.thinking?.length || 0})})`);
      const translatedBatch = JSON.parse(content).translations || [];
      if (translatedBatch.length !== batch.length) throw new Error(`expected ${batch.length} translations, received ${translatedBatch.length}`);
      for (let index = 0; index < batch.length; index++) {
        const expected = batch[index];
        const translated = translatedBatch[index];
        if (translated.id !== expected.id || !translated.text?.trim()) throw new Error(`invalid translation for ${expected.id}`);
        if (placeholders(expected.text).join('|') !== placeholders(translated.text).join('|')) throw new Error(`placeholders changed in ${expected.id}`);
      }
      return translatedBatch;
    } catch (error) {
      lastError = error;
      process.stderr.write(`${locale}: batch retry ${attempt}/${maxAttempts}: ${error.message}\n`);
    }
  }
  if (batch.length > 1) {
    const middle = Math.ceil(batch.length / 2);
    process.stderr.write(`${locale}: splitting failed batch ${batch.length} into ${middle}+${batch.length - middle}\n`);
    return [
      ...await translateBatch(locale, target, batch.slice(0, middle)),
      ...await translateBatch(locale, target, batch.slice(middle)),
    ];
  }
  throw new Error(`${locale}: item failed after ${maxAttempts} attempts: ${lastError.message}`);
}

async function generate(locale) {
  if (existing[locale]) {
    validate(locale, existing[locale]);
    process.stdout.write(`${locale}: already complete\n`);
    return;
  }
  const target = languageNames[locale];
  progress[locale] ||= {};
  const missing = items.filter((item) => !progress[locale][item.id]);
  for (let offset = 0; offset < missing.length; offset += batchSize) {
    const batch = missing.slice(offset, offset + batchSize).map(({id, text}) => ({id, text}));
    const translatedBatch = await translateBatch(locale, target, batch);
    for (let index = 0; index < batch.length; index++) {
      const expected = batch[index];
      const translated = translatedBatch[index];
      if (translated.id !== expected.id || !translated.text?.trim()) throw new Error(`${locale}: invalid translation for ${expected.id}`);
      if (placeholders(expected.text).join('|') !== placeholders(translated.text).join('|')) throw new Error(`${locale}: placeholders changed in ${expected.id}`);
      progress[locale][expected.id] = translated.text;
    }
    fs.writeFileSync(progressPath, JSON.stringify(progress, null, 2) + '\n');
    process.stdout.write(`${locale}: ${Math.min(offset + batch.length, missing.length)}/${missing.length}\n`);
  }
  const result = {interface: {}, questions: {}};
  for (const [key, text] of Object.entries(source.interface)) result.interface[key] = text ? progress[locale][`i:${key}`] : '';
  questionIndex = 0;
  for (const [sourcePrompt, question] of Object.entries(source.questions)) {
    result.questions[sourcePrompt] = {};
    for (const field of ['prompt', 'short_label', 'followup']) {
      result.questions[sourcePrompt][field] = question[field] ? progress[locale][`q:${questionIndex}:${field}`] : '';
    }
    questionIndex += 1;
  }
  validate(locale, result);
  existing[locale] = result;
  fs.writeFileSync(outputPath, JSON.stringify(existing, null, 2) + '\n');
  process.stdout.write(`${locale}: complete\n`);
}

for (const locale of locales) await generate(locale);
