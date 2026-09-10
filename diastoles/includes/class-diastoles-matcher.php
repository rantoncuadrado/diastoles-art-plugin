<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_Matcher {
	public static function create_candidates( int $response_id ): void {
		global $wpdb;

		$target = Diastoles_DB::get_response( $response_id );
		if ( ! $target || 'complete' !== $target->processing_status || ! (int) $target->allow_network || (int) $target->withdrawn ) {
			return;
		}
		$target_analysis = json_decode( (string) $target->analysis_json, true ) ?: array();
		$target_concepts = Diastoles_Anthropic::smell_concepts( $target_analysis );
		if ( ! $target_concepts ) {
			return;
		}

		$responses_table    = Diastoles_DB::table( 'responses' );
		$participants_table = Diastoles_DB::table( 'participants' );
		$connections_table  = Diastoles_DB::table( 'connections' );
		$target_consent     = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT consent_fragments FROM $participants_table WHERE id = %d",
				(int) $target->participant_id
			)
		);
		if ( ! $target_consent ) {
			return;
		}

		$candidate_limit = self::candidate_history_limit();
		$candidates = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*
				FROM $responses_table r
				INNER JOIN $participants_table p ON p.id = r.participant_id
				WHERE r.id != %d
					AND r.participant_id != %d
					AND r.processing_status = 'complete'
					AND r.withdrawn = 0
					AND r.allow_network = 1
					AND p.consent_fragments = 1
					AND NOT EXISTS (
						SELECT 1 FROM $connections_table c
						WHERE (c.response_a_id = %d AND c.response_b_id = r.id)
							OR (c.response_b_id = %d AND c.response_a_id = r.id)
				)
				ORDER BY r.created_at DESC
				LIMIT %d",
				$response_id,
				(int) $target->participant_id,
				$response_id,
				$response_id,
				$candidate_limit
			)
		);

		if ( empty( $candidates ) ) {
			Diastoles_DB::record_processing_event( $response_id, 'info', 'No eligible connection candidates were found before prefiltering. This can happen when every other response is incomplete, withdrawn, hidden, from the same participant, lacks consent, or already has a connection with this exact response pair.' );
			return;
		}

		$candidates = self::prefilter( $target, $candidates );
		if ( empty( $candidates ) ) {
			Diastoles_DB::record_processing_event( $response_id, 'info', 'No eligible connection candidates remained after deterministic prefiltering.' );
			return;
		}

		$selection_context = array();
		foreach ( $candidates as $candidate ) {
			$selection_context[ (int) $candidate->id ] = (array) ( $candidate->diastoles_selection_context ?? array() );
		}

		$ranking = Diastoles_Anthropic::rank_candidates( $target, array_slice( $candidates, 0, self::ai_candidate_limit() ) );
		if ( is_wp_error( $ranking ) ) {
			Diastoles_DB::record_processing_error( $response_id, $ranking->get_error_message() );
			return;
		}

		$allowed_ids = array_map( static fn( object $item ): int => (int) $item->id, $candidates );
		$created = 0;
		foreach ( array_slice( $ranking['connections'] ?? array(), 0, self::max_connections_per_response() ) as $connection ) {
			$candidate_id = (int) ( $connection['response_id'] ?? 0 );
			$semantic_score = min( 1, max( 0, (float) ( $connection['score'] ?? 0 ) ) );
			if ( ! in_array( $candidate_id, $allowed_ids, true ) ) {
				continue;
			}
			$candidate = Diastoles_DB::get_response( $candidate_id );
			if ( ! $candidate ) {
				continue;
			}
			$candidate_analysis = json_decode( (string) $candidate->analysis_json, true ) ?: array();
			$candidate_concepts = Diastoles_Anthropic::smell_concepts( $candidate_analysis );
			if ( ! $candidate_concepts ) {
				continue;
			}
			$evidence = Diastoles_Anthropic::connection_evidence( (array) ( $connection['evidence'] ?? array() ), $target, $candidate );
			if ( 'live' === Diastoles_Anthropic::mode() && ! $evidence ) {
				continue;
			}
			if ( 'mock' === Diastoles_Anthropic::mode() ) {
				$evidence = array_map( 'sanitize_text_field', (array) ( $connection['evidence'] ?? array() ) );
			}
			$suggested_target_index = min(
				count( $target_concepts ) - 1,
				max( 0, (int) ( $connection['target_concept_index'] ?? 0 ) )
			);
			$suggested_candidate_index = min(
				count( $candidate_concepts ) - 1,
				max( 0, (int) ( $connection['response_concept_index'] ?? 0 ) )
			);
			list( $target_concept_index, $candidate_concept_index ) = self::resolve_concept_pair(
				$target_concepts,
				$candidate_concepts,
				$suggested_target_index,
				$suggested_candidate_index
			);
			$target_theme    = self::question_theme( $target );
			$candidate_theme = self::question_theme( $candidate );
			$themes_match    = '' !== $target_theme && $target_theme === $candidate_theme;
			$theme_adjustment = $themes_match ? self::same_theme_adjustment() : self::different_theme_adjustment();
			$score            = min( 1, max( 0, $semantic_score + $theme_adjustment ) );

			$first         = min( (int) $target->id, (int) $candidate->id );
			$second        = max( (int) $target->id, (int) $candidate->id );
			$a             = $first === (int) $target->id ? $target : $candidate;
			$b             = $second === (int) $target->id ? $target : $candidate;
			$a_concept     = $first === (int) $target->id ? $target_concept_index : $candidate_concept_index;
			$b_concept     = $second === (int) $target->id ? $target_concept_index : $candidate_concept_index;
			$auto_approved    = $score >= self::auto_approve_threshold();
			$connection_status = $auto_approved
				? 'approved'
				: ( $score >= self::manual_approval_threshold() ? 'pending' : 'held' );
			$wpdb->insert(
				$connections_table,
				array(
					'public_id'        => wp_generate_uuid4(),
					'response_a_id'    => $first,
					'response_b_id'    => $second,
					'participant_a_id' => (int) $a->participant_id,
					'participant_b_id' => (int) $b->participant_id,
					'relation_type'    => self::valid_relation_type( (string) ( $connection['relation_type'] ?? '' ) ),
					'score'            => $score,
					'explanation_json' => wp_json_encode(
						array(
							'evidence'        => $evidence,
							'context'         => array(
								'semantic_score'   => $semantic_score,
								'target_theme'     => $target_theme,
								'candidate_theme'  => $candidate_theme,
								'theme_adjustment' => $theme_adjustment,
							),
							'selection_context' => $selection_context[ $candidate_id ] ?? array(),
							'concept_a_index' => $a_concept,
							'concept_b_index' => $b_concept,
						)
					),
					'status'            => $connection_status,
					'created_at'        => current_time( 'mysql', true ),
					'moderated_at'      => $auto_approved ? current_time( 'mysql', true ) : null,
				),
				array( '%s', '%d', '%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s' )
			);
			if ( $wpdb->insert_id ) {
				$created++;
			}
		}
		if ( 0 === $created ) {
			Diastoles_DB::record_processing_event( $response_id, 'info', 'The AI ranking returned no valid connection that passed evidence and response-pair checks.' );
		}
	}

	public static function auto_approve_threshold(): float {
		return min( 1, max( 0, (float) get_option( 'diastoles_connection_auto_approve_threshold', 0.5 ) ) );
	}

	public static function manual_approval_threshold(): float {
		$threshold = min( 1, max( 0, (float) get_option( 'diastoles_connection_manual_approval_threshold', 0 ) ) );
		return min( self::auto_approve_threshold(), $threshold );
	}

	public static function sanitize_positive_int( mixed $value, int $default, int $min = 1, int $max = 1000 ): int {
		return min( $max, max( $min, absint( $value ) ?: $default ) );
	}

	public static function sanitize_weight( mixed $value, float $default, float $min = -10, float $max = 20 ): float {
		return min( $max, max( $min, (float) ( is_numeric( $value ) ? $value : $default ) ) );
	}

	public static function candidate_history_limit(): int {
		return self::sanitize_positive_int( get_option( 'diastoles_matching_candidate_history_limit', 200 ), 200, 20, 1000 );
	}

	public static function ai_candidate_limit(): int {
		return self::sanitize_positive_int( get_option( 'diastoles_matching_ai_candidate_limit', 12 ), 12, 4, 40 );
	}

	public static function max_connections_per_response(): int {
		return self::sanitize_positive_int( get_option( 'diastoles_matching_max_connections', 3 ), 3, 1, 10 );
	}

	public static function shared_nuance_weight(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_shared_nuance_weight', 8 ), 8, 0, 20 );
	}

	public static function shared_context_weight(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_shared_context_weight', 5 ), 5, 0, 20 );
	}

	public static function shared_semantic_field_weight(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_shared_semantic_field_weight', 3 ), 3, 0, 20 );
	}

	public static function shared_emotional_tone_weight(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_shared_emotional_tone_weight', 3 ), 3, 0, 20 );
	}

	public static function profile_match_weight(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_profile_match_weight', 1 ), 1, 0, 10 );
	}

	public static function profile_diversity_weight(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_profile_diversity_weight', 1 ), 1, 0, 10 );
	}

	public static function local_vector_weight(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_local_vector_weight', 6 ), 6, 0, 20 );
	}

	public static function same_theme_adjustment(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_same_theme_adjustment', 0.12 ), 0.12, -1, 1 );
	}

	public static function different_theme_adjustment(): float {
		return self::sanitize_weight( get_option( 'diastoles_matching_different_theme_adjustment', -0.07 ), -0.07, -1, 1 );
	}

	public static function local_semantic_vector_enabled(): bool {
		return (bool) get_option( 'diastoles_matching_local_semantic_vector_enabled', 1 );
	}

	public static function auto_approve_pending_connections(): int {
		$changes = self::synchronize_connection_thresholds();
		return $changes['approved'];
	}

	public static function synchronize_connection_thresholds(): array {
		global $wpdb;

		$table            = Diastoles_DB::table( 'connections' );
		$auto_threshold   = self::auto_approve_threshold();
		$manual_threshold = self::manual_approval_threshold();
		$approved         = (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE $table
				SET status = 'approved', moderated_at = %s
				WHERE status IN ('pending', 'held') AND score >= %f",
				current_time( 'mysql', true ),
				$auto_threshold
			)
		);
		$held = (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE $table
				SET status = 'held', moderated_at = NULL
				WHERE status = 'pending' AND score < %f",
				$manual_threshold
			)
		);
		$pending = (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE $table
				SET status = 'pending', moderated_at = NULL
				WHERE status = 'held' AND score >= %f AND score < %f",
				$manual_threshold,
				$auto_threshold
			)
		);

		return compact( 'approved', 'pending', 'held' );
	}

	private static function prefilter( object $target, array $candidates ): array {
		$target_terms   = self::concept_terms( $target );
		$target_fields  = self::semantic_fields( $target );
		$target_tones   = self::emotional_tones( $target );
		$target_context = Diastoles_Anthropic::matching_context_terms( $target );
		$target_profile = self::profile_tags( (int) $target->participant_id );
		$target_vector  = self::local_semantic_vector( $target );
		$scored         = array();
		foreach ( $candidates as $candidate ) {
			$candidate_analysis = json_decode( (string) $candidate->analysis_json, true ) ?: array();
			if ( ! Diastoles_Anthropic::smell_concepts( $candidate_analysis ) ) {
				continue;
			}
			$candidate_terms   = self::concept_terms( $candidate );
			$candidate_fields  = self::semantic_fields( $candidate );
			$candidate_tones   = self::emotional_tones( $candidate );
			$candidate_context = Diastoles_Anthropic::matching_context_terms( $candidate );
			$shared_terms      = array_values( array_intersect( $target_terms, $candidate_terms ) );
			$shared_fields     = array_values( array_intersect( $target_fields, $candidate_fields ) );
			$shared_tones      = array_values( array_intersect( $target_tones, $candidate_tones ) );
			$shared_context    = array_values( array_intersect( $target_context, $candidate_context ) );
			$candidate_profile = self::profile_tags( (int) $candidate->participant_id );
			$profile_matches   = array_values( array_intersect( $target_profile, $candidate_profile ) );
			$profile_diversity = self::profile_diversity( $target_profile, $candidate_profile );
			$vector_similarity = self::local_semantic_vector_enabled() ? self::cosine_similarity( $target_vector, self::local_semantic_vector( $candidate ) ) : 0.0;
			$reasons           = array();
			$score             = 0.0;

			$score += self::append_reasons( $reasons, 'shared_nuance', $shared_terms, self::shared_nuance_weight() );
			$score += self::append_reasons( $reasons, 'semantic_field', $shared_fields, self::shared_semantic_field_weight() );
			$score += self::append_reasons( $reasons, 'emotional_tone', $shared_tones, self::shared_emotional_tone_weight() );
			$score += self::append_reasons( $reasons, 'shared_context', $shared_context, self::shared_context_weight() );
			$score += self::append_reasons( $reasons, 'profile_match', $profile_matches, self::profile_match_weight() );
			if ( $profile_diversity > 0 ) {
				$weight   = round( $profile_diversity * self::profile_diversity_weight(), 4 );
				$score   += $weight;
				$reasons[] = array( 'type' => 'profile_diversity', 'label' => 'different optional profile coordinates', 'weight' => $weight );
			}
			if ( $vector_similarity > 0 ) {
				$weight   = round( $vector_similarity * self::local_vector_weight(), 4 );
				$score   += $weight;
				$reasons[] = array( 'type' => 'local_semantic_vector', 'label' => 'cosine ' . round( $vector_similarity, 3 ), 'weight' => $weight );
			}

			if ( $score > 0 ) {
				$candidate->diastoles_selection_context = array(
					'selection_score'   => round( $score, 4 ),
					'selection_bucket'  => self::selection_bucket( $shared_terms, $shared_fields, $shared_tones, $shared_context, $profile_diversity, $vector_similarity ),
					'selection_reasons' => $reasons,
				);
				$scored[] = array(
					'response' => $candidate,
					'score'    => $score,
				);
			}
		}
		usort( $scored, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );
		return array_map( static fn( array $item ): object => $item['response'], $scored );
	}

	private static function append_reasons( array &$reasons, string $type, array $labels, float $weight ): float {
		$total = 0.0;
		foreach ( array_values( array_unique( array_filter( $labels ) ) ) as $label ) {
			$reasons[] = array(
				'type'   => $type,
				'label'  => (string) $label,
				'weight' => $weight,
			);
			$total += $weight;
		}
		return $total;
	}

	private static function selection_bucket( array $shared_terms, array $shared_fields, array $shared_tones, array $shared_context, float $profile_diversity, float $vector_similarity ): string {
		if ( $shared_terms ) {
			return 'literal_affinity';
		}
		if ( $shared_fields && $shared_tones ) {
			return 'poetic_tension_or_affinity';
		}
		if ( $vector_similarity >= 0.2 ) {
			return 'local_semantic_vector';
		}
		if ( $shared_context ) {
			return 'continuity_context';
		}
		if ( $profile_diversity > 0 ) {
			return 'profile_diversity';
		}
		return 'semantic_overlap';
	}

	private static function profile_tags( int $participant_id ): array {
		$profile = Diastoles_DB::get_profile( $participant_id );
		if ( ! $profile || ! (int) $profile->use_for_matching ) {
			return array();
		}
		$data = Diastoles_DB::profile_payload( $profile );
		$tags = array();
		foreach ( array( 'gender', 'age_band', 'environment', 'country', 'continent', 'multilingual_status' ) as $key ) {
			if ( $data[ $key ] ) {
				$tags[] = $key . ':' . strtolower( (string) $data[ $key ] );
			}
		}
		foreach ( array( 'native_languages', 'other_languages', 'work_areas', 'hobbies', 'rooted_places' ) as $key ) {
			foreach ( $data[ $key ] as $value ) {
				$tags[] = $key . ':' . strtolower( (string) $value );
			}
		}
		return array_values( array_unique( $tags ) );
	}

	private static function profile_diversity( array $a, array $b ): float {
		if ( empty( $a ) || empty( $b ) ) {
			return 0.0;
		}
		$dimensions = array( 'country:', 'continent:', 'native_languages:', 'environment:', 'age_band:' );
		$score      = 0.0;
		foreach ( $dimensions as $dimension ) {
			$a_values = array_values( array_filter( $a, static fn( string $tag ): bool => str_starts_with( $tag, $dimension ) ) );
			$b_values = array_values( array_filter( $b, static fn( string $tag ): bool => str_starts_with( $tag, $dimension ) ) );
			if ( $a_values && $b_values && ! array_intersect( $a_values, $b_values ) ) {
				$score += 0.25;
			}
		}
		return min( 1.0, $score );
	}

	private static function concept_terms( object $response ): array {
		$data = json_decode( (string) $response->analysis_json, true ) ?: array();
		$tags = array();
		foreach ( Diastoles_Anthropic::smell_concepts( $data ) as $concept ) {
			$tags[] = mb_strtolower( $concept['phrase'] );
			$tags   = array_merge( $tags, array_map( 'mb_strtolower', $concept['anchors'] ) );
		}
		return array_values( array_unique( $tags ) );
	}

	private static function semantic_fields( object $response ): array {
		$data   = json_decode( (string) $response->analysis_json, true ) ?: array();
		$fields = array();
		foreach ( Diastoles_Anthropic::smell_concepts( $data ) as $concept ) {
			$fields = array_merge( $fields, (array) ( $concept['semantic_fields'] ?? array() ) );
		}
		$fields = array_values( array_unique( array_map( 'mb_strtolower', $fields ) ) );
		return array_values( array_diff( $fields, array( 'abstract', 'other' ) ) );
	}

	private static function emotional_tones( object $response ): array {
		$data  = json_decode( (string) $response->analysis_json, true ) ?: array();
		$tones = array_map( static fn( mixed $tone ): string => mb_strtolower( trim( (string) $tone ) ), (array) ( $data['emotional_tones'] ?? array() ) );
		return array_values( array_unique( array_filter( $tones ) ) );
	}

	private static function local_semantic_vector( object $response ): array {
		$vector = array();
		foreach ( self::concept_terms( $response ) as $term ) {
			$vector[ 'nuance:' . $term ] = ( $vector[ 'nuance:' . $term ] ?? 0 ) + 1.0;
		}
		foreach ( self::semantic_fields( $response ) as $field ) {
			$vector[ 'field:' . $field ] = ( $vector[ 'field:' . $field ] ?? 0 ) + 0.65;
		}
		foreach ( self::emotional_tones( $response ) as $tone ) {
			$vector[ 'tone:' . $tone ] = ( $vector[ 'tone:' . $tone ] ?? 0 ) + 0.55;
		}
		foreach ( Diastoles_Anthropic::matching_context_terms( $response ) as $term ) {
			$vector[ 'context:' . $term ] = ( $vector[ 'context:' . $term ] ?? 0 ) + 0.45;
		}
		return $vector;
	}

	private static function cosine_similarity( array $a, array $b ): float {
		if ( empty( $a ) || empty( $b ) ) {
			return 0.0;
		}
		$dot = 0.0;
		foreach ( $a as $key => $value ) {
			if ( isset( $b[ $key ] ) ) {
				$dot += (float) $value * (float) $b[ $key ];
			}
		}
		if ( $dot <= 0 ) {
			return 0.0;
		}
		$magnitude_a = sqrt( array_sum( array_map( static fn( mixed $value ): float => (float) $value * (float) $value, $a ) ) );
		$magnitude_b = sqrt( array_sum( array_map( static fn( mixed $value ): float => (float) $value * (float) $value, $b ) ) );
		if ( $magnitude_a <= 0 || $magnitude_b <= 0 ) {
			return 0.0;
		}
		return min( 1.0, max( 0.0, $dot / ( $magnitude_a * $magnitude_b ) ) );
	}

	private static function question_theme( object $response ): string {
		global $wpdb;
		$theme = mb_strtolower(
			trim(
				(string) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT theme FROM ' . Diastoles_DB::table( 'questions' ) . ' WHERE id = %d',
					(int) $response->question_id
				)
				)
			)
		);
		return preg_replace( '/\s+/u', ' ', $theme ) ?: $theme;
	}

	private static function resolve_concept_pair( array $target_concepts, array $candidate_concepts, int $suggested_target, int $suggested_candidate ): array {
		$best       = array( $suggested_target, $suggested_candidate );
		$best_score = 0;
		foreach ( $target_concepts as $target_index => $target_concept ) {
			$target_phrase  = mb_strtolower( (string) $target_concept['phrase'] );
			$target_anchors = array_map( 'mb_strtolower', (array) $target_concept['anchors'] );
			foreach ( $candidate_concepts as $candidate_index => $candidate_concept ) {
				$candidate_phrase  = mb_strtolower( (string) $candidate_concept['phrase'] );
				$candidate_anchors = array_map( 'mb_strtolower', (array) $candidate_concept['anchors'] );
				$pair_score = 0;
				if ( $target_phrase === $candidate_phrase ) {
					$pair_score += 8;
				}
				$pair_score += count( array_intersect( $target_anchors, $candidate_anchors ) ) * 5;
				if ( in_array( $target_phrase, $candidate_anchors, true ) || in_array( $candidate_phrase, $target_anchors, true ) ) {
					$pair_score += 4;
				}
				if ( $pair_score > $best_score ) {
					$best       = array( (int) $target_index, (int) $candidate_index );
					$best_score = $pair_score;
				}
			}
		}
		return $best;
	}

	private static function valid_relation_type( string $type ): string {
		$type = strtolower( $type );
		return in_array( $type, array( 'affinity', 'tension', 'complementarity', 'continuity' ), true )
			? $type
			: 'affinity';
	}
}
