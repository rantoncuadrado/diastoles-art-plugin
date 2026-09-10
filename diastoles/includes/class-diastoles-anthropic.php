<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_Anthropic {
	private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
	private const VERSION  = '2023-06-01';
	private const PROCESSING_MODEL = 'claude-haiku-4-5-20251001';
	private const CONNECTION_MODEL = 'claude-sonnet-5';

	public static function mode(): string {
		return 'live' === get_option( 'diastoles_ai_mode', 'mock' ) ? 'live' : 'mock';
	}

	public static function model_choices(): array {
		return array(
			'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 — cheapest / fastest',
			'claude-sonnet-5'           => 'Claude Sonnet 5 — recommended',
			'claude-opus-4-8'           => 'Claude Opus 4.8 — superior / more expensive',
		);
	}

	public static function sanitize_model( mixed $model ): string {
		$model = sanitize_text_field( (string) $model );
		return array_key_exists( $model, self::model_choices() ) ? $model : self::PROCESSING_MODEL;
	}

	public static function sanitize_translation_model( mixed $model ): string {
		return self::sanitize_processing_model( $model );
	}

	public static function sanitize_analysis_model( mixed $model ): string {
		return self::sanitize_connection_model( $model );
	}

	public static function sanitize_processing_model( mixed $model ): string {
		$model = sanitize_text_field( (string) $model );
		return array_key_exists( $model, self::model_choices() ) ? $model : self::PROCESSING_MODEL;
	}

	public static function sanitize_connection_model( mixed $model ): string {
		$model = sanitize_text_field( (string) $model );
		return array_key_exists( $model, self::model_choices() ) ? $model : self::CONNECTION_MODEL;
	}

	public static function processing_model(): string {
		$legacy_default = get_option( 'diastoles_translation_model', self::PROCESSING_MODEL );
		return self::sanitize_processing_model( get_option( 'diastoles_processing_model', $legacy_default ) );
	}

	public static function connection_model(): string {
		$legacy_default = get_option( 'diastoles_analysis_model', self::CONNECTION_MODEL );
		return self::sanitize_connection_model( get_option( 'diastoles_connection_model', $legacy_default ) );
	}

	public static function translation_model(): string {
		return self::processing_model();
	}

	public static function analysis_model(): string {
		return self::processing_model();
	}

	public static function model(): string {
		return self::connection_model();
	}

	public static function translate_text( string $text, string $locale ): string|WP_Error {
		if ( 'mock' === self::mode() ) {
			return $text;
		}
		$language = Diastoles_I18n::languages()[ Diastoles_I18n::normalize( $locale ) ]['english_name'] ?? 'English';
		$result = self::request(
			"Translate the following text faithfully into $language. Preserve tone, ambiguity, punctuation and proper names. Do not explain or add content.\n\n" . $text,
			array(
				'type' => 'object',
				'properties' => array( 'translation' => array( 'type' => 'string' ) ),
				'required' => array( 'translation' ),
				'additionalProperties' => false,
			),
			min( 4096, max( 256, (int) ceil( mb_strlen( $text ) * 1.5 ) ) ),
			self::translation_model()
		);
		return is_wp_error( $result ) ? $result : (string) ( $result['translation'] ?? '' );
	}

	public static function analyze_response( string $original, string $explanation ): array|WP_Error {
		if ( 'mock' === self::mode() ) {
			return self::mock_analysis( $original, $explanation );
		}

		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'source_language' => array( 'type' => 'string' ),
				'translation_en'  => array( 'type' => 'string' ),
				'smells'          => self::smell_concepts_schema(),
				'states'          => self::string_array_schema(),
				'moments'         => self::string_array_schema(),
				'relationships'   => self::string_array_schema(),
				'places'          => self::string_array_schema(),
				'themes'          => self::string_array_schema(),
				'emotional_tones' => self::string_array_schema(),
				'temporality'     => array( 'type' => 'string' ),
				'supporting_evidence' => self::supporting_evidence_schema(),
			),
			'required'             => array(
				'source_language',
				'translation_en',
				'smells',
				'states',
				'moments',
				'relationships',
				'places',
				'themes',
				'emotional_tones',
				'temporality',
				'supporting_evidence',
			),
			'additionalProperties' => false,
		);

		$prompt = "Analyze the participant's words without embellishing or completing them. "
			. "Return source_language as a lowercase ISO 639-1 language code, or und only when it cannot be determined. "
			. "Translate faithfully into English. Extract short, lowercase English labels only when supported by the text. "
			. "The smells field contains zero to four independent scent concepts. Each item has phrase and anchors. Phrase must "
			. "be a complete, meaningful English noun phrase faithfully supported by the response; it may be short ('coffee', "
			. "'wet earth') or complex ('the sadness of knowing you are close and not understanding how you left'). Anchors "
			. "contains the lowercase English head noun first, followed by any other meaningful nouns in that phrase. "
			. "Treat generic scent words such as smell, scent, odor, aroma, perfume, fragrance and essence as carriers, not "
			. "anchors, whenever the text names what produces or characterizes the scent. Put that referent first instead: "
			. "'the smell of a forest' has forest as its anchor, 'the smell of effort' has effort, 'the perfume of roses' "
			. "has roses, and 'new car smell' has car. Use a generic scent word as an anchor only when no more specific "
			. "referent exists. Preserve a named product or brand as one complete primary anchor: 'Old Spice' has old spice, "
			. "not spice. When a scented product is applied to a body part, put the product first: 'armpits bathed in Axe' "
			. "has axe before armpits. Split coordinated concepts only when the coordinated terms directly name independent "
			. "sensory sources: 'coffee and tea' becomes two items, one for coffee and one for tea. When one named source is "
			. "followed by coordinated metaphors, comparisons, roles, properties or uses, keep the entire description as one "
			. "concept. Those descriptive nouns must not become separate concepts or anchors; the named source remains the only "
			. "anchor unless another noun is part of that source's identity. For example, 'El amoniaco, como sopa primigenia y "
			. "limpiador deletéreo' produces exactly one item: phrase 'ammonia as primordial soup and a deleterious cleaner', "
			. "anchors ['ammonia'], and semantic fields ['cleaning product', 'industrial and synthetic']. Never create separate "
			. "soup or cleaner concepts from that response. Keep a phrase together "
			. "when its nouns form one concept: 'my grandmother's kitchen' is one item with kitchen and grandmother as anchors. "
			. "Treat the optional explanation as context for an existing concept unless it explicitly names another independent "
			. "odor or sensory source. A descriptive adjective must not become a second scent concept: a response of 'freshness' "
			. "with the explanation 'just sort of outdoorsy' has one smell concept, freshness; outdoorsy may be a theme. Conversely, "
			. "keep every explicitly named sensory source: forest and pure oxygen are separate concepts, as are effort, sweaty "
			. "hands, Axe and wet hair. "
			. "A concept may be a literal odor/source or a symbolic/emotional scent supported in the context of a smell question. "
			. "Do not invent a material odor such as perfume or food when absent. Use an empty smells array only for refusals, "
			. "no-answer statements, gibberish, or text with no meaningful literal or symbolic concept. Merge direct translations "
			. "and true synonyms, but keep merely related concepts distinct. Order concepts by relevance. "
			. "Classify each smell concept into one or more ordinary semantic fields chosen only from: plant, food, drink, "
			. "earth and minerals, water and weather, body, animal, smoke and fire, cleaning product, personal fragrance, "
			. "industrial and synthetic, indoor place, outdoor nature, atmosphere, abstract, other. Semantic fields must "
			. "describe what the named concept ordinarily is, not what it might emotionally represent. Coffee is drink and "
			. "food; bread is food; lavender is plant; do not classify any of them as home, comfort, safety or belonging. "
			. "For every smell concept, state, place and relationship that may support matching, add a supporting_evidence "
			. "item with its kind, English label, source and a short verbatim excerpt. Source must be response or explanation, "
			. "and excerpt must occur literally in that source text. Do not provide evidence by paraphrasing. Labels without "
			. "verbatim support will not be available to the relationship matcher. Never infer a diagnosis or identity.\n\n"
			. "emotional_tones contains zero to five short lowercase English tone labels supported by the words, such as "
			. "nostalgia, longing, calm, fear, joy, grief, intimacy, discomfort, hope, tenderness, excess, shame or wonder. "
			. "Use ordinary affective descriptions, not diagnoses. "
			. "Response:\n" . $original . "\n\nOptional explanation:\n" . $explanation;

		return self::request( $prompt, $schema, 900, self::processing_model() );
	}

	public static function rank_candidates( object $target, array $candidates ): array|WP_Error {
		if ( 'mock' === self::mode() ) {
			return self::mock_ranking( $target, $candidates );
		}

		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'connections' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'response_id'  => array( 'type' => 'integer' ),
							'target_concept_index' => array( 'type' => 'integer' ),
							'response_concept_index' => array( 'type' => 'integer' ),
							'relation_type' => array(
								'type' => 'string',
								'enum' => array( 'affinity', 'tension', 'complementarity', 'continuity' ),
							),
							'score'        => array( 'type' => 'number' ),
							'evidence'     => array(
								'type'  => 'array',
								'items' => array(
									'type'                 => 'object',
									'properties'           => array(
										'response_id' => array( 'type' => 'integer' ),
										'source'      => array( 'type' => 'string', 'enum' => array( 'response', 'explanation' ) ),
										'kind'        => array( 'type' => 'string', 'enum' => array( 'concept', 'state', 'place', 'relationship' ) ),
										'label'       => array( 'type' => 'string' ),
										'excerpt'     => array( 'type' => 'string' ),
									),
									'required'             => array( 'response_id', 'source', 'kind', 'label', 'excerpt' ),
									'additionalProperties' => false,
								),
							),
						),
						'required'             => array( 'response_id', 'target_concept_index', 'response_concept_index', 'relation_type', 'score', 'evidence' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array( 'connections' ),
			'additionalProperties' => false,
		);

		$payload = array(
			'target'     => self::response_for_prompt( $target ),
			'candidates' => array_map( array( self::class, 'response_for_prompt' ), $candidates ),
		);
		$prompt  = "Rank only meaningful, conventional semantic relationships between one specific target smell concept and one specific candidate "
			. "smell concept. Return their zero-based indices as target_concept_index and response_concept_index. "
			. "Return at most three. Use only the supplied response IDs. Evidence must contain short labels or exact excerpts "
			. "already present in the supplied data; do not write creative connective prose. A valid relationship must be "
			. "supported by identity, synonymy, a direct hypernym, a shared ordinary semantic field, material/source overlap, "
			. "or a commonplace physical association between the named concepts. Do not connect concepts through emotions, "
			. "memories, biography, places or relationships unless the relevant label appears in supported_context with a "
			. "verbatim participant excerpt. Never use an unsupported inference or creative symbolism. Every evidence item must "
			. "copy response_id, source, kind, label and excerpt from a supplied supported_context item; this makes it explicit "
			. "whether the support came from the participant's response or optional explanation. The question_theme is supplied "
			. "only for deterministic score adjustment after ranking; do not "
			. "use it as evidence or alter the semantic score because of it. Score from 0 to 1. Omit weak, imaginative or "
			. "merely circumstantial matches.\n\n" . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );

		return self::request( $prompt, $schema, 1200, self::connection_model() );
	}

	public static function test_connection(): true|WP_Error {
		if ( 'mock' === self::mode() ) {
			return true;
		}

		$schema = array(
			'type'                 => 'object',
			'properties'           => array( 'ok' => array( 'type' => 'boolean' ) ),
			'required'             => array( 'ok' ),
			'additionalProperties' => false,
		);
		$models = array_unique( array( self::processing_model(), self::connection_model() ) );
		foreach ( $models as $model ) {
			$result = self::request( 'Return ok as true.', $schema, 32, $model );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		return true;
	}

	public static function save_api_key( string $api_key ): void {
		$api_key = trim( $api_key );
		if ( '' === $api_key ) {
			return;
		}

		$key        = hash( 'sha256', wp_salt( 'secure_auth' ), true );
		$iv         = random_bytes( 12 );
		$tag        = '';
		$ciphertext = openssl_encrypt( $api_key, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
		if ( false !== $ciphertext ) {
			update_option( 'diastoles_anthropic_key', base64_encode( $iv . $tag . $ciphertext ), false );
		}
	}

	public static function has_api_key(): bool {
		return '' !== self::api_key();
	}

	private static function api_key(): string {
		if ( defined( 'DIASTOLES_ANTHROPIC_API_KEY' ) ) {
			return (string) DIASTOLES_ANTHROPIC_API_KEY;
		}

		$encoded = (string) get_option( 'diastoles_anthropic_key', '' );
		$raw     = base64_decode( $encoded, true );
		if ( false === $raw || strlen( $raw ) < 29 ) {
			return '';
		}

		$iv         = substr( $raw, 0, 12 );
		$tag        = substr( $raw, 12, 16 );
		$ciphertext = substr( $raw, 28 );
		$key        = hash( 'sha256', wp_salt( 'secure_auth' ), true );
		$plaintext  = openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
		return false === $plaintext ? '' : $plaintext;
	}

	private static function request( string $prompt, array $schema, int $max_tokens, ?string $model = null ): array|WP_Error {
		$api_key = self::api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'diastoles_missing_key', 'Anthropic API key is not configured.' );
		}
		$model = self::sanitize_model( $model ?: self::processing_model() );

		$body = array(
			'model'         => $model,
			'max_tokens'    => $max_tokens,
			'system'        => 'You are a careful multilingual analyst. Preserve human voice. Never invent participant content.',
			'messages'      => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'output_config' => array(
				'format' => array(
					'type'   => 'json_schema',
					'schema' => $schema,
				),
			),
		);
		if ( in_array( $model, array( 'claude-sonnet-5' ), true ) ) {
			$body['thinking'] = array( 'type' => 'disabled' );
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 45,
				'headers' => array(
					'content-type'      => 'application/json',
					'x-api-key'         => $api_key,
					'anthropic-version' => self::VERSION,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status   = wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$data     = json_decode( $raw_body, true );
		if ( $status < 200 || $status >= 300 ) {
			$error_type = is_array( $data ) ? (string) ( $data['error']['type'] ?? '' ) : '';
			$message    = is_array( $data ) ? (string) ( $data['error']['message'] ?? '' ) : '';
			if ( '' === trim( $message ) ) {
				$message = 'Anthropic request failed.';
			}
			$detail = sprintf( 'Anthropic HTTP %d using %s%s: %s', $status, $model, $error_type ? ' [' . $error_type . ']' : '', $message );
			if ( ! is_array( $data ) && '' !== trim( $raw_body ) ) {
				$detail .= ' Raw response: ' . mb_substr( sanitize_textarea_field( $raw_body ), 0, 500 );
			}
			return new WP_Error( 'diastoles_anthropic_error', sanitize_text_field( $detail ), array( 'status' => $status, 'model' => $model ) );
		}
		if ( 'refusal' === ( $data['stop_reason'] ?? '' ) ) {
			return new WP_Error( 'diastoles_anthropic_refusal', 'Anthropic declined to process this response.' );
		}

		foreach ( $data['content'] ?? array() as $block ) {
			if ( 'text' === ( $block['type'] ?? '' ) ) {
				$parsed = json_decode( $block['text'], true );
				if ( is_array( $parsed ) ) {
					return $parsed;
				}
			}
		}

		return new WP_Error(
			'diastoles_invalid_ai_response',
			sanitize_text_field( 'Anthropic returned an invalid structured response using ' . $model . '. Raw response: ' . mb_substr( sanitize_textarea_field( $raw_body ), 0, 500 ) )
		);
	}

	private static function string_array_schema(): array {
		return array( 'type' => 'array', 'items' => array( 'type' => 'string' ) );
	}

	private static function smell_concepts_schema(): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type'                 => 'object',
				'properties'           => array(
					'phrase'          => array( 'type' => 'string' ),
					'anchors'         => self::string_array_schema(),
					'semantic_fields' => self::semantic_fields_schema(),
				),
				'required'             => array( 'phrase', 'anchors', 'semantic_fields' ),
				'additionalProperties' => false,
			),
		);
	}

	private static function supporting_evidence_schema(): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type'                 => 'object',
				'properties'           => array(
					'kind'    => array( 'type' => 'string', 'enum' => array( 'concept', 'state', 'place', 'relationship' ) ),
					'label'   => array( 'type' => 'string' ),
					'source'  => array( 'type' => 'string', 'enum' => array( 'response', 'explanation' ) ),
					'excerpt' => array( 'type' => 'string' ),
				),
				'required'             => array( 'kind', 'label', 'source', 'excerpt' ),
				'additionalProperties' => false,
			),
		);
	}

	private static function semantic_fields_schema(): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type' => 'string',
				'enum' => self::semantic_fields(),
			),
		);
	}

	private static function semantic_fields(): array {
		return array(
			'plant',
			'food',
			'drink',
			'earth and minerals',
			'water and weather',
			'body',
			'animal',
			'smoke and fire',
			'cleaning product',
			'personal fragrance',
			'industrial and synthetic',
			'indoor place',
			'outdoor nature',
			'atmosphere',
			'abstract',
			'other',
		);
	}

	public static function smell_concepts( array $analysis ): array {
		$concepts = array();
		$seen     = array();
		foreach ( (array) ( $analysis['smells'] ?? array() ) as $item ) {
			if ( is_string( $item ) ) {
				$phrase  = trim( $item );
				$anchors = $phrase ? array( mb_strtolower( $phrase ) ) : array();
				$semantic_fields = array();
			} elseif ( is_array( $item ) ) {
				$phrase  = trim( (string) ( $item['phrase'] ?? '' ) );
				$anchors = array_values(
					array_filter( array_map( static fn( mixed $anchor ): string => mb_strtolower( trim( (string) $anchor ) ), (array) ( $item['anchors'] ?? array() ) ) )
				);
				if ( $phrase && ! $anchors ) {
					$anchors = array( mb_strtolower( $phrase ) );
				}
				$semantic_fields = array_values(
					array_intersect(
						self::semantic_fields(),
						array_map( static fn( mixed $field ): string => mb_strtolower( trim( (string) $field ) ), (array) ( $item['semantic_fields'] ?? array() ) )
					)
				);
			} else {
				continue;
			}
			$anchors = self::meaningful_scent_anchors( $phrase, $anchors );
			$key     = mb_strtolower( preg_replace( '/\s+/u', ' ', $phrase ) );
			if ( $phrase && ! isset( $seen[ $key ] ) ) {
				$concepts[] = array(
					'phrase'          => $phrase,
					'anchors'         => array_values( array_unique( $anchors ) ),
					'semantic_fields' => array_values( array_unique( $semantic_fields ) ),
				);
				$seen[ $key ] = true;
			}
			if ( count( $concepts ) >= 4 ) {
				break;
			}
		}
		return $concepts;
	}

	private static function meaningful_scent_anchors( string $phrase, array $anchors ): array {
		$generic = array(
			'aroma',
			'essence',
			'fragrance',
			'odor',
			'odour',
			'perfume',
			'scent',
			'smell',
			'arome',
			'essencia',
			'fragancia',
			'fragrancia',
			'odeur',
			'olor',
			'parfum',
			'profumo',
		);
		$normalized_phrase = trim( preg_replace( '/\s+/u', ' ', mb_strtolower( $phrase ) ) );
		$carrier_pattern   = '(?:aroma|arôme|essence|essência|fragancia|fragrance|fragrância|odor|odour|odeur|olor|parfum|perfume|profumo|scent|smell)';
		$preposition       = '(?:a|à|da|de|del|della|des|di|do|du|of|from|like)';
		$has_referent      = preg_match( '/^(?:the|el|la|l[’\']|le|il|lo|o|a)?\s*' . $carrier_pattern . '\s+' . $preposition . '\s+(.+)$/u', $normalized_phrase, $matches );
		$specific          = array_values(
			array_filter(
				$anchors,
				static fn( string $anchor ): bool => ! in_array( remove_accents( mb_strtolower( $anchor ) ), $generic, true )
			)
		);
		$only_phrase_fallback = 1 === count( $specific )
			&& $normalized_phrase === trim( preg_replace( '/\s+/u', ' ', mb_strtolower( $specific[0] ) ) );
		if ( $specific && ! ( $has_referent && $only_phrase_fallback ) ) {
			return self::prioritize_named_scent_source( $normalized_phrase, $specific );
		}

		if ( $has_referent ) {
			$referent = trim( preg_replace( '/^(?:a|an|the|el|la|las|los|le|les|un|una|une)\s+/u', '', $matches[1] ) );
			if ( '' !== $referent ) {
				return array( $referent );
			}
		}

		return array_values( array_unique( $anchors ) );
	}

	private static function prioritize_named_scent_source( string $phrase, array $anchors ): array {
		$normalized_phrase = remove_accents( mb_strtolower( $phrase ) );
		$protected_sources = array( 'old spice', 'pine sol' );
		foreach ( $protected_sources as $source ) {
			if ( preg_match( '/(?:^|\s)' . preg_quote( $source, '/' ) . '(?:$|\s)/u', $normalized_phrase ) ) {
				$source_words = explode( ' ', $source );
				$remaining    = array_values(
					array_filter(
						$anchors,
						static fn( string $anchor ): bool => ! in_array( remove_accents( mb_strtolower( $anchor ) ), $source_words, true )
					)
				);
				return array_values( array_unique( array_merge( array( $source ), $remaining ) ) );
			}
		}

		$applied_pattern = '/\b(?:bathed|covered|doused|sprayed|scented|soaked)\s+(?:in|with)\s+(.+)$/u';
		if ( preg_match( $applied_pattern, $normalized_phrase, $matches ) ) {
			$applied_source = trim( $matches[1] );
			$source_anchors  = array_values(
				array_filter(
					$anchors,
					static fn( string $anchor ): bool => str_contains( $applied_source, remove_accents( mb_strtolower( $anchor ) ) )
				)
			);
			if ( $source_anchors ) {
				return array_values( array_unique( array_merge( $source_anchors, array_diff( $anchors, $source_anchors ) ) ) );
			}
		}

		return array_values( array_unique( $anchors ) );
	}

	private static function response_for_prompt( object $response ): array {
		global $wpdb;

		$analysis = json_decode( (string) $response->analysis_json, true ) ?: array();
		$concepts = self::smell_concepts( $analysis );
		$question_theme = mb_strtolower( trim( (string) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT theme FROM ' . Diastoles_DB::table( 'questions' ) . ' WHERE id = %d',
				(int) $response->question_id
			)
		) ) );
		return array(
			'response_id'    => (int) $response->id,
			'question_theme' => $question_theme,
			'concepts'       => $concepts,
			'supported_context' => self::supported_context( $response, $analysis, $concepts ),
		);
	}

	private static function supported_context( object $response, array $analysis, array $concepts ): array {
		$allowed_labels = array(
			'concept'      => array(),
			'state'        => array_map( 'mb_strtolower', (array) ( $analysis['states'] ?? array() ) ),
			'place'        => array_map( 'mb_strtolower', (array) ( $analysis['places'] ?? array() ) ),
			'relationship' => array_map( 'mb_strtolower', (array) ( $analysis['relationships'] ?? array() ) ),
		);
		foreach ( $concepts as $concept ) {
			$allowed_labels['concept'][] = mb_strtolower( (string) $concept['phrase'] );
			$allowed_labels['concept']   = array_merge( $allowed_labels['concept'], array_map( 'mb_strtolower', (array) $concept['anchors'] ) );
		}

		$supported = array();
		foreach ( (array) ( $analysis['supporting_evidence'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$kind    = sanitize_key( (string) ( $item['kind'] ?? '' ) );
			$label   = mb_strtolower( trim( (string) ( $item['label'] ?? '' ) ) );
			$source  = sanitize_key( (string) ( $item['source'] ?? '' ) );
			$excerpt = trim( (string) ( $item['excerpt'] ?? '' ) );
			if ( ! isset( $allowed_labels[ $kind ] ) || ! in_array( $label, $allowed_labels[ $kind ], true ) || ! in_array( $source, array( 'response', 'explanation' ), true ) ) {
				continue;
			}
			$source_text = 'response' === $source ? (string) $response->original_text : (string) $response->explanation_text;
			if ( '' === $excerpt || ! str_contains( self::normalized_evidence_text( $source_text ), self::normalized_evidence_text( $excerpt ) ) ) {
				continue;
			}
			$supported[] = array(
				'response_id' => (int) $response->id,
				'kind'        => $kind,
				'label'       => $label,
				'source'      => $source,
				'excerpt'     => $excerpt,
			);
		}
		return $supported;
	}

	public static function matching_context_terms( object $response ): array {
		$analysis = json_decode( (string) $response->analysis_json, true ) ?: array();
		$concepts = self::smell_concepts( $analysis );
		$terms    = array();
		foreach ( self::supported_context( $response, $analysis, $concepts ) as $item ) {
			if ( in_array( $item['kind'], array( 'state', 'place', 'relationship' ), true ) ) {
				$terms[] = mb_strtolower( (string) $item['label'] );
			}
		}
		return array_values( array_unique( $terms ) );
	}

	public static function connection_evidence( array $items, object $target, object $candidate ): array {
		$responses = array(
			(int) $target->id    => $target,
			(int) $candidate->id => $candidate,
		);
		$validated = array();
		$seen_responses = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$response_id = (int) ( $item['response_id'] ?? 0 );
			$source      = sanitize_key( (string) ( $item['source'] ?? '' ) );
			$excerpt     = trim( (string) ( $item['excerpt'] ?? '' ) );
			if ( ! isset( $responses[ $response_id ] ) || ! in_array( $source, array( 'response', 'explanation' ), true ) || '' === $excerpt ) {
				continue;
			}
			$response    = $responses[ $response_id ];
			$source_text = 'response' === $source ? (string) $response->original_text : (string) $response->explanation_text;
			if ( ! str_contains( self::normalized_evidence_text( $source_text ), self::normalized_evidence_text( $excerpt ) ) ) {
				continue;
			}
			$prefix      = 'response' === $source ? 'Participant response' : 'Participant explanation';
			$validated[] = $prefix . ': “' . sanitize_text_field( $excerpt ) . '”';
			$seen_responses[] = $response_id;
		}
		return 2 === count( array_unique( $seen_responses ) ) ? array_values( array_unique( $validated ) ) : array();
	}

	private static function normalized_evidence_text( string $text ): string {
		return mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $text ) ?: $text ) );
	}

	private static function mock_analysis( string $original, string $explanation ): array {
		$text      = trim( $original . ' ' . $explanation );
		$lower     = mb_strtolower( $text );
		$catalogue = array(
			'smells' => array( 'coffee', 'tea', 'rain', 'wood', 'sea', 'soap', 'bread', 'smoke', 'earth', 'jasmine', 'perfume', 'food', 'forest', 'sadness', 'safety', 'waiting' ),
			'states' => array( 'home', 'safety', 'nostalgia', 'loss', 'calm', 'joy', 'fear', 'loneliness', 'belonging', 'hope' ),
			'themes' => array( 'family', 'childhood', 'future', 'change', 'departure', 'connection', 'disconnection', 'memory' ),
			'emotional_tones' => array( 'nostalgia', 'longing', 'calm', 'fear', 'joy', 'grief', 'intimacy', 'discomfort', 'hope', 'tenderness', 'excess', 'shame', 'wonder' ),
		);
		$result    = array();
		foreach ( $catalogue as $group => $words ) {
			$result[ $group ] = array_values(
				array_filter(
					$words,
					static fn( string $word ): bool => str_contains( $lower, $word )
				)
			);
		}
		$concepts = array_map(
			static fn( string $phrase ): array => array( 'phrase' => $phrase, 'anchors' => array( $phrase ) ),
			$result['smells']
		);
		$mock_patterns = array(
			'café'          => array( 'phrase' => 'coffee', 'anchors' => array( 'coffee' ) ),
			'té'            => array( 'phrase' => 'tea', 'anchors' => array( 'tea' ) ),
			'amoniaco'      => array(
				'phrase'          => 'ammonia as primordial soup and a deleterious cleaner',
				'anchors'         => array( 'ammonia' ),
				'semantic_fields' => array( 'cleaning product', 'industrial and synthetic' ),
			),
			'ammonia'       => array(
				'phrase'          => 'ammonia as primordial soup and a deleterious cleaner',
				'anchors'         => array( 'ammonia' ),
				'semantic_fields' => array( 'cleaning product', 'industrial and synthetic' ),
			),
			'tristeza de saberte cerca' => array(
				'phrase'  => 'the sadness of knowing you are close and not understanding how you left',
				'anchors' => array( 'sadness' ),
			),
			'triste'      => array( 'phrase' => 'sadness', 'anchors' => array( 'sadness' ) ),
			'sad'         => array( 'phrase' => 'sadness', 'anchors' => array( 'sadness' ) ),
			'casa de mi abuela' => array( 'phrase' => "my grandmother's house", 'anchors' => array( 'house', 'grandmother' ) ),
			'grandmother' => array( 'phrase' => "my grandmother's house", 'anchors' => array( 'house', 'grandmother' ) ),
		);
		foreach ( $mock_patterns as $needle => $concept ) {
			if ( 'triste' === $needle && str_contains( $lower, 'tristeza de saberte cerca' ) ) {
				continue;
			}
			if ( str_contains( $lower, $needle ) ) {
				$concepts[] = $concept;
			}
		}
		if ( str_contains( $lower, 'wet earth' ) || str_contains( $lower, 'tierra mojada' ) ) {
			$concepts = array_values(
				array_filter( $concepts, static fn( array $concept ): bool => 'earth' !== $concept['phrase'] )
			);
			$concepts[] = array( 'phrase' => 'wet earth', 'anchors' => array( 'earth' ) );
		}
		$result['smells'] = self::smell_concepts( array( 'smells' => $concepts ) );
		if ( empty( $result['states'] ) ) {
			$result['states'] = array( 'memory' );
		}
		$result['source_language'] = 'und';
		$result['translation_en']  = $text;
		$result['moments']         = array();
		$result['relationships']   = array();
		$result['places']          = array();
		$result['emotional_tones'] = $result['emotional_tones'] ?? array();
		$result['temporality']     = 'unspecified';
		return $result;
	}

	private static function mock_ranking( object $target, array $candidates ): array {
		$target_tags = self::flatten_analysis( $target->analysis_json );
		$ranked      = array();
		foreach ( $candidates as $candidate ) {
			$shared = array_values( array_intersect( $target_tags, self::flatten_analysis( $candidate->analysis_json ) ) );
			if ( empty( $shared ) ) {
				continue;
			}
			list( $target_concept_index, $response_concept_index ) = self::mock_concept_pair( $target, $candidate );
			$ranked[] = array(
				'response_id'            => (int) $candidate->id,
				'target_concept_index'   => $target_concept_index,
				'response_concept_index' => $response_concept_index,
				'relation_type'           => 'affinity',
				'score'                   => min( 0.95, 0.45 + ( count( $shared ) * 0.12 ) ),
				'evidence'                => array_slice( $shared, 0, 4 ),
			);
		}
		usort( $ranked, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );
		return array( 'connections' => array_slice( $ranked, 0, 3 ) );
	}

	private static function mock_concept_pair( object $target, object $candidate ): array {
		$target_analysis    = json_decode( (string) $target->analysis_json, true ) ?: array();
		$candidate_analysis = json_decode( (string) $candidate->analysis_json, true ) ?: array();
		$target_concepts    = self::smell_concepts( $target_analysis );
		$candidate_concepts = self::smell_concepts( $candidate_analysis );
		foreach ( $target_concepts as $target_index => $target_concept ) {
			$target_terms = array_map( 'mb_strtolower', array_merge( array( $target_concept['phrase'] ), $target_concept['anchors'] ) );
			foreach ( $candidate_concepts as $candidate_index => $candidate_concept ) {
				$candidate_terms = array_map( 'mb_strtolower', array_merge( array( $candidate_concept['phrase'] ), $candidate_concept['anchors'] ) );
				if ( array_intersect( $target_terms, $candidate_terms ) ) {
					return array( $target_index, $candidate_index );
				}
			}
		}
		return array( 0, 0 );
	}

	private static function flatten_analysis( string $json ): array {
		$data = json_decode( $json, true ) ?: array();
		$tags = array();
		foreach ( self::smell_concepts( $data ) as $concept ) {
			$tags[] = $concept['phrase'];
			$tags   = array_merge( $tags, $concept['anchors'] );
		}
		foreach ( array( 'states', 'moments', 'relationships', 'places', 'themes', 'emotional_tones' ) as $key ) {
			$tags = array_merge( $tags, is_array( $data[ $key ] ?? null ) ? $data[ $key ] : array() );
		}
		return array_values( array_unique( array_map( 'strval', $tags ) ) );
	}
}
