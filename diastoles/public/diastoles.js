(function () {
	'use strict';

	const root = document.getElementById('diastoles-app');
	if (!root || !root.dataset.restUrl) return;
	let background = document.getElementById('diastoles-background');
	if (!background) {
		background = document.createElement('div');
		background.id = 'diastoles-background';
		background.className = 'diastoles-background';
		background.setAttribute('aria-hidden', 'true');
		root.before(background);
	}
	if (root.style.getPropertyValue('--dia-photo')) {
		background.style.setProperty('--dia-photo', root.style.getPropertyValue('--dia-photo'));
	}
	if (root.dataset.photo) background.dataset.photo = root.dataset.photo;

	const apiRoot = root.dataset.restUrl;
	const assetsRoot = root.dataset.assetsUrl || '';
	const assetsVersion = root.dataset.assetsVersion || '';
	const recoveryStorageKey = 'diastoles_recovery';
	const interfaceLanguageStorageKey = 'diastoles_interface_language';
	let activeRecoveryToken = '';
	const photographs = [
		'steps-togetherness.webp',
		'disconnected-poster.webp',
		'city-never-stops.webp',
		'dock-attention.webp',
		'city-country-home.webp',
		'water-attention.webp',
		'train-compartment.webp',
		'train-doorway.webp',
		'switch-off-read.webp',
	];
	let state = null;
	let activeTab = 'answer';
	let activeSillageTab = 'notes';
	let activeRecoveryUrl = '';
	let network = null;
	let networkView = null;
	let mapDrag = null;
	let networkMapVariant = 'current';
	let sharedFragmentsVisible = 12;
	const articleLimits = {
		connections: 12,
		traces: 12,
		story: 12,
		questions: 12,
		questionConnections: 12,
		neighborhoodExternal: 12,
	};
	const networkLab = {
		layerSmells: new Set(),
		scentSearch: '',
		storyPath: [],
		storyModes: [],
		storyOffset: 0,
		neighborhood: '',
		neighborhoodMode: 'theme',
		questionNeighborhoodVisible: 12,
		layers: new Set(['affinity', 'tension', 'complementarity', 'continuity']),
		minimumScore: 0.3,
	};
	let initialPhotographChosen = false;
	let photographRequest = 0;
	let rememberedInterfaceLanguage = readRememberedInterfaceLanguage();
	let translationPollTimer = 0;
	let translationPolls = 0;
	let pendingScrollTarget = '';
	const loggedConnectionViews = new Set();
	let hasLoggedInitialTab = false;

	const h = (value) =>
		String(value ?? '')
			.replaceAll('&', '&amp;')
			.replaceAll('<', '&lt;')
			.replaceAll('>', '&gt;')
			.replaceAll('"', '&quot;')
			.replaceAll("'", '&#039;');
	const tooltipAttribute = (value) => h(value).replaceAll('\n', '&#10;');
	const interfaceCatalogs = window.DiastolesConfig?.interfaceCatalogs || {};
	const availableLanguages = window.DiastolesConfig?.languages || { en: { name: 'English', dir: 'ltr' } };
	let interfaceText = window.DiastolesConfig?.interfaceText || {};
	const privacyUrl = window.DiastolesConfig?.privacyUrl || '/diastoles-privacy/';
	const t = (key) => String(interfaceText[key] ?? interfaceCatalogs.en?.[key] ?? '');
	const interfaceLanguageOverrides = {
		es: {
			idea_credit_prefix: 'Ideación',
			companion_project_prefix: 'Diástoles es un proyecto complementario de',
			network_concept_anchors: 'Matices: {anchors}',
			shortcut_button: 'Descargar acceso directo',
		},
	};
	const localizedInterfaceText = (key) =>
		interfaceLanguageOverrides[selectedInterfaceLanguage()]?.[key] || t(key);
	const localizedInterfaceMarkup = (key) => {
		const override = interfaceLanguageOverrides[selectedInterfaceLanguage()]?.[key];
		return `<span data-interface-text="${h(key)}"${override ? ' class="notranslate"' : ''}>${h(override || t(key))}</span>`;
	};
	const textLines = (key) => t(key).split(/\r?\n/).map(h).join('<br>');
	const formatText = (key, values) =>
		Object.entries(values).reduce((text, [name, value]) => text.replaceAll(`{${name}}`, String(value)), t(key));
	const localizedTemplateMarkup = (key, placeholder, value) => {
		const language = selectedInterfaceLanguage();
		const override = interfaceLanguageOverrides[language]?.[key];
		const template = override || t(key);
		const [prefix = '', suffix = ''] = template.split(`{${placeholder}}`);
		const fixed = (text) => override ? `<span class="notranslate">${h(text)}</span>` : h(text);
		return `${fixed(prefix)}${h(value)}${fixed(suffix)}`;
	};

	function normalizeLanguage(value) {
		const aliases = {
			catalan: 'ca',
			english: 'en',
			french: 'fr',
			german: 'de',
			italian: 'it',
			portuguese: 'pt',
			spanish: 'es',
		};
		const language = String(value || '').split('|').pop().trim().toLowerCase().replace('_', '-');
		if (language.startsWith('zh')) return 'zh-cn';
		return aliases[language] || language.split('-')[0];
	}

	function readRememberedInterfaceLanguage() {
		try {
			return normalizeLanguage(localStorage.getItem(interfaceLanguageStorageKey));
		} catch (error) {
			return '';
		}
	}

	function rememberInterfaceLanguage(value) {
		const language = normalizeLanguage(value);
		if (!language) return;
		rememberedInterfaceLanguage = language;
		try {
			localStorage.setItem(interfaceLanguageStorageKey, language);
		} catch (error) {}
	}

	function concealUntranslatedInterface() {
		// Native catalogs are selected before the first application render.
	}

	function waitForTranslatedInterface(language = selectedInterfaceLanguage(), force = false) {
		root.classList.remove('diastoles-awaiting-translation');
	}

	function selectedInterfaceLanguage() {
		const fromUrl = normalizeLanguage(new URL(window.location.href).searchParams.get('lang'));
		if (fromUrl && availableLanguages[fromUrl]) return fromUrl;
		const remembered = normalizeLanguage(rememberedInterfaceLanguage);
		if (remembered && availableLanguages[remembered]) return remembered;
		const browser = normalizeLanguage(navigator.language || '');
		if (browser && availableLanguages[browser]) return browser;
		const fallback = normalizeLanguage(window.DiastolesConfig?.defaultLanguage || 'en');
		return availableLanguages[fallback] ? fallback : 'en';
	}

	function selectedInterfaceLanguageName() {
		const language = availableLanguages[selectedInterfaceLanguage()] || availableLanguages.en || {};
		return language.english_name || language.name || selectedInterfaceLanguage();
	}

	function applyInterfaceLanguage(locale) {
		const normalized = normalizeLanguage(locale);
		const language = availableLanguages[normalized] ? normalized : 'en';
		rememberInterfaceLanguage(language);
		interfaceText = interfaceCatalogs[language] || interfaceCatalogs.en || interfaceText;
		document.documentElement.lang = language;
		document.documentElement.dir = availableLanguages[language]?.dir || 'ltr';
		root.lang = language;
		root.dir = availableLanguages[language]?.dir || 'ltr';
		const url = new URL(window.location.href);
		url.searchParams.set('lang', language);
		window.history.replaceState(window.history.state || {}, '', url);
		return language;
	}

	function languageSelector() {
		const current = selectedInterfaceLanguage();
		const nativeLanguageOrder = [
			'ar',
			'fa',
			'id',
			'ms',
			'bn',
			'ca',
			'de',
			'en',
			'eu',
			'fr',
			'gl',
			'hi',
			'it',
			'ja',
			'pl',
			'pt',
			'ru',
			'zh-cn',
		];
		const orderIndex = (code) => {
			const index = nativeLanguageOrder.indexOf(code);
			return index === -1 ? nativeLanguageOrder.length : index;
		};
		const languages = Object.entries(availableLanguages).sort(([codeA, languageA], [codeB, languageB]) => {
			if (codeA === current) return -1;
			if (codeB === current) return 1;
			return orderIndex(codeA) - orderIndex(codeB) || String(languageA.name || codeA).localeCompare(String(languageB.name || codeB));
		});
		const options = languages.map(([code, language]) =>
			`<option value="${h(code)}"${code === current ? ' selected' : ''}>${h(language.name || code)}</option>`,
		).join('');
		return `<label class="diastoles-native-language"><span class="screen-reader-text">Language</span><select data-interface-language aria-label="Language">${options}</select></label>`;
	}

	function hasPendingTranslations() {
		const locale = selectedInterfaceLanguage();
		const items = [...(state?.responses || []), ...(state?.connections || []), ...(network?.nodes || [])];
		return items.some((item) => {
			const source = normalizeLanguage(item.language || '');
			return source && source !== locale && !item.translation;
		});
	}

	function scheduleTranslationRefresh() {
		window.clearTimeout(translationPollTimer);
		if (!['connections', 'journey', 'fragments', 'network'].includes(activeTab) || !hasPendingTranslations() || translationPolls >= 3) return;
		translationPollTimer = window.setTimeout(async () => {
			translationPolls += 1;
			try {
				state = await api('state');
				if (activeTab === 'fragments' || activeTab === 'network') network = await api('network');
				render();
				scheduleTranslationRefresh();
			} catch (error) {}
		}, 2500);
	}

	function syncTranslationDisclosures() {
		const interfaceLanguage = selectedInterfaceLanguage();
		root.querySelectorAll('[data-interface-text]').forEach((element) => {
			const key = element.dataset.interfaceText;
			const override = interfaceLanguageOverrides[interfaceLanguage]?.[key];
			element.classList.toggle('notranslate', Boolean(override));
			element.textContent = override || t(key);
		});
		root.querySelectorAll('[data-interface-template]').forEach((element) => {
			const key = element.dataset.interfaceTemplate;
			const placeholder = element.dataset.templatePlaceholder;
			const value = element.dataset.templateValue || '';
			element.innerHTML = localizedTemplateMarkup(key, placeholder, value);
		});
		root.querySelectorAll('[data-source-language]').forEach((details) => {
			const sourceLanguage = normalizeLanguage(details.dataset.sourceLanguage);
			const matchInterface = details.dataset.hideWhenSourceMatchesInterface === 'true';
			const unknownSourceMatches = (!sourceLanguage || sourceLanguage === 'und') && details.dataset.translationIsOriginal === 'true' && interfaceLanguage === 'en';
			details.hidden = Boolean(matchInterface && (sourceLanguage === interfaceLanguage || unknownSourceMatches));
		});
	}

	function translatedFragment(original, translation, sourceLanguage, hideWhenSourceMatchesInterface = true) {
		const normalizedSource = normalizeLanguage(sourceLanguage);
		if (!translation && normalizedSource && normalizedSource !== selectedInterfaceLanguage()) {
			return `<p class="diastoles-translation-pending">${h(t('translation_pending'))}</p>`;
		}
		const translatedText = translation || original;
		if (!translatedText) return '';
		const translationIsOriginal = translatedText === original;
		const unknownSourceMatches = (!normalizedSource || normalizedSource === 'und') && translationIsOriginal && selectedInterfaceLanguage() === 'en';
		const hidden = hideWhenSourceMatchesInterface && (translationIsOriginal || normalizedSource === selectedInterfaceLanguage() || unknownSourceMatches) ? ' hidden' : '';
		const matchAttribute = hideWhenSourceMatchesInterface ? ' data-hide-when-source-matches-interface="true"' : '';
		return `<details class="diastoles-translation" data-source-language="${h(sourceLanguage)}" data-translation-is-original="${translationIsOriginal ? 'true' : 'false'}"${matchAttribute}${hidden}><summary>${h(t('translation_summary'))}</summary><p>${h(translatedText)}</p></details>`;
	}

	function originalFragment(node, className = 'diastoles-trace-fragment') {
		const fragment = node.fragment ?? node.original ?? '';
		const language = node.language || 'und';
		return `<p class="${h(className)}"><span class="notranslate" translate="no"${language !== 'und' ? ` lang="${h(language)}"` : ''}>“${h(fragment)}”</span></p>${translatedFragment(fragment, node.translation, language, true)}`;
	}

	function loadMoreArticles(list, visible, total) {
		return visible < total ? `<button type="button" class="diastoles-secondary diastoles-load-more" data-action="load-more-articles" data-list="${h(list)}">${h(t('load_more_items'))}</button>` : '';
	}

	function refreshPageTranslation(attempt = 0) {
		syncTranslationDisclosures();
	}

	function recoveryToken() {
		if (activeRecoveryToken) return activeRecoveryToken;
		try {
			return sessionStorage.getItem(recoveryStorageKey) || '';
		} catch (error) {
			return '';
		}
	}

	function rememberRecoveryToken(value) {
		if (!/^[a-f0-9]{64}$/i.test(value || '')) return;
		activeRecoveryToken = value;
		try {
			sessionStorage.setItem(recoveryStorageKey, value);
		} catch (error) {
			// A first-party cookie remains the primary session mechanism.
		}
	}

	function rememberRecoveryUrl(url) {
		try {
			rememberRecoveryToken(new URL(url).searchParams.get('diastoles_recover'));
		} catch (error) {
			// Ignore a malformed recovery URL; the server cookie may still work.
		}
	}

	async function copyText(value) {
		try {
			await navigator.clipboard.writeText(value);
			return;
		} catch (error) {
			const field = document.createElement('textarea');
			field.value = value;
			field.setAttribute('readonly', '');
			field.style.position = 'fixed';
			field.style.opacity = '0';
			document.body.appendChild(field);
			field.select();
			document.execCommand('copy');
			field.remove();
		}
	}

	async function api(path, options = {}) {
		const token = recoveryToken();
		const requestUrl = new URL(apiRoot + path, window.location.href);
		if (token) requestUrl.searchParams.set('diastoles_recovery', token);
		requestUrl.searchParams.set('lang', selectedInterfaceLanguage());
		const headers = {
			'Content-Type': 'application/json',
			...(token ? { 'X-Diastoles-Recovery': token } : {}),
			...(options.headers || {}),
		};
		const response = await fetch(requestUrl, {
			...options,
			credentials: 'same-origin',
			cache: 'no-store',
			headers,
		});
		const data = await response.json().catch(() => ({}));
		if (!response.ok) throw new Error(data.message || t('generic_error'));
		return data;
	}

	function currentQuestionId() {
		return state?.question?.id || 0;
	}

	function screenForTab(tab = activeTab) {
		return ({
			answer: 'stimulus',
			sillage: 'your_sillage',
			fragments: 'atmosphere',
			network: 'collective_maps',
		})[tab] || tab || '';
	}

	function logEvent(eventType, screen = screenForTab(), metadata = {}, questionId = currentQuestionId()) {
		if (!state?.authenticated) return;
		window.setTimeout(() => {
			api('events', {
				method: 'POST',
				body: JSON.stringify({
					event_type: eventType,
					screen,
					question_id: questionId || 0,
					metadata,
				}),
			}).catch(() => undefined);
		}, 0);
	}

	async function load() {
		try {
			const recovery = new URL(window.location.href).searchParams.get('diastoles_recover');
			if (recovery) {
				rememberRecoveryToken(recovery);
				await api('session/recover', {
					method: 'POST',
					body: JSON.stringify({ token: recovery }),
				});
				const url = new URL(window.location.href);
				url.searchParams.delete('diastoles_recover');
				window.history.replaceState({}, '', url);
			}
			state = await api('state');
			render();
			if (state?.authenticated && !hasLoggedInitialTab) {
				hasLoggedInitialTab = true;
				logEvent('tab_opened', screenForTab(activeTab), { tab: activeTab, initial: true });
			}
		} catch (error) {
			renderError(error.message);
		}
	}

	function render() {
		if (!state?.authenticated) {
			renderWelcome();
			return;
		}

		setPhotograph(activeTab === 'answer' ? (state.question?.id || 0) + 1 : activeTab);
		waitForTranslatedInterface(rememberedInterfaceLanguage || selectedInterfaceLanguage());
		ensureAuthenticatedShell();
		root.querySelector('.diastoles-auth-header').innerHTML = renderExperienceHeaderContents(true);
		root.querySelector('.diastoles-nav').innerHTML = `
			${tabButton('answer', t('nav_question'))}
			${tabButton('sillage', t('nav_sillage'))}
			${tabButton('fragments', t('nav_shared_fragments'))}
			${tabButton('network', t('nav_map'))}
		`;
		root.querySelector('.diastoles-main').innerHTML = renderTab();
		root.querySelector('.diastoles-credit-slot').innerHTML = renderCredit();
		logVisibleConnections();
		concealUntranslatedInterface();
		refreshPageTranslation();
		scrollToPendingTarget();
		window.requestAnimationFrame(updateMapVariantScrollCues);
	}

	function scrollToPendingTarget() {
		if (!pendingScrollTarget) return;
		const selector = pendingScrollTarget;
		pendingScrollTarget = '';
		window.requestAnimationFrame(() => {
			scrollToElementWithContext(root.querySelector(selector));
		});
	}

	function scrollToElementWithContext(element) {
		if (!element) return;
		const rect = element.getBoundingClientRect();
		const viewportOffset = Math.max(96, Math.min(180, window.innerHeight * 0.16));
		const top = window.scrollY + rect.top - viewportOffset;
		window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
	}

	function renderExperienceHeader(authenticated = false) {
		return `<header class="diastoles-header${authenticated ? ' diastoles-auth-header' : ''}">
			${renderExperienceHeaderContents(authenticated)}
		</header>`;
	}

	function renderExperienceHeaderContents(authenticated = false) {
		const wordmark = authenticated
			? '<a class="diastoles-wordmark" href="#" data-action="tab" data-tab="answer">Diástoles</a>'
			: '<span class="diastoles-wordmark">Diástoles</span>';
		return `
			${wordmark}
			<p>${h(t('header_tagline'))}</p>
			${state?.ai_mode === 'mock' ? `<span class="diastoles-mode">${h(t('mock_mode_label'))}</span>` : ''}
			<div class="diastoles-language-slot diastoles-header-language-slot">${languageSelector()}</div>
		`;
	}

	function ensureAuthenticatedShell() {
		if (root.dataset.screen === 'authenticated' && root.querySelector('.diastoles-main')) return;
		root.dataset.screen = 'authenticated';
		root.innerHTML = `
			<div class="diastoles-atmosphere" aria-hidden="true"></div>
			<header class="diastoles-header diastoles-auth-header"></header>
			<nav class="diastoles-nav" aria-label="Experience"></nav>
			<main class="diastoles-main"></main>
			<div class="diastoles-credit-slot"></div>
			<div id="diastoles-notice" class="diastoles-notice" role="status"></div>
		`;
	}

	function tabButton(id, label) {
		return `<button type="button" data-action="tab" data-tab="${id}" class="${activeTab === id ? 'is-active' : ''}">${h(label)}</button>`;
	}

	function renderTab() {
		if (activeTab === 'sillage') return renderSillage();
		if (activeTab === 'fragments') return renderSharedFragments();
		if (activeTab === 'network') return renderNetwork();
		return renderQuestion();
	}

	function sillageButton(id, label) {
		return `<button type="button" data-action="sillage-tab" data-sillage-tab="${id}" class="${activeSillageTab === id ? 'is-active' : ''}">${h(label)}</button>`;
	}

	function renderSillage() {
		const tabs = `<nav class="diastoles-inner-tabs" aria-label="${h(t('nav_sillage'))}">
			${sillageButton('notes', t('nav_notes'))}
			${sillageButton('affinities', `${t('nav_affinities')}${state.connections.length ? ` · ${state.connections.length}` : ''}`)}
		</nav>`;
		return `${tabs}${activeSillageTab === 'affinities' ? renderConnections() : renderJourney()}`;
	}

	function renderWelcome() {
		root.dataset.screen = 'welcome';
		setPhotograph('welcome');
		waitForTranslatedInterface(rememberedInterfaceLanguage || selectedInterfaceLanguage());
		root.innerHTML = `
			<div class="diastoles-atmosphere" aria-hidden="true"></div>
			${renderExperienceHeader()}
			<section class="diastoles-welcome">
				<p class="diastoles-kicker">${h(t('welcome_kicker'))}</p>
				<h1>${textLines('welcome_heading')}</h1>
				<p class="diastoles-intro">${textLines('welcome_intro')}</p>
				<div class="diastoles-language">${h(t('language_note'))}</div>
				<form id="diastoles-join-form" class="diastoles-card">
					${honeypotField()}
					<label>
						<span>${h(t('pseudonym_label'))}</span>
						<input type="text" name="pseudonym" maxlength="80" autocomplete="off">
					</label>
					<details class="diastoles-profile-details">
						<summary>${h(t('optional_coordinates_summary'))}</summary>
						<p>${h(t('optional_coordinates_intro'))}</p>
						${profileFields({}, true)}
					</details>
					<label class="diastoles-check diastoles-consent-notice">
						<input type="checkbox" name="consent_fragments" value="1" required data-consent-toggle>
						<span>${h(t('consent_label'))} <a href="${h(privacyUrl)}" target="_blank" rel="noopener noreferrer">${h(t('privacy_link_label'))}</a></span>
					</label>
					<p class="diastoles-fineprint">${h(t('privacy_note'))}</p>
					<button class="diastoles-primary" type="submit" data-consent-submit>${h(t('enter_button'))}</button>
				</form>
			</section>
			${renderCredit()}
		`;
		concealUntranslatedInterface();
		refreshPageTranslation();
	}

	function renderRecovery(url) {
		root.dataset.screen = 'recovery';
		activeRecoveryUrl = url;
		setPhotograph('recovery');
		window.history.replaceState({ diastolesRecovery: true }, '', url);
		const mobileShare = typeof navigator.share === 'function' && /Android|iPhone|iPad|Mobile/i.test(navigator.userAgent);
		const shortcut = /Mac|iPhone|iPad/i.test(navigator.platform || navigator.userAgent) ? '⌘D' : 'Ctrl+D';
		const desktopHint = h(t('desktop_save_hint')).replace('{shortcut}', `<kbd>${h(shortcut)}</kbd>`);
		const desktopShortcutButton = mobileShare ? '' : `
			<button class="diastoles-secondary" type="button" data-action="download-shortcut" data-url="${h(url)}">
				${h(localizedInterfaceText('shortcut_button'))}
			</button>
		`;
		waitForTranslatedInterface(rememberedInterfaceLanguage || selectedInterfaceLanguage());
		root.innerHTML = `
			<div class="diastoles-atmosphere" aria-hidden="true"></div>
			${renderExperienceHeader()}
			<section class="diastoles-welcome diastoles-recovery">
				<p class="diastoles-kicker">${h(t('recovery_kicker'))}</p>
				<h1>${h(t('recovery_heading'))}</h1>
				<p>${h(t('recovery_intro'))}</p>
				<div class="diastoles-recovery-link">${h(url)}</div>
				<div class="diastoles-actions">
					<button class="diastoles-primary" type="button" data-action="bookmark-recovery" data-url="${h(url)}" data-shortcut="${h(shortcut)}">
						${h(mobileShare ? t('share_button') : t('bookmark_button'))}
					</button>
					${desktopShortcutButton}
					<button class="diastoles-secondary" type="button" data-action="copy-recovery" data-url="${h(url)}">${h(t('copy_link_button'))}</button>
					<button class="diastoles-secondary" type="button" data-action="continue">${h(t('continue_button'))}</button>
				</div>
				<p class="diastoles-bookmark-hint">
					${mobileShare
						? h(t('mobile_save_hint'))
						: desktopHint}
				</p>
			</section>
			${renderCredit()}
		`;
		concealUntranslatedInterface();
		refreshPageTranslation();
	}

	function bookmarkFallbackHint(shortcut) {
		const language = selectedInterfaceLanguage();
		const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
		const isAndroid = /Android/i.test(navigator.userAgent);
		const isMac = /Mac/i.test(navigator.platform || navigator.userAgent);
		if (language === 'es') {
			if (isIOS) return 'En iPhone/iPad, abre Compartir y elige “Añadir a favoritos” o “Añadir a pantalla de inicio”. He copiado el enlace privado por si quieres guardarlo en otro lugar.';
			if (isAndroid) return 'En Android, abre el menú del navegador y elige “Añadir a pantalla de inicio” o “Añadir a marcadores”. He copiado el enlace privado por si quieres guardarlo en otro lugar.';
			if (isMac) return `Los navegadores no dejan crear favoritos automáticamente. He copiado el enlace privado; pulsa ${shortcut} para añadirlo a favoritos.`;
			return `Los navegadores no dejan crear favoritos automáticamente. He copiado el enlace privado; pulsa ${shortcut} para añadirlo a favoritos.`;
		}
		if (isIOS) return 'On iPhone/iPad, open Share and choose “Add Bookmark” or “Add to Home Screen”. I copied the private link in case you want to save it somewhere else.';
		if (isAndroid) return 'On Android, open the browser menu and choose “Add to Home Screen” or “Add bookmark”. I copied the private link in case you want to save it somewhere else.';
		if (isMac) return `Browsers do not allow bookmarks to be created automatically. I copied the private link; press ${shortcut} to bookmark it.`;
		return `Browsers do not allow bookmarks to be created automatically. I copied the private link; press ${shortcut} to bookmark it.`;
	}

	function downloadPrivateShortcut(url) {
		const isMac = /Mac/i.test(navigator.platform || navigator.userAgent);
		const filename = isMac ? 'Diastoles-private-link.webloc' : 'Diastoles-private-link.url';
		const body = isMac
			? `<?xml version="1.0" encoding="UTF-8"?>\n<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">\n<plist version="1.0">\n<dict>\n\t<key>URL</key>\n\t<string>${url}</string>\n</dict>\n</plist>\n`
			: `[InternetShortcut]\nURL=${url}\n`;
		const blob = new Blob([body], { type: isMac ? 'application/xml' : 'text/plain' });
		const link = document.createElement('a');
		link.href = URL.createObjectURL(blob);
		link.download = filename;
		document.body.appendChild(link);
		link.click();
		link.remove();
		window.setTimeout(() => URL.revokeObjectURL(link.href), 1000);
	}

	function revealConsentRequirement(form) {
		const notice = form?.querySelector('.diastoles-consent-notice');
		const checkbox = form?.querySelector('[data-consent-toggle]');
		if (!notice || !checkbox) return;
		notice.classList.remove('is-missing');
		// Restart the subtle pulse when the user clicks repeatedly.
		window.requestAnimationFrame(() => notice.classList.add('is-missing'));
		checkbox.setAttribute('aria-invalid', 'true');
		checkbox.focus({ preventScroll: false });
	}

	function renderQuestion() {
		if (!state.question) {
			if (state.daily_remaining === 0) {
				return `
					<section class="diastoles-empty">
						<p class="diastoles-kicker">${h(t('daily_limit_kicker'))}</p>
						<h2>${h(t('daily_limit_heading'))}</h2>
						<p>${h(t('daily_limit_body'))}</p>
					</section>`;
			}
			return `
				<section class="diastoles-empty">
					<p class="diastoles-kicker">${h(t('all_questions_kicker'))}</p>
					<h2>${h(t('all_questions_heading'))}</h2>
					<p>${h(t('all_questions_body'))}</p>
				</section>`;
		}

		return `
			<section class="diastoles-question">
				<div class="diastoles-question-meta">
					<p class="diastoles-kicker">${h(themeLabel(state.question.theme))}</p>
					<p class="diastoles-daily-count">${h(formatText('daily_count_template', { count: state.daily_count, limit: state.daily_limit }))}</p>
				</div>
				<h1>${h(state.question.prompt)}</h1>
				<div class="diastoles-question-language-row">
					<p class="diastoles-language">${h(t('language_note'))}</p>
					<button class="diastoles-quiet" type="button" data-action="skip-question" data-id="${state.question.id}">${h(t('skip_question_button'))}</button>
				</div>
				<form id="diastoles-response-form">
					${honeypotField()}
					<input type="hidden" name="question_id" value="${state.question.id}">
					<label class="diastoles-field">
						<span>${h(t('fragment_label'))}</span>
						<small class="diastoles-fragment-guidance">${h(t('fragment_guidance'))}</small>
						<textarea name="original_text" rows="5" maxlength="4000" required autofocus></textarea>
					</label>
					<div class="diastoles-short-fragment" role="status" tabindex="-1" hidden>
						<p>${h(t('short_fragment_prompt'))}</p>
						<div class="diastoles-short-fragment-actions">
							<button class="diastoles-secondary" type="button" data-action="expand-short-fragment">${h(t('short_fragment_expand_button'))}</button>
							<button class="diastoles-quiet" type="button" data-action="share-short-fragment">${h(t('short_fragment_share_button'))}</button>
						</div>
					</div>
					<label class="diastoles-field diastoles-followup">
						<span>${h(state.question.followup)} <small>${h(t('optional_suffix'))}</small></span>
						<textarea name="explanation_text" rows="3" maxlength="4000"></textarea>
					</label>
					<div class="diastoles-actions">
						<button class="diastoles-primary" type="submit">${h(t('submit_response_button'))}</button>
					</div>
				</form>
			</section>`;
	}

	function renderConnections() {
		if (!state.connections.length) {
			return `
				<section class="diastoles-empty">
					<p class="diastoles-kicker">${h(t('connections_empty_kicker'))}</p>
					<h2>${h(t('connections_empty_heading'))}</h2>
					${t('connections_empty_body') ? `<p>${h(t('connections_empty_body'))}</p>` : ''}
				</section>`;
		}

		return `
			<section>
				<p class="diastoles-kicker">${h(t('connections_kicker'))}</p>
				<h1>${h(t('connections_heading'))}</h1>
				<div class="diastoles-stack diastoles-article-grid">
					${state.connections.slice(0, articleLimits.connections).map(connectionCard).join('')}
				</div>
				${loadMoreArticles('connections', articleLimits.connections, state.connections.length)}
			</section>`;
	}

	function connectionCard(connection) {
		const evidence = connection.evidence?.length
			? `<div class="diastoles-evidence">${connection.evidence.map((item) => `<span>${h(item)}</span>`).join('')}</div>`
			: '';
		const context = connection.context?.length
				? `<p class="diastoles-context">${h(connection.context.join(' · '))}</p>`
			: '';
		return `
			<article class="diastoles-connection" data-connection-id="${h(connection.id || '')}">
				<div class="diastoles-connection-heading">
					<p class="diastoles-relation">${h(relationDisplayText(connection.relation_type))}</p>
					${connection.question ? `<p class="diastoles-connection-question">${h(connection.question)}</p>` : ''}
				</div>
				${originalFragment(connection)}
				${evidence}
				${context}
			</article>`;
	}

	function logVisibleConnections() {
		if (activeTab !== 'sillage' || activeSillageTab !== 'affinities') return;
		root.querySelectorAll('[data-connection-id]').forEach((card) => {
			const id = card.dataset.connectionId;
			if (!id || loggedConnectionViews.has(id)) return;
			loggedConnectionViews.add(id);
			logEvent('connection_viewed', 'your_sillage', { connection_id: id });
		});
	}

	function responseStatusLabel(status) {
		const normalized = String(status || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '_');
		return t(`response_status_${normalized}`) || status || '';
	}

	function renderJourney() {
		return `
			<section>
				<p class="diastoles-kicker">${h(t('journey_kicker'))}</p>
				<h1>${h(t('journey_heading'))}</h1>
				<div class="diastoles-stack diastoles-article-grid">
					${state.responses.length ? state.responses
						.slice(0, articleLimits.traces)
						.map(
							(item) => `
							<article class="diastoles-trace">
								<p class="diastoles-trace-question">${h(item.question)}</p>
								${originalFragment(item)}
								<div class="diastoles-trace-meta">
									<span>${h(responseStatusLabel(item.status))}</span>
									<button class="diastoles-quiet" data-action="withdraw" data-id="${h(item.id)}">${h(t('withdraw_button'))}</button>
								</div>
							</article>`,
						)
						.join('') : `<p>${h(t('journey_empty'))}</p>`}
				</div>
				${loadMoreArticles('traces', articleLimits.traces, state.responses.length)}
				<details class="diastoles-profile-editor">
					<summary>${h(t('profile_editor_summary'))}</summary>
					<form id="diastoles-profile-form" class="diastoles-card">
						<p>${h(t('profile_editor_intro'))}</p>
						${profileFields(state.profile)}
						<button class="diastoles-secondary" type="submit">${h(t('save_profile_button'))}</button>
					</form>
				</details>
			</section>`;
	}

	function renderSharedFragments() {
		if (!network) {
			window.setTimeout(loadNetwork, 0);
			return `<section class="diastoles-empty"><p class="diastoles-loading">${h(t('network_loading'))}</p></section>`;
		}
		const fragments = network.recent_fragments || [];
		const visible = fragments.slice(-sharedFragmentsVisible).reverse();
		const batches = [];
		for (let index = 0; index < visible.length; index += 12) batches.push(visible.slice(index, index + 12));
		return `<section>
			<p class="diastoles-kicker">${h(t('shared_fragments_kicker'))}</p>
			<h1>${h(t('network_recent_heading'))}</h1>
			${batches.map((batch, index) => `<div class="diastoles-fragments" data-fragment-page="${index + 1}">${batch.map(fragmentCard).join('')}</div>`).join('')}
			${sharedFragmentsVisible < fragments.length ? `<button type="button" class="diastoles-secondary diastoles-load-more" data-action="load-more-fragments">${h(t('load_more_fragments'))}</button>` : ''}
		</section>`;
	}

	function fragmentCard(node) {
		return `<article>
			<p class="diastoles-fragment-question">${h(formatText('network_fragment_question', { question: node.question_label || node.question }))}</p>
			${originalFragment(node, 'diastoles-shared-fragment')}
			<ul class="diastoles-concepts">
				${(node.concepts || []).map((concept) => `<li><span>${h(concept.phrase)}</span><small data-interface-template="network_concept_anchors" data-template-placeholder="anchors" data-template-value="${h(concept.anchors.join(' · '))}">${localizedTemplateMarkup('network_concept_anchors', 'anchors', concept.anchors.join(' · '))}</small></li>`).join('')}
			</ul>
		</article>`;
	}

	function appendSharedFragments(button) {
		if (!network?.recent_fragments?.length) return;
		const section = button.closest('.diastoles-main > section');
		if (!section) return;
		const fragments = network.recent_fragments || [];
		const previousVisible = sharedFragmentsVisible;
		sharedFragmentsVisible = Math.min(sharedFragmentsVisible + 12, fragments.length);
		const ordered = fragments.slice(-sharedFragmentsVisible).reverse();
		const batch = ordered.slice(previousVisible, sharedFragmentsVisible);
		if (!batch.length) {
			button.remove();
			return;
		}
		const page = Math.floor(previousVisible / 12) + 1;
		const container = document.createElement('div');
		container.className = 'diastoles-fragments';
		container.dataset.fragmentPage = String(page);
		container.innerHTML = batch.map(fragmentCard).join('');
		button.before(container);
		pendingScrollTarget = `.diastoles-fragments[data-fragment-page="${page}"]`;
		scrollToPendingTarget();
		logEvent('load_more', 'atmosphere', { list: 'fragments', visible_before: previousVisible, visible_after: sharedFragmentsVisible });
		refreshPageTranslation();
		if (sharedFragmentsVisible >= fragments.length) button.remove();
	}

	function profileFields(profile = {}, includePublicationEmail = false) {
		const normalizedProfile = {
			...profile,
			environment:
				profile.environment === 'countryside'
					? 'rural'
					: profile.environment === 'town'
						? 'small-town'
						: profile.environment,
		};
		const selected = (name, value) => (normalizedProfile[name] === value ? ' selected' : '');
		const list = (name) => {
			const values = profile[name] || [];
			if (includePublicationEmail && name === 'native_languages' && !values.length) {
				return h(selectedInterfaceLanguageName());
			}
			return h(values.join(', '));
		};
		return `
			<div class="diastoles-profile-grid">
				${includePublicationEmail ? `<label class="diastoles-profile-wide">
					<span>${h(t('publication_email_label'))}</span>
					<input type="email" name="publication_email" maxlength="254" autocomplete="email" aria-describedby="diastoles-publication-email-note">
					<small id="diastoles-publication-email-note" class="diastoles-fineprint">${h(t('publication_email_note'))}</small>
				</label>` : ''}
				<label>
					<span>${h(t('gender_label'))}</span>
					<select name="gender">
						<option value="">${h(t('prefer_not_to_say'))}</option>
						<option value="woman"${selected('gender', 'woman')}>${h(t('gender_woman'))}</option>
						<option value="man"${selected('gender', 'man')}>${h(t('gender_man'))}</option>
						<option value="fluid"${selected('gender', 'fluid')}>${h(t('gender_fluid'))}</option>
						<option value="self-described"${selected('gender', 'self-described')}>${h(t('gender_self_described'))}</option>
					</select>
				</label>
				<label>
					<span>${h(t('age_range_label'))}</span>
					<select name="age_band">
						<option value="">${h(t('prefer_not_to_say'))}</option>
						${['under-25', '25-34', '35-44', '45-54', '55-64', '65+'].map((value) => `<option value="${value}"${selected('age_band', value)}>${value}</option>`).join('')}
					</select>
				</label>
				<label>
					<span>${h(t('environment_label'))}</span>
					<select name="environment">
						<option value="">${h(t('prefer_not_to_say'))}</option>
						<option value="rural"${selected('environment', 'rural')}>${h(t('environment_rural'))}</option>
						<option value="hamlet"${selected('environment', 'hamlet')}>${h(t('environment_hamlet'))}</option>
						<option value="village"${selected('environment', 'village')}>${h(t('environment_village'))}</option>
						<option value="small-town"${selected('environment', 'small-town')}>${h(t('environment_small_town'))}</option>
						<option value="small-city"${selected('environment', 'small-city')}>${h(t('environment_small_city'))}</option>
						<option value="city"${selected('environment', 'city')}>${h(t('environment_city'))}</option>
						<option value="between-places"${selected('environment', 'between-places')}>${h(t('environment_between_places'))}</option>
					</select>
				</label>
				<label><span>${h(t('country_label'))}</span><input type="text" name="country" maxlength="100" value="${h(profile.country)}"></label>
				<label><span>${h(t('continent_label'))}</span><input type="text" name="continent" maxlength="40" value="${h(profile.continent)}"></label>
				<label><span>${h(t('native_language_label'))}</span><input type="text" name="native_languages" maxlength="300" value="${list('native_languages')}" placeholder="${h(t('native_language_placeholder'))}"></label>
				<label><span>${h(t('other_languages_label'))}</span><input type="text" name="other_languages" maxlength="300" value="${list('other_languages')}" placeholder="${h(t('other_languages_placeholder'))}"></label>
				<label>
					<span>${h(t('language_life_label'))}</span>
					<select name="multilingual_status">
						<option value="">${h(t('prefer_not_to_say'))}</option>
						<option value="mostly-one"${selected('multilingual_status', 'mostly-one')}>${h(t('language_mostly_one'))}</option>
						<option value="multilingual"${selected('multilingual_status', 'multilingual')}>${h(t('language_multilingual'))}</option>
						<option value="between-languages"${selected('multilingual_status', 'between-languages')}>${h(t('language_between'))}</option>
					</select>
				</label>
				<label><span>${h(t('work_areas_label'))}</span><input type="text" name="work_areas" maxlength="500" value="${list('work_areas')}" placeholder="${h(t('work_areas_placeholder'))}"></label>
				<label><span>${h(t('hobbies_label'))}</span><input type="text" name="hobbies" maxlength="500" value="${list('hobbies')}" placeholder="${h(t('hobbies_placeholder'))}"></label>
				<label class="diastoles-profile-wide"><span>${h(t('rooted_places_label'))}</span><input type="text" name="rooted_places" maxlength="500" value="${list('rooted_places')}" placeholder="${h(t('rooted_places_placeholder'))}"></label>
			</div>`;
	}

	function honeypotField() {
		return `
			<div class="diastoles-honeypot" aria-hidden="true" inert>
				<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
			</div>`;
	}

	function stableNoise(value) {
		let hash = 2166136261;
		for (const character of String(value)) {
			hash ^= character.charCodeAt(0);
			hash = Math.imul(hash, 16777619);
		}
		return (hash >>> 0) / 4294967295;
	}

	function curvedEdgePath(source, target, edgeId, sharedCluster = false) {
		const dx = target.x - source.x;
		const dy = target.y - source.y;
		const distance = Math.max(1, Math.hypot(dx, dy));
		const direction = stableNoise(`${edgeId}:curve`) < 0.5 ? -1 : 1;
		const sharedClusterBend = sharedCluster ? 125 - distance * 0.2 : 0;
		const bend = Math.min(140, Math.max(sharedCluster ? 82 : 38, distance * 0.22, sharedClusterBend)) * direction;
		const normalX = -dy / distance;
		const normalY = dx / distance;
		const firstFlow = 0.27 + stableNoise(`${edgeId}:flow-a`) * 0.08;
		const secondFlow = 0.65 + stableNoise(`${edgeId}:flow-b`) * 0.08;
		const firstBend = bend * (0.82 + stableNoise(`${edgeId}:bend-a`) * 0.2);
		const secondBend = bend * (0.82 + stableNoise(`${edgeId}:bend-b`) * 0.2);
		const controlAX = source.x + dx * firstFlow + normalX * firstBend;
		const controlAY = source.y + dy * firstFlow + normalY * firstBend;
		const controlBX = source.x + dx * secondFlow + normalX * secondBend;
		const controlBY = source.y + dy * secondFlow + normalY * secondBend;
		return `M ${source.x} ${source.y} C ${controlAX} ${controlAY} ${controlBX} ${controlBY} ${target.x} ${target.y}`;
	}

	function buildNetworkLayout(nodes = network.nodes, smellClusters = network.smell_clusters || []) {
		const nodesById = new Map(nodes.map((node) => [node.id, node]));
		const clusters = smellClusters
			.map((cluster) => ({
				...cluster,
				node_ids: (cluster.node_ids || []).filter((id) => nodesById.has(id)),
			}))
			.filter((cluster) => cluster.node_ids.length);
		const clusteredIds = new Set(clusters.flatMap((cluster) => cluster.node_ids));

		nodes.forEach((node) => {
			if (clusteredIds.has(node.id)) return;
			clusters.push({
				id: `single-${node.id}`,
				label: node.smells[0],
				aliases: node.smells || [],
				node_ids: [node.id],
			});
		});

		const goldenAngle = Math.PI * (3 - Math.sqrt(5));
		const clusterSpacing = 430;
		const margin = 100;
		const positions = new Map();
		const clusterLayouts = clusters.map((cluster, index) => {
			const clusterAngle = index * goldenAngle + (stableNoise(`${cluster.id}:angle`) - 0.5) * 0.5;
			const clusterDistance = index === 0 ? 0 : clusterSpacing * Math.sqrt(index);
			const center = {
				x: Math.cos(clusterAngle) * clusterDistance,
				y: Math.sin(clusterAngle) * clusterDistance * 0.78,
			};
			const count = cluster.node_ids.length;
			const orbit = Math.min(155, 95 + Math.sqrt(count) * 25);
			return {
				...cluster,
				center,
				orbit,
				rx: Math.max(140, orbit + 75) * (0.94 + stableNoise(`${cluster.id}:width`) * 0.12),
				ry: Math.max(96, (orbit + 75) * (0.6 + stableNoise(`${cluster.id}:height`) * 0.12)),
				rotation: (stableNoise(`${cluster.id}:rotation`) - 0.5) * 34,
			};
		});

		let minX = Infinity;
		let minY = Infinity;
		let maxX = -Infinity;
		let maxY = -Infinity;
		clusterLayouts.forEach((cluster) => {
			const extent = Math.max(cluster.rx, cluster.ry);
			minX = Math.min(minX, cluster.center.x - extent);
			minY = Math.min(minY, cluster.center.y - extent);
			maxX = Math.max(maxX, cluster.center.x + extent);
			maxY = Math.max(maxY, cluster.center.y + extent);
		});
		const shiftX = margin - minX;
		const shiftY = margin - minY;
		clusterLayouts.forEach((cluster) => {
			cluster.center.x += shiftX;
			cluster.center.y += shiftY;
			const count = cluster.node_ids.length;
			cluster.node_ids.forEach((id, nodeIndex) => {
				const angleJitter = (stableNoise(`${id}:angle`) - 0.5) * 0.72;
				const angle = nodeIndex * goldenAngle - Math.PI / 2 + angleJitter;
				const organicRadius = 0.48 + Math.sqrt((nodeIndex + 1) / Math.max(1, count)) * 0.42;
				const distanceJitter = 0.9 + stableNoise(`${id}:distance`) * 0.2;
				const verticalRatio = 0.62 + stableNoise(`${id}:vertical`) * 0.2;
				const distance = count === 1 ? cluster.orbit * 0.62 : cluster.orbit * organicRadius * distanceJitter;
				positions.set(id, {
					x: cluster.center.x + Math.cos(angle) * distance,
					y: cluster.center.y + Math.sin(angle) * distance * verticalRatio,
				});
			});
		});
		const worldWidth = maxX - minX + margin * 2;
		const worldHeight = maxY - minY + margin * 2;

		return { positions, clusters: clusterLayouts, worldWidth, worldHeight };
	}

	function fitNetworkView(layout, width, height) {
		const padding = 48;
		const scale = Math.min(
			1,
			(width - padding * 2) / layout.worldWidth,
			(height - padding * 2) / layout.worldHeight,
		);
		return {
			scale,
			x: (width - layout.worldWidth * scale) / 2,
			y: (height - layout.worldHeight * scale) / 2,
		};
	}

	function applyNetworkTransform() {
		const viewport = root.querySelector('.diastoles-network-viewport');
		if (!viewport || !networkView) return;
		viewport.setAttribute(
			'transform',
			`translate(${networkView.x} ${networkView.y}) scale(${networkView.scale})`,
		);
	}

	function zoomNetwork(factor, clientX, clientY) {
		const svg = root.querySelector('.diastoles-map svg');
		if (!svg || !networkView) return;
		const bounds = svg.getBoundingClientRect();
		const anchorX = Number.isFinite(clientX) ? ((clientX - bounds.left) / bounds.width) * 760 : 380;
		const anchorY = Number.isFinite(clientY) ? ((clientY - bounds.top) / bounds.height) * 520 : 260;
		const nextScale = Math.min(4, Math.max(0.18, networkView.scale * factor));
		const worldX = (anchorX - networkView.x) / networkView.scale;
		const worldY = (anchorY - networkView.y) / networkView.scale;
		networkView.x = anchorX - worldX * nextScale;
		networkView.y = anchorY - worldY * nextScale;
		networkView.scale = nextScale;
		applyNetworkTransform();
	}

	function resetNetworkView() {
		if (!networkView?.initial) return;
		Object.assign(networkView, networkView.initial);
		applyNetworkTransform();
	}

	function networkVariantTabs() {
		const variants = [
			['current', t('map_variant_global')],
			['alt-3', t('map_variant_narrative')],
			['alt-4', t('map_variant_questions')],
			['alt-7', t('map_variant_layers')],
		];
		return `<div class="diastoles-map-variants-shell">
			<nav class="diastoles-map-variants" aria-label="Map alternatives">
				${variants.map(([id, label]) => `<button type="button" class="${networkMapVariant === id ? 'is-active' : ''}" data-action="network-map-variant" data-variant="${id}">${h(label)}</button>`).join('')}
			</nav>
			<span class="diastoles-map-variants-cue diastoles-map-variants-cue-left" aria-hidden="true">←</span>
			<span class="diastoles-map-variants-cue diastoles-map-variants-cue-right" aria-hidden="true">→</span>
		</div>`;
	}

	function updateMapVariantScrollCues() {
		root.querySelectorAll('.diastoles-map-variants-shell').forEach((shell) => {
			const scroller = shell.querySelector('.diastoles-map-variants');
			if (!scroller) return;
			const tolerance = 2;
			const maxScroll = scroller.scrollWidth - scroller.clientWidth;
			const hasOverflow = maxScroll > tolerance;
			shell.classList.toggle('has-left-scroll', hasOverflow && scroller.scrollLeft > tolerance);
			shell.classList.toggle('has-right-scroll', hasOverflow && scroller.scrollLeft < maxScroll - tolerance);
		});
	}

	function connectedNetwork() {
		return connectedGraphForEdges(network.edges || []);
	}

	function connectedGraphForEdges(edges) {
		const ids = new Set();
		edges.forEach((edge) => {
			ids.add(edge.source);
			ids.add(edge.target);
		});
		return {
			nodes: network.nodes.filter((node) => ids.has(node.id)),
			edges: edges.filter((edge) => ids.has(edge.source) && ids.has(edge.target)),
			ids,
		};
	}

	function networkNodeMap() {
		return new Map(network.nodes.map((node) => [node.id, node]));
	}

	function alternativeLayout(nodes, width = 760, height = 420, selectedIds = new Set()) {
		const positions = new Map();
		const groupsById = new Map();
		nodes.forEach((node) => {
			const id = node.primary_smell_id || node.canonical_smells?.[0]?.id || node.concept || node.id;
			const label = node.canonical_smells?.[0]?.label || node.concept || 'Scent';
			if (!groupsById.has(id)) groupsById.set(id, { id, label, nodes: [] });
			groupsById.get(id).nodes.push(node);
		});
		const groups = [...groupsById.values()].sort((a, b) => {
			const aSelected = a.nodes.some((node) => selectedIds.has(node.id));
			const bSelected = b.nodes.some((node) => selectedIds.has(node.id));
			return Number(bSelected) - Number(aSelected) || b.nodes.length - a.nodes.length || a.label.localeCompare(b.label);
		});
		const goldenAngle = Math.PI * (3 - Math.sqrt(5));
		const centerX = width / 2;
		const centerY = height / 2;
		const maximum = Math.min(width * 0.38, height * 0.34);
		const clusters = groups.map((group, groupIndex) => {
			const selected = group.nodes.some((node) => selectedIds.has(node.id));
			const progress = groups.length === 1 || (selected && groupIndex === 0) ? 0 : Math.sqrt((groupIndex + 0.35) / groups.length);
			const angle = groupIndex * goldenAngle + stableNoise(`${group.id}:cloud-angle`) * 0.6;
			const center = {
				x: centerX + Math.cos(angle) * maximum * progress * 1.72,
				y: centerY + Math.sin(angle) * maximum * progress,
			};
			const orbit = 18 + Math.sqrt(group.nodes.length) * 13;
			group.nodes.forEach((node, nodeIndex) => {
				const nodeAngle = nodeIndex * goldenAngle + stableNoise(`${node.id}:cloud-node`) * 0.7;
				const distance = group.nodes.length === 1 ? 0 : orbit * (0.35 + 0.55 * Math.sqrt((nodeIndex + 1) / group.nodes.length));
				positions.set(node.id, {
					x: center.x + Math.cos(nodeAngle) * distance,
					y: center.y + Math.sin(nodeAngle) * distance * 0.68,
				});
			});
			return {
				...group,
				center,
				rx: Math.max(58, orbit + 34),
				ry: Math.max(40, (orbit + 34) * 0.66),
				selected,
			};
		});
		return { positions, clusters };
	}

	function alternativePositions(nodes, width = 760, height = 420) {
		return alternativeLayout(nodes, width, height).positions;
	}

	function fullEdgeTooltip(edge, nodesById) {
		const source = nodesById.get(edge.source);
		const target = nodesById.get(edge.target);
		const sourcePair = `${source?.question || source?.question_label || ''} — “${source?.translation || t('translation_pending')}”`;
		const targetPair = `${target?.question || target?.question_label || ''} — “${target?.translation || t('translation_pending')}”`;
		return `• ${sourcePair}\n• ${targetPair}`;
	}

	function relationDisplayText(type) {
		const fallback = {
			affinity: 'Affinity',
			tension: 'Tension',
			complementarity: 'Complementarity',
			continuity: 'Continuity',
		};
		return t(`relation_${type}`) || fallback[type] || 'Relationship';
	}

	function relationLabelSources() {
		return `<span class="diastoles-relation-label-sources" aria-hidden="true">${['affinity', 'tension', 'complementarity', 'continuity'].map((type) => `<span data-relation-label="${type}">${h(relationDisplayText(type))}</span>`).join('')}</span>`;
	}

	function tooltipSourceMarkup(key, lines) {
		return `<span data-map-tooltip-source="${h(key)}">${lines.filter(Boolean).map((line) => `<span>${h(line)}</span>`).join('')}</span>`;
	}

	function edgeTooltipSourceMarkup(edge, nodesById, prefix = '') {
		const source = nodesById.get(edge.source);
		const target = nodesById.get(edge.target);
		const lines = [
			relationDisplayText(edge.type),
			`• ${source?.question || source?.question_label || ''} — “${source?.translation || t('translation_pending')}”`,
			`• ${target?.question || target?.question_label || ''} — “${target?.translation || t('translation_pending')}”`,
		];
		return tooltipSourceMarkup(`${prefix}edge:${edge.id}`, lines);
	}

	function edgeTooltipText(edge, source, target) {
		const sourcePair = `${source?.question || source?.question_label || ''} — “${source?.translation || t('translation_pending')}”`;
		const targetPair = `${target?.question || target?.question_label || ''} — “${target?.translation || t('translation_pending')}”`;
		return `${relationDisplayText(edge.type)}\n• ${sourcePair}\n• ${targetPair}`;
	}

	function mapInstructionsAndLegend() {
		return `${mapInstructions()}<div class="diastoles-map-legend" aria-label="${h(t('network_legend_heading'))}">
			${['affinity', 'tension', 'complementarity', 'continuity'].map((type) => `<span><i class="edge-${type}" aria-hidden="true"></i><span><b data-relation-label="${type}">${h(relationDisplayText(type))}</b><small>${h(t(`relation_${type}_description`))}</small></span></span>`).join('')}
		</div>`;
	}

	function mapInstructions() {
		return `<div class="diastoles-map-instructions">
			<p class="diastoles-map-help">${h(t('network_hover_help'))}</p>
			<p class="diastoles-map-help">${h(t('network_navigation_help'))}</p>
		</div>`;
	}

	function renderOrganicNetworkGraph(nodes, edges, key) {
		const width = 760;
		const height = 520;
		const layout = buildNetworkLayout(nodes);
		const viewKey = `${key}:${nodes.map((node) => node.id).join('|')}:${edges.map((edge) => edge.id).join('|')}`;
		if (!networkView || networkView.key !== viewKey) {
			const initial = fitNetworkView(layout, width, height);
			networkView = { key: viewKey, ...initial, initial: { ...initial } };
		}
		const nodesById = new Map(nodes.map((node) => [node.id, node]));
		const clusterClouds = layout.clusters.map((cluster) => `<g class="diastoles-smell-cluster" aria-label="${h(cluster.label)}">
			<ellipse class="diastoles-smell-cloud${cluster.node_ids.length > 1 ? ' is-interactive' : ''}" cx="${cluster.center.x}" cy="${cluster.center.y}" rx="${cluster.rx}" ry="${cluster.ry}" transform="rotate(${cluster.rotation} ${cluster.center.x} ${cluster.center.y})" tabindex="0" data-map-tooltip="${h(cluster.label)}" data-tooltip-source="${h(`${key}:cloud:${cluster.id}`)}" />
			<text class="diastoles-smell-label" x="${cluster.center.x}" y="${cluster.center.y}">${h(cluster.label)} (${cluster.node_ids.length})</text>
		</g>`).join('');
		const clusterTooltipSources = layout.clusters.map((cluster) => tooltipSourceMarkup(`${key}:cloud:${cluster.id}`, [cluster.label])).join('');
		const edgeParts = edges.map((edge) => {
			const source = layout.positions.get(edge.source);
			const target = layout.positions.get(edge.target);
			if (!source || !target) return null;
			const sourceNode = nodesById.get(edge.source);
			const targetNode = nodesById.get(edge.target);
			const path = curvedEdgePath(source, target, edge.id, sourceNode?.primary_smell_id === targetNode?.primary_smell_id);
			const tooltip = fullEdgeTooltip(edge, nodesById);
			return {
				line: `<g class="diastoles-edge" data-edge-id="${h(edge.id)}"><path d="${path}" class="diastoles-edge-line edge-${h(edge.type)}" /></g>`,
				hit: `<path d="${path}" class="diastoles-edge-hit" data-edge-id="${h(edge.id)}" data-source="${h(edge.source)}" data-target="${h(edge.target)}" data-relation-type="${h(edge.type)}" tabindex="0" data-map-tooltip="${tooltipAttribute(tooltip)}" data-tooltip-source="${h(`${key}:edge:${edge.id}`)}" />`,
				source: edgeTooltipSourceMarkup(edge, nodesById, `${key}:`),
			};
		}).filter(Boolean);
		const nodeMarkup = nodes.map((node) => {
			const point = layout.positions.get(node.id);
			return `<g class="diastoles-node" data-node-id="${h(node.id)}"><circle class="diastoles-node-diffusion" cx="${point.x}" cy="${point.y}" r="72" tabindex="0" data-map-tooltip="${h(node.concept || '')}" data-tooltip-source="${h(`${key}:node:${node.id}`)}" /></g>`;
		}).join('');
		const nodeTooltipSources = nodes.map((node) => tooltipSourceMarkup(`${key}:node:${node.id}`, [node.concept || node.smells?.join(', ') || ''])).join('');
		return `<div class="diastoles-map">
			<div class="diastoles-map-controls"><button type="button" data-action="map-zoom-out" aria-label="${h(t('network_zoom_out'))}">−</button><button type="button" data-action="map-zoom-in" aria-label="${h(t('network_zoom_in'))}">+</button><button type="button" data-action="map-fit-all">${h(t('network_fit_all'))}</button></div>
			<svg viewBox="0 0 ${width} ${height}" role="img" aria-label="${h(t('network_aria_label'))}">
				<defs><radialGradient id="diastoles-node-diffusion"><stop offset="0%" stop-color="#b86946" stop-opacity="1"/><stop offset="24%" stop-color="#b86946" stop-opacity="0.96"/><stop offset="36%" stop-color="#b86946" stop-opacity="0.62"/><stop offset="54%" stop-color="#b86946" stop-opacity="0.24"/><stop offset="76%" stop-color="#b86946" stop-opacity="0.07"/><stop offset="100%" stop-color="#b86946" stop-opacity="0"/></radialGradient><radialGradient id="diastoles-smell-cloud"><stop offset="0%" stop-color="#b86946" stop-opacity="0.32"/><stop offset="58%" stop-color="#b86946" stop-opacity="0.18"/><stop offset="100%" stop-color="#b86946" stop-opacity="0.05"/></radialGradient></defs>
				<g class="diastoles-network-viewport" transform="translate(${networkView.x} ${networkView.y}) scale(${networkView.scale})">${clusterClouds}${edgeParts.map((part) => part.line).join('')}${nodeMarkup}${edgeParts.map((part) => part.hit).join('')}</g>
			</svg>${relationLabelSources()}<span class="diastoles-map-tooltip-sources" aria-hidden="true">${clusterTooltipSources}${nodeTooltipSources}${edgeParts.map((part) => part.source).join('')}</span><div class="diastoles-map-tooltip" role="tooltip" hidden></div>
		</div>`;
	}

	function renderAlternativeGraph(nodes, edges, options = {}) {
		const width = 760;
		const height = options.height || 420;
		const selected = new Set(options.selected || []);
		const related = new Set(options.related || []);
		const layout = alternativeLayout(nodes, width, height, selected);
		const positions = layout.positions;
		const nodesById = new Map(nodes.map((node) => [node.id, node]));
		const cloudMarkup = layout.clusters.map((cluster) => `<g class="diastoles-alt-cloud${cluster.selected ? ' is-selected' : ''}">
			<ellipse cx="${cluster.center.x}" cy="${cluster.center.y}" rx="${cluster.rx}" ry="${cluster.ry}" />
			<text x="${cluster.center.x}" y="${cluster.center.y - cluster.ry + 18}">${h(cluster.label)} (${cluster.nodes.length})</text>
		</g>`).join('');
		const edgeMarkup = edges.map((edge) => {
			const source = positions.get(edge.source);
			const target = positions.get(edge.target);
			if (!source || !target) return '';
			const tooltip = fullEdgeTooltip(edge, nodesById);
			return `<g class="diastoles-alt-edge${selected.has(edge.source) || selected.has(edge.target) ? ' is-selected' : ''}">
				<line class="edge-${h(edge.type)}" x1="${source.x}" y1="${source.y}" x2="${target.x}" y2="${target.y}" />
				<line class="diastoles-alt-edge-hit" x1="${source.x}" y1="${source.y}" x2="${target.x}" y2="${target.y}" tabindex="0" data-source="${h(edge.source)}" data-target="${h(edge.target)}" data-relation-type="${h(edge.type)}" data-map-tooltip="${tooltipAttribute(tooltip)}" aria-label="${tooltipAttribute(tooltip)}" />
			</g>`;
		}).join('');
		const nodeMarkup = nodes.map((node) => {
			const point = positions.get(node.id);
			const isSelected = selected.has(node.id);
			const isRelated = related.has(node.id);
			const isDimmed = options.shrinkUnrelated && selected.size && !isSelected && !isRelated;
			const action = options.nodeAction ? ` data-action="${h(options.nodeAction)}" data-node-id="${h(node.id)}"` : '';
			const radius = isSelected ? 18 : isRelated ? 15 : isDimmed ? 5 : 10;
			return `<g class="diastoles-alt-node${isSelected ? ' is-selected' : ''}${isRelated ? ' is-related' : ''}${isDimmed ? ' is-dimmed' : ''}" data-alt-node="${h(node.id)}" transform="translate(${point.x} ${point.y})"${action} tabindex="0" data-map-tooltip="${h(node.concept || node.smells?.join(', ') || '')}">
				<circle r="${radius}" />
			</g>`;
		}).join('');
		const viewport = options.pannable ? 'diastoles-network-viewport' : 'diastoles-alt-viewport';
		const transform = options.pannable && networkView ? ` transform="translate(${networkView.x} ${networkView.y}) scale(${networkView.scale})"` : '';
		return `<div class="diastoles-map diastoles-alt-graph${options.pannable ? ' is-pannable' : ''}">
			${options.pannable ? `<div class="diastoles-map-controls"><button type="button" data-action="map-zoom-out">−</button><button type="button" data-action="map-zoom-in">+</button><button type="button" data-action="map-fit-all">${h(t('network_fit_all'))}</button></div>` : ''}
			<svg viewBox="0 0 ${width} ${height}" role="img" aria-label="Alternative view of the collective map">
				<g class="${viewport}"${transform}>${cloudMarkup}${edgeMarkup}${nodeMarkup}</g>
			</svg>
			${relationLabelSources()}
			<div class="diastoles-map-tooltip" role="tooltip" hidden></div>
		</div>`;
	}

	function networkAlternativeShell(number, title, introduction, body) {
		return `<section>
			<p class="diastoles-kicker">${h(t('network_kicker'))}</p>
			<h1>${h(t('network_heading'))}</h1>
			${networkVariantTabs()}
			${relationLabelSources()}
			${body}
		</section>`;
	}

	function connectedSmellClusters(edges = network.edges || []) {
		const { ids } = connectedGraphForEdges(edges);
		return (network.smell_clusters || [])
			.map((cluster) => ({ ...cluster, node_ids: cluster.node_ids.filter((id) => ids.has(id)) }))
			.filter((cluster) => cluster.node_ids.length)
			.sort((a, b) => b.node_ids.length - a.node_ids.length || a.label.localeCompare(b.label));
	}

	function focusGraphBySmells(edges, smellIds, includeUnconnectedSameSmell = true) {
		const graph = connectedGraphForEdges(edges);
		const selectedSmells = smellIds instanceof Set ? smellIds : new Set(smellIds || []);
		if (!selectedSmells.size) return graph;
		const availableClusters = includeUnconnectedSameSmell ? (network.smell_clusters || []) : connectedSmellClusters(edges);
		const selectedIds = new Set();
		availableClusters.filter((cluster) => selectedSmells.has(cluster.id)).forEach((cluster) => cluster.node_ids.forEach((id) => selectedIds.add(id)));
		if (!selectedIds.size) return { nodes: [], edges: [], ids: new Set() };
		const focusedEdges = edges.filter((edge) => selectedIds.has(edge.source) || selectedIds.has(edge.target));
		const visibleIds = new Set(selectedIds);
		focusedEdges.forEach((edge) => { visibleIds.add(edge.source); visibleIds.add(edge.target); });
		return {
			nodes: network.nodes.filter((node) => visibleIds.has(node.id)),
			edges: focusedEdges,
			ids: visibleIds,
		};
	}

	function renderScentNavigator(clusters, selectedIds) {
		const action = 'network-layer-smell';
		const selected = selectedIds instanceof Set ? selectedIds : new Set();
		const query = networkLab.scentSearch.trim().toLowerCase();
		let shown = 0;
		const buttons = clusters.map((cluster) => {
			const active = selected.has(cluster.id);
			const matches = !query || cluster.label.toLowerCase().includes(query);
			const visible = active || (matches && shown < 30);
			if (visible && !active) shown += 1;
			return `<button type="button"${visible ? '' : ' hidden'} class="${active ? 'is-active' : ''}" data-scent-result data-search="${h(cluster.label.toLowerCase())}" data-action="${action}" data-smell-id="${h(cluster.id)}">${h(cluster.label)} <small>${cluster.node_ids.length}</small></button>`;
		}).join('');
		return `<div class="diastoles-scent-navigator">
			<div class="diastoles-scent-chips"><button type="button" class="${selected.size ? '' : 'is-active'}" data-action="${action}" data-smell-id="">${h(t('scent_filter_all'))}</button>${buttons}</div>
		</div>`;
	}

	function deterministicStoryStart(nodes) {
		const seed = activeRecoveryToken || window.sessionStorage.getItem(recoveryStorageKey) || 'collective';
		return nodes[Math.floor(stableNoise(`${seed}:story-start`) * nodes.length)] || nodes[0];
	}

	function uniqueStoryResponses(items, excludedResponseId = '') {
		const byResponse = new Map();
		items.forEach((item) => {
			const node = item.node || item;
			if (!node || node.response_id === excludedResponseId || byResponse.has(node.response_id)) return;
			byResponse.set(node.response_id, { node, relation: item.relation || '' });
		});
		return [...byResponse.values()];
	}

	function storyChoices(current, mode, graph) {
		if (mode === 'anchors') {
			const anchors = new Set((current.anchors || []).map((anchor) => anchor.trim().toLowerCase()).filter(Boolean));
			return uniqueStoryResponses(network.nodes.filter((node) => (node.anchors || []).some((anchor) => anchors.has(anchor.trim().toLowerCase()))), current.response_id);
		}
		if (mode === 'question') {
			const question = current.question || current.question_label || '';
			return uniqueStoryResponses(network.nodes.filter((node) => (node.question || node.question_label || '') === question), current.response_id);
		}
		const responseNodeIds = new Set(network.nodes.filter((node) => node.response_id === current.response_id).map((node) => node.id));
		return uniqueStoryResponses(graph.edges
			.filter((edge) => responseNodeIds.has(edge.source) || responseNodeIds.has(edge.target))
			.map((edge) => ({ node: networkNodeMap().get(responseNodeIds.has(edge.source) ? edge.target : edge.source), relation: edge.type })), current.response_id);
	}

	function storyQuestionButton(node) {
		const question = node.question || node.question_label || '';
		return `<button type="button" class="diastoles-story-question-link" data-action="network-story-question" data-node-id="${h(node.id)}">${h(question)}</button>`;
	}

	function storyConceptButton(node) {
		const concept = node.concept || node.canonical_smells?.[0]?.label || '';
		return `<button type="button" class="diastoles-story-concept-link" data-action="network-story-concept" data-node-id="${h(node.id)}">${h(concept)}</button>`;
	}

	function storyResponseCard(node, relation = '') {
		return `<article>${storyQuestionButton(node)}${originalFragment(node)}${storyConceptButton(node)}${relation ? `<small class="diastoles-story-relation">${h(relationDisplayText(relation))}</small>` : ''}<button type="button" class="diastoles-secondary" data-action="network-story-next" data-node-id="${h(node.id)}">${h(t('story_continue_button'))}</button></article>`;
	}

	function renderMapAltThree() {
		const graph = connectedNetwork();
		if (!networkLab.storyPath.length) {
			return networkAlternativeShell(3, t('map_variant_narrative'), 'The trail begins at a random connected fragment, then moves only through real saved relationships. Your starting point varies by private session but remains stable when you return.', `<div class="diastoles-story-start"><button class="diastoles-primary" type="button" data-action="network-story-start">${h(t('story_begin_button'))}</button></div>`);
		}
		const currentId = networkLab.storyPath.at(-1);
		const current = networkNodeMap().get(currentId);
		const mode = networkLab.storyModes.at(-1) || 'connections';
		const choices = storyChoices(current, mode, graph);
		const visibleChoices = choices.slice(0, articleLimits.story);
		const intro = 'Click a scent name to find responses sharing at least one nuance. Click a question to find every response to that question. Click Continue the trail to jump to one of the connections generated from the response.';
		const body = `<article class="diastoles-story-card"><p class="diastoles-kicker">${h(formatText('story_step_label', { count: networkLab.storyPath.length }))}</p>${storyQuestionButton(current)}${originalFragment(current)}${storyConceptButton(current)}</article><div class="diastoles-story-choices"><h3>Where Next?</h3>${choices.length ? `<div class="diastoles-article-grid">${visibleChoices.map(({ node, relation }) => storyResponseCard(node, relation)).join('')}</div>${loadMoreArticles('story', articleLimits.story, choices.length)}` : `<p>${h(t('story_branch_ends'))}</p>`}</div><div class="diastoles-alt-actions">${networkLab.storyPath.length > 1 ? `<button type="button" class="diastoles-secondary" data-action="network-story-back">${h(t('story_back_button'))}</button>` : ''}<button type="button" class="diastoles-secondary" data-action="network-story-restart">${h(t('story_start_elsewhere_button'))}</button></div>`;
		return networkAlternativeShell(3, t('map_variant_narrative'), intro, body);
	}

	function neighborhoodName(node) {
		return node.question_theme || node.theme || 'Question without a theme';
	}

	function questionNeighborhoodKey(node) {
		return String(node.question_id || node.question_label || node.question || 'Question without a label');
	}

	function questionNeighborhoodLabel(node) {
		return node.question_label || node.question || 'Question without a label';
	}

	function renderNeighborhoodModeToggle() {
		return `<label class="diastoles-neighborhood-mode"><input type="checkbox" data-question-neighborhood-mode value="question"${networkLab.neighborhoodMode === 'question' ? ' checked' : ''}> <span>${h(t('question_neighborhood_group_by_question'))}</span></label>`;
	}

	function themeLabel(theme) {
		const key = `theme_${String(theme || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')}`;
		return t(key) || String(theme || '').replace(/[-_]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) || 'Question without a theme';
	}

	function networkNeighborhoods() {
		const groups = new Map();
		const nodesById = networkNodeMap();
		network.nodes.forEach((node) => {
			const id = networkLab.neighborhoodMode === 'question' ? questionNeighborhoodKey(node) : neighborhoodName(node);
			if (!groups.has(id)) {
				groups.set(id, {
					id,
					label: networkLab.neighborhoodMode === 'question' ? questionNeighborhoodLabel(node) : themeLabel(id),
					nodes: [],
				});
			}
			groups.get(id).nodes.push(node);
		});
		return [...groups.values()]
			.map((group) => {
				const nodes = group.nodes;
				const ids = new Set(nodes.map((node) => node.id));
				const responseIds = new Set(nodes.map((node) => node.response_id));
				const internalEdges = network.edges
					.filter((edge) => ids.has(edge.source) && ids.has(edge.target));
				const externalEdges = network.edges
					.filter((edge) => ids.has(edge.source) !== ids.has(edge.target));
				const edges = (networkLab.neighborhoodMode === 'question' ? [...internalEdges, ...externalEdges] : internalEdges)
					.sort((a, b) => Date.parse(b.created || '') - Date.parse(a.created || '') || Number(b.score) - Number(a.score));
				const graphNodes = networkLab.neighborhoodMode === 'question'
					? [...new Map(edges.flatMap((edge) => [nodesById.get(edge.source), nodesById.get(edge.target)]).filter(Boolean).map((node) => [node.id, node])).values()]
					: nodes;
				return {
					...group,
					ids,
					edges,
					internalEdges,
					externalEdges,
					graphNodes,
					responseCount: responseIds.size,
					connectionCount: edges.length,
					internalConnectionCount: internalEdges.length,
					externalConnectionCount: externalEdges.length,
				};
			})
			.sort((a, b) => b.connectionCount - a.connectionCount || b.responseCount - a.responseCount || a.label.localeCompare(b.label));
	}

	function neighborhoodExternalConnections(selected) {
		if (networkLab.neighborhoodMode === 'question') return [];
		const nodesById = networkNodeMap();
		const counts = new Map();
		(network.edges || []).forEach((edge) => {
			const sourceInside = selected.ids.has(edge.source);
			const targetInside = selected.ids.has(edge.target);
			if (sourceInside === targetInside) return;
			const outside = nodesById.get(sourceInside ? edge.target : edge.source);
			const theme = neighborhoodName(outside || {});
			if (!theme || theme === selected.id) return;
			counts.set(theme, (counts.get(theme) || 0) + 1);
		});
		return [...counts.entries()]
			.map(([theme, count]) => ({ theme, label: themeLabel(theme), count }))
			.sort((a, b) => b.count - a.count || a.label.localeCompare(b.label));
	}

	function renderNeighborhoodExternalConnections(selected) {
		if (networkLab.neighborhoodMode === 'question') return '';
		const nodesById = networkNodeMap();
		const external = neighborhoodExternalConnections(selected);
		const externalResponses = new Map();
		(selected.externalEdges || [])
			.slice()
			.sort((a, b) => Date.parse(b.created || '') - Date.parse(a.created || '') || Number(b.score) - Number(a.score))
			.forEach((edge) => {
				const sourceInside = selected.ids.has(edge.source);
				const targetInside = selected.ids.has(edge.target);
				const outside = nodesById.get(sourceInside ? edge.target : edge.source);
				if (!outside || externalResponses.has(outside.response_id)) return;
				externalResponses.set(outside.response_id, { node: outside, relation: edge.type });
			});
		const responses = [...externalResponses.values()];
		if (!external.length && !responses.length) return '';
		const visibleResponses = responses.slice(0, articleLimits.neighborhoodExternal);
		const responseCards = visibleResponses.map(({ node, relation }, index) => `<article data-neighborhood-external-index="${index}"><p class="diastoles-fragment-question">${h(node.question || node.question_label || '')}</p>${originalFragment(node)}<small class="diastoles-story-relation">${h(relationDisplayText(relation))}</small></article>`).join('');
		return `<aside class="diastoles-neighborhood-external">
			<h3>${h(t('neighborhood_external_connections_heading'))}</h3>
			${external.length ? `<div class="diastoles-neighborhood-external-links">${external.map((item) => `<button type="button" data-action="network-neighborhood" data-neighborhood="${h(item.theme)}">${h(formatText('neighborhood_external_connection_count', { theme: item.label, count: item.count }))}</button>`).join('')}</div>` : ''}
			${responseCards ? `<div class="diastoles-neighborhood-external-responses diastoles-article-grid">${responseCards}</div>${loadMoreArticles('neighborhoodExternal', articleLimits.neighborhoodExternal, responses.length)}` : ''}
		</aside>`;
	}

	function neighbourhoodConnectionCard(edge, index = 0) {
		const nodeMap = networkNodeMap();
		const source = nodeMap.get(edge.source);
		const target = nodeMap.get(edge.target);
		if (!source || !target) return '';
		const tooltip = edgeTooltipText(edge, source, target);
		return `<article class="diastoles-connection diastoles-neighborhood-connection" data-question-connection-index="${index}">
			<p class="diastoles-relation">${h(relationDisplayText(edge.type))}</p>
			<p class="diastoles-connection-score">${Math.round(Number(edge.score || 0) * 100)}%</p>
			<p class="diastoles-connection-explanation">${h(tooltip)}</p>
		</article>`;
	}

	function renderMapAltFour() {
		const neighborhoods = networkNeighborhoods();
		const selected = neighborhoods.find((group) => group.id === networkLab.neighborhood);
		const modeToggle = renderNeighborhoodModeToggle();
		if (!selected) {
			const visibleNeighborhoods = neighborhoods.slice(0, networkLab.questionNeighborhoodVisible);
			const cards = visibleNeighborhoods.map((group, index) => {
				return `<button type="button" data-neighborhood-index="${index}" data-action="network-neighborhood" data-neighborhood="${h(group.id)}"><span>${h(group.label)}</span><small>${h(formatText('neighborhood_response_count', { count: group.responseCount }))} · ${h(formatText('neighborhood_internal_connection_count', { count: group.internalConnectionCount }))} · ${h(formatText('neighborhood_external_connection_count_short', { count: group.externalConnectionCount }))}</small></button>`;
			}).join('');
			return networkAlternativeShell(4, t('map_variant_questions'), t('question_neighborhoods_intro'), `${modeToggle}<div class="diastoles-neighborhoods">${cards}</div>${networkLab.questionNeighborhoodVisible < neighborhoods.length ? `<button type="button" class="diastoles-secondary diastoles-load-more" data-action="load-more-neighborhoods">${h(t('load_more_neighborhoods'))}</button>` : ''}`);
		}
		const responses = [...new Map(selected.nodes.map((node) => [node.response_id, node])).values()];
		const visibleResponses = responses.slice(0, articleLimits.questions);
		const responseCards = visibleResponses.map((node) => `<article><p class="diastoles-fragment-question">${h(node.question || node.question_label || '')}</p>${originalFragment(node)}</article>`).join('');
		const visibleEdges = selected.edges.slice(0, articleLimits.questionConnections);
		const edgeCards = visibleEdges.map(neighbourhoodConnectionCard).join('');
		const externalConnections = renderNeighborhoodExternalConnections(selected);
		const emptyConnectionsText = networkLab.neighborhoodMode === 'question' ? t('question_neighborhood_empty_connections') : t('neighborhood_empty_connections');
		const graph = selected.edges.length
			? `<h3 class="diastoles-question-connections">${h(t('neighborhood_connections_heading'))}</h3><div class="diastoles-stack diastoles-article-grid">${edgeCards}</div>${loadMoreArticles('questionConnections', articleLimits.questionConnections, selected.edges.length)}${renderOrganicNetworkGraph(selected.graphNodes || selected.nodes, visibleEdges, `question-neighborhood:${selected.id}:${articleLimits.questionConnections}`)}`
			: `<p class="diastoles-no-inner-connections">${h(emptyConnectionsText)}</p>`;
		return networkAlternativeShell(4, t('map_variant_questions'), t('question_neighborhood_open_intro'), `${modeToggle}<button type="button" class="diastoles-secondary" data-action="network-neighborhood" data-neighborhood="">${h(t('all_neighborhoods_button'))}</button><h3 class="diastoles-neighborhood-title">${h(selected.label)}</h3><div class="diastoles-question-responses diastoles-article-grid">${responseCards}</div>${loadMoreArticles('questions', articleLimits.questions, responses.length)}${graph}${networkLab.neighborhoodMode === 'question' ? '' : externalConnections}`);
	}

	function renderMapAltSeven() {
		const types = ['affinity', 'tension', 'complementarity', 'continuity'];
		const edges = network.edges.filter((edge) => networkLab.layers.has(edge.type) && Number(edge.score) >= networkLab.minimumScore);
		const graph = focusGraphBySmells(edges, networkLab.layerSmells, false);
		const clusters = connectedSmellClusters(edges);
		const controls = types.map((type) => `<button type="button" class="${networkLab.layers.has(type) ? 'is-active' : ''}" data-action="network-layer" data-layer="${type}" aria-describedby="layer-description-${type}"><i class="edge-${type}"></i>${h(relationDisplayText(type))} <small>${network.edges.filter((edge) => edge.type === type).length}</small><span class="diastoles-layer-description" id="layer-description-${type}" role="tooltip"><b>${h(relationDisplayText(type))}</b>${h(t(`relation_${type}_description`))}</span></button>`).join('');
		const minimumIntensity = t('relationship_layers_minimum_intensity');
		const intensity = `<label class="diastoles-intensity-control"><span>${h(minimumIntensity)}</span><input type="range" min="0.3" max="1" step="0.05" value="${networkLab.minimumScore}" data-relationship-intensity aria-label="${h(minimumIntensity)}"></label>`;
		return networkAlternativeShell(7, t('map_variant_layers'), t('relationship_layers_intro'), `<div class="diastoles-layer-controls">${controls}${intensity}</div>${renderScentNavigator(clusters, networkLab.layerSmells)}${graph.nodes.length ? renderOrganicNetworkGraph(graph.nodes, graph.edges, `layers:${[...networkLab.layers].join('-')}:${networkLab.minimumScore}:${[...networkLab.layerSmells].sort().join('-') || 'all'}`) : `<div class="diastoles-alt-placeholder"><p>${h(t('relationship_layers_empty'))}</p></div>`}`);
	}

	function renderNetworkAlternative() {
		return ({
			'alt-3': renderMapAltThree,
			'alt-4': renderMapAltFour,
			'alt-7': renderMapAltSeven,
		}[networkMapVariant] || renderMapAltThree)();
	}

	function renderNetwork() {
		if (!network) {
			window.setTimeout(loadNetwork, 0);
			return `<section class="diastoles-empty"><p class="diastoles-loading">${h(t('network_loading'))}</p></section>`;
		}
		if (!network.nodes.length) {
			return `<section class="diastoles-empty"><h2>${h(t('network_empty_heading'))}</h2><p>${h(t('network_empty_body'))}</p></section>`;
		}
		if (networkMapVariant !== 'current') return renderNetworkAlternative();

		const width = 760;
		const height = 520;
		const layout = buildNetworkLayout();
		const viewKey = network.nodes.map((node) => `${node.id}:${node.primary_smell_id || ''}`).join('|');
		if (!networkView || networkView.key !== viewKey) {
			const initial = fitNetworkView(layout, width, height);
			networkView = { key: viewKey, ...initial, initial: { ...initial } };
		}
		const clusterCount = (count) => count === 1 ? t('network_cluster_count_one') : formatText('network_cluster_count', { count });
		const clusterClouds = layout.clusters.map((cluster) => {
			const interactive = cluster.node_ids.length > 1;
			const cloudInteraction = interactive
				? ` tabindex="0" aria-label="${h(cluster.label)}" data-map-tooltip="${h(cluster.label)}" data-tooltip-source="${h(`global:cloud:${cluster.id}`)}"`
				: '';
			return `
				<g class="diastoles-smell-cluster" aria-label="${h(`${cluster.label}, ${clusterCount(cluster.node_ids.length)}`)}">
					<ellipse class="diastoles-smell-cloud${interactive ? ' is-interactive' : ''}" cx="${cluster.center.x}" cy="${cluster.center.y}" rx="${cluster.rx}" ry="${cluster.ry}" transform="rotate(${cluster.rotation} ${cluster.center.x} ${cluster.center.y})"${cloudInteraction} />
					<text class="diastoles-smell-label" x="${cluster.center.x}" y="${cluster.center.y}">${h(cluster.label)} (${cluster.node_ids.length})</text>
				</g>`;
		})
			.join('');
		const clusterTooltipSources = layout.clusters
			.filter((cluster) => cluster.node_ids.length > 1)
			.map((cluster) => tooltipSourceMarkup(`global:cloud:${cluster.id}`, [cluster.label]))
			.join('');
		const nodesById = new Map(network.nodes.map((node) => [node.id, node]));
		const edgeMarkup = network.edges
			.map((edge) => {
				const source = layout.positions.get(edge.source);
				const target = layout.positions.get(edge.target);
				if (!source || !target) return null;
				const sourceNode = nodesById.get(edge.source);
				const targetNode = nodesById.get(edge.target);
				const tooltip = fullEdgeTooltip(edge, nodesById);
				const sharedCluster = sourceNode?.primary_smell_id === targetNode?.primary_smell_id;
				const path = curvedEdgePath(source, target, edge.id, sharedCluster);
				return {
					line: `<g class="diastoles-edge" data-edge-id="${h(edge.id)}"><path d="${path}" class="diastoles-edge-line edge-${h(edge.type)}" /></g>`,
					hit: `<path d="${path}" class="diastoles-edge-hit" data-edge-id="${h(edge.id)}" data-source="${h(edge.source)}" data-target="${h(edge.target)}" data-relation-type="${h(edge.type)}" tabindex="0" aria-label="${tooltipAttribute(tooltip)}" data-map-tooltip="${tooltipAttribute(tooltip)}" data-tooltip-source="${h(`global:edge:${edge.id}`)}" />`,
					source: edgeTooltipSourceMarkup(edge, nodesById, 'global:'),
				};
			})
			.filter(Boolean);
		const edgeLines = edgeMarkup.map((edge) => edge.line).join('');
		const edgeHits = edgeMarkup.map((edge) => edge.hit).join('');
		const edgeTooltipSources = edgeMarkup.map((edge) => edge.source).join('');
		const circles = network.nodes
			.map((node) => {
				const point = layout.positions.get(node.id);
				if (!point) return '';
				const tooltip = node.concept || node.smells.join(', ');
				return `
					<g class="diastoles-node" data-node-id="${h(node.id)}">
						<circle class="diastoles-node-diffusion" cx="${point.x}" cy="${point.y}" r="72" tabindex="0" aria-label="${h(tooltip)}" data-map-tooltip="${h(tooltip)}" data-tooltip-source="${h(`global:node:${node.id}`)}" />
					</g>`;
			})
			.join('');
		const nodeTooltipSources = network.nodes
			.map((node) => tooltipSourceMarkup(`global:node:${node.id}`, [node.concept || node.smells.join(', ')]))
			.join('');

		return `
			<section>
				<p class="diastoles-kicker">${h(t('network_kicker'))}</p>
				<h1>${h(t('network_heading'))}</h1>
				${networkVariantTabs()}
				<div class="diastoles-map-legend" aria-label="${h(t('network_legend_heading'))}">
					${['affinity', 'tension', 'complementarity', 'continuity']
						.map((type) => `<span><i class="edge-${type}" aria-hidden="true"></i><span><b data-relation-label="${type}">${h(relationDisplayText(type))}</b><small>${h(t(`relation_${type}_description`))}</small></span></span>`)
						.join('')}
				</div>
				<div class="diastoles-map">
					<div class="diastoles-map-controls">
						<button type="button" data-action="map-zoom-out" aria-label="${h(t('network_zoom_out'))}">−</button>
						<button type="button" data-action="map-zoom-in" aria-label="${h(t('network_zoom_in'))}">+</button>
						<button type="button" data-action="map-fit-all">${h(t('network_fit_all'))}</button>
					</div>
					<svg viewBox="0 0 ${width} ${height}" role="img" aria-label="${h(t('network_aria_label'))}">
						<defs>
							<radialGradient id="diastoles-node-diffusion">
								<stop offset="0%" stop-color="#b86946" stop-opacity="1" />
								<stop offset="24%" stop-color="#b86946" stop-opacity="0.96" />
								<stop offset="36%" stop-color="#b86946" stop-opacity="0.62" />
								<stop offset="54%" stop-color="#b86946" stop-opacity="0.24" />
								<stop offset="76%" stop-color="#b86946" stop-opacity="0.07" />
								<stop offset="100%" stop-color="#b86946" stop-opacity="0" />
							</radialGradient>
							<radialGradient id="diastoles-smell-cloud">
								<stop offset="0%" stop-color="#b86946" stop-opacity="0.32" />
								<stop offset="58%" stop-color="#b86946" stop-opacity="0.18" />
								<stop offset="100%" stop-color="#b86946" stop-opacity="0.05" />
							</radialGradient>
						</defs>
						<g class="diastoles-network-viewport" transform="translate(${networkView.x} ${networkView.y}) scale(${networkView.scale})">
							${clusterClouds}${edgeLines}${circles}${edgeHits}
						</g>
					</svg>
					${relationLabelSources()}
					<span class="diastoles-map-tooltip-sources" aria-hidden="true">${clusterTooltipSources}${nodeTooltipSources}${edgeTooltipSources}</span>
					<div class="diastoles-map-tooltip" role="tooltip" hidden></div>
				</div>
			</section>`;
	}

	async function loadNetwork() {
		try {
			network = await api('network');
			if (activeTab === 'network' || activeTab === 'fragments') render();
			scheduleTranslationRefresh();
		} catch (error) {
			renderError(error.message);
		}
	}

	function notice(message) {
		const target = document.getElementById('diastoles-notice');
		if (!target) return;
		target.textContent = message;
		target.classList.add('is-visible');
		window.setTimeout(() => target.classList.remove('is-visible'), 3500);
	}

	function positionMapTooltip(map, tooltip, trigger, event) {
		const mapBounds = map.getBoundingClientRect();
		const triggerBounds = trigger.getBoundingClientRect();
		const tooltipBounds = tooltip.getBoundingClientRect();
		const clientX = event?.clientX || triggerBounds.left + triggerBounds.width / 2;
		const clientY = event?.clientY || triggerBounds.top + triggerBounds.height / 2;
		const left = Math.min(Math.max(12, clientX - mapBounds.left + 14), Math.max(12, mapBounds.width - tooltipBounds.width - 12));
		const top = Math.min(Math.max(12, clientY - mapBounds.top + 14), Math.max(12, mapBounds.height - tooltipBounds.height - 12));
		tooltip.style.left = `${left}px`;
		tooltip.style.top = `${top}px`;
	}

	function updateMapInteraction(trigger, event) {
		const map = trigger.closest('.diastoles-map');
		const tooltip = map?.querySelector('.diastoles-map-tooltip');
		if (!map || !tooltip) return;

		const sourceKey = trigger.dataset.tooltipSource || '';
		const translatedSource = sourceKey
			? Array.from(map.querySelectorAll('[data-map-tooltip-source]')).find((source) => source.dataset.mapTooltipSource === sourceKey)
			: null;
		const translatedLines = translatedSource
			? Array.from(translatedSource.children).map((line) => line.textContent.trim()).filter(Boolean)
			: [];
		if (translatedLines.length) {
			tooltip.textContent = translatedLines.join('\n');
		} else {
			const relationType = trigger.dataset.relationType || '';
			const relationLabel = relationType
				? (root.querySelector(`[data-relation-label="${CSS.escape(relationType)}"]`)?.textContent.trim() || relationDisplayText(relationType))
				: '';
			tooltip.textContent = [relationLabel, trigger.dataset.mapTooltip || ''].filter(Boolean).join('\n');
		}
		tooltip.hidden = false;
		positionMapTooltip(map, tooltip, trigger, event);

		map.querySelectorAll('.diastoles-node.is-related, .diastoles-alt-node.is-hover-related').forEach((node) => node.classList.remove('is-related', 'is-hover-related'));
		map.querySelectorAll('.diastoles-edge.is-active, .diastoles-alt-edge.is-active').forEach((edge) => edge.classList.remove('is-active'));
		const edge = trigger.closest('.diastoles-edge-hit, .diastoles-alt-edge-hit');
		if (!edge) return;
		if (edge.classList.contains('diastoles-edge-hit')) {
			map.querySelectorAll('.diastoles-edge').forEach((line) => line.classList.toggle('is-active', line.dataset.edgeId === edge.dataset.edgeId));
		} else {
			edge.closest('.diastoles-alt-edge')?.classList.add('is-active');
		}
		const connected = new Set([edge.dataset.source, edge.dataset.target]);
		map.querySelectorAll('.diastoles-node').forEach((node) => {
			node.classList.toggle('is-related', connected.has(node.dataset.nodeId));
		});
		map.querySelectorAll('.diastoles-alt-node').forEach((node) => {
			node.classList.toggle('is-hover-related', connected.has(node.dataset.altNode));
		});
	}

	function clearMapInteraction(trigger) {
		const map = trigger.closest('.diastoles-map');
		if (!map) return;
		const tooltip = map.querySelector('.diastoles-map-tooltip');
		if (tooltip) tooltip.hidden = true;
		map.querySelectorAll('.diastoles-node.is-related, .diastoles-alt-node.is-hover-related').forEach((node) => node.classList.remove('is-related', 'is-hover-related'));
		map.querySelectorAll('.diastoles-edge.is-active, .diastoles-alt-edge.is-active').forEach((edge) => edge.classList.remove('is-active'));
	}

	function renderError(message) {
		waitForTranslatedInterface(rememberedInterfaceLanguage || selectedInterfaceLanguage());
		root.innerHTML = `<div class="diastoles-error"><h2>${h(t('error_heading'))}</h2><p>${h(message)}</p><button class="diastoles-secondary" data-action="continue">${h(t('retry_button'))}</button></div>`;
		concealUntranslatedInterface();
		refreshPageTranslation();
	}

	function renderCredit() {
		return `<footer class="diastoles-credit"><span>${h(t('photo_credit_prefix'))} <a href="https://www.pacosantamaria.es/" target="_blank" rel="noopener noreferrer">Paco Santamaría,</a> ${localizedInterfaceMarkup('idea_credit_prefix')} <a href="https://remotefrog.com" target="_blank" rel="noopener noreferrer">Raúl Antón Cuadrado</a> | ${localizedInterfaceMarkup('companion_project_prefix')} <a href="https://sistoles.com/" target="_blank" rel="noopener noreferrer">Sístoles</a></span><a href="${h(privacyUrl)}" target="_blank" rel="noopener noreferrer">${h(t('privacy_footer_label'))}</a></footer>`;
	}

	function setPhotograph(context) {
		let index = 0;
		if (typeof context === 'number') index = Math.abs(context) % photographs.length;
		else if (context === 'connections') index = 7;
		else if (context === 'journey') index = 4;
		else if (context === 'network') index = 1;
		else if (context === 'recovery') index = 2;
		const photograph = photographs[index];
		if (!initialPhotographChosen) {
			// The server-preloaded photograph is already painted before the REST
			// response arrives. Keep it for the initial screen to avoid a second
			// background swap during startup.
			initialPhotographChosen = true;
			return;
		}
		if (background.dataset.photo === photograph) return;
		const request = ++photographRequest;
		const version = assetsVersion ? `?ver=${encodeURIComponent(assetsVersion)}` : '';
		const url = `${assetsRoot}${photograph}${version}`;
		const image = new Image();
		image.src = url;
		const ready = typeof image.decode === 'function'
			? image.decode().catch(() => undefined)
			: new Promise((resolve) => {
				image.addEventListener('load', resolve, { once: true });
				image.addEventListener('error', resolve, { once: true });
			});
		ready.then(() => {
			if (request !== photographRequest) return;
			background.style.setProperty('--dia-photo', `url("${url}")`);
			background.dataset.photo = photograph;
			root.dataset.photo = photograph;
		});
	}

	function profilePayload(formData) {
		const split = (name) =>
			String(formData.get(name) || '')
				.split(',')
				.map((item) => item.trim())
				.filter(Boolean);
		const profile = {
			gender: formData.get('gender') || '',
			age_band: formData.get('age_band') || '',
			environment: formData.get('environment') || '',
			country: formData.get('country') || '',
			continent: formData.get('continent') || '',
			native_languages: split('native_languages'),
			other_languages: split('other_languages'),
			multilingual_status: formData.get('multilingual_status') || '',
			work_areas: split('work_areas'),
			hobbies: split('hobbies'),
			rooted_places: split('rooted_places'),
		};
		return profile;
	}

	root.addEventListener('click', async (event) => {
		const button = event.target.closest('[data-action]');
		if (!button) return;
		const action = button.dataset.action;
		if (action === 'tab') {
			event.preventDefault();
			activeTab = button.dataset.tab;
			render();
			logEvent('tab_opened', screenForTab(activeTab), { tab: activeTab });
			scheduleTranslationRefresh();
		} else if (action === 'sillage-tab') {
			event.preventDefault();
			activeSillageTab = button.dataset.sillageTab || 'notes';
			articleLimits.connections = 12;
			articleLimits.traces = 12;
			render();
			logEvent('tab_opened', 'your_sillage', { subtab: activeSillageTab });
			scheduleTranslationRefresh();
		} else if (action === 'network-map-variant') {
			networkMapVariant = button.dataset.variant || 'current';
			networkView = null;
			render();
			logEvent('map_variant_opened', 'collective_maps', { variant: networkMapVariant });
		} else if (action === 'network-layer-smell') {
			const smellId = button.dataset.smellId || '';
			if (!smellId) networkLab.layerSmells.clear();
			else if (networkLab.layerSmells.has(smellId)) networkLab.layerSmells.delete(smellId);
			else if (networkLab.layerSmells.size < 30) networkLab.layerSmells.add(smellId);
			else notice('You can select up to 30 scents.');
			networkView = null;
			render();
		} else if (action === 'network-story-start') {
			const nodes = connectedNetwork().nodes;
			const first = deterministicStoryStart(nodes);
			const index = Math.max(0, nodes.findIndex((node) => node.id === first?.id));
			networkLab.storyPath = nodes.length ? [nodes[(index + networkLab.storyOffset) % nodes.length].id] : [];
			networkLab.storyModes = networkLab.storyPath.length ? ['connections'] : [];
			articleLimits.story = 12;
			render();
		} else if (action === 'network-story-next') {
			networkLab.storyPath.push(button.dataset.nodeId);
			networkLab.storyModes.push('connections');
			articleLimits.story = 12;
			render();
		} else if (action === 'network-story-back') {
			networkLab.storyPath.pop();
			networkLab.storyModes.pop();
			articleLimits.story = 12;
			render();
		} else if (action === 'network-story-restart') {
			networkLab.storyOffset += 1;
			networkLab.storyPath = [];
			networkLab.storyModes = [];
			articleLimits.story = 12;
			render();
		} else if (action === 'network-story-concept') {
			networkLab.storyPath.push(button.dataset.nodeId);
			networkLab.storyModes.push('anchors');
			articleLimits.story = 12;
			render();
		} else if (action === 'network-story-question') {
			networkLab.storyPath.push(button.dataset.nodeId);
			networkLab.storyModes.push('question');
			articleLimits.story = 12;
			render();
		} else if (action === 'network-neighborhood') {
			networkLab.neighborhood = button.dataset.neighborhood || '';
			articleLimits.questions = 12;
			articleLimits.questionConnections = 12;
			articleLimits.neighborhoodExternal = 12;
			render();
		} else if (action === 'load-more-neighborhoods') {
			const previousVisible = networkLab.questionNeighborhoodVisible;
			networkLab.questionNeighborhoodVisible += 12;
			pendingScrollTarget = `[data-neighborhood-index="${previousVisible}"]`;
			render();
			logEvent('load_more', 'collective_maps', { list: 'neighborhoods', visible_before: previousVisible, visible_after: networkLab.questionNeighborhoodVisible, variant: networkMapVariant });
		} else if (action === 'load-more-fragments') {
			appendSharedFragments(button);
		} else if (action === 'load-more-articles') {
			const list = button.dataset.list;
			if (Object.prototype.hasOwnProperty.call(articleLimits, list)) {
				const previousVisible = articleLimits[list];
				articleLimits[list] += 12;
				if (list === 'questionConnections') pendingScrollTarget = `[data-question-connection-index="${previousVisible}"]`;
				if (list === 'questions') pendingScrollTarget = `.diastoles-question-responses article:nth-child(${previousVisible + 1})`;
				if (list === 'neighborhoodExternal') pendingScrollTarget = `[data-neighborhood-external-index="${previousVisible}"]`;
			}
			render();
			logEvent('load_more', screenForTab(), { list, variant: networkMapVariant, sillage_tab: activeSillageTab });
		} else if (action === 'network-layer') {
			const layer = button.dataset.layer;
			if (networkLab.layers.has(layer)) networkLab.layers.delete(layer);
			else networkLab.layers.add(layer);
			render();
		} else if (action === 'continue') {
			await load();
		} else if (action === 'map-zoom-in') {
			zoomNetwork(1.25);
		} else if (action === 'map-zoom-out') {
			zoomNetwork(0.8);
		} else if (action === 'map-fit-all') {
			resetNetworkView();
		} else if (action === 'expand-short-fragment') {
			const form = button.closest('#diastoles-response-form');
			form?.querySelector('.diastoles-short-fragment')?.setAttribute('hidden', '');
			form?.querySelector('[name="original_text"]')?.focus();
		} else if (action === 'share-short-fragment') {
			const form = button.closest('#diastoles-response-form');
			if (!form) return;
			form.dataset.shortFragmentConfirmed = 'true';
			form.requestSubmit();
		} else if (action === 'bookmark-recovery') {
			const url = button.dataset.url;
			const hint = button.closest('.diastoles-recovery')?.querySelector('.diastoles-bookmark-hint');
			const shortcut = button.dataset.shortcut || (/Mac|iPhone|iPad/i.test(navigator.platform || navigator.userAgent) ? '⌘D' : 'Ctrl+D');
			if (window.external?.AddFavorite) {
				try {
					window.external.AddFavorite(url, t('share_title'));
					button.textContent = t('bookmark_button');
					return;
				} catch (error) {
					// Modern browsers usually block programmatic bookmarks; fall through to share/copy.
				}
			}
			if (window.sidebar?.addPanel) {
				try {
					window.sidebar.addPanel(t('share_title'), url, '');
					button.textContent = t('bookmark_button');
					return;
				} catch (error) {
					// Modern browsers usually block programmatic bookmarks; fall through to share/copy.
				}
			}
			const canShare = typeof navigator.share === 'function' && /Android|iPhone|iPad|Mobile/i.test(navigator.userAgent);
			if (canShare) {
				try {
					await navigator.share({
						title: t('share_title'),
						text: t('share_text'),
						url,
					});
					button.textContent = t('shared_confirmation');
					return;
				} catch (error) {
					if (error.name === 'AbortError') return;
				}
			}
			await copyText(url);
			button.textContent = formatText('bookmark_copied_confirmation', { shortcut: button.dataset.shortcut });
			if (hint) hint.textContent = bookmarkFallbackHint(shortcut);
		} else if (action === 'download-shortcut') {
			downloadPrivateShortcut(button.dataset.url);
			const hint = button.closest('.diastoles-recovery')?.querySelector('.diastoles-bookmark-hint');
			if (hint) {
				hint.textContent = selectedInterfaceLanguage() === 'es'
					? 'He descargado un acceso directo a tu enlace privado. Guárdalo en el escritorio o en una carpeta privada.'
					: 'I downloaded a shortcut to your private link. Keep it on your desktop or in a private folder.';
			}
		} else if (action === 'copy-recovery') {
			await copyText(button.dataset.url);
			button.textContent = t('copied_confirmation');
		} else if (action === 'skip-question') {
			await api(`questions/${button.dataset.id}/skip`, { method: 'POST', body: '{}' });
			await load();
		} else if (action === 'withdraw') {
			if (!window.confirm(t('withdraw_confirmation'))) return;
			await api(`responses/${button.dataset.id}/withdraw`, { method: 'POST', body: '{}' });
			await load();
		}
	});

	root.addEventListener('change', (event) => {
		if (event.target.matches('[data-interface-language]')) {
			const from = selectedInterfaceLanguage();
			applyInterfaceLanguage(event.target.value);
			network = null;
			translationPolls = 0;
			logEvent('language_changed', screenForTab(), { from, to: selectedInterfaceLanguage() });
			if (root.dataset.screen === 'recovery') {
				renderRecovery(activeRecoveryUrl || window.location.href);
				return;
			}
			if (root.dataset.screen === 'welcome') {
				renderWelcome();
				return;
			}
			load();
			return;
		}
		if (event.target.matches('[data-consent-toggle]')) {
			const form = event.target.closest('#diastoles-join-form');
			const notice = form?.querySelector('.diastoles-consent-notice');
			if (event.target.checked) {
				notice?.classList.remove('is-missing');
				event.target.removeAttribute('aria-invalid');
			}
			return;
		}
		if (event.target.matches('[data-relationship-intensity]')) {
			networkLab.minimumScore = Number(event.target.value) || 0.3;
			networkView = null;
			render();
			return;
		}
		if (event.target.matches('[data-question-neighborhood-mode]')) {
			networkLab.neighborhoodMode = event.target.checked ? 'question' : 'theme';
			networkLab.neighborhood = '';
			networkLab.questionNeighborhoodVisible = 12;
			articleLimits.questions = 12;
			articleLimits.questionConnections = 12;
			networkView = null;
			render();
			return;
		}
		if (event.target.matches('[data-scent-select]')) {
			const selected = [...event.target.selectedOptions].map((option) => option.value).filter(Boolean).slice(0, 30);
			networkLab.layerSmells = new Set(selected);
			networkView = null;
			render();
		}
	});

	root.addEventListener('scroll', (event) => {
		if (event.target?.classList?.contains('diastoles-map-variants')) {
			updateMapVariantScrollCues();
		}
	}, true);

	window.addEventListener('resize', () => {
		window.requestAnimationFrame(updateMapVariantScrollCues);
	});

	root.addEventListener('input', (event) => {
		if (event.target.matches('[data-scent-name-search]')) {
			const query = event.target.value.trim().toLowerCase();
			networkLab.scentSearch = event.target.value;
			const navigator = event.target.closest('.diastoles-scent-navigator');
			let shown = 0;
			navigator?.querySelectorAll('[data-scent-result]').forEach((item, index) => {
				const matches = !query || item.dataset.search.includes(query) || item.textContent.toLowerCase().includes(query);
				const selected = item.classList.contains('is-active');
				const visible = selected || (matches && shown < 30);
				item.hidden = !visible;
				if (visible && !selected) shown += 1;
			});
		}
	});

	root.addEventListener(
		'wheel',
		(event) => {
			if (!event.target.closest('.diastoles-map') || event.target.closest('.diastoles-map-controls')) return;
			event.preventDefault();
			zoomNetwork(event.deltaY < 0 ? 1.12 : 0.89, event.clientX, event.clientY);
		},
		{ passive: false },
	);

	root.addEventListener('pointerdown', (event) => {
		const map = event.target.closest('.diastoles-map');
		if (
			!map ||
			!networkView ||
			event.button !== 0 ||
			event.target.closest('[data-map-tooltip]') ||
			event.target.closest('.diastoles-map-controls')
		) {
			return;
		}
		const svg = map.querySelector('svg');
		const bounds = svg?.getBoundingClientRect();
		if (!svg || !bounds) return;
		mapDrag = {
			map,
			pointerId: event.pointerId,
			startX: event.clientX,
			startY: event.clientY,
			originX: networkView.x,
			originY: networkView.y,
			scaleX: 760 / bounds.width,
			scaleY: 520 / bounds.height,
		};
		map.classList.add('is-dragging');
		map.setPointerCapture?.(event.pointerId);
	});

	root.addEventListener('pointerover', (event) => {
		const trigger = event.target.closest('[data-map-tooltip]');
		if (trigger) updateMapInteraction(trigger, event);
	});

	root.addEventListener('pointermove', (event) => {
		if (mapDrag && networkView) {
			networkView.x = mapDrag.originX + (event.clientX - mapDrag.startX) * mapDrag.scaleX;
			networkView.y = mapDrag.originY + (event.clientY - mapDrag.startY) * mapDrag.scaleY;
			applyNetworkTransform();
			return;
		}
		const trigger = event.target.closest('[data-map-tooltip]');
		const map = trigger?.closest('.diastoles-map');
		const tooltip = map?.querySelector('.diastoles-map-tooltip');
		if (trigger && map && tooltip) positionMapTooltip(map, tooltip, trigger, event);
	});

	root.addEventListener('pointerout', (event) => {
		const trigger = event.target.closest('[data-map-tooltip]');
		if (trigger && !trigger.contains(event.relatedTarget)) clearMapInteraction(trigger);
	});

	const finishMapDrag = (event) => {
		if (!mapDrag || (event.pointerId !== undefined && event.pointerId !== mapDrag.pointerId)) return;
		mapDrag.map.classList.remove('is-dragging');
		mapDrag.map.releasePointerCapture?.(mapDrag.pointerId);
		mapDrag = null;
	};
	root.addEventListener('pointerup', finishMapDrag);
	root.addEventListener('pointercancel', finishMapDrag);

	root.addEventListener('focusin', (event) => {
		const trigger = event.target.closest('[data-map-tooltip]');
		if (trigger) updateMapInteraction(trigger);
	});

	root.addEventListener('focusout', (event) => {
		const trigger = event.target.closest('[data-map-tooltip]');
		if (trigger) clearMapInteraction(trigger);
	});

	root.addEventListener('submit', async (event) => {
		event.preventDefault();
		const form = event.target;
		if (form.id === 'diastoles-response-form' && form.dataset.shortFragmentConfirmed !== 'true') {
			const values = new FormData(form);
			const wordCount = String(values.get('original_text') || '').trim().split(/\s+/u).filter(Boolean).length;
			if (wordCount > 0 && wordCount <= 3) {
				const invitation = form.querySelector('.diastoles-short-fragment');
				invitation?.removeAttribute('hidden');
				invitation?.focus();
				return;
			}
		}
		const submit = form.querySelector('[type="submit"]');
		if (submit) submit.disabled = true;
		try {
			if (form.id === 'diastoles-join-form') {
				const values = new FormData(form);
				if (values.get('consent_fragments') !== '1') {
					if (submit) submit.disabled = false;
					revealConsentRequirement(form);
					return;
				}
				const result = await api('session', {
					method: 'POST',
					body: JSON.stringify({
						pseudonym: values.get('pseudonym'),
						publication_email: values.get('publication_email'),
						consent_fragments: values.get('consent_fragments') === '1',
						website: values.get('website'),
						...profilePayload(values),
					}),
				});
				if (result.existing) {
					await load();
					return;
				}
				rememberRecoveryUrl(result.recovery_url);
				renderRecovery(result.recovery_url);
			} else if (form.id === 'diastoles-profile-form') {
				const values = new FormData(form);
				const result = await api('profile', {
					method: 'POST',
					body: JSON.stringify(profilePayload(values)),
				});
				state.profile = result.profile;
				notice(t('profile_saved_notice'));
				if (submit) submit.disabled = false;
			} else if (form.id === 'diastoles-response-form') {
				const values = new FormData(form);
				await api('responses', {
					method: 'POST',
					body: JSON.stringify({
						question_id: Number(values.get('question_id')),
						original_text: values.get('original_text'),
						explanation_text: values.get('explanation_text'),
						allow_network: true,
						website: values.get('website'),
					}),
				});
				notice(t('response_saved_notice'));
				await load();
			}
		} catch (error) {
			notice(error.message);
			if (submit) submit.disabled = false;
		}
	});

	applyInterfaceLanguage(selectedInterfaceLanguage());
	load();
})();
