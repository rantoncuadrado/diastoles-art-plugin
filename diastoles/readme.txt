=== Diástoles ===
Contributors: diastoles
Tags: art, multilingual, community, ai
Requires at least: 6.5
Requires PHP: 8.0
Stable tag: 0.15.50
License: GPLv2 or later

An anonymous multilingual experience that connects human memories through smell.

== Installation ==

1. Upload and activate the plugin.
2. Create a page containing [diastoles_experience].
3. Open Diastoles > AI settings.
4. Test in Mock mode or configure an Anthropic API key and switch to Live.
5. Choose the active languages under Diastoles > Interface texts > Languages.
6. Review or edit bundled interface copy under Interface texts and bundled question copy under Questions. Participants may answer in any language.

== Privacy ==

The plugin does not require names, phone numbers or email addresses. Participants
may optionally provide an email solely to receive one publication notice; it is
excluded from portable exports and never sent to Anthropic. In Live mode,
response text is sent to Anthropic for translation, classification and connection
ranking. Site owners are responsible for publishing an appropriate privacy notice
and retention policy.

== Changelog ==

= 0.15.50 =
* Add an admin Dynamic Translations screen with filters for matices/anchors, concepts, responses, explanations and other inferred kinds.
* Allow editing and retrying dynamic translations from wp-admin.
* Queue semantic field translations along with response concepts and matices.

= 0.15.49 =
* Show optional explanations in the admin Responses list and detail view.
* Add editable per-language explanation translations in response detail.

= 0.15.48 =
* Show optional explanations as part of the public voice while keeping response and explanation structurally separate.
* Translate optional explanations independently and use compact combined text in map tooltips.

= 0.15.47 =
* Keep optional explanations out of response translations shown in participant recaps.

= 0.15.46 =
* Sort the public language selector with the current language first and remaining languages by native name.

= 0.15.45 =
* Save participant responses immediately and process AI analysis in the background.
* Queue response translations instead of blocking the public submit request.

= 0.15.44 =
* Add a participant detail view with optional profile context and all participant responses.
* Add Activity Log cleanup tools for deleting all logs or logs within a selected date range.

= 0.15.43 =
* Auto-fill native language from the selected interface language for new participant optional context.
* Paginate admin Participants by 50 rows with filters for pseudonym/context, response count and response dates.
* Add admin participant deletion that removes the participant, their responses and related connections.

= 0.15.42 =
* Paginate admin Connections tabs by 25 items per page.
* Add score sorting to admin Connections filters.
* Pretranslate concept anchors/matices along with concept phrases for participant responses.

= 0.15.41 =
* Add detailed examples, result impact and AI-cost guidance to Matching settings.
* Add Matching presets for balanced, conservative and strange/poetic matching configurations.

= 0.15.40 =
* Add Matching settings for candidate search limits, prefilter weights and theme score adjustments.
* Expand candidate search from recent-only matching to a configurable historical candidate pool.
* Add emotional tones to response analysis and prefilter scoring.
* Store candidate selection reasons and buckets in connection explanation data.
* Add a local semantic vector option for cheap pre-ranking before final AI connection ranking.

= 0.15.39 =
* Split AI configuration into a routine processing model and a final connection ranking model.
* Keep embeddings disabled but documented in AI settings for future semantic candidate search.

= 0.15.38 =
* Fix opening trigger-cloud cards in the collective maps view.

= 0.15.37 =
* Remove evidence/reason text from map relationship hover tooltips.

= 0.15.36 =
* Keep participants on the welcome or private-link screen when they change interface language.

= 0.15.35 =
* Highlight the consent notice when participants try to enter without accepting it.
* Improve private-link bookmark guidance across desktop and mobile browsers.
* Add a downloadable private-link shortcut for desktop browsers.

= 0.15.34 =
* Improve connection cards by showing question theme before each question.
* Show response translations inline inside the quoted response text.
* Moderate connection cards asynchronously in admin without reloading the page.

= 0.15.33 =
* Show the originating question and theme for each side of a connection in admin moderation.
* Add admin filters for connection relationship type, question theme, score range and free-text search.

= 0.15.32 =
* Add connection status tabs for pending, approved, rejected and held suggestions.
* Allow admins to reject approved connections and approve rejected or held connections.

= 0.15.31 =
* Remove unsupported JSON schema minItems constraints from Anthropic relationship ranking requests.

= 0.15.30 =
* Fix ZIP export after making the network endpoint request-aware.

= 0.15.29 =
* Add a custom date-range selector for recalculation jobs.

= 0.15.28 =
* Allow multiple connections between the same two anonymous participants when they come from different response pairs.
* Record processing-history notes when candidate matching finds no eligible candidates or no valid AI-ranked connections.

= 0.15.27 =
* Disable default Claude Sonnet 5 thinking for structured analysis requests so responses return the expected JSON text block.

= 0.15.26 =
* Show an explicit diagnostic message when a failed response has no stored processing error.
* Add processing history to the admin response detail screen.
* Add immediate admin button feedback while retry/translation forms submit.
* Hide failed responses from the public network automatically.
* Store richer Anthropic failure details for future debugging.

= 0.15.25 =
* Show participant-facing response statuses as friendly translated labels.
* Show exact latest processing errors in the admin Responses screen.
* Add a Retry processing action for failed/pending responses.

= 0.15.24 =
* Add exportable activity logging for key participant interactions.
* Count skipped stimuli per question.
* Rename concept anchors to nuances/matices and localize scent nuance labels.
* Translate Your Sillage labels in the Simplified Chinese interface.

= 0.15.23 =
* Rename the Relationship Layers intensity control from relationship intensity to affinity intensity.

= 0.15.22 =
* Refine Your Notes and Affinities empty-state headings around trace and kindred scent language.

= 0.15.21 =
* Shift public wording from questions/neighbourhoods to sparks, triggers and trigger clouds; update Collective Maps heading to “A constellation of scents”.

= 0.15.20 =
* Rename the Narrative Walk map variant to Scent Trail / Itinerario Olfativo and align trail continuation copy.

= 0.15.19 =
* Rename the Collective Maps “Scents” variant to Kindred Scents / Aromas Afines across bundled interface languages.

= 0.15.18 =
* Make Collective Maps horizontal-scroll cues dynamic: right cue appears only when more content exists to the right, and left cue appears after scrolling.

= 0.15.17 =
* Rename the public shared-fragments area to Atmosphere, with “The shared atmosphere” and “Notes released into the air” copy across bundled interface languages.

= 0.15.16 =
* Group personal notes and affinities under Your Sillage, and add a visible horizontal-scroll cue to Collective Maps variants on small screens.

= 0.15.15 =
* Improve mobile navigation so all experience tabs are visible without horizontal scrolling.

= 0.15.14 =
* Show paginated externally connected responses inside Question Neighbourhoods, after the internal relationship section.

= 0.15.13 =
* Refine Question Neighbourhoods with internal/external relationship counts, linked external neighbourhoods, exact-question relationship mode, response pagination and recent relationship ordering.

= 0.15.12 =
* Clarify that translation-only recalculation skips completed translations and only fills missing, pending, failed or newly detected concept translations.

= 0.15.11 =
* Add a translation-only recalculation job for existing participant responses and scent concepts without deleting relationships or retranslating completed translations.
* Automatically fill missing active-language translations when saving questions in Live mode.
* Show cross-theme neighbourhood connection counts even when a theme has no internal relationships.

= 0.15.10 =
* Show per-theme counts of approved relationships from an open theme neighbourhood to other question themes.
* Keep external theme counts hidden when Question neighbourhoods is grouped by exact question.

= 0.15.9 =
* Add an admin Responses screen with response detail, translation status, manual translation edits, retries and response withdrawal.
* Hold locally detected offensive, hateful or violent responses for manual review before analysis or public display.
* Process normal responses immediately after submission and pretranslate participant responses/concepts into active languages.
* Requeue stale pending or failed dynamic translations instead of leaving them stuck indefinitely.
* Localize resonance relation labels, narrative walk controls and map scent labels.
* Remove extra map instructions/alternative headers and refine map tab, layer and scent-chip styling.

= 0.15.8 =
* Restore the authenticated shell overflow behaviour so the page does not shift upward.
* Stop auto-scrolling after loading more Atmosphere fragments; new batches are appended in place without moving the whole viewport.

= 0.15.7 =
* Scroll newly loaded Atmosphere and article batches with contextual top spacing instead of pinning them to the viewport edge.
* Allow the authenticated shell to grow vertically while still preventing horizontal overflow.

= 0.15.6 =
* Keep Atmosphere load-more scoped to the active section while appending a new batch.
* Add a Question Neighbourhoods checkbox to switch between theme neighbourhoods and exact-question groups.
* Scope neighbourhood relationships to the selected grouping mode and keep them ordered by score.

= 0.15.5 =
* Add separate Anthropic model selectors for participant-response translation and semantic analysis/connections.
* Default translations to Claude Haiku 4.5 and analysis/connections to Claude Sonnet 5.
* Offer Claude Opus 4.8 as a higher-cost option without selecting it by default.

= 0.15.4 =
* Simplify Relationship Layers by removing the scent search/select toolbar and long map instructions.
* Localize the Relationship Layers intro, scent reset chip and Fit Space control.
* Rename the graph reset control from Fit All to Fit Space.

= 0.15.3 =
* Keep the viewport positioned at the first newly added Atmosphere batch after loading more.
* Translate the Question neighbourhoods load-more button and related neighbourhood labels.
* Group Question neighbourhoods by question theme, not exact question text.
* Show strongest theme-neighbourhood relationships ordered by connection score in groups of 12.

= 0.15.2 =
* Move the photographic background into a persistent viewport layer so expanding profile details or changing content height cannot recrop it.
* Keep the authenticated app shell stable while switching tabs, updating only the active main content.
* Append additional Atmosphere batches with JavaScript instead of rebuilding the tab.
* Hide translation disclosures when the translated text is identical to the original fragment.

= 0.15.1 =
* Bundle prefilled translations for all 18 non-English languages, covering interface copy and the question library.
* Seed bundled question translations without overwriting later manual edits.
* Let manual admin edits override bundled interface translations while keeping completeness indicators at 100%.

= 0.15.0 =
* Replace browser-side Google translation with native origin-rendered language catalogs and a first-party language selector.
* Add 19 configurable languages, including right-to-left rendering for Arabic and Persian.
* Add manual interface translation editing with English reference copy and completeness indicators.
* Add manual question, follow-up and map-label translations plus multilingual CSV import and export.
* Add an asynchronous Anthropic-backed cache for participant-content translations while preserving original words.
* Keep the private state endpoint uncached and localize questions before they reach the browser.

= 0.14.9 =
* Center the public Diástoles credit and privacy link.
* Give the Privacy Notice a dedicated Diástoles template without the site theme's tagline, copyright, placeholder email or WordPress credit.
* Reserve a stable scrollbar gutter and use the small viewport height so layout changes cannot alter the background crop.

= 0.14.8 =
* Preload and paint the initial photograph and Diástoles wordmark before the application and translation services start.
* Never reveal English source content while a remembered non-English interface is still being translated.
* Anchor the photographic background to the viewport so translated layout changes cannot resize or recrop it.
* Remove the background scale animation and decode later photographs before swapping them into view.
* Preconnect to the Google Translate origins to reduce cold-start translation latency.

= 0.14.7 =
* Restore the lightweight 0.11.1 anti-flash behaviour: hide only newly rendered interface elements while keeping the photographic background visible.
* Reveal translated content after a 90 ms settling window and fail open after 1.2 seconds instead of blocking the full page behind a loader.
* Remove the standalone whole-page translation blocker.

= 0.14.6 =
* Restore GTranslate's faster native DOM observation instead of re-clicking the already active language after every render.
* Remove the artificial post-translation settling delay and reveal confirmed translations immediately.

= 0.14.5 =
* Replace the blank translation wait with an immediate branded Diástoles transition.
* Reveal completed translations sooner while continuing to suppress the English first paint.

= 0.14.4 =
* Keep translated screens hidden at the parent level until translated content is detected and stable.
* Prevent an English first paint on the standalone Privacy Notice page.

= 0.14.3 =
* Extend the public credit with linked attribution for Paco Santamaría, Raúl Antón Cuadrado and Sístoles.

= 0.14.2 =
* Automatically publish a concise Diástoles Privacy Notice page without overwriting later edits.
* Link the notice from the required entry consent and from every public experience footer.
* Identify the project controllers, service providers, retention criteria and participant rights.

= 0.14.1 =
* Require explicit participation consent and keep the entry button disabled until it is granted.
* Move the optional publication email into the collapsed profile details and clarify the privacy notice.
* Add the Diástoles header to entry and recovery screens, simplify the recovery copy and move Skip beside the language note.
* Prevent untranslated interface flashes while GTranslate processes a newly rendered screen.
* Keep complete scent phrases in Narrative Walk and simplify the Relationship Layers scent selector.

= 0.14.0 =
* Unify public article cards in responsive two-column grids and paginate every article list in groups of 12.
* Preserve original response text and reveal Translation only when its language differs from the selected interface language.
* Keep scent and question exploration inside Narrative Walk, using shared anchors or exact questions to create new steps.
* Let Relationship Layers select up to 30 scents simultaneously through search buttons or a multiple selector.
* Rename public navigation to Your Trace, Atmosphere, Collective Maps and Narrative Walk.

= 0.13.2 =
* Keep every loaded group of 12 shared fragments in its own stable multi-column container.
* Make relationship-layer hover descriptions translatable into the selected interface language.
* Keep concept exploration inside Narrative walk and list every question associated with the selected concept.

= 0.13.1 =
* Rename the global map to Constellation and remove the Map by scents tab.
* Replace the fixed strong-relationship filter with an unnumbered intensity slider from 0.30 to 1.00.
* Show relationship descriptions only when hovering or focusing their layer buttons.
* Align the Atmosphere kicker with the other tabs and reduce public H1 sizes.

= 0.13.0 =
* Move recently shared fragments into their own paginated Atmosphere tab.
* Replace the map laboratory with Global map, Map by scents, Narrative walk, Question neighbourhoods and Relationship layers.
* Reuse the organic scent-cloud layout, navigation and complete relationship explanations across every graph.
* Translate every hover explanation into the participant's selected interface language.
* Hide the Google Translate loading overlay and improve translated fragment and anchor layout.

= 0.12.1 =
* Use complete, consistently translatable relationship explanations across the current and alternative maps.
* Group alternative-map responses into canonical scent clouds.
* Show source and destination questions in the narrative walk.
* Rebuild neighbourhoods around questions and show their responses and any internal connections.
* Make unrelated nodes recede in the search map and remove alternatives 6 and 8.

= 0.12.0 =
* Add a map laboratory with the current collective map and eight switchable navigation alternatives.
* Add atlas controls, connected-scent focus, narrative walks, semantic neighbourhoods, search, a semantic lens, relationship layers and a synchronized carousel.
* Reuse stored network data and connection scores without making additional AI requests.

= 0.11.1 =
* Prevent the English source interface from flashing before GTranslate restores a participant's chosen language.
* Keep the photographic background visible and reveal translated interface content with a short fade.

= 0.11.0 =
* Split question management into Add a question, Question library and Import / Export tabs.
* Add a documented, round-trip CSV export containing every question column.
* Add CSV imports that merge by exact prompt or replace the participant-facing question library while preserving questions referenced by responses.
* Add a configurable manual-review threshold below the automatic-approval threshold.
* Keep lower-scoring connection suggestions stored outside the review queue so lowering the manual threshold can restore them.

= 0.10.25 =
* Preserve the chosen interface language from joining through the private-link screen and into the experience.
* Keep Your traces and the other participant tabs in the chosen language after dynamic renders.
* Place the GTranslate language control on the private-link screen and remember the participant's choice locally.

= 0.10.24 =
* Add the curated love, heartbreak, sport, connection and music question collection without changing existing questions.
* Add new questions about speed, forbidden things and a first kiss, with duplicate-safe one-time importing.

= 0.10.23 =
* Show the GTranslate flag background only while GTranslate's click-controlled flag menu is actually open.

= 0.10.22 =
* Keep the GTranslate flag panel fully hidden until the globe is hovered or focused.

= 0.10.21 =
* Add a temporary opaque panel behind the expanded GTranslate flags so they remain legible over page content.

= 0.10.20 =
* Move the real GTranslate globe inside the welcome section before a participant enters the experience.

= 0.10.19 =
* Keep exactly one GTranslate globe flag marked as the current language.
* Replace the floating flag orbit inside Diástoles with a downward-opening row constrained to the header width.

= 0.10.18 =
* Warn administrators when GTranslate is not active and link directly to its installation search.

= 0.10.17 =
* Increase heading size slightly and constrain Question metadata to the response-column width.
* Refine the fragment guidance, language invitation and collective-map notice hierarchy.
* Move the real GTranslate globe into the Diástoles header while preserving it across interface renders.

= 0.10.16 =
* Use the Question heading scale across all tabs and enlarge the Diástoles header identity.
* Place relationship type before its question on one row and match resonance fragments to Your traces typography.
* Keep original-language and English-translation disclosure rules consistent across Resonances, Your traces and the collective map.
* Put the question theme and daily response count on one line.
* Preserve relationship-tooltip bullet line breaks and add a header language globe synchronized with GTranslate.

= 0.10.15 =
* Bring the two collective-map instructions closer together.
* Simplify the map legend to the four relationship categories in a two-column grid.

= 0.10.14 =
* Keep one named scent source as a single concept when coordinated nouns only describe its metaphors, properties, roles or uses.
* Add the ammonia-as-primordial-soup example to Live classification guidance and Mock-mode regression coverage.

= 0.10.13 =
* Present both question-and-response pairs as separate bullet points in relationship tooltips.
* Keep short relationships inside a shared smell cloud reachable by opening their curves and placing their hit areas above nodes.

= 0.10.12 =
* Show the prompting question before each fragment in Your traces.
* Hide the English translation when the original fragment is already in English.

= 0.10.11 =
* Show the question that prompted a fragment before each relationship in Resonances.

= 0.10.10 =
* Make the language invitation more personal and inclusive.
* Add an optional publication-notification email with purpose-limited copy and validation.
* Keep publication emails out of AI requests and portable research exports.

= 0.10.9 =
* Require states, places and relationships used for matching to carry a validated verbatim participant excerpt.
* Identify response and optional-explanation evidence explicitly in map relationship tooltips.
* Keep reviewed relationships between equal concepts visible inside their shared cloud.
* Adjust relationship scores by +0.12 for a shared question theme and -0.07 for different themes.
* Migrate the original question library to nine grouped themes without changing custom or newly added questions.
* Organize the Questions administration screen into dynamic tabs derived from themes currently in use.

= 0.10.8 =
* Restrict relationship evidence to literal concepts and ordinary semantic fields instead of inferred emotional associations.
* Add a deterministic score bonus for matching question themes and a penalty when themes differ.
* Correct multi-concept relationship indices by preferring the strongest exact concept pair.
* Allow multi-word question themes to be saved and compared without losing their spaces.

= 0.10.7 =
* Place the writing guidance directly below its heading and before the response field.
* Make both writing invitations explicitly compatible with deliberate brevity.

= 0.10.6 =
* Keep the Spanish recent-fragment label consistently rendered as “Anclajes”.
* Double collective-map cloud-name size and show each parenthesized total inline at the same size.
* Invite participants to turn a smell into a fuller scene, image or thought.
* Offer a non-blocking expansion prompt before sharing a one-to-three-word fragment.

= 0.10.5 =
* Preserve compound product names such as Old Spice as the primary map anchor.
* Prioritize an applied scent product such as Axe over the body part where it appears.
* Keep explanatory qualities such as “outdoorsy” as context instead of creating redundant scent concepts.
* Reinforce extraction of every explicitly named sensory source in compound responses.
* Shorten the recent-fragment label from “Noun anchors” to “Anchors”.
* Remove the human-moderation explanation from the empty Resonances state.
* Expand the header tagline to include connection alongside scent and memory.
* Keep empty tab states as wide as populated Traces and Collective Map sections.

= 0.10.4 =
* Anchor phrases such as “the smell of…” and “the perfume of…” to the named source instead of a generic scent word.
* Reinforce the AI extraction instructions and normalize existing stored concepts defensively.
* Show a shared cloud's name when hovering or focusing its free area while preserving individual concept hover details.
* Shorten the participant-facing translation disclosure to “Translation”.

= 0.10.3 =
* Replaces the per-response collective-map checkbox with an informational notice and enables anonymous map participation by default.

= 0.10.2 =
* Adds clearer spacing between the recalculation confirmation and its action button.

= 0.10.1 =
* Enlarges map points and gives connections thinner, more organic cubic curves.

= 0.10.0 =
* Add a Recalculate admin screen with 7, 30, 90, 365-day and all-time periods.
* Queue concepts, relationships or both as one-response WP-Cron batches with progress and error reporting.
* Remove affected relationships before recalculation so obsolete concept indices cannot remain visible.
* Warn administrators about relationship replacement and potential Anthropic API usage.

= 0.9.0 =
* Store zero to four complete noun-phrase scent concepts with their noun anchors in analysis_json.
* Split coordinated concepts such as coffee and tea into independent map nodes.
* Match and connect specific concept indices while preserving the original response as one database trace.
* Keep responses without concepts in the database but out of the map, recent-fragment list and matching.
* Display complete concepts and their noun anchors beneath recently shared fragments.

= 0.8.0 =
* Show each recent fragment's editable short question label.
* Reveal a fragment translation only when its source language differs from the selected interface language.
* Let GTranslate render the stored English translation into the active interface language.
* Expand smell analysis from literal odors to supported symbolic and emotional scent anchors.

= 0.7.0 =
* Make optional profile details and the participation notice clearer on entry.
* Use Diástoles as the exact bookmark and share title.
* Draw stronger scent clouds for every cluster, including single fragments.
* Hide same-scent links, omit unclassified scent nodes and use curved relationship paths.
* Enlarge map points and clarify relationship meanings and hover context.

= 0.6.2 =
* Reapply the active GTranslate language after JavaScript renders dynamic interface content.

= 0.6.1 =
* Replace the hidden cluster grid with a stable golden-angle distribution.
* Add deterministic organic variation to fragment angle, distance and scent-cloud shape.

= 0.6.0 =
* Group equivalent multilingual scent labels into canonical shared-smell clouds without merging human fragments.
* Add wheel and button zoom, drag-to-pan navigation and a fit-all control to the collective graph.
* Keep approved response relationships independent from scent equivalence and preserve all existing response data.

= 0.5.7 =
* Replace the separate node core and halo with one continuous radial diffusion.
* Expand the complete diffusion by 20% on direct or relationship hover.

= 0.5.6 =
* Remove the redundant “Relationship:” and “Smell:” prefixes from collective-map hover labels.

= 0.5.5 =
* Enlarge a hovered relationship by 30% and its two connected scent nodes by 20%.
* Remove the white node outline and replace browser-native hover labels with map-styled tooltips.

= 0.5.4 =
* Give scent nodes a soft diffusion halo and enlarge highlighted nodes and relationships by 20%.
* Spread the constellation across more of the available map using an organic elliptical layout.

= 0.5.3 =
* Replace the question-mark help cursor on map relationships and scents with a subtler exploration crosshair.

= 0.5.2 =
* Give every relationship type a distinct map line style and add a visible legend.
* Add map hover instructions and relationship tooltips with supporting evidence.
* Label the recent-fragment section accurately and expose English translations when available.
* Preserve human fragments in their original language when third-party page translation is active.

= 0.5.1 =
* Add a configurable AI-score threshold for automatic connection approval.
* Keep lower-scoring connections in the moderation queue for manual approval or rejection.
* Apply the threshold to existing pending connections when the review screen is opened.

= 0.5.0 =
* Add an Interface texts screen for editable onboarding, profile and private-link copy.
* Refresh consent, optional-coordinate and private-return explanations.
* Expand place types from rural areas and hamlets through small cities and cities.
* Use optional profile details for matching automatically without displaying profile context.

= 0.4.8 =
* Make the private recovery address bookmark-ready and add desktop and mobile save actions.

= 0.4.7 =
* Authenticate experience requests explicitly when strict browser privacy prevents cookies and custom headers.

= 0.4.6 =
* Keep the private recovery credential in page memory so Continue works when session storage is unavailable.

= 0.4.5 =
* Keep the private recovery credential in the current tab as a fallback when a private browser blocks the session cookie.

= 0.4.4 =
* Use a host-only, site-wide session cookie for reliable private-browsing support.

= 0.4.3 =
* Prevent cached participant state from showing the registration screen to returning visitors.
* Resume an existing anonymous session instead of displaying a session conflict.
* Isolate request limits by anonymous session so participants can safely share the same network.
