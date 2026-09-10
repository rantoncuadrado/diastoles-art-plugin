# How Diástoles calculates connections

This document explains how the plugin turns anonymous scent responses into possible resonances between participants.

The design principle is simple: the AI may help translate, classify and compare, but it must not invent meaningful participant content. Human words remain the source.

## 1. What is saved for each response

When a participant submits a response, Diástoles stores:

- the original response text;
- the optional explanation text, if any;
- the source language;
- an English translation;
- processing status;
- moderation / visibility flags;
- the stimulus that generated the response;
- the anonymous participant ID.

If Live mode is enabled, a routine AI model also extracts structured analysis.

## 2. Routine analysis

Routine analysis is designed to be cheap and stable. The recommended model is Claude Haiku 4.5.

For each response, the model returns structured JSON such as:

```json
{
  "source_language": "es",
  "translation_en": "The smell of wet earth after the storm",
  "smells": [
    {
      "phrase": "wet earth after the storm",
      "anchors": ["earth", "storm"],
      "semantic_fields": ["earth", "weather", "memory"]
    }
  ],
  "states": ["calm", "nostalgia"],
  "places": ["outdoors"],
  "relationships": [],
  "moments": ["after rain"],
  "emotional_tones": ["nostalgia", "relief"]
}
```

The plugin validates this structure. Unsupported or empty signals are ignored.

## 3. Matices, semantic fields and tones

Diástoles uses three levels of meaning.

### Matices

Matices are concrete scent anchors or scent-adjacent words.

Examples:

- `coffee`
- `smoke`
- `wet earth`
- `chlorine`
- `grandmother's kitchen`

They help preserve the material quality of the response.

### Semantic fields

Semantic fields are broader families.

Examples:

- `food`
- `smoke`
- `body`
- `home`
- `weather`
- `ritual`
- `distance`

They allow two responses to connect even when they do not share the same exact word.

Example:

- Response A: “BBQ smoke”
- Response B: “the smell of burnt wood in winter”

They do not share the exact phrase, but they may share fields such as `smoke`, `fire`, `heat` or `memory`.

### Emotional tones

Emotional tones are ordinary affective signals.

Examples:

- `longing`
- `comfort`
- `discomfort`
- `intimacy`
- `loss`
- `anticipation`

They let the system notice that two different smells may carry a similar inner weather.

## 4. Candidate preselection

The plugin does not send every response to the final AI ranking step. That would be expensive and would often produce obvious or noisy matches.

Instead, it builds a candidate pool with local SQL/PHP scoring.

Default signals include:

- exact shared matices;
- shared semantic fields;
- shared emotional tones;
- same or related question theme;
- profile overlap or contrast, when optional context exists;
- whether the pair has already been connected;
- whether both responses are complete, visible and eligible for the network.

This is the cheap phase. It has no direct AI token cost once the responses have already been analyzed.

## 5. Default prefilter weights

The current matching screen exposes these weights so they can be adjusted without changing code.

Default-style interpretation:

| Signal | Meaning | Typical impact |
| --- | --- | --- |
| Exact matiz overlap | Two responses share a scent anchor, such as `coffee` or `smoke`. | Strong candidate boost. |
| Semantic field overlap | Two responses belong to related fields, such as `food`, `home` or `weather`. | Medium candidate boost. |
| Emotional tone overlap | Two responses carry similar affect, such as `longing` or `comfort`. | Medium candidate boost. |
| Question theme boost | Responses came from the same theme. | Small boost. |
| Different theme adjustment | Responses came from different themes. | Can slightly reduce obvious thematic clustering, or encourage cross-theme matches depending on settings. |
| Profile signal | Optional participant context suggests meaningful overlap or contrast. | Small boost; never required. |
| Poetic / strange boost | Lets rarer, less obvious candidates survive preselection. | Increases unexpected matches and may increase final-ranking cost if more candidates survive. |

The exact numeric values live in `wp-admin → Diástoles → Matching`.

## 6. Final relationship ranking

Only the shortlisted candidates are sent to the stronger connection-ranking model.

The prompt includes:

- the target response;
- a limited number of candidate responses;
- their extracted concepts;
- supported states, places and relationship labels;
- question theme information;
- optional profile tags, if allowed;
- evidence snippets that already exist in the participant text.

The model must return structured JSON with:

- candidate ID;
- relationship type;
- score;
- explanation evidence;
- no invented participant prose.

Relationship types:

- `affinity`: the fragments share a meaningful resonance.
- `tension`: the fragments contradict, disturb or pull against one another.
- `complementarity`: the fragments complete each other from different angles.
- `continuity`: one fragment carries forward an image, place, feeling or time opened by the other.

## 7. Thresholds and moderation

Connections are stored with a score.

The site owner can configure:

- automatic approval threshold;
- manual approval threshold.

Typical behavior:

- score greater than or equal to automatic threshold: approved immediately;
- score between manual and automatic thresholds: available for manual review;
- score below manual threshold: stored as held / below threshold;
- rejected: hidden unless an admin re-approves it.

Changing thresholds does not automatically erase old decisions. Recalculation can be used when the matching logic or extracted concepts have changed.

## 8. What “poetic hybrid” means

The matching system can be tuned to prefer either:

- obvious semantic proximity; or
- stranger, more poetic resonances.

The poetic hybrid approach keeps the factual structure but allows less literal signals to matter.

Example:

- “chlorine after swimming lessons”
- “hospital corridors after visiting my father”

They do not share a scent. A conservative system might miss them. A poetic-hybrid setting might notice:

- sterile / chemical semantic field;
- body memory;
- childhood or vulnerability;
- institutional spaces;
- emotional tension.

The connection still needs evidence in the original words. The AI may classify the relationship, but it cannot invent a new symbolic explanation unsupported by the submitted text.

## 9. Why embeddings are currently disabled

Embeddings would turn each response into a vector: a list of numbers representing semantic proximity.

Example, simplified:

```text
"wet earth after rain"      → [0.18, -0.42, 0.77, 0.03]
"soil after a storm"        → [0.20, -0.39, 0.74, 0.01]
"new plastic packaging"     → [-0.61, 0.10, -0.08, 0.55]
```

The first two vectors are close; the third is far away.

In a future version, embeddings could help find candidates across a large historical archive before using the stronger AI ranking model. For the current expected scale, Diástoles uses extracted concepts and local scoring instead.

## 10. Cost shape

For each new response in Live mode:

1. routine analysis and translations have AI cost;
2. candidate preselection has no direct AI cost;
3. final ranking has AI cost proportional to how many candidates are sent.

Reducing final candidates from 12 to 9 usually cuts final-ranking input tokens by roughly 20–25%, but may miss some weaker or stranger connections.

The matching screen lets the organizer tune this tradeoff.

## 11. Data kept for auditing

Connection records keep:

- relation type;
- score;
- status;
- explanation JSON;
- candidate selection reasons where available;
- timestamps;
- response IDs and anonymous participant IDs.

This makes later review and export possible without exposing real identities.
