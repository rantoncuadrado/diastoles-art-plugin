<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_REST {
	private const NS = 'diastoles/v1';
	private const DAILY_RESPONSE_LIMIT = 5;
	private const MIN_RESPONSE_SECONDS = 10;
	private const GLOBAL_DUPLICATE_LIMIT = 3;

	public static function register_routes(): void {
		register_rest_route(
			self::NS,
			'/session',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'create_session' ),
				'permission_callback' => array( self::class, 'public_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/session/recover',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'recover_session' ),
				'permission_callback' => array( self::class, 'public_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/state',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_state' ),
				'permission_callback' => array( self::class, 'public_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/profile',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( self::class, 'update_profile' ),
				'permission_callback' => array( self::class, 'participant_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/responses',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'create_response' ),
				'permission_callback' => array( self::class, 'participant_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/questions/(?P<id>\d+)/skip',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'skip_question' ),
				'permission_callback' => array( self::class, 'participant_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/responses/(?P<id>[a-f0-9-]+)/withdraw',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'withdraw_response' ),
				'permission_callback' => array( self::class, 'participant_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/network',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_network' ),
				'permission_callback' => array( self::class, 'public_permission' ),
			)
		);
		register_rest_route(
			self::NS,
			'/events',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'record_event' ),
				'permission_callback' => array( self::class, 'participant_permission' ),
			)
		);
	}

	public static function public_permission( WP_REST_Request $request ): true|WP_Error {
		$origin = $request->get_header( 'origin' );
		if ( $origin && wp_parse_url( $origin, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return new WP_Error( 'diastoles_origin', 'Invalid request origin.', array( 'status' => 403 ) );
		}

		$recovery_token = sanitize_text_field( (string) $request->get_param( 'diastoles_recovery' ) );
		if ( ! $recovery_token ) {
			$recovery_token = sanitize_text_field( $request->get_header( 'x-diastoles-recovery' ) );
		}
		Diastoles_Session::use_request_recovery_token( $recovery_token );

		return self::rate_limit( $recovery_token );
	}

	public static function participant_permission( WP_REST_Request $request ): true|WP_Error {
		$public = self::public_permission( $request );
		if ( is_wp_error( $public ) ) {
			return $public;
		}
		return Diastoles_Session::current()
			? true
			: new WP_Error( 'diastoles_session_required', 'Your anonymous session could not be found.', array( 'status' => 401 ) );
	}

	public static function create_session( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		if ( Diastoles_Session::current() ) {
			return new WP_REST_Response(
				array(
					'ok'       => true,
					'existing' => true,
				)
			);
		}
		if ( '' !== trim( sanitize_text_field( (string) $request->get_param( 'website' ) ) ) ) {
			return new WP_Error( 'diastoles_automated_submission', 'This request could not be accepted.', array( 'status' => 400 ) );
		}

		$consent_fragments = rest_sanitize_boolean( $request->get_param( 'consent_fragments' ) );
		if ( ! $consent_fragments ) {
			return new WP_Error( 'diastoles_consent_required', 'Consent is required to participate.', array( 'status' => 400 ) );
		}
		$raw_email = trim( (string) $request->get_param( 'publication_email' ) );
		$email     = sanitize_email( $raw_email );
		if ( '' !== $raw_email && ( '' === $email || ! is_email( $email ) ) ) {
			return new WP_Error( 'diastoles_invalid_email', 'Enter a valid email address or leave the field empty.', array( 'status' => 400 ) );
		}

		$session      = Diastoles_Session::create(
			(string) $request->get_param( 'pseudonym' ),
			$consent_fragments
		);
		Diastoles_DB::save_publication_email( (int) $session['participant_id'], $email );
		Diastoles_DB::save_profile( (int) $session['participant_id'], self::profile_from_request( $request ) );
		$recovery_url = add_query_arg(
			array(
				'diastoles_recover' => $session['recovery_token'],
				'lang' => Diastoles_I18n::request_locale( $request ),
			),
			home_url( '/' )
		);

		return new WP_REST_Response(
			array(
				'ok'           => true,
				'recovery_url' => esc_url_raw( $recovery_url ),
			),
			201
		);
	}

	public static function recover_session( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$token = sanitize_text_field( (string) $request->get_param( 'token' ) );
		if ( strlen( $token ) !== 64 || ! ctype_xdigit( $token ) || ! Diastoles_Session::recover( $token ) ) {
			return new WP_Error( 'diastoles_invalid_recovery', 'This recovery link is invalid.', array( 'status' => 404 ) );
		}
		return new WP_REST_Response( array( 'ok' => true ) );
	}

	public static function get_state( WP_REST_Request $request ): WP_REST_Response {
		$locale      = Diastoles_I18n::request_locale( $request );
		$participant = Diastoles_Session::current();
		if ( ! $participant ) {
			return self::uncached_response(
				array(
					'authenticated' => false,
					'locale'        => $locale,
					'direction'     => Diastoles_I18n::direction( $locale ),
					'ai_mode'       => Diastoles_Anthropic::mode(),
				)
			);
		}

		$daily_count     = self::daily_response_count( (int) $participant->id );
		$daily_remaining = max( 0, self::DAILY_RESPONSE_LIMIT - $daily_count );

		return self::uncached_response(
			array(
				'authenticated' => true,
				'participant'   => array(
					'pseudonym' => $participant->pseudonym,
				),
				'profile'       => Diastoles_DB::profile_payload( Diastoles_DB::get_profile( (int) $participant->id ) ),
				'locale'        => $locale,
				'direction'     => Diastoles_I18n::direction( $locale ),
				'question'      => $daily_remaining > 0 ? self::next_question( (int) $participant->id, $locale ) : null,
				'responses'     => self::participant_responses( (int) $participant->id, $locale ),
				'connections'   => self::participant_connections( (int) $participant->id, $locale ),
				'daily_limit'   => self::DAILY_RESPONSE_LIMIT,
				'daily_count'   => $daily_count,
				'daily_remaining' => $daily_remaining,
				'ai_mode'       => Diastoles_Anthropic::mode(),
			)
		);
	}

	private static function uncached_response( array $data ): WP_REST_Response {
		$response = new WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Vary', 'Cookie' );
		return $response;
	}

	public static function update_profile( WP_REST_Request $request ): WP_REST_Response {
		$participant = Diastoles_Session::current();
		Diastoles_DB::save_profile( (int) $participant->id, self::profile_from_request( $request ) );
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'profile' => Diastoles_DB::profile_payload( Diastoles_DB::get_profile( (int) $participant->id ) ),
			)
		);
	}

	public static function create_response( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$participant = Diastoles_Session::current();
		$question_id = absint( $request->get_param( 'question_id' ) );
		$original    = trim( sanitize_textarea_field( (string) $request->get_param( 'original_text' ) ) );
		$explanation = trim( sanitize_textarea_field( (string) $request->get_param( 'explanation_text' ) ) );

		if ( '' !== trim( sanitize_text_field( (string) $request->get_param( 'website' ) ) ) ) {
			return new WP_Error( 'diastoles_automated_submission', 'This response could not be accepted.', array( 'status' => 400 ) );
		}

		if ( self::daily_response_count( (int) $participant->id ) >= self::DAILY_RESPONSE_LIMIT ) {
			return new WP_Error(
				'diastoles_daily_limit',
				'You have left five traces today. Return tomorrow and see what has begun to resonate.',
				array( 'status' => 429 )
			);
		}

		if ( mb_strlen( $original ) < 2 || mb_strlen( $original ) > 4000 || mb_strlen( $explanation ) > 4000 ) {
			return new WP_Error( 'diastoles_invalid_response', 'Write between 2 and 4,000 characters.', array( 'status' => 400 ) );
		}

		$questions_table = Diastoles_DB::table( 'questions' );
		$question        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $questions_table WHERE id = %d AND active = 1", $question_id )
		);
		if ( ! $question ) {
			return new WP_Error( 'diastoles_question_missing', 'This question is no longer available.', array( 'status' => 404 ) );
		}

		$views_table = Diastoles_DB::table( 'question_views' );
		$view        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status, created_at FROM $views_table WHERE participant_id = %d AND question_id = %d",
				(int) $participant->id,
				$question_id
			)
		);
		if ( ! $view || 'seen' !== $view->status ) {
			return new WP_Error( 'diastoles_question_not_seen', 'Open a question before leaving a trace.', array( 'status' => 400 ) );
		}
		$seconds_visible = time() - strtotime( $view->created_at . ' UTC' );
		if ( $seconds_visible < self::MIN_RESPONSE_SECONDS ) {
			return new WP_Error(
				'diastoles_response_too_fast',
				'Stay with the question for a few more seconds before leaving your trace.',
				array( 'status' => 429 )
			);
		}

		$content_hash    = self::content_hash( $original );
		$responses_table = Diastoles_DB::table( 'responses' );
		$own_duplicate   = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $responses_table WHERE participant_id = %d AND content_hash = %s",
				(int) $participant->id,
				$content_hash
			)
		);
		if ( $own_duplicate > 0 ) {
			return new WP_Error(
				'diastoles_duplicate_response',
				'You have already left this trace. Try adding something new.',
				array( 'status' => 409 )
			);
		}

		if ( mb_strlen( self::normalized_content( $original ) ) >= 20 ) {
			$duplicate_since = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
			$recent_matches  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $responses_table
					WHERE content_hash = %s AND withdrawn = 0 AND created_at >= %s",
					$content_hash,
					$duplicate_since
				)
			);
			if ( $recent_matches >= self::GLOBAL_DUPLICATE_LIMIT ) {
				return new WP_Error(
					'diastoles_repeated_content',
					'This exact fragment has been repeated too many times. Please write it in your own words.',
					array( 'status' => 409 )
				);
			}
		}

		$needs_review = self::content_needs_review( $original . "\n" . $explanation );
		$wpdb->insert(
			$responses_table,
			array(
				'public_id'         => wp_generate_uuid4(),
				'participant_id'    => (int) $participant->id,
				'question_id'       => $question_id,
				'original_text'     => $original,
				'content_hash'      => $content_hash,
				'explanation_text'  => $explanation,
				'source_language'   => '',
				'translation_en'    => '',
				'analysis_json'     => '{}',
				'processing_status' => $needs_review ? 'needs_review' : 'pending',
				'allow_network'     => $needs_review ? 0 : 1,
				'withdrawn'         => 0,
				'created_at'        => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);
		$response_id = (int) $wpdb->insert_id;

		$wpdb->update(
			Diastoles_DB::table( 'question_views' ),
			array( 'status' => 'answered' ),
			array(
				'participant_id' => (int) $participant->id,
				'question_id'    => $question_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		if ( $needs_review ) {
			Diastoles_DB::record_processing_error( $response_id, 'Response held for manual content review before analysis.' );
		} else {
			if ( ! wp_next_scheduled( 'diastoles_process_response', array( $response_id ) ) ) {
				wp_schedule_single_event( time() + 1, 'diastoles_process_response', array( $response_id ) );
			}
		}
		Diastoles_DB::record_event(
			'response_submitted',
			array(
				'participant'        => $participant,
				'question_id'        => $question_id,
				'screen'             => 'stimulus',
				'interface_language' => Diastoles_I18n::request_locale( $request ),
				'metadata'           => array(
					'response_id'       => $response_id,
					'processing_status' => $needs_review ? 'needs_review' : 'pending',
					'original_length'   => mb_strlen( $original ),
					'explanation_length' => mb_strlen( $explanation ),
				),
			)
		);

		return new WP_REST_Response( array( 'ok' => true, 'response_id' => $response_id ), 201 );
	}

	private static function content_needs_review( string $text ): bool {
		$text = remove_accents( mb_strtolower( $text ) );
		$patterns = array(
			'/\b(kill yourself|go kill yourself|rape|rapist|lynch|exterminate|gas the|white power|heil hitler)\b/u',
			'/\b(nazi|terrorist|subhuman|vermin|parasite)\b.*\b(people|women|men|immigrants|jews|muslims|black|gay|trans|foreigners)\b/u',
			'/\b(women|men|immigrants|jews|muslims|black people|gay people|trans people|foreigners)\b.*\b(are|should be)\b.*\b(inferior|subhuman|killed|raped|deported|eradicated)\b/u',
			'/\b(puta|zorra|maricon|maric[oó]n|moro de mierda|negro de mierda|vete a tu pais|vete a tu pa[ií]s|violarlas|matarlas|matarlos|exterminar)\b/u',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $text ) ) {
				return true;
			}
		}
		return false;
	}

	public static function skip_question( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$participant = Diastoles_Session::current();
		$question_id = absint( $request['id'] );
		$wpdb->update(
			Diastoles_DB::table( 'question_views' ),
			array( 'status' => 'skipped' ),
			array(
				'participant_id' => (int) $participant->id,
				'question_id'    => $question_id,
				'status'         => 'seen',
			),
			array( '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( $wpdb->rows_affected > 0 ) {
			Diastoles_DB::increment_question_skip_count( $question_id );
		}
		Diastoles_DB::record_event(
			'skip',
			array(
				'participant'        => $participant,
				'question_id'        => $question_id,
				'screen'             => 'stimulus',
				'interface_language' => Diastoles_I18n::request_locale( $request ),
			)
		);
		return new WP_REST_Response( array( 'ok' => true ) );
	}

	public static function record_event( WP_REST_Request $request ): WP_REST_Response {
		Diastoles_DB::record_event(
			sanitize_key( (string) $request->get_param( 'event_type' ) ),
			array(
				'participant'        => Diastoles_Session::current(),
				'question_id'        => absint( $request->get_param( 'question_id' ) ),
				'screen'             => sanitize_key( (string) $request->get_param( 'screen' ) ),
				'interface_language' => Diastoles_I18n::request_locale( $request ),
				'metadata'           => (array) $request->get_param( 'metadata' ),
			)
		);
		return new WP_REST_Response( array( 'ok' => true ), 201 );
	}

	public static function withdraw_response( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$participant = Diastoles_Session::current();
		$table       = Diastoles_DB::table( 'responses' );
		$response    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE public_id = %s AND participant_id = %d",
				sanitize_text_field( $request['id'] ),
				(int) $participant->id
			)
		);
		if ( ! $response ) {
			return new WP_Error( 'diastoles_response_missing', 'Response not found.', array( 'status' => 404 ) );
		}

		$wpdb->update(
			$table,
			array( 'withdrawn' => 1, 'allow_network' => 0 ),
			array( 'id' => (int) $response->id ),
			array( '%d', '%d' ),
			array( '%d' )
		);
		$connections = Diastoles_DB::table( 'connections' );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $connections SET status = 'withdrawn' WHERE response_a_id = %d OR response_b_id = %d",
				(int) $response->id,
				(int) $response->id
			)
		);
		return new WP_REST_Response( array( 'ok' => true ) );
	}

	public static function get_network( ?WP_REST_Request $request = null ): WP_REST_Response {
		global $wpdb;
		$locale      = Diastoles_I18n::request_locale( $request );
		$responses   = Diastoles_DB::table( 'responses' );
		$connections = Diastoles_DB::table( 'connections' );
		$questions   = Diastoles_DB::table( 'questions' );
		$nodes       = $wpdb->get_results(
			"SELECT r.public_id, r.original_text, r.explanation_text, r.translation_en, r.source_language, r.analysis_json,
				q.id AS question_id, q.prompt AS question_prompt, q.short_label AS question_short_label, q.followup AS question_followup, q.theme AS question_theme
			FROM $responses r
			INNER JOIN $questions q ON q.id = r.question_id
			WHERE r.processing_status = 'complete' AND r.allow_network = 1 AND r.withdrawn = 0
			ORDER BY r.created_at DESC
			LIMIT 500"
		);
		$edges       = $wpdb->get_results(
			"SELECT c.public_id, c.relation_type, c.score, c.explanation_json, c.created_at,
				a.public_id AS source, b.public_id AS target
			FROM $connections c
			INNER JOIN $responses a ON a.id = c.response_a_id
			INNER JOIN $responses b ON b.id = c.response_b_id
			WHERE c.status = 'approved' AND a.withdrawn = 0 AND b.withdrawn = 0
			LIMIT 1000"
		);
		$node_payloads    = array();
		$recent_fragments = array();
		foreach ( array_reverse( $nodes ) as $response ) {
			$localized_question = Diastoles_I18n::question_translation( (int) $response->question_id, $locale, (object) array(
				'prompt' => $response->question_prompt,
				'short_label' => $response->question_short_label,
				'followup' => $response->question_followup,
			) );
			$response->question_prompt      = $localized_question['prompt'];
			$response->question_short_label = $localized_question['short_label'];
			$localized_fragment    = self::response_translation( $response, $locale );
			$localized_explanation = self::explanation_translation( $response, $locale );
			$analysis       = json_decode( $response->analysis_json, true ) ?: array();
			$concepts       = Diastoles_Anthropic::smell_concepts( $analysis );
			$question_label = trim( (string) $response->question_short_label );
			if ( '' === $question_label ) {
				$prompt         = trim( preg_replace( '/\s+/u', ' ', (string) $response->question_prompt ) );
				$question_label = mb_strlen( $prompt ) > 90 ? rtrim( mb_substr( $prompt, 0, 89 ) ) . '…' : $prompt;
			}
			$concepts = array_values(
				array_filter(
					$concepts,
					static fn( array $concept ): bool => ! in_array(
						strtolower( trim( remove_accents( $concept['phrase'] ) ) ),
						array( '', 'anonymous scent', 'unclassified scent', 'unknown', 'unknown scent' ),
						true
					)
				)
			);
			if ( ! $concepts ) {
				continue;
			}

			$phrases = array_column( $concepts, 'phrase' );
			$localized_concepts = array_map(
				static function ( array $concept ) use ( $locale ): array {
					$localized = Diastoles_I18n::dynamic_translation( (string) ( $concept['phrase'] ?? '' ), $locale, 'en' );
					if ( '' !== trim( $localized ) ) {
						$concept['phrase'] = $localized;
					}
					if ( isset( $concept['anchors'] ) && is_array( $concept['anchors'] ) ) {
						$concept['anchors'] = array_values(
							array_map(
								static function ( string $anchor ) use ( $locale ): string {
									$localized_anchor = Diastoles_I18n::dynamic_translation( $anchor, $locale, 'en' );
									return '' !== trim( $localized_anchor ) ? $localized_anchor : $anchor;
								},
								array_filter( array_map( 'strval', $concept['anchors'] ) )
							)
						);
					}
					return $concept;
				},
				$concepts
			);
			$recent_fragments[] = array(
				'id'             => $response->public_id,
				'fragment'       => $response->original_text,
				'explanation'    => $response->explanation_text,
				'translation'    => $localized_fragment,
				'explanation_translation' => $localized_explanation,
				'language'       => $response->source_language,
				'question'       => $response->question_prompt,
				'question_label' => $question_label,
				'smells'         => $phrases,
				'concepts'       => $localized_concepts,
			);

			foreach ( $concepts as $index => $concept ) {
				$primary_anchor = $concept['anchors'][0] ?? $concept['phrase'];
				$canonical      = self::canonical_smell( $primary_anchor );
				$localized_phrase = Diastoles_I18n::dynamic_translation( (string) $concept['phrase'], $locale, 'en' );
				$localized_label  = Diastoles_I18n::dynamic_translation( (string) $canonical['label'], $locale, 'en' );
				if ( '' !== trim( $localized_phrase ) ) {
					$concept['phrase'] = $localized_phrase;
				}
				if ( '' !== trim( $localized_label ) ) {
					$canonical['label'] = $localized_label;
				}
				if ( isset( $concept['anchors'] ) && is_array( $concept['anchors'] ) ) {
					$concept['anchors'] = array_values(
						array_map(
							static function ( string $anchor ) use ( $locale ): string {
								$localized_anchor = Diastoles_I18n::dynamic_translation( $anchor, $locale, 'en' );
								return '' !== trim( $localized_anchor ) ? $localized_anchor : $anchor;
							},
							array_filter( array_map( 'strval', $concept['anchors'] ) )
						)
					);
				}
				$node_payloads[] = array(
					'id'               => $response->public_id . ':concept-' . $index,
					'response_id'      => $response->public_id,
					'concept_index'    => $index,
					'question_id'      => (int) $response->question_id,
					'concept'          => $concept['phrase'],
					'anchors'          => $concept['anchors'],
					'fragment'         => $response->original_text,
					'explanation'      => $response->explanation_text,
					'translation'      => $localized_fragment,
					'explanation_translation' => $localized_explanation,
					'language'         => $response->source_language,
					'question'         => $response->question_prompt,
					'question_label'   => $question_label,
					'smells'           => array( $concept['phrase'] ),
					'canonical_smells' => array( $canonical ),
					'primary_smell_id' => $canonical['id'],
					'states'           => $analysis['states'] ?? array(),
					'semantic_fields'  => $concept['semantic_fields'] ?? array(),
					'question_theme'   => $response->question_theme,
				);
			}
		}
		$smell_clusters = array();
		foreach ( $node_payloads as $node ) {
			$primary = $node['canonical_smells'][0] ?? null;
			if ( ! $primary ) {
				continue;
			}
			$id = $primary['id'];
			if ( ! isset( $smell_clusters[ $id ] ) ) {
				$smell_clusters[ $id ] = array(
					'id'       => $id,
					'label'    => $primary['label'],
					'aliases'  => array(),
					'node_ids' => array(),
				);
			}
			$smell_clusters[ $id ]['aliases'][]  = $primary['alias'];
			$smell_clusters[ $id ]['node_ids'][] = $node['id'];
		}
		$smell_clusters = array_values(
			array_map(
				static function ( array $cluster ): array {
					$cluster['aliases']  = array_values( array_unique( $cluster['aliases'] ) );
					$cluster['node_ids'] = array_values( array_unique( $cluster['node_ids'] ) );
					return $cluster;
				},
				$smell_clusters
			)
		);

		$nodes_by_id = array_column( $node_payloads, null, 'id' );
		$edge_payloads = array_values(
			array_filter(
				array_map(
					static function ( object $edge ): array {
						$explanation = json_decode( $edge->explanation_json, true ) ?: array();
						$evidence    = array_values( (array) ( $explanation['evidence'] ?? array() ) );
						$verified    = $evidence && count(
							array_filter(
								$evidence,
								static fn( mixed $item ): bool => is_string( $item ) && ( str_starts_with( $item, 'Participant response:' ) || str_starts_with( $item, 'Participant explanation:' ) )
							)
						) === count( $evidence );
						return array(
							'id'       => $edge->public_id,
							'source'   => $edge->source . ':concept-' . max( 0, (int) ( $explanation['concept_a_index'] ?? 0 ) ),
							'target'   => $edge->target . ':concept-' . max( 0, (int) ( $explanation['concept_b_index'] ?? 0 ) ),
							'type'     => $edge->relation_type,
							'score'    => (float) $edge->score,
							'created'  => mysql2date( DATE_ATOM, $edge->created_at . ' UTC', false ),
							'evidence' => $evidence,
							'evidence_verified' => $verified,
						);
					},
					$edges
				),
				static function ( array $edge ) use ( $nodes_by_id ): bool {
					$source = $nodes_by_id[ $edge['source'] ] ?? null;
					$target = $nodes_by_id[ $edge['target'] ] ?? null;
					if ( ! $source || ! $target ) {
						return false;
					}
					$source_fragment = mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $source['fragment'] ) ) );
					$target_fragment = mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $target['fragment'] ) ) );
					return $source_fragment !== $target_fragment;
				}
			)
		);

		return new WP_REST_Response(
			array(
				'locale'           => $locale,
				'direction'        => Diastoles_I18n::direction( $locale ),
				'nodes'            => $node_payloads,
				'recent_fragments' => $recent_fragments,
				'smell_clusters'   => $smell_clusters,
				'edges'            => $edge_payloads,
			)
		);
	}

	private static function canonical_smell( string $smell ): array {
		$alias      = trim( $smell );
		$normalized = strtolower( remove_accents( $alias ) );
		$normalized = preg_replace( '/[^\p{L}\p{N}\s-]+/u', ' ', $normalized );
		$normalized = trim( preg_replace( '/\s+/', ' ', (string) $normalized ) );
		$aliases    = array(
			'jasmine'    => array( 'jasmine', 'jazmin', 'jasmin', 'yasemin', 'gelsomino' ),
			'coffee'     => array( 'coffee', 'cafe', 'caffe', 'kaffee', 'koffie' ),
			'rain'       => array( 'rain', 'lluvia', 'pluie', 'regen', 'chuva', 'pioggia' ),
			'wet earth'  => array( 'wet earth', 'tierra mojada', 'petricor', 'petrichor', 'terra molhada' ),
			'bread'      => array( 'bread', 'pan', 'pain', 'brot', 'pao', 'pane' ),
			'sea'        => array( 'sea', 'ocean', 'mar', 'mer', 'meer', 'mare' ),
			'smoke'      => array( 'smoke', 'humo', 'fumee', 'rauch', 'fumaca', 'fumo' ),
			'soap'       => array( 'soap', 'jabon', 'savon', 'seife', 'sabonete', 'sapone' ),
			'wood'       => array( 'wood', 'madera', 'bois', 'holz', 'madeira', 'legno' ),
			'forest'     => array( 'forest', 'bosque', 'foret', 'wald', 'floresta', 'foresta' ),
			'lavender'   => array( 'lavender', 'lavanda', 'lavande', 'lavendel' ),
			'rose'       => array( 'rose', 'rosa' ),
			'orange blossom' => array( 'orange blossom', 'azahar', 'fleur d oranger', 'zagara' ),
		);
		$canonical = $normalized ?: 'unclassified scent';
		foreach ( $aliases as $label => $known_aliases ) {
			if ( in_array( $normalized, $known_aliases, true ) ) {
				$canonical = $label;
				break;
			}
		}

		return array(
			'id'    => 'smell-' . substr( hash( 'sha256', $canonical ), 0, 12 ),
			'label' => ucwords( $canonical ),
			'alias' => $alias ?: $canonical,
		);
	}

	private static function next_question( int $participant_id, string $locale = 'en' ): ?array {
		global $wpdb;
		$questions = Diastoles_DB::table( 'questions' );
		$views     = Diastoles_DB::table( 'question_views' );
		$question  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT q.* FROM $questions q
				INNER JOIN $views v ON v.question_id = q.id
				WHERE q.active = 1 AND v.participant_id = %d AND v.status = 'seen'
				ORDER BY v.created_at DESC
				LIMIT 1",
				$participant_id
			)
		);
		if ( $question ) {
			return self::question_payload( $question, $locale );
		}

		$question  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT q.* FROM $questions q
				WHERE q.active = 1
					AND NOT EXISTS (
						SELECT 1 FROM $views v WHERE v.question_id = q.id AND v.participant_id = %d
					)
				ORDER BY RAND()
				LIMIT 1",
				$participant_id
			)
		);
		if ( ! $question ) {
			return null;
		}
		$wpdb->insert(
			$views,
			array(
				'participant_id' => $participant_id,
				'question_id'    => (int) $question->id,
				'status'         => 'seen',
				'created_at'     => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		return self::question_payload( $question, $locale );
	}

	private static function question_payload( object $question, string $locale = 'en' ): array {
		$translation = Diastoles_I18n::question_translation( (int) $question->id, $locale, $question );
		return array(
			'id'          => (int) $question->id,
			'prompt'      => $translation['prompt'],
			'short_label' => $translation['short_label'],
			'followup'    => $translation['followup'],
			'theme'       => $question->theme,
			'intensity'   => (int) $question->intensity,
		);
	}

	private static function response_translation( object $response, string $locale ): string {
		$locale = Diastoles_I18n::normalize( $locale ) ?: 'en';
		$source = Diastoles_I18n::normalize( $response->source_language ?? '' );
		if ( $source && $source === $locale ) {
			return (string) $response->original_text;
		}
		if ( 'en' === $locale && '' !== trim( (string) ( $response->translation_en ?? '' ) ) ) {
			return (string) $response->translation_en;
		}
		return Diastoles_I18n::dynamic_translation( (string) $response->original_text, $locale, $source );
	}

	private static function explanation_translation( object $response, string $locale ): string {
		$explanation = trim( (string) ( $response->explanation_text ?? '' ) );
		if ( '' === $explanation ) {
			return '';
		}
		$locale = Diastoles_I18n::normalize( $locale ) ?: 'en';
		$source = Diastoles_I18n::normalize( $response->source_language ?? '' );
		if ( $source && $source === $locale ) {
			return $explanation;
		}
		return Diastoles_I18n::dynamic_translation( $explanation, $locale, $source );
	}

	private static function participant_responses( int $participant_id, string $locale = 'en' ): array {
		global $wpdb;
		$responses = Diastoles_DB::table( 'responses' );
		$questions = Diastoles_DB::table( 'questions' );
		$rows      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.public_id, r.original_text, r.explanation_text, r.translation_en, r.source_language,
					r.processing_status, r.created_at, q.id AS question_id, q.prompt AS question_prompt, q.short_label, q.followup
				FROM $responses r
				INNER JOIN $questions q ON q.id = r.question_id
				WHERE r.participant_id = %d AND r.withdrawn = 0
				ORDER BY r.created_at DESC
				LIMIT 50",
				$participant_id
			)
		);
		return array_map(
			static function ( object $row ) use ( $locale ): array {
				$question = Diastoles_I18n::question_translation( (int) $row->question_id, $locale, (object) array( 'prompt' => $row->question_prompt, 'short_label' => $row->short_label, 'followup' => $row->followup ) );
				return array(
				'id'          => $row->public_id,
				'question'    => $question['prompt'],
				'original'    => $row->original_text,
				'explanation' => $row->explanation_text,
				'translation' => self::response_translation( $row, $locale ),
				'explanation_translation' => self::explanation_translation( $row, $locale ),
				'language'    => $row->source_language,
				'status'      => $row->processing_status,
				'created'     => mysql2date( DATE_ATOM, $row->created_at . ' UTC', false ),
				);
			},
			$rows
		);
	}

	private static function participant_connections( int $participant_id, string $locale = 'en' ): array {
		global $wpdb;
		$connections = Diastoles_DB::table( 'connections' );
		$responses   = Diastoles_DB::table( 'responses' );
		$questions   = Diastoles_DB::table( 'questions' );
		$rows        = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*,
					CASE WHEN c.participant_a_id = %d THEN rb.original_text ELSE ra.original_text END AS other_original,
					CASE WHEN c.participant_a_id = %d THEN rb.explanation_text ELSE ra.explanation_text END AS other_explanation,
					CASE WHEN c.participant_a_id = %d THEN rb.translation_en ELSE ra.translation_en END AS other_translation,
					CASE WHEN c.participant_a_id = %d THEN rb.source_language ELSE ra.source_language END AS other_language,
					CASE WHEN c.participant_a_id = %d THEN qb.id ELSE qa.id END AS other_question_id,
					CASE WHEN c.participant_a_id = %d THEN qb.prompt ELSE qa.prompt END AS other_question,
					CASE WHEN c.participant_a_id = %d THEN qb.short_label ELSE qa.short_label END AS other_question_short_label,
					CASE WHEN c.participant_a_id = %d THEN qb.followup ELSE qa.followup END AS other_question_followup
				FROM $connections c
				INNER JOIN $responses ra ON ra.id = c.response_a_id
				INNER JOIN $responses rb ON rb.id = c.response_b_id
				INNER JOIN $questions qa ON qa.id = ra.question_id
				INNER JOIN $questions qb ON qb.id = rb.question_id
				WHERE c.status = 'approved'
					AND (c.participant_a_id = %d OR c.participant_b_id = %d)
				ORDER BY c.moderated_at DESC
				LIMIT 30",
				$participant_id,
				$participant_id,
				$participant_id,
				$participant_id,
				$participant_id,
				$participant_id,
				$participant_id,
				$participant_id,
				$participant_id,
				$participant_id
			)
		);

		return array_map(
			static function ( object $row ) use ( $locale ): array {
				$explanation = json_decode( $row->explanation_json, true ) ?: array();
				$question = Diastoles_I18n::question_translation( (int) $row->other_question_id, $locale, (object) array( 'prompt' => $row->other_question, 'short_label' => $row->other_question_short_label, 'followup' => $row->other_question_followup ) );
				return array(
					'id'                => $row->public_id,
					'relation_type'     => $row->relation_type,
					'evidence'          => array_values( array_filter( array_map( static fn( string $text ): string => Diastoles_I18n::dynamic_translation( $text, $locale, 'en' ), (array) ( $explanation['evidence'] ?? array() ) ) ) ),
					'context'           => array_values( array_filter( array_map( static fn( string $text ): string => Diastoles_I18n::dynamic_translation( $text, $locale, 'en' ), (array) ( $explanation['context'] ?? array() ) ) ) ),
					'question'          => $question['prompt'],
					'fragment'          => $row->other_original,
					'explanation'       => $row->other_explanation,
					'translation'       => self::response_translation( (object) array( 'original_text' => $row->other_original, 'translation_en' => $row->other_translation, 'source_language' => $row->other_language ), $locale ),
					'explanation_translation' => self::explanation_translation( (object) array( 'explanation_text' => $row->other_explanation, 'source_language' => $row->other_language ), $locale ),
					'language'          => $row->other_language,
				);
			},
			$rows
		);
	}

	private static function profile_from_request( WP_REST_Request $request ): array {
		$profile = array(
			'gender'              => $request->get_param( 'gender' ),
			'age_band'            => $request->get_param( 'age_band' ),
			'environment'         => $request->get_param( 'environment' ),
			'country'             => $request->get_param( 'country' ),
			'continent'           => $request->get_param( 'continent' ),
			'native_languages'    => $request->get_param( 'native_languages' ),
			'other_languages'     => $request->get_param( 'other_languages' ),
			'multilingual_status' => $request->get_param( 'multilingual_status' ),
			'work_areas'          => $request->get_param( 'work_areas' ),
			'hobbies'             => $request->get_param( 'hobbies' ),
			'rooted_places'       => $request->get_param( 'rooted_places' ),
		);
		$has_details = false;
		foreach ( $profile as $value ) {
			if ( is_array( $value ) ? ! empty( array_filter( $value ) ) : '' !== trim( (string) $value ) ) {
				$has_details = true;
				break;
			}
		}
		$profile['use_for_matching']      = $has_details;
		$profile['allow_context_display'] = false;
		return $profile;
	}

	private static function daily_response_count( int $participant_id ): int {
		global $wpdb;
		$now_local = current_datetime();
		$day_start = $now_local->setTime( 0, 0 )->setTimezone( new DateTimeZone( 'UTC' ) );
		$table     = Diastoles_DB::table( 'responses' );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE participant_id = %d AND created_at >= %s",
				$participant_id,
				$day_start->format( 'Y-m-d H:i:s' )
			)
		);
	}

	private static function content_hash( string $content ): string {
		return hash( 'sha256', self::normalized_content( $content ) );
	}

	private static function normalized_content( string $content ): string {
		$content = mb_strtolower( trim( $content ) );
		return preg_replace( '/\s+/u', ' ', $content ) ?: $content;
	}

	private static function rate_limit( string $recovery_token = '' ): true|WP_Error {
		$ip            = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
		$session_token = sanitize_text_field( wp_unslash( $_COOKIE['diastoles_session'] ?? '' ) );
		if ( $recovery_token ) {
			$identity = 'recovery:' . $recovery_token;
		} elseif ( $session_token ) {
			$identity = 'session:' . $session_token;
		} else {
			$identity = 'ip:' . $ip;
		}
		$key           = 'diastoles_rate_' . hash_hmac( 'sha256', $identity, wp_salt( 'nonce' ) );
		$hit = (int) get_transient( $key );
		if ( $hit > 180 ) {
			return new WP_Error( 'diastoles_rate_limit', 'Too many requests. Please wait a moment.', array( 'status' => 429 ) );
		}
		set_transient( $key, $hit + 1, MINUTE_IN_SECONDS );
		return true;
	}
}
