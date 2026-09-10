<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_Texts {
	private const OPTION = 'diastoles_interface_texts';
	private const VERSION_OPTION = 'diastoles_interface_texts_version';
	private const VERSION = '0.15.25';

	public static function fields(): array {
		return array(
			'Welcome' => array(
				'welcome_kicker' => array(
					'label'   => 'Kicker',
					'default' => 'An anonymous collective experience',
				),
				'welcome_heading' => array(
					'label'       => 'Main heading',
					'default'     => "What remains\nwhen everything\nis connected?",
					'type'        => 'textarea',
					'description' => 'Use a new line where you want a visual line break.',
				),
				'welcome_intro' => array(
					'label'   => 'Introduction',
					'default' => "Enter through a smell. Leave a fragment. Return whenever you want\nto discover where another person's words touch yours.",
					'type'    => 'textarea',
				),
				'language_note' => array(
					'label'   => 'Language note',
					'default' => 'Write in the language in which you feel most free.',
				),
				'pseudonym_label' => array(
					'label'   => 'Pseudonym label',
					'default' => 'Pseudonym (optional)',
				),
				'publication_email_label' => array(
					'label'   => 'Publication email label',
					'default' => 'Email (optional)',
				),
				'publication_email_note' => array(
					'label'   => 'Publication email explanation',
					'default' => 'Only if you would like to be notified when the exploration results are published. It will not be used for any other purpose.',
					'type'    => 'textarea',
				),
				'optional_coordinates_summary' => array(
					'label'   => 'Optional details heading',
					'default' => 'CLICK HERE to share a few optional details — they help build more meaningful connections',
				),
				'optional_coordinates_intro' => array(
					'label'   => 'Optional details explanation',
					'default' => 'These details would be quietly used to enrich and diversify matches. Answer only what feels relevant to who you are. Leaving everything blank is also perfectly ok.',
					'type'    => 'textarea',
				),
				'consent_label' => array(
					'label'   => 'Participation notice',
					'default' => 'I agree that my words may be stored, translated and displayed anonymously as part of the Diástoles creative project. I can withdraw them later using my private link.',
					'type'    => 'textarea',
				),
				'privacy_note' => array(
					'label'   => 'Privacy note',
					'default' => 'No name, email address or any other personal data is required to participate, but you may add personal details that will be used exclusively to build more meaningful connections. If you provide an email address, it will be stored only for the publication notice.',
					'type'    => 'textarea',
				),
				'privacy_link_label' => array(
					'label'   => 'Privacy notice link',
					'default' => 'Read the Privacy Notice.',
				),
				'enter_button' => array(
					'label'   => 'Enter button',
					'default' => 'Enter the experience',
				),
			),
			'Experience navigation' => array(
				'header_tagline' => array(
					'label'   => 'Header tagline',
					'default' => 'A living network of scent, memory and connection',
				),
				'mock_mode_label' => array(
					'label'   => 'Mock mode label',
					'default' => 'Mock mode',
				),
				'nav_question' => array(
					'label'   => 'Spark tab',
					'default' => 'Spark',
				),
				'nav_sillage' => array(
					'label'   => 'Sillage tab',
					'default' => 'Your Sillage',
				),
				'nav_notes' => array(
					'label'   => 'Notes subtab',
					'default' => 'Your Notes',
				),
				'nav_affinities' => array(
					'label'   => 'Affinities subtab',
					'default' => 'Affinities',
				),
				'nav_resonances' => array(
					'label'   => 'Resonances tab',
					'default' => 'Resonances',
				),
				'nav_traces' => array(
					'label'   => 'Traces tab',
					'default' => 'Your Trace',
				),
				'nav_shared_fragments' => array(
					'label'   => 'Atmosphere tab',
					'default' => 'Atmosphere',
				),
				'nav_map' => array(
					'label'   => 'Map tab',
					'default' => 'Collective Maps',
				),
			),
			'Question flow' => array(
				'daily_limit_kicker' => array(
					'label'   => 'Daily limit kicker',
					'default' => 'Five traces today',
				),
				'daily_limit_heading' => array(
					'label'   => 'Daily limit heading',
					'default' => 'Let the words breathe.',
				),
				'daily_limit_body' => array(
					'label'   => 'Daily limit explanation',
					'default' => 'You have left five traces today. Return tomorrow and see what has begun to resonate.',
					'type'    => 'textarea',
				),
				'all_questions_kicker' => array(
					'label'   => 'All questions answered kicker',
					'default' => 'For now',
				),
				'all_questions_heading' => array(
					'label'   => 'All questions answered heading',
					'default' => 'You have answered every available question.',
				),
				'all_questions_body' => array(
					'label'   => 'All questions answered explanation',
					'default' => 'Return later. The collective map may have changed.',
					'type'    => 'textarea',
				),
				'daily_count_template' => array(
					'label'       => 'Daily count',
					'default'     => '{count} of {limit} traces today',
					'description' => 'Keep {count} and {limit}.',
				),
				'fragment_label' => array(
					'label'   => 'Fragment field',
					'default' => 'Turn the smell into a fragment',
				),
				'fragment_guidance' => array(
					'label'   => 'Fragment writing guidance',
					'default' => 'You can simply name it, or go further and give it a place, a moment, a texture, or something it hides…',
					'type'    => 'textarea',
				),
				'short_fragment_prompt' => array(
					'label'   => 'Short fragment invitation',
					'default' => 'Sometimes a fragment gains depth when you give it a place, a circumstance, or something it carries. But you can choose brevity when that feels truer to what you feel.',
					'type'    => 'textarea',
				),
				'short_fragment_expand_button' => array(
					'label'   => 'Expand short fragment button',
					'default' => 'Expand it',
				),
				'short_fragment_share_button' => array(
					'label'   => 'Share short fragment button',
					'default' => 'Share it as it is',
				),
				'optional_suffix' => array(
					'label'   => 'Optional suffix',
					'default' => '(optional)',
				),
				'submit_response_button' => array(
					'label'   => 'Submit response button',
					'default' => 'Leave this trace',
				),
				'skip_question_button' => array(
					'label'   => 'Skip button',
					'default' => 'Skip this spark',
				),
			),
			'Resonances and traces' => array(
				'connections_empty_kicker' => array(
					'label'   => 'Empty resonances kicker',
					'default' => 'Affinities',
				),
				'connections_empty_heading' => array(
					'label'   => 'Empty resonances heading',
					'default' => 'No kindred scents have met yours yet.',
				),
				'connections_empty_body' => array(
					'label'   => 'Empty resonances explanation',
					'default' => '',
					'type'    => 'textarea',
				),
				'connections_kicker' => array(
					'label'   => 'Resonances kicker',
					'default' => 'Affinities',
				),
				'connections_heading' => array(
					'label'   => 'Resonances heading',
					'default' => 'Where another voice touches yours',
				),
				'translation_summary' => array(
					'label'   => 'Translation disclosure',
					'default' => 'Translation',
				),
				'translation_pending' => array(
					'label'   => 'Translation pending',
					'default' => 'Translation is being prepared…',
				),
				'journey_kicker' => array(
					'label'   => 'Traces kicker',
					'default' => 'Your Notes',
				),
				'journey_heading' => array(
					'label'   => 'Traces heading',
					'default' => 'The trace you have left behind',
				),
				'journey_empty' => array(
					'label'   => 'Empty traces message',
					'default' => 'Your traces will gather here.',
				),
				'response_status_complete' => array(
					'label'   => 'Response status: complete',
					'default' => 'Processed',
				),
				'response_status_pending' => array(
					'label'   => 'Response status: pending',
					'default' => 'Preparing…',
				),
				'response_status_processing' => array(
					'label'   => 'Response status: processing',
					'default' => 'Preparing…',
				),
				'response_status_needs_review' => array(
					'label'   => 'Response status: needs review',
					'default' => 'Pending review',
				),
				'response_status_failed' => array(
					'label'   => 'Response status: failed',
					'default' => 'Needs retry',
				),
				'response_status_withdrawn' => array(
					'label'   => 'Response status: withdrawn',
					'default' => 'Withdrawn',
				),
				'withdraw_button' => array(
					'label'   => 'Withdraw button',
					'default' => 'Withdraw',
				),
				'withdraw_confirmation' => array(
					'label'   => 'Withdraw confirmation',
					'default' => 'Withdraw this fragment from future connections and the collective map?',
					'type'    => 'textarea',
				),
			),
			'Collective Maps' => array(
				'network_loading' => array(
					'label'   => 'Loading message',
					'default' => 'Growing the collective map…',
				),
				'network_empty_heading' => array(
					'label'   => 'Empty map heading',
					'default' => 'The first roots have not appeared yet.',
				),
				'network_empty_body' => array(
					'label'   => 'Empty map explanation',
					'default' => 'Anonymous fragments shared with the network will gather here.',
					'type'    => 'textarea',
				),
				'network_kicker' => array(
					'label'   => 'Map kicker',
					'default' => 'Collective Maps',
				),
				'network_heading' => array(
					'label'   => 'Map heading',
					'default' => 'A constellation of scents',
				),
				'map_variant_global' => array(
					'label'   => 'Constellation view',
					'default' => 'Constellation',
				),
				'map_variant_narrative' => array(
					'label'   => 'Scent trail view',
					'default' => 'Scent Trail',
				),
				'map_variant_questions' => array(
					'label'   => 'Trigger clouds view',
					'default' => 'Triggers',
				),
				'map_variant_layers' => array(
					'label'   => 'Relationship layers view',
					'default' => 'Kindred Scents',
				),
				'story_begin_button' => array(
					'label'   => 'Scent trail begin button',
					'default' => 'Begin somewhere',
				),
				'story_continue_button' => array(
					'label'   => 'Scent trail continue button',
					'default' => 'Continue the trail →',
				),
				'story_back_button' => array(
					'label'   => 'Scent trail back button',
					'default' => 'Back',
				),
				'story_start_elsewhere_button' => array(
					'label'   => 'Scent trail restart button',
					'default' => 'Start elsewhere',
				),
				'story_step_label' => array(
					'label'       => 'Scent trail step label',
					'default'     => 'Step {count}',
					'description' => 'Keep {count}; it becomes the current scent-trail step number.',
				),
				'story_branch_ends' => array(
					'label'   => 'Scent trail empty branch',
					'default' => 'This branch ends here.',
				),
				'relationship_layers_intro' => array(
					'label'   => 'Relationship layers intro',
					'default' => 'Turn relationship families on and off, choose the minimum intensity, then explore all connected scents or focus on one or some.',
					'type'    => 'textarea',
				),
				'scent_filter_all' => array(
					'label'   => 'All-scents filter',
					'default' => 'All',
				),
				'relationship_layers_minimum_intensity' => array(
					'label'   => 'Relationship layers minimum intensity',
					'default' => 'Minimum affinity intensity',
				),
				'relationship_layers_empty' => array(
					'label'   => 'Relationship layers empty state',
					'default' => 'No relationships match the active layers and intensity.',
				),
				'network_hover_help' => array(
					'label'   => 'Map interaction instructions',
					'default' => 'Hover over a point to reveal its complete scent concept. Hover over a line to discover which two concepts are connected.',
					'type'    => 'textarea',
				),
				'network_connection_evidence' => array(
					'label'       => 'Map connection evidence',
					'default'     => 'Based on participant text: {evidence}',
					'description' => 'Keep {evidence}; it becomes verbatim support identified as participant response or explanation.',
				),
				'network_navigation_help' => array(
					'label'   => 'Map navigation instructions',
					'default' => 'Drag to move through the graph. Use the mouse wheel or the controls to zoom.',
					'type'    => 'textarea',
				),
				'network_zoom_in' => array(
					'label'   => 'Zoom-in control',
					'default' => 'Zoom in',
				),
				'network_zoom_out' => array(
					'label'   => 'Zoom-out control',
					'default' => 'Zoom out',
				),
				'network_fit_all' => array(
					'label'   => 'Fit-all control',
					'default' => 'Fit Space',
				),
				'network_shared_smell' => array(
					'label'   => 'Shared-nuance legend',
					'default' => 'Shared nuance',
				),
				'network_cluster_count' => array(
					'label'       => 'Shared-anchor concept count',
					'default'     => '{count} concepts',
					'description' => 'Keep {count}.',
				),
				'network_cluster_count_one' => array(
					'label'   => 'Single concept count',
					'default' => '1 concept',
				),
				'network_legend_heading' => array(
					'label'   => 'Relationship legend heading',
					'default' => 'Relationships',
				),
				'relation_affinity' => array(
					'label'   => 'Affinity relationship',
					'default' => 'Affinity',
				),
				'relation_affinity_description' => array(
					'label'   => 'Affinity explanation',
					'default' => 'The fragments share a feeling, memory or meaning.',
				),
				'relation_tension' => array(
					'label'   => 'Tension relationship',
					'default' => 'Tension',
				),
				'relation_tension_description' => array(
					'label'   => 'Tension explanation',
					'default' => 'The fragments hold contrasting or conflicting experiences.',
				),
				'relation_complementarity' => array(
					'label'   => 'Complementarity relationship',
					'default' => 'Complementarity',
				),
				'relation_complementarity_description' => array(
					'label'   => 'Complementarity explanation',
					'default' => 'The fragments are different, but each adds context to the other.',
				),
				'relation_continuity' => array(
					'label'   => 'Continuity relationship',
					'default' => 'Continuity',
				),
				'relation_continuity_description' => array(
					'label'   => 'Continuity explanation',
					'default' => 'One fragment carries forward a place, memory, feeling or image introduced by the other.',
				),
				'network_aria_label' => array(
					'label'   => 'Accessible map label',
					'default' => 'Network of anonymous responses',
				),
				'network_recent_heading' => array(
					'label'   => 'Recent fragments heading',
					'default' => 'Notes released into the air',
				),
				'shared_fragments_kicker' => array(
					'label'   => 'Atmosphere kicker',
					'default' => 'The shared atmosphere',
				),
				'load_more_fragments' => array(
					'label'   => 'Load more fragments button',
					'default' => 'Load 12 more',
				),
				'load_more_items' => array(
					'label'   => 'Generic load-more button',
					'default' => 'Load 12 more',
				),
				'load_more_neighborhoods' => array(
					'label'   => 'Load more trigger clouds button',
					'default' => 'Load 12 more trigger clouds',
				),
				'question_neighborhood_group_by_question' => array(
					'label'   => 'Specific spark grouping checkbox',
					'default' => 'Specific spark',
				),
				'question_neighborhoods_intro' => array(
					'label'   => 'Trigger clouds intro',
					'default' => 'The most active trigger clouds appear first. Open one to read its responses and see the relationships it gathers.',
					'type'    => 'textarea',
				),
				'question_neighborhood_open_intro' => array(
					'label'   => 'Open trigger cloud intro',
					'default' => 'Inside a trigger cloud, responses come from sparks that share the same theme. Relationships are ordered by recency and shown in groups of 12.',
					'type'    => 'textarea',
				),
				'all_neighborhoods_button' => array(
					'label'   => 'All trigger clouds button',
					'default' => '← All trigger clouds',
				),
				'neighborhood_response_count' => array(
					'label'       => 'Neighbourhood response count',
					'default'     => '{count} responses',
					'description' => 'Keep {count}; it becomes the number of unique responses.',
				),
				'neighborhood_connection_count' => array(
					'label'       => 'Neighbourhood connection count',
					'default'     => '{count} connections',
					'description' => 'Keep {count}; it becomes the number of approved connections.',
				),
				'neighborhood_internal_connection_count' => array(
					'label'       => 'Neighbourhood internal connection count',
					'default'     => '{count} internal connections',
					'description' => 'Keep {count}; it becomes the number of approved connections between responses from questions in the same neighbourhood.',
				),
				'neighborhood_external_connection_count_short' => array(
					'label'       => 'Neighbourhood external connection count',
					'default'     => '{count} external connections',
					'description' => 'Keep {count}; it becomes the number of approved connections to responses from questions in other neighbourhoods.',
				),
				'neighborhood_connections_heading' => array(
					'label'   => 'Neighbourhood connections heading',
					'default' => 'Recent relationships in this neighbourhood',
				),
				'neighborhood_external_connections_heading' => array(
					'label'   => 'Neighbourhood external connections heading',
					'default' => 'Connections to other neighbourhoods',
				),
				'neighborhood_external_connection_count' => array(
					'label'       => 'Neighbourhood external connection count',
					'default'     => '{theme} · {count}',
					'description' => 'Keep {theme} and {count}; they become the connected theme name and relationship count.',
				),
				'neighborhood_empty_connections' => array(
					'label'   => 'Neighbourhood empty connections',
					'default' => 'There are no approved relationships inside this neighbourhood yet.',
				),
				'question_neighborhood_empty_connections' => array(
					'label'   => 'Exact-question empty connections',
					'default' => 'There are no approved relationships generated from this spark yet.',
				),
				'theme_belonging' => array(
					'label'   => 'Theme: belonging',
					'default' => 'Belonging',
				),
				'theme_distance' => array(
					'label'   => 'Theme: distance',
					'default' => 'Distance',
				),
				'theme_everyday_life' => array(
					'label'   => 'Theme: everyday life',
					'default' => 'Everyday life',
				),
				'theme_intimacy' => array(
					'label'   => 'Theme: intimacy',
					'default' => 'Intimacy',
				),
				'theme_memory' => array(
					'label'   => 'Theme: memory',
					'default' => 'Memory',
				),
				'theme_relationships' => array(
					'label'   => 'Theme: relationships',
					'default' => 'Relationships',
				),
				'theme_self_experience' => array(
					'label'   => 'Theme: self-experience',
					'default' => 'Self-experience',
				),
				'theme_tension' => array(
					'label'   => 'Theme: tension',
					'default' => 'Tension',
				),
				'theme_time_experience' => array(
					'label'   => 'Theme: time experience',
					'default' => 'Time experience',
				),
				'network_recent_body' => array(
					'label'   => 'Recent fragments explanation',
					'default' => 'Recently shared responses with at least one scent concept. Load them in groups of 12; every complete concept appears beneath its original fragment.',
					'type'    => 'textarea',
				),
				'network_concept_anchors' => array(
					'label'       => 'Concept anchors',
					'default'     => 'Nuances: {anchors}',
					'description' => 'Keep {anchors}; it becomes the nuances extracted from the complete concept.',
				),
				'network_fragment_question' => array(
					'label'       => 'Recent fragment spark',
					'default'     => 'Spark: {question}',
					'description' => 'Keep {question}; it becomes the short map label configured for the spark.',
				),
			),
			'Profile' => array(
				'profile_editor_summary' => array(
					'label'   => 'Optional details editor heading',
					'default' => 'Your optional personal information',
				),
				'profile_editor_intro' => array(
					'label'   => 'Profile editor explanation',
					'default' => 'These optional details are quietly used to enrich and diversify matches. They are never shown as your identity.',
					'type'    => 'textarea',
				),
				'gender_label' => array(
					'label'   => 'Gender',
					'default' => 'Gender',
				),
				'gender_woman' => array(
					'label'   => 'Gender option: woman',
					'default' => 'Woman',
				),
				'gender_man' => array(
					'label'   => 'Gender option: man',
					'default' => 'Man',
				),
				'gender_fluid' => array(
					'label'   => 'Gender option: fluid / non-binary',
					'default' => 'Fluid / non-binary',
				),
				'gender_self_described' => array(
					'label'   => 'Gender option: self-described',
					'default' => 'Self-described',
				),
				'age_range_label' => array(
					'label'   => 'Age range',
					'default' => 'Age range',
				),
				'environment_label' => array(
					'label'   => 'Where you live',
					'default' => 'Where you live',
				),
				'country_label' => array(
					'label'   => 'Country',
					'default' => 'Country',
				),
				'continent_label' => array(
					'label'   => 'Continent or region',
					'default' => 'Continent or region',
				),
				'native_language_label' => array(
					'label'   => 'Native language',
					'default' => 'Native Language',
				),
				'native_language_placeholder' => array(
					'label'   => 'Native language example',
					'default' => 'Spanish, Catalan',
				),
				'other_languages_label' => array(
					'label'   => 'Other languages',
					'default' => 'Other languages',
				),
				'other_languages_placeholder' => array(
					'label'   => 'Other languages example',
					'default' => 'English, French',
				),
				'language_life_label' => array(
					'label'   => 'Language life',
					'default' => 'Language life',
				),
				'language_mostly_one' => array(
					'label'   => 'Language life: mostly one',
					'default' => 'Mostly one language',
				),
				'language_multilingual' => array(
					'label'   => 'Language life: multilingual',
					'default' => 'Multilingual',
				),
				'language_between' => array(
					'label'   => 'Language life: between languages',
					'default' => 'Living between languages',
				),
				'work_areas_label' => array(
					'label'   => 'Work or fields',
					'default' => 'Work or fields',
				),
				'work_areas_placeholder' => array(
					'label'   => 'Work or fields example',
					'default' => 'Poetry, education',
				),
				'hobbies_label' => array(
					'label'   => 'Hobbies or practices',
					'default' => 'Hobbies or practices',
				),
				'hobbies_placeholder' => array(
					'label'   => 'Hobbies example',
					'default' => 'Walking, music',
				),
				'rooted_places_label' => array(
					'label'   => 'Rooted places',
					'default' => 'Places you feel rooted in',
				),
				'rooted_places_placeholder' => array(
					'label'   => 'Rooted places example',
					'default' => "Madrid, the coast, my grandmother's kitchen",
				),
				'save_profile_button' => array(
					'label'   => 'Save profile button',
					'default' => 'Save optional details',
				),
				'prefer_not_to_say' => array(
					'label'   => 'Empty select option',
					'default' => 'Prefer not to say',
				),
				'environment_rural' => array(
					'label'   => 'Environment: rural area',
					'default' => 'Rural area',
				),
				'environment_hamlet' => array(
					'label'   => 'Environment: hamlet',
					'default' => 'Hamlet (fewer than 100 people)',
				),
				'environment_village' => array(
					'label'   => 'Environment: village',
					'default' => 'Village (roughly 100–1,000 people)',
				),
				'environment_small_town' => array(
					'label'   => 'Environment: small town',
					'default' => 'Small town (roughly 1,000–20,000 people)',
				),
				'environment_small_city' => array(
					'label'   => 'Environment: small city',
					'default' => 'Small city (roughly 20,000–100,000 people)',
				),
				'environment_city' => array(
					'label'   => 'Environment: city',
					'default' => 'City (more than 100,000 people)',
				),
				'environment_between_places' => array(
					'label'   => 'Environment: between places',
					'default' => 'Between places',
				),
			),
			'Private return link' => array(
				'recovery_kicker' => array(
					'label'   => 'Kicker',
					'default' => 'Your anonymous session',
				),
				'recovery_heading' => array(
					'label'   => 'Heading',
					'default' => 'Bookmark your way back.',
				),
				'recovery_intro' => array(
					'label'   => 'Explanation',
					'default' => 'Bookmark this private link and return whenever you want to continue your anonymous session, share more olfactory traces and discover new connections.',
					'type'    => 'textarea',
				),
				'bookmark_button' => array(
					'label'   => 'Desktop bookmark button',
					'default' => 'Bookmark',
				),
				'shortcut_button' => array(
					'label'   => 'Desktop shortcut button',
					'default' => 'Download shortcut',
				),
				'share_button' => array(
					'label'   => 'Mobile save button',
					'default' => 'Save or share private link',
				),
				'copy_link_button' => array(
					'label'   => 'Copy button',
					'default' => 'Copy link',
				),
				'continue_button' => array(
					'label'   => 'Continue button',
					'default' => 'Continue',
				),
				'mobile_save_hint' => array(
					'label'   => 'Mobile hint',
					'default' => 'Choose Add Bookmark, Add to Home Screen, or send the link somewhere private.',
					'type'    => 'textarea',
				),
				'desktop_save_hint' => array(
					'label'       => 'Desktop hint',
					'default'     => 'This private address is ready. Press {shortcut} to add the bookmark.',
					'description' => 'Keep {shortcut}; it becomes ⌘D or Ctrl+D.',
				),
				'share_title' => array(
					'label'   => 'Share sheet title',
					'default' => 'Diástoles',
				),
				'share_text' => array(
					'label'   => 'Share sheet text',
					'default' => 'My private return link to Diástoles.',
				),
				'shared_confirmation' => array(
					'label'   => 'Shared confirmation',
					'default' => 'Private link shared',
				),
				'bookmark_copied_confirmation' => array(
					'label'       => 'Desktop copied confirmation',
					'default'     => 'Link copied — press {shortcut}',
					'description' => 'Keep {shortcut}; it becomes ⌘D or Ctrl+D.',
				),
				'copied_confirmation' => array(
					'label'   => 'Copy confirmation',
					'default' => 'Copied',
				),
			),
			'System messages and credit' => array(
				'generic_error' => array(
					'label'   => 'Generic request error',
					'default' => 'Something went wrong.',
				),
				'error_heading' => array(
					'label'   => 'Error heading',
					'default' => 'Something interrupted the thread.',
				),
				'retry_button' => array(
					'label'   => 'Retry button',
					'default' => 'Try again',
				),
				'profile_saved_notice' => array(
					'label'   => 'Profile saved notice',
					'default' => 'Your optional details have been saved.',
				),
				'response_saved_notice' => array(
					'label'   => 'Response saved notice',
					'default' => 'Your words have entered the network.',
				),
				'photo_credit_prefix' => array(
					'label'   => 'Photo credit prefix',
					'default' => 'Photographs by',
				),
				'idea_credit_prefix' => array(
					'label'   => 'Idea credit prefix',
					'default' => 'Concept by',
				),
				'companion_project_prefix' => array(
					'label'   => 'Companion project prefix',
					'default' => 'Diástoles is a companion project to',
				),
				'privacy_footer_label' => array(
					'label'   => 'Privacy footer link',
					'default' => 'Privacy',
				),
			),
		);
	}

	public static function defaults(): array {
		$defaults = array();
		foreach ( self::fields() as $fields ) {
			foreach ( $fields as $key => $field ) {
				$defaults[ $key ] = $field['default'];
			}
		}
		return $defaults;
	}

	public static function all(): array {
		$stored = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
	}

	public static function maybe_upgrade(): void {
		if ( self::VERSION === get_option( self::VERSION_OPTION ) ) {
			return;
		}

		$stored = get_option( self::OPTION, array() );
		if ( is_array( $stored ) ) {
			$replacements = array(
				'language_note' => array(
					array( 'Write in any language.', 'Write in whichever language feels like yours.' ),
					self::defaults()['language_note'],
				),
				'privacy_note' => array(
					array(
						'No name, email or phone number is required. AI translates and finds relationships; it does not rewrite your words. You can withdraw a fragment later.',
						'No name, email or phone number is required. If you provide an email, it is stored only for the publication notice and is never sent to the AI. AI translates and finds relationships; it does not rewrite your words. You can withdraw a fragment later.',
					),
					self::defaults()['privacy_note'],
				),
				'optional_coordinates_summary' => array(
					'A few optional coordinates (They would be used to build meaningful connections)',
					self::defaults()['optional_coordinates_summary'],
				),
				'consent_label' => array(
					array(
						'I agree that my words may be stored, translated and anonymously used as a part of a creative project.',
						"I'm aware and agree that my words may be stored, translated and anonymously used as part of a creative project.",
					),
					self::defaults()['consent_label'],
				),
				'profile_editor_summary' => array(
					array( 'Your optional coordinates', 'Your optional details' ),
					self::defaults()['profile_editor_summary'],
				),
				'profile_saved_notice' => array( 'Your optional coordinates have been saved.', 'Your optional details have been saved.' ),
				'bookmark_button' => array(
					array( 'Bookmark private link', 'Bookmark Diástoles' ),
					self::defaults()['bookmark_button'],
				),
				'recovery_intro' => array(
					'Bookmark this private link and return whenever you want to continue your anonymous session and discover new connections.',
					self::defaults()['recovery_intro'],
				),
				'share_title' => array( 'Diástoles — private return link', self::defaults()['share_title'] ),
				'translation_summary' => array(
					array( 'English translation', 'Translation into the selected language' ),
					self::defaults()['translation_summary'],
				),
				'connections_empty_body' => array(
					'Connections appear here after they have been reviewed by a human moderator.',
					self::defaults()['connections_empty_body'],
				),
				'header_tagline' => array(
					'A living network of scent and memory',
					self::defaults()['header_tagline'],
				),
				'fragment_label' => array( 'Your fragment', self::defaults()['fragment_label'] ),
				'fragment_guidance' => array(
					array(
						"Don't only name it. Give it a place, a moment, a texture, or something it hides.",
						'You can simply name it, or give it a place, a moment, a texture, or something it hides.',
					),
					self::defaults()['fragment_guidance'],
				),
				'short_fragment_prompt' => array(
					'A short fragment is welcome. Would you like to give it a place, a moment or a secret?',
					self::defaults()['short_fragment_prompt'],
				),
				'network_hover_help' => array(
					'Hover over a point to reveal its smell. Hover over a line to discover the relationship between two fragments.',
					self::defaults()['network_hover_help'],
				),
				'network_shared_smell' => array( 'Shared smell', self::defaults()['network_shared_smell'] ),
				'network_cluster_count' => array( '{count} fragments', self::defaults()['network_cluster_count'] ),
				'network_concept_anchors' => array( array( 'Noun anchors: {anchors}', 'Anchors: {anchors}', 'Anclajes: {anchors}', 'Anclas: {anchors}' ), self::defaults()['network_concept_anchors'] ),
				'network_recent_body' => array(
					array(
						'The 12 most recently shared anonymous fragments. Some may still be waiting to form a connection.',
						'The 12 most recently shared responses with at least one scent concept. Every complete concept appears beneath its original fragment.',
					),
					self::defaults()['network_recent_body'],
				),
				'map_variant_global' => array( 'Global map', self::defaults()['map_variant_global'] ),
				'nav_question' => array( array( 'Question', 'Pregunta' ), self::defaults()['nav_question'] ),
				'nav_traces' => array( 'Your traces', self::defaults()['nav_traces'] ),
				'nav_shared_fragments' => array( 'Shared fragments', self::defaults()['nav_shared_fragments'] ),
				'nav_map' => array( 'Collective map', self::defaults()['nav_map'] ),
				'network_heading' => array( array( 'A constellation made only of human words', 'Una constelación hecha solo de palabras humanas' ), self::defaults()['network_heading'] ),
				'map_variant_narrative' => array( array( 'Narrative walk', 'Narrative Walk' ), self::defaults()['map_variant_narrative'] ),
				'map_variant_questions' => array( array( 'Question neighbourhoods', 'Question neighborhoods', 'Vecindarios de Preguntas', 'Vecindarios de preguntas' ), self::defaults()['map_variant_questions'] ),
				'question_neighborhood_group_by_question' => array( array( 'Group by exact question', 'Agrupar por pregunta exacta' ), self::defaults()['question_neighborhood_group_by_question'] ),
				'relationship_layers_minimum_intensity' => array( array( 'Minimum relationship intensity', 'Intensidad mínima de la relación' ), self::defaults()['relationship_layers_minimum_intensity'] ),
				'connections_empty_heading' => array( array( 'No words have met yours yet.', 'Aún no han coincidido palabras con las tuyas.' ), self::defaults()['connections_empty_heading'] ),
				'journey_heading' => array( array( 'Words you have left behind', 'Palabras que has dejado atrás' ), self::defaults()['journey_heading'] ),
				'journey_kicker' => array( 'Your traces', self::defaults()['journey_kicker'] ),
				'network_kicker' => array( array( 'Collective map', 'Collective Map', 'Map exploration', 'Map Exploration' ), self::defaults()['network_kicker'] ),
			);
			foreach ( $replacements as $key => list( $old, $new ) ) {
				$old_values = is_array( $old ) ? $old : array( $old );
				if ( in_array( $stored[ $key ] ?? null, $old_values, true ) ) {
					$stored[ $key ] = $new;
				}
			}
			update_option( self::OPTION, $stored );
		}

		update_option( self::VERSION_OPTION, self::VERSION );
	}

	public static function sanitize( mixed $input ): array {
		$input = is_array( $input ) ? $input : array();
		$clean = array();
		foreach ( self::fields() as $fields ) {
			foreach ( $fields as $key => $field ) {
				$value = wp_unslash( $input[ $key ] ?? $field['default'] );
				$clean[ $key ] = 'textarea' === ( $field['type'] ?? '' )
					? sanitize_textarea_field( $value )
					: sanitize_text_field( $value );
			}
		}
		return $clean;
	}
}
