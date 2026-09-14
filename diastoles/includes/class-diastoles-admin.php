<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_Admin {
	private const RECALCULATION_OPTION = 'diastoles_recalculation_job';
	private const RECALCULATION_LOCK = 'diastoles_recalculation_lock';

	public static function register_menu(): void {
		add_menu_page(
			'Diástoles',
			'Diástoles',
			'manage_options',
			'diastoles',
			array( self::class, 'render_dashboard' ),
			'dashicons-networking',
			58
		);
		add_submenu_page(
			'diastoles',
			'Connections',
			'Connections',
			'manage_options',
			'diastoles-connections',
			array( self::class, 'render_connections' )
		);
		add_submenu_page(
			'diastoles',
			'Questions',
			'Questions',
			'manage_options',
			'diastoles-questions',
			array( self::class, 'render_questions' )
		);
		add_submenu_page(
			'diastoles',
			'Interface texts',
			'Interface texts',
			'manage_options',
			'diastoles-texts',
			array( self::class, 'render_texts' )
		);
		add_submenu_page(
			'diastoles',
			'Participants',
			'Participants',
			'manage_options',
			'diastoles-participants',
			array( self::class, 'render_participants' )
		);
		add_submenu_page(
			'diastoles',
			'Responses',
			'Responses',
			'manage_options',
			'diastoles-responses',
			array( self::class, 'render_responses' )
		);
		add_submenu_page(
			'diastoles',
			'Dynamic Translations',
			'Dynamic Translations',
			'manage_options',
			'diastoles-dynamic-translations',
			array( self::class, 'render_dynamic_translations' )
		);
		add_submenu_page(
			'diastoles',
			'Data & Export',
			'Data & Export',
			'manage_options',
			'diastoles-export',
			array( self::class, 'render_export' )
		);
		add_submenu_page(
			'diastoles',
			'Activity Log',
			'Activity Log',
			'manage_options',
			'diastoles-events',
			array( self::class, 'render_events' )
		);
		add_submenu_page(
			'diastoles',
			'Recalculate',
			'Recalculate',
			'manage_options',
			'diastoles-recalculate',
			array( self::class, 'render_recalculation' )
		);
		add_submenu_page(
			'diastoles',
			'Matching settings',
			'Matching settings',
			'manage_options',
			'diastoles-matching',
			array( self::class, 'render_matching_settings' )
		);
		add_submenu_page(
			'diastoles',
			'AI settings',
			'AI settings',
			'manage_options',
			'diastoles-ai',
			array( self::class, 'render_settings' )
		);
	}

	public static function register_settings(): void {
		register_setting(
			'diastoles_ai',
			'diastoles_ai_mode',
			array(
				'type'              => 'string',
				'default'           => 'mock',
				'sanitize_callback' => static fn( string $value ): string => 'live' === $value ? 'live' : 'mock',
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_anthropic_model',
			array(
				'type'              => 'string',
				'default'           => 'claude-sonnet-5',
				'sanitize_callback' => array( 'Diastoles_Anthropic', 'sanitize_analysis_model' ),
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_processing_model',
			array(
				'type'              => 'string',
				'default'           => 'claude-haiku-4-5-20251001',
				'sanitize_callback' => array( 'Diastoles_Anthropic', 'sanitize_processing_model' ),
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_connection_model',
			array(
				'type'              => 'string',
				'default'           => 'claude-sonnet-5',
				'sanitize_callback' => array( 'Diastoles_Anthropic', 'sanitize_connection_model' ),
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_translation_model',
			array(
				'type'              => 'string',
				'default'           => 'claude-haiku-4-5-20251001',
				'sanitize_callback' => array( 'Diastoles_Anthropic', 'sanitize_translation_model' ),
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_analysis_model',
			array(
				'type'              => 'string',
				'default'           => 'claude-sonnet-5',
				'sanitize_callback' => array( 'Diastoles_Anthropic', 'sanitize_analysis_model' ),
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_api_key_input',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( self::class, 'save_key_setting' ),
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_connection_auto_approve_threshold',
			array(
				'type'              => 'number',
				'default'           => 0.5,
				'sanitize_callback' => array( self::class, 'sanitize_connection_threshold' ),
			)
		);
		register_setting(
			'diastoles_ai',
			'diastoles_connection_manual_approval_threshold',
			array(
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => array( self::class, 'sanitize_manual_connection_threshold' ),
			)
		);
		register_setting(
			'diastoles_texts',
			'diastoles_interface_texts',
			array(
				'type'              => 'array',
				'default'           => Diastoles_Texts::defaults(),
				'sanitize_callback' => array( 'Diastoles_Texts', 'sanitize' ),
			)
		);
		self::register_matching_settings();
	}

	private static function register_matching_settings(): void {
		$int_settings = array(
			'diastoles_matching_candidate_history_limit' => array( 200, 20, 1000 ),
			'diastoles_matching_ai_candidate_limit'      => array( 12, 4, 40 ),
			'diastoles_matching_max_connections'         => array( 3, 1, 10 ),
		);
		foreach ( $int_settings as $name => $limits ) {
			register_setting(
				'diastoles_matching',
				$name,
				array(
					'type'              => 'integer',
					'default'           => $limits[0],
					'sanitize_callback' => static fn( mixed $value ): int => Diastoles_Matcher::sanitize_positive_int( $value, $limits[0], $limits[1], $limits[2] ),
				)
			);
		}

		$float_settings = array(
			'diastoles_matching_shared_nuance_weight'         => array( 8, 0, 20 ),
			'diastoles_matching_shared_context_weight'        => array( 5, 0, 20 ),
			'diastoles_matching_shared_semantic_field_weight' => array( 3, 0, 20 ),
			'diastoles_matching_shared_emotional_tone_weight' => array( 3, 0, 20 ),
			'diastoles_matching_profile_match_weight'         => array( 1, 0, 10 ),
			'diastoles_matching_profile_diversity_weight'     => array( 1, 0, 10 ),
			'diastoles_matching_local_vector_weight'          => array( 6, 0, 20 ),
			'diastoles_matching_same_theme_adjustment'        => array( 0.12, -1, 1 ),
			'diastoles_matching_different_theme_adjustment'   => array( -0.07, -1, 1 ),
		);
		foreach ( $float_settings as $name => $limits ) {
			register_setting(
				'diastoles_matching',
				$name,
				array(
					'type'              => 'number',
					'default'           => $limits[0],
					'sanitize_callback' => static fn( mixed $value ): float => Diastoles_Matcher::sanitize_weight( $value, $limits[0], $limits[1], $limits[2] ),
				)
			);
		}

		register_setting(
			'diastoles_matching',
			'diastoles_matching_local_semantic_vector_enabled',
			array(
				'type'              => 'boolean',
				'default'           => 1,
				'sanitize_callback' => static fn( mixed $value ): int => $value ? 1 : 0,
			)
		);
	}

	public static function save_key_setting( string $value ): string {
		if ( '' !== trim( $value ) ) {
			Diastoles_Anthropic::save_api_key( $value );
		}
		return '';
	}

	public static function sanitize_connection_threshold( mixed $value ): float {
		return min( 1, max( 0, (float) $value ) );
	}

	public static function sanitize_manual_connection_threshold( mixed $value ): float {
		$threshold = self::sanitize_connection_threshold( $value );
		$automatic = self::sanitize_connection_threshold(
			wp_unslash( $_POST['diastoles_connection_auto_approve_threshold'] ?? Diastoles_Matcher::auto_approve_threshold() )
		);
		return min( $automatic, $threshold );
	}

	public static function render_dashboard(): void {
		self::require_admin();
		global $wpdb;

		$counts = array(
			'participants' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Diastoles_DB::table( 'participants' ) ),
			'responses'    => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Diastoles_DB::table( 'responses' ) . ' WHERE withdrawn = 0' ),
			'pending'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . Diastoles_DB::table( 'connections' ) . " WHERE status = 'pending'" ),
			'approved'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . Diastoles_DB::table( 'connections' ) . " WHERE status = 'approved'" ),
		);
		?>
		<div class="wrap">
			<h1>Diástoles</h1>
			<p>Anonymous voices, connected through scent.</p>
			<table class="widefat striped" style="max-width: 720px">
				<tbody>
					<tr><th>Participants</th><td><?php echo esc_html( $counts['participants'] ); ?></td></tr>
					<tr><th>Responses</th><td><?php echo esc_html( $counts['responses'] ); ?></td></tr>
					<tr><th>Connections awaiting review</th><td><?php echo esc_html( $counts['pending'] ); ?></td></tr>
					<tr><th>Approved connections</th><td><?php echo esc_html( $counts['approved'] ); ?></td></tr>
					<tr><th>AI mode</th><td><?php echo esc_html( Diastoles_Anthropic::mode() ); ?></td></tr>
				</tbody>
			</table>
			<p>
				Use <code>[diastoles_experience]</code> on a page, then open that page in a private window
				to test another anonymous participant.
			</p>
		</div>
		<?php
	}

	public static function render_participants(): void {
		self::require_admin();
		$participant_id = absint( $_GET['participant_id'] ?? 0 );
		if ( $participant_id ) {
			self::render_participant_detail( $participant_id );
			return;
		}

		global $wpdb;
		$participants = Diastoles_DB::table( 'participants' );
		$profiles     = Diastoles_DB::table( 'participant_profiles' );
		$responses    = Diastoles_DB::table( 'responses' );

		$page          = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page      = 50;
		$offset        = ( $page - 1 ) * $per_page;
		$search        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$min_responses = '' !== (string) ( $_GET['min_responses'] ?? '' ) ? absint( $_GET['min_responses'] ) : null;
		$max_responses = '' !== (string) ( $_GET['max_responses'] ?? '' ) ? absint( $_GET['max_responses'] ) : null;
		$date_from     = sanitize_text_field( wp_unslash( $_GET['response_from'] ?? '' ) );
		$date_to       = sanitize_text_field( wp_unslash( $_GET['response_to'] ?? '' ) );
		$orderby       = sanitize_key( $_GET['orderby'] ?? 'created_at' );
		$order         = 'asc' === strtolower( (string) ( $_GET['order'] ?? '' ) ) ? 'ASC' : 'DESC';
		$where         = array( '1=1' );
		$params        = array();
		$having        = array();
		$having_params = array();

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(p.public_id LIKE %s OR p.pseudonym LIKE %s OR p.publication_email LIKE %s OR pr.gender LIKE %s OR pr.age_band LIKE %s OR pr.environment LIKE %s OR pr.country LIKE %s OR pr.continent LIKE %s OR pr.native_languages_json LIKE %s OR pr.other_languages_json LIKE %s OR pr.multilingual_status LIKE %s OR pr.work_areas_json LIKE %s OR pr.hobbies_json LIKE %s OR pr.rooted_places_json LIKE %s)';
			$params = array_merge( $params, array_fill( 0, 14, $like ) );
		}
		if ( null !== $min_responses ) {
			$having[] = 'response_count >= %d';
			$having_params[] = $min_responses;
		}
		if ( null !== $max_responses ) {
			$having[] = 'response_count <= %d';
			$having_params[] = $max_responses;
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$having[] = 'last_response_at >= %s';
			$having_params[] = $date_from . ' 00:00:00';
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$having[] = 'last_response_at <= %s';
			$having_params[] = $date_to . ' 23:59:59';
		}

		$where_sql  = implode( ' AND ', $where );
		$having_sql = $having ? ' HAVING ' . implode( ' AND ', $having ) : '';
		$base_sql   = "FROM $participants p
			LEFT JOIN $profiles pr ON pr.participant_id = p.id
			LEFT JOIN $responses r ON r.participant_id = p.id AND r.withdrawn = 0
			WHERE $where_sql
			GROUP BY p.id
			$having_sql";
		$prepared_base = $params || $having_params ? $wpdb->prepare( $base_sql, ...array_merge( $params, $having_params ) ) : $base_sql;
		$total         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM (SELECT p.id, COUNT(r.id) AS response_count, MAX(r.created_at) AS last_response_at $prepared_base) participant_matches" );
		$order_map     = array(
			'pseudonym'       => 'p.pseudonym',
			'responses'       => 'response_count',
			'last_response'   => 'last_response_at',
			'created_at'      => 'p.created_at',
			'last_seen'       => 'p.last_seen_at',
		);
		$order_by      = $order_map[ $orderby ] ?? $order_map['created_at'];
		$rows          = $wpdb->get_results(
			"SELECT p.id, p.public_id, p.pseudonym, p.publication_email, p.consent_fragments,
				p.created_at, p.last_seen_at, pr.gender, pr.age_band, pr.environment, pr.country, pr.continent,
				pr.native_languages_json, pr.other_languages_json, pr.multilingual_status, pr.work_areas_json,
				pr.hobbies_json, pr.rooted_places_json, pr.use_for_matching, COUNT(r.id) AS response_count,
				MIN(r.created_at) AS first_response_at, MAX(r.created_at) AS last_response_at
			$prepared_base
			ORDER BY $order_by $order, p.id DESC
			LIMIT " . (int) $per_page . ' OFFSET ' . (int) $offset
		);
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		$base_url = remove_query_arg( array( 'paged', 'deleted' ) );
		?>
		<div class="wrap">
			<h1>Participants</h1>
			<p>Operational view. Publication emails are shown only for the promised results notification; session and recovery secrets are never shown here.</p>
			<?php if ( isset( $_GET['deleted'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Participant deleted with their responses and related connections.</p></div>
			<?php endif; ?>
			<form method="get" style="margin: 16px 0; display: flex; flex-wrap: wrap; gap: 8px 12px; align-items: end;">
				<input type="hidden" name="page" value="diastoles-participants">
				<label>
					<span class="screen-reader-text">Search participants</span>
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search pseudonym or optional context" style="min-width: 280px;">
				</label>
				<label>Min responses<br><input type="number" min="0" name="min_responses" value="<?php echo esc_attr( null === $min_responses ? '' : $min_responses ); ?>" style="width: 110px;"></label>
				<label>Max responses<br><input type="number" min="0" name="max_responses" value="<?php echo esc_attr( null === $max_responses ? '' : $max_responses ); ?>" style="width: 110px;"></label>
				<label>Responses from<br><input type="date" name="response_from" value="<?php echo esc_attr( $date_from ); ?>"></label>
				<label>Responses to<br><input type="date" name="response_to" value="<?php echo esc_attr( $date_to ); ?>"></label>
				<label>Order by<br>
					<select name="orderby">
						<?php foreach ( array( 'created_at' => 'Created', 'last_seen' => 'Last seen', 'last_response' => 'Last response', 'responses' => 'Responses', 'pseudonym' => 'Pseudonym' ) as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $orderby, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Direction<br>
					<select name="order">
						<option value="desc"<?php selected( $order, 'DESC' ); ?>>Descending</option>
						<option value="asc"<?php selected( $order, 'ASC' ); ?>>Ascending</option>
					</select>
				</label>
				<?php submit_button( 'Filter', 'secondary', 'submit', false ); ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-participants' ) ); ?>">Reset</a>
			</form>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Anonymous ID</th><th>Pseudonym</th><th>Publication email</th><th>Optional context</th><th>Responses</th>
						<th>First response (UTC)</th><th>Last response (UTC)</th><th>Network</th><th>Last seen (UTC)</th><th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="10">No participants match these filters.</td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$context = self::participant_context_summary( $row );
						?>
						<tr>
							<td><code><?php echo esc_html( substr( $row->public_id, 0, 8 ) ); ?></code></td>
							<td><?php echo esc_html( $row->pseudonym ?: '—' ); ?></td>
							<td><?php echo esc_html( $row->publication_email ?: '—' ); ?></td>
							<td><?php echo esc_html( $context ? implode( ' · ', $context ) : 'Not shared' ); ?></td>
							<td><?php echo esc_html( (int) $row->response_count ); ?></td>
							<td><?php echo esc_html( $row->first_response_at ?: '—' ); ?></td>
							<td><?php echo esc_html( $row->last_response_at ?: '—' ); ?></td>
							<td><?php echo $row->consent_fragments ? 'Yes' : 'No'; ?></td>
							<td><?php echo esc_html( $row->last_seen_at ); ?></td>
							<td>
								<p>
									<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-participants&participant_id=' . (int) $row->id ) ); ?>">View participant</a>
								</p>
								<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" onsubmit="return window.confirm('Delete this participant, their responses and all related connections? This cannot be undone from Diástoles.');">
									<input type="hidden" name="action" value="diastoles_delete_participant">
									<input type="hidden" name="participant_id" value="<?php echo esc_attr( (int) $row->id ); ?>">
									<?php wp_nonce_field( 'diastoles_delete_participant_' . (int) $row->id ); ?>
									<?php submit_button( 'Delete', 'delete small', 'submit', false ); ?>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<span class="displaying-num"><?php echo esc_html( $total ); ?> participants</span>
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => esc_url_raw( add_query_arg( 'paged', '%#%', $base_url ) ),
								'format'    => '',
								'current'   => $page,
								'total'     => $total_pages,
								'prev_text' => '‹',
								'next_text' => '›',
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_participant_detail( int $participant_id ): void {
		global $wpdb;
		$participants = Diastoles_DB::table( 'participants' );
		$profiles     = Diastoles_DB::table( 'participant_profiles' );
		$responses    = Diastoles_DB::table( 'responses' );
		$questions    = Diastoles_DB::table( 'questions' );
		$connections  = Diastoles_DB::table( 'connections' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT p.*, pr.gender, pr.age_band, pr.environment, pr.country, pr.continent,
					pr.native_languages_json, pr.other_languages_json, pr.multilingual_status,
					pr.work_areas_json, pr.hobbies_json, pr.rooted_places_json, pr.use_for_matching,
					pr.allow_context_display, pr.updated_at AS profile_updated_at
				FROM $participants p
				LEFT JOIN $profiles pr ON pr.participant_id = p.id
				WHERE p.id = %d",
				$participant_id
			)
		);
		if ( ! $row ) {
			?>
			<div class="wrap">
				<h1>Participant not found</h1>
				<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-participants' ) ); ?>">Back to participants</a></p>
			</div>
			<?php
			return;
		}
		$response_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, q.prompt AS question_prompt, q.short_label AS question_short_label, q.theme AS question_theme,
					(SELECT COUNT(*) FROM $connections c WHERE c.response_a_id = r.id OR c.response_b_id = r.id) AS connection_count
				FROM $responses r
				LEFT JOIN $questions q ON q.id = r.question_id
				WHERE r.participant_id = %d
				ORDER BY r.created_at DESC",
				$participant_id
			)
		);
		$context = self::participant_context_summary( $row );
		?>
		<div class="wrap">
			<h1>Participant <code><?php echo esc_html( substr( $row->public_id, 0, 8 ) ); ?></code></h1>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-participants' ) ); ?>">Back to participants</a></p>
			<div class="card" style="max-width: 980px">
				<h2>Participant data</h2>
				<table class="widefat striped">
					<tbody>
						<tr><th>Anonymous ID</th><td><code><?php echo esc_html( $row->public_id ); ?></code></td></tr>
						<tr><th>Pseudonym</th><td><?php echo esc_html( $row->pseudonym ?: '—' ); ?></td></tr>
						<tr><th>Publication email</th><td><?php echo esc_html( $row->publication_email ?: '—' ); ?></td></tr>
						<tr><th>Network consent</th><td><?php echo $row->consent_fragments ? 'Yes' : 'No'; ?></td></tr>
						<tr><th>Created / last seen</th><td><?php echo esc_html( $row->created_at . ' / ' . $row->last_seen_at ); ?></td></tr>
						<tr><th>Optional context</th><td><?php echo esc_html( $context ? implode( ' · ', $context ) : 'Not shared' ); ?></td></tr>
						<tr><th>Matching context enabled</th><td><?php echo ! empty( $row->use_for_matching ) ? 'Yes' : 'No'; ?></td></tr>
						<tr><th>Profile updated</th><td><?php echo esc_html( $row->profile_updated_at ?: '—' ); ?></td></tr>
					</tbody>
				</table>
			</div>
			<h2>Responses</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Date (UTC)</th>
						<th>Stimulus</th>
						<th>Original text</th>
						<th>English translation</th>
						<th>Language</th>
						<th>Status</th>
						<th>Connections</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $response_rows ) : ?>
						<tr><td colspan="8">This participant has no responses yet.</td></tr>
					<?php endif; ?>
					<?php foreach ( $response_rows as $response ) : ?>
						<tr>
							<td><?php echo esc_html( $response->created_at ); ?></td>
							<td>
								<strong><?php echo esc_html( $response->question_theme ?: '—' ); ?></strong><br>
								<?php echo esc_html( $response->question_short_label ?: $response->question_prompt ?: '—' ); ?>
							</td>
							<td><?php echo esc_html( mb_substr( (string) $response->original_text, 0, 240 ) ); ?></td>
							<td><?php echo esc_html( mb_substr( (string) $response->translation_en, 0, 240 ) ?: '—' ); ?></td>
							<td><?php echo esc_html( $response->source_language ?: '—' ); ?></td>
							<td><?php echo esc_html( $response->withdrawn ? 'withdrawn' : $response->processing_status ); ?></td>
							<td><?php echo esc_html( (int) $response->connection_count ); ?></td>
							<td>
								<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-responses&response_id=' . (int) $response->id ) ); ?>">View response</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin-top: 16px">
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" onsubmit="return window.confirm('Delete this participant, their responses and all related connections? This cannot be undone from Diástoles.');">
					<input type="hidden" name="action" value="diastoles_delete_participant">
					<input type="hidden" name="participant_id" value="<?php echo esc_attr( (int) $row->id ); ?>">
					<?php wp_nonce_field( 'diastoles_delete_participant_' . (int) $row->id ); ?>
					<?php submit_button( 'Delete participant', 'delete', 'submit', false ); ?>
				</form>
			</p>
		</div>
		<?php
	}

	private static function participant_context_summary( object $row ): array {
		$json_list = static fn( string $property ): array => json_decode( (string) ( $row->{$property} ?? '' ), true ) ?: array();
		return array_values(
			array_filter(
				array(
					$row->gender ?? '',
					$row->age_band ?? '',
					$row->environment ?? '',
					$row->country ?? '',
					$row->continent ?? '',
					implode( ', ', $json_list( 'native_languages_json' ) ),
					implode( ', ', $json_list( 'other_languages_json' ) ),
					$row->multilingual_status ?? '',
					implode( ', ', $json_list( 'work_areas_json' ) ),
					implode( ', ', $json_list( 'hobbies_json' ) ),
					implode( ', ', $json_list( 'rooted_places_json' ) ),
				),
				static fn( mixed $value ): bool => '' !== trim( (string) $value )
			)
		);
	}

	public static function delete_participant(): void {
		self::require_admin();
		$participant_id = absint( $_POST['participant_id'] ?? 0 );
		check_admin_referer( 'diastoles_delete_participant_' . $participant_id );
		if ( ! $participant_id ) {
			wp_die( 'Missing participant.' );
		}

		global $wpdb;
		$responses = Diastoles_DB::table( 'responses' );
		$response_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, original_text, explanation_text FROM $responses WHERE participant_id = %d",
				$participant_id
			)
		);
		$response_ids = array_map( static fn( object $row ): int => (int) $row->id, $response_rows );
		if ( $response_ids ) {
			$placeholders = implode( ', ', array_fill( 0, count( $response_ids ), '%d' ) );
			$connections  = Diastoles_DB::table( 'connections' );
			$events       = Diastoles_DB::table( 'processing_events' );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $connections WHERE response_a_id IN ($placeholders) OR response_b_id IN ($placeholders) OR participant_a_id = %d OR participant_b_id = %d", ...array_merge( $response_ids, $response_ids, array( $participant_id, $participant_id ) ) ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $events WHERE response_id IN ($placeholders)", ...$response_ids ) );
		} else {
			$connections = Diastoles_DB::table( 'connections' );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $connections WHERE participant_a_id = %d OR participant_b_id = %d", $participant_id, $participant_id ) );
		}

		$text_hashes = array();
		foreach ( $response_rows as $row ) {
			foreach ( array( $row->original_text, $row->explanation_text ) as $text ) {
				$text = trim( (string) $text );
				if ( '' !== $text ) {
					$text_hashes[] = hash( 'sha256', $text );
				}
			}
		}
		$text_hashes = array_values( array_unique( $text_hashes ) );
		if ( $text_hashes ) {
			$translations = Diastoles_DB::table( 'dynamic_translations' );
			$placeholders = implode( ', ', array_fill( 0, count( $text_hashes ), '%s' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $translations WHERE source_hash IN ($placeholders)", ...$text_hashes ) );
		}

		$wpdb->delete( $responses, array( 'participant_id' => $participant_id ), array( '%d' ) );
		$wpdb->delete( Diastoles_DB::table( 'participant_profiles' ), array( 'participant_id' => $participant_id ), array( '%d' ) );
		$wpdb->delete( Diastoles_DB::table( 'question_views' ), array( 'participant_id' => $participant_id ), array( '%d' ) );
		$wpdb->delete( Diastoles_DB::table( 'events' ), array( 'participant_id' => $participant_id ), array( '%d' ) );
		$wpdb->delete( Diastoles_DB::table( 'participants' ), array( 'id' => $participant_id ), array( '%d' ) );

		wp_safe_redirect( add_query_arg( 'deleted', '1', admin_url( 'admin.php?page=diastoles-participants' ) ) );
		exit;
	}

	public static function render_responses(): void {
		self::require_admin();
		$response_id = absint( $_GET['response_id'] ?? 0 );
		if ( $response_id ) {
			self::render_response_detail( $response_id );
			return;
		}

		global $wpdb;
		$responses = Diastoles_DB::table( 'responses' );
		$questions = Diastoles_DB::table( 'questions' );
		$rows      = $wpdb->get_results(
			"SELECT r.id, r.public_id, r.original_text, r.explanation_text, r.source_language, r.translation_en, r.processing_status,
				r.allow_network, r.withdrawn, r.created_at, q.short_label, q.prompt
			FROM $responses r
			INNER JOIN $questions q ON q.id = r.question_id
			ORDER BY r.created_at DESC
			LIMIT 250"
		);
		$latest_errors = self::latest_response_errors( wp_list_pluck( $rows, 'id' ) );
		?>
		<div class="wrap">
			<h1>Responses</h1>
			<p>Review participant responses, translation coverage and responses held for moderation. “Delete” withdraws the response from public views without removing database history.</p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Response</th><th>Question</th><th>Status</th><th>Network</th><th>Translations</th><th>Created</th><th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td>
								<code><?php echo esc_html( substr( $row->public_id, 0, 8 ) ); ?></code><br>
								<?php echo esc_html( wp_trim_words( $row->original_text, 16 ) ); ?>
								<?php if ( '' !== trim( (string) $row->explanation_text ) ) : ?>
									<br><small><em>Explanation:</em> <?php echo esc_html( wp_trim_words( $row->explanation_text, 18 ) ); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $row->short_label ?: wp_trim_words( $row->prompt, 12 ) ); ?></td>
							<td>
								<?php echo esc_html( $row->processing_status ); ?><?php echo $row->withdrawn ? ' · withdrawn' : ''; ?>
								<?php if ( 'failed' === $row->processing_status && ! empty( $latest_errors[ (int) $row->id ] ) ) : ?>
									<br><small><strong>Latest error:</strong> <?php echo esc_html( $latest_errors[ (int) $row->id ]['message'] ); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo $row->allow_network && ! $row->withdrawn ? 'Visible' : 'Hidden'; ?></td>
							<td><?php echo esc_html( self::translation_coverage_label( $row ) ); ?></td>
							<td><?php echo esc_html( $row->created_at ); ?></td>
							<td><a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-responses&response_id=' . (int) $row->id ) ); ?>">Open</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function render_response_detail( int $response_id ): void {
		global $wpdb;
		$responses = Diastoles_DB::table( 'responses' );
		$questions = Diastoles_DB::table( 'questions' );
		$row       = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT r.*, q.prompt, q.short_label
				FROM $responses r INNER JOIN $questions q ON q.id = r.question_id
				WHERE r.id = %d",
				$response_id
			)
		);
		if ( ! $row ) {
			wp_die( esc_html__( 'Response not found.', 'diastoles' ) );
		}
		$translations = self::response_translation_rows( $row );
		$explanation_translations = self::explanation_translation_rows( $row );
		$latest_error = self::latest_response_errors( array( $response_id ) )[ $response_id ] ?? null;
		$processing_events = self::response_processing_events( $response_id );
		?>
		<div class="wrap">
			<h1>Response</h1>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-responses' ) ); ?>">← Back to responses</a></p>
			<div class="card" style="max-width: 960px">
				<p><strong>ID:</strong> <code><?php echo esc_html( $row->public_id ); ?></code></p>
				<p><strong>Status:</strong> <?php echo esc_html( $row->processing_status ); ?> · <strong>Network:</strong> <?php echo $row->allow_network && ! $row->withdrawn ? 'visible' : 'hidden'; ?></p>
				<?php if ( 'failed' === $row->processing_status ) : ?>
					<?php if ( $latest_error ) : ?>
						<p><strong>Latest processing error:</strong> <?php echo esc_html( $latest_error['message'] ); ?></p>
						<p class="description">Recorded at <?php echo esc_html( $latest_error['created_at'] ); ?> UTC.</p>
					<?php else : ?>
						<p><strong>Latest processing error:</strong> No processing error was recorded for this response.</p>
						<p class="description">This usually means the failure happened before detailed diagnostics were available, or the request stopped before WordPress could record the error. Click “Retry processing” to capture the exact error if it fails again.</p>
					<?php endif; ?>
				<?php endif; ?>
				<p><strong>Question:</strong> <?php echo esc_html( $row->short_label ?: $row->prompt ); ?></p>
				<blockquote><?php echo esc_html( $row->original_text ); ?></blockquote>
				<?php if ( '' !== trim( (string) $row->explanation_text ) ) : ?>
					<p><strong>Explanation:</strong> <?php echo esc_html( $row->explanation_text ); ?></p>
				<?php endif; ?>
				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<?php if ( 'needs_review' === $row->processing_status ) : ?>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="diastoles_moderate_response">
							<input type="hidden" name="response_id" value="<?php echo esc_attr( $row->id ); ?>">
							<input type="hidden" name="decision" value="approve">
							<?php wp_nonce_field( 'diastoles_moderate_response_' . (int) $row->id ); ?>
							<?php submit_button( 'Approve and process', 'primary', 'submit', false ); ?>
						</form>
					<?php endif; ?>
					<?php if ( in_array( $row->processing_status, array( 'failed', 'pending', 'processing' ), true ) && ! (int) $row->withdrawn ) : ?>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="diastoles_retry_response_processing">
							<input type="hidden" name="response_id" value="<?php echo esc_attr( $row->id ); ?>">
							<?php wp_nonce_field( 'diastoles_retry_response_processing_' . (int) $row->id ); ?>
							<?php submit_button( 'Retry processing', 'primary', 'submit', false ); ?>
						</form>
					<?php endif; ?>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="diastoles_moderate_response">
						<input type="hidden" name="response_id" value="<?php echo esc_attr( $row->id ); ?>">
						<input type="hidden" name="decision" value="withdraw">
						<?php wp_nonce_field( 'diastoles_moderate_response_' . (int) $row->id ); ?>
						<?php submit_button( 'Withdraw response', 'delete', 'submit', false ); ?>
					</form>
					<?php if ( 'complete' === $row->processing_status ) : ?>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="diastoles_retry_response_translations">
							<input type="hidden" name="response_id" value="<?php echo esc_attr( $row->id ); ?>">
							<?php wp_nonce_field( 'diastoles_retry_response_translations_' . (int) $row->id ); ?>
							<?php submit_button( 'Retry translations', 'secondary', 'submit', false ); ?>
						</form>
					<?php endif; ?>
				</div>
			</div>
			<h2>Processing history</h2>
			<table class="widefat striped" style="max-width: 960px">
				<thead><tr><th>Timestamp</th><th>Type</th><th>Message</th></tr></thead>
				<tbody>
					<?php if ( ! $processing_events ) : ?>
						<tr><td colspan="3">No processing events recorded yet.</td></tr>
					<?php endif; ?>
					<?php foreach ( $processing_events as $event ) : ?>
						<tr>
							<td><?php echo esc_html( $event->created_at ); ?></td>
							<td><?php echo esc_html( $event->event_type ); ?></td>
							<td><?php echo esc_html( $event->message ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<h2>Response translations</h2>
			<table class="widefat striped">
				<thead><tr><th>Language</th><th>Status</th><th>Translation</th><th>Save</th></tr></thead>
				<tbody>
					<?php foreach ( $translations as $locale => $translation ) : ?>
						<tr>
							<td><?php echo esc_html( Diastoles_I18n::languages()[ $locale ]['name'] ?? strtoupper( $locale ) ); ?></td>
							<td><?php echo esc_html( $translation['status'] ); ?></td>
							<td>
								<form id="translation-<?php echo esc_attr( $locale ); ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
									<input type="hidden" name="action" value="diastoles_save_response_translation">
									<input type="hidden" name="response_id" value="<?php echo esc_attr( $row->id ); ?>">
									<input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
									<input type="hidden" name="source" value="response">
									<?php wp_nonce_field( 'diastoles_save_response_translation_' . (int) $row->id . '_' . $locale . '_response' ); ?>
									<textarea class="large-text" rows="3" name="translated_text"><?php echo esc_textarea( $translation['text'] ); ?></textarea>
								</form>
							</td>
							<td><button form="translation-<?php echo esc_attr( $locale ); ?>" class="button button-secondary">Save</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( '' !== trim( (string) $row->explanation_text ) ) : ?>
				<h2>Explanation translations</h2>
				<p class="description">These translations are stored separately from the main response translation and are used when the optional explanation is shown publicly.</p>
				<table class="widefat striped">
					<thead><tr><th>Language</th><th>Status</th><th>Explanation translation</th><th>Save</th></tr></thead>
					<tbody>
						<?php foreach ( $explanation_translations as $locale => $translation ) : ?>
							<tr>
								<td><?php echo esc_html( Diastoles_I18n::languages()[ $locale ]['name'] ?? strtoupper( $locale ) ); ?></td>
								<td><?php echo esc_html( $translation['status'] ); ?></td>
								<td>
									<form id="explanation-translation-<?php echo esc_attr( $locale ); ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
										<input type="hidden" name="action" value="diastoles_save_response_translation">
										<input type="hidden" name="response_id" value="<?php echo esc_attr( $row->id ); ?>">
										<input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
										<input type="hidden" name="source" value="explanation">
										<?php wp_nonce_field( 'diastoles_save_response_translation_' . (int) $row->id . '_' . $locale . '_explanation' ); ?>
										<textarea class="large-text" rows="3" name="translated_text"><?php echo esc_textarea( $translation['text'] ); ?></textarea>
									</form>
								</td>
								<td><button form="explanation-translation-<?php echo esc_attr( $locale ); ?>" class="button button-secondary">Save</button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<script>
			document.querySelectorAll('form[action*="admin-post.php"]').forEach((form) => {
				form.addEventListener('submit', () => {
					const button = form.querySelector('input[type="submit"], button[type="submit"], button:not([type])');
					if (!button) return;
					if ('value' in button) button.value = 'Voy a ello…';
					else button.textContent = 'Voy a ello…';
					button.disabled = true;
				});
			});
		</script>
		<?php
	}

	private static function translation_coverage_label( object $response ): string {
		$rows = self::response_translation_rows( $response );
		$complete = count( array_filter( $rows, static fn( array $row ): bool => 'complete' === $row['status'] || 'original' === $row['status'] ) );
		return $complete . '/' . count( $rows ) . ' ready';
	}

	private static function response_translation_rows( object $response ): array {
		global $wpdb;
		$source = Diastoles_I18n::normalize( $response->source_language ?? '' );
		$hash   = hash( 'sha256', (string) $response->original_text );
		$table  = Diastoles_DB::table( 'dynamic_translations' );
		$dynamic = $wpdb->get_results(
			$wpdb->prepare( "SELECT language_code, translated_text, status FROM $table WHERE source_hash = %s", $hash ),
			OBJECT_K
		);
		$rows = array();
		foreach ( Diastoles_I18n::active_languages() as $locale => $language ) {
			if ( $source && $source === $locale ) {
				$rows[ $locale ] = array( 'status' => 'original', 'text' => (string) $response->original_text );
			} elseif ( 'en' === $locale ) {
				$rows[ $locale ] = array(
					'status' => '' !== trim( (string) $response->translation_en ) ? 'complete' : 'pending',
					'text'   => (string) $response->translation_en,
				);
			} else {
				$row = $dynamic[ $locale ] ?? null;
				$rows[ $locale ] = array(
					'status' => $row ? (string) $row->status : 'missing',
					'text'   => $row ? (string) $row->translated_text : '',
				);
			}
		}
		return $rows;
	}

	private static function explanation_translation_rows( object $response ): array {
		$explanation = trim( (string) ( $response->explanation_text ?? '' ) );
		if ( '' === $explanation ) {
			return array();
		}
		global $wpdb;
		$source = Diastoles_I18n::normalize( $response->source_language ?? '' );
		$hash   = hash( 'sha256', $explanation );
		$table  = Diastoles_DB::table( 'dynamic_translations' );
		$dynamic = $wpdb->get_results(
			$wpdb->prepare( "SELECT language_code, translated_text, status FROM $table WHERE source_hash = %s", $hash ),
			OBJECT_K
		);
		$rows = array();
		foreach ( Diastoles_I18n::active_languages() as $locale => $language ) {
			if ( $source && $source === $locale ) {
				$rows[ $locale ] = array( 'status' => 'original', 'text' => $explanation );
			} else {
				$row = $dynamic[ $locale ] ?? null;
				$rows[ $locale ] = array(
					'status' => $row ? (string) $row->status : 'missing',
					'text'   => $row ? (string) $row->translated_text : '',
				);
			}
		}
		return $rows;
	}

	private static function latest_response_errors( array $response_ids ): array {
		$response_ids = array_values( array_unique( array_filter( array_map( 'absint', $response_ids ) ) ) );
		if ( ! $response_ids ) {
			return array();
		}
		global $wpdb;
		$events = Diastoles_DB::table( 'processing_events' );
		$placeholders = implode( ', ', array_fill( 0, count( $response_ids ), '%d' ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT response_id, message, created_at
				FROM $events
				WHERE event_type = 'error' AND response_id IN ($placeholders)
				ORDER BY id DESC",
				...$response_ids
			)
		);
		$latest = array();
		foreach ( $rows as $row ) {
			$id = (int) $row->response_id;
			if ( isset( $latest[ $id ] ) ) {
				continue;
			}
			$latest[ $id ] = array(
				'message'    => (string) $row->message,
				'created_at' => (string) $row->created_at,
			);
		}
		return $latest;
	}

	private static function response_processing_events( int $response_id ): array {
		global $wpdb;
		$events = Diastoles_DB::table( 'processing_events' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, message, created_at
				FROM $events
				WHERE response_id = %d
				ORDER BY id DESC
				LIMIT 50",
				$response_id
			)
		);
	}

	public static function render_dynamic_translations(): void {
		self::require_admin();
		global $wpdb;
		$table = Diastoles_DB::table( 'dynamic_translations' );
		$language = Diastoles_I18n::normalize( $_GET['language'] ?? '' );
		$status = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) );
		$kind = sanitize_key( wp_unslash( $_GET['kind'] ?? '' ) );
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page = 50;
		$where = array( '1=1' );
		$params = array();
		if ( $language ) {
			$where[] = 'language_code = %s';
			$params[] = $language;
		}
		if ( in_array( $status, array( 'pending', 'complete', 'failed', 'missing' ), true ) ) {
			$where[] = 'status = %s';
			$params[] = $status;
		}
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(source_text LIKE %s OR translated_text LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}
		$where_sql = implode( ' AND ', $where );
		$sql_base = "FROM $table WHERE $where_sql";
		$prepared_base = $params ? $wpdb->prepare( $sql_base, ...$params ) : $sql_base;
		$raw_rows = $wpdb->get_results(
			"SELECT id, source_hash, language_code, source_text, translated_text, status, updated_at
			$prepared_base
			ORDER BY updated_at DESC, id DESC
			LIMIT 500"
		);
		$kinds = self::dynamic_translation_kind_index();
		$rows = array_values(
			array_filter(
				array_map(
					static function ( object $row ) use ( $kinds ): object {
						$row->kind = $kinds[ $row->source_hash ] ?? self::infer_dynamic_translation_kind( (string) $row->source_text );
						return $row;
					},
					$raw_rows
				),
				static fn( object $row ): bool => '' === $kind || $row->kind === $kind
			)
		);
		$total = count( $rows );
		$rows = array_slice( $rows, ( $page - 1 ) * $per_page, $per_page );
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		$base_url = remove_query_arg( array( 'paged', 'saved', 'retried' ) );
		?>
		<div class="wrap">
			<h1>Dynamic Translations</h1>
			<p>Translations for participant fragments, explanations, scent concepts, matices/anchors, semantic fields and relationship evidence. “Kind” is inferred from saved response analysis.</p>
			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Translation saved.</p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['retried'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( absint( $_GET['retried'] ) ); ?> translations queued for retry.</p></div>
			<?php endif; ?>
			<form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin:16px 0">
				<input type="hidden" name="page" value="diastoles-dynamic-translations">
				<label>Search<br><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Source or translation"></label>
				<label>Kind<br>
					<select name="kind">
						<?php foreach ( array( '' => 'All kinds', 'response' => 'Response', 'explanation' => 'Explanation', 'concept' => 'Concept phrase', 'anchor' => 'Matiz / anchor', 'semantic_field' => 'Semantic field', 'evidence' => 'Evidence/context', 'unknown' => 'Unknown' ) as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $kind, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Language<br>
					<select name="language">
						<option value="">All languages</option>
						<?php foreach ( Diastoles_I18n::active_languages() as $locale => $info ) : ?>
							<option value="<?php echo esc_attr( $locale ); ?>"<?php selected( $language, $locale ); ?>><?php echo esc_html( $info['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Status<br>
					<select name="status">
						<?php foreach ( array( '' => 'All statuses', 'pending' => 'Pending', 'complete' => 'Complete', 'failed' => 'Failed' ) as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php submit_button( 'Filter', 'secondary', 'submit', false ); ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-dynamic-translations' ) ); ?>">Reset</a>
			</form>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="margin: 0 0 12px">
				<input type="hidden" name="action" value="diastoles_retry_dynamic_translations">
				<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
				<input type="hidden" name="language" value="<?php echo esc_attr( $language ); ?>">
				<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
				<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
				<?php wp_nonce_field( 'diastoles_retry_dynamic_translations' ); ?>
				<?php submit_button( 'Retry pending/missing shown filter', 'secondary', 'submit', false ); ?>
			</form>
			<table class="widefat striped">
				<thead><tr><th>Kind</th><th>Source</th><th>Language</th><th>Status</th><th>Translation</th><th>Updated</th><th>Save</th></tr></thead>
				<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="7">No dynamic translations match this filter.</td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( self::dynamic_translation_kind_label( $row->kind ) ); ?></td>
							<td><code><?php echo esc_html( mb_substr( (string) $row->source_text, 0, 220 ) ); ?></code></td>
							<td><?php echo esc_html( Diastoles_I18n::languages()[ $row->language_code ]['name'] ?? $row->language_code ); ?></td>
							<td><?php echo esc_html( $row->status ); ?></td>
							<td>
								<form id="dynamic-translation-<?php echo esc_attr( (int) $row->id ); ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
									<input type="hidden" name="action" value="diastoles_save_dynamic_translation">
									<input type="hidden" name="translation_id" value="<?php echo esc_attr( (int) $row->id ); ?>">
									<?php wp_nonce_field( 'diastoles_save_dynamic_translation_' . (int) $row->id ); ?>
									<textarea class="large-text" rows="2" name="translated_text"><?php echo esc_textarea( $row->translated_text ); ?></textarea>
								</form>
							</td>
							<td><?php echo esc_html( $row->updated_at ); ?></td>
							<td><button form="dynamic-translation-<?php echo esc_attr( (int) $row->id ); ?>" class="button button-secondary">Save</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<span class="displaying-num"><?php echo esc_html( $total ); ?> translations</span>
					<?php echo wp_kses_post( paginate_links( array( 'base' => esc_url_raw( add_query_arg( 'paged', '%#%', $base_url ) ), 'format' => '', 'current' => $page, 'total' => $total_pages, 'prev_text' => '‹', 'next_text' => '›' ) ) ); ?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function dynamic_translation_kind_label( string $kind ): string {
		return array(
			'response'       => 'Response',
			'explanation'    => 'Explanation',
			'concept'        => 'Concept phrase',
			'anchor'         => 'Matiz / anchor',
			'semantic_field' => 'Semantic field',
			'evidence'       => 'Evidence/context',
			'unknown'        => 'Unknown',
		)[ $kind ] ?? 'Unknown';
	}

	private static function infer_dynamic_translation_kind( string $source_text ): string {
		$words = preg_split( '/\s+/u', trim( $source_text ) ) ?: array();
		return count( array_filter( $words ) ) <= 3 ? 'unknown' : 'evidence';
	}

	private static function dynamic_translation_kind_index(): array {
		global $wpdb;
		$responses = Diastoles_DB::table( 'responses' );
		$rows = $wpdb->get_results( "SELECT original_text, explanation_text, analysis_json FROM $responses WHERE processing_status = 'complete' ORDER BY id DESC LIMIT 1000" );
		$index = array();
		$add = static function ( string $text, string $kind ) use ( &$index ): void {
			$text = trim( $text );
			if ( '' === $text ) {
				return;
			}
			$hash = hash( 'sha256', $text );
			if ( ! isset( $index[ $hash ] ) || 'unknown' === $index[ $hash ] ) {
				$index[ $hash ] = $kind;
			}
		};
		foreach ( $rows as $row ) {
			$add( (string) $row->original_text, 'response' );
			$add( (string) $row->explanation_text, 'explanation' );
			$analysis = json_decode( (string) $row->analysis_json, true ) ?: array();
			foreach ( Diastoles_Anthropic::smell_concepts( $analysis ) as $concept ) {
				$add( (string) ( $concept['phrase'] ?? '' ), 'concept' );
				foreach ( array_filter( array_map( 'strval', (array) ( $concept['anchors'] ?? array() ) ) ) as $anchor ) {
					$add( $anchor, 'anchor' );
				}
				foreach ( array_filter( array_map( 'strval', (array) ( $concept['semantic_fields'] ?? array() ) ) ) as $field ) {
					$add( $field, 'semantic_field' );
				}
			}
			foreach ( (array) ( $analysis['supporting_evidence'] ?? array() ) as $evidence ) {
				$add( (string) ( $evidence['excerpt'] ?? '' ), 'evidence' );
			}
		}
		return $index;
	}

	public static function render_export(): void {
		self::require_admin();
		?>
		<div class="wrap">
			<h1>Data &amp; Export</h1>
			<p>
				Download a portable research archive containing CSV and JSON files. It includes original participant
				words, translations, labels, profiles and relationships, but excludes session hashes, recovery hashes and publication emails.
			</p>
			<div class="card" style="max-width: 760px">
				<h2>Portable archive</h2>
				<p>The ZIP contains a manifest, one CSV per dataset, the public network and a complete JSON export.</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="diastoles_export">
					<?php wp_nonce_field( 'diastoles_export' ); ?>
					<?php submit_button( 'Download ZIP export', 'primary', 'submit', false ); ?>
				</form>
			</div>
			<div class="card" style="max-width: 760px; margin-top: 16px">
				<h2>Full recovery</h2>
				<p>
					This export is designed for analysis and portability. For disaster recovery, also keep a full
					WordPress database/site backup from your hosting provider.
				</p>
			</div>
		</div>
		<?php
	}

	public static function render_events(): void {
		self::require_admin();
		global $wpdb;
		$events = Diastoles_DB::table( 'events' );
		$participants = Diastoles_DB::table( 'participants' );
		$deleted = isset( $_GET['deleted_events'] ) ? absint( $_GET['deleted_events'] ) : null;
		$rows = $wpdb->get_results(
			"SELECT e.*, p.public_id AS participant_public_id
			FROM $events e
			LEFT JOIN $participants p ON p.id = e.participant_id
			ORDER BY e.created_at DESC
			LIMIT 200"
		);
		?>
		<div class="wrap">
			<h1>Activity Log</h1>
			<p>
				User-facing events from the Diástoles experience. IP addresses and user agents are stored only as hashes.
			</p>
			<?php if ( null !== $deleted ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $deleted ); ?> activity log events deleted.</p></div>
			<?php endif; ?>
			<div class="card" style="max-width: 760px">
				<h2>Download events</h2>
				<p>The CSV includes timestamp, event type, screen, anonymous participant, stimulus/question, interface language and metadata.</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="diastoles_export_events">
					<?php wp_nonce_field( 'diastoles_export_events' ); ?>
					<?php submit_button( 'Download activity CSV', 'primary', 'submit', false ); ?>
				</form>
			</div>
			<div class="card" style="max-width: 760px; margin-top: 16px">
				<h2>Delete events</h2>
				<p>Delete activity logs only. This does not delete participants, responses, questions or connections.</p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" onsubmit="return window.confirm('Delete matching activity log events? This cannot be undone from Diástoles.');">
					<input type="hidden" name="action" value="diastoles_delete_events">
					<?php wp_nonce_field( 'diastoles_delete_events' ); ?>
					<p>
						<label><input type="radio" name="delete_scope" value="period" checked> Delete a period</label>
						<label style="margin-left: 16px"><input type="radio" name="delete_scope" value="all"> Delete all logs</label>
					</p>
					<p style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end;">
						<label>From<br><input type="date" name="from"></label>
						<label>To<br><input type="date" name="to"></label>
						<?php submit_button( 'Delete activity logs', 'delete', 'submit', false ); ?>
					</p>
				</form>
			</div>
			<h2>Recent events</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Timestamp</th>
						<th>Event</th>
						<th>Screen</th>
						<th>Participant</th>
						<th>Stimulus</th>
						<th>Language</th>
						<th>Metadata</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="7">No events have been recorded yet.</td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->created_at ); ?></td>
							<td><?php echo esc_html( $row->event_type ); ?></td>
							<td><?php echo esc_html( $row->screen ); ?></td>
							<td><code><?php echo esc_html( $row->participant_public_id ?: '—' ); ?></code></td>
							<td><?php echo esc_html( $row->question_label ?: ( $row->question_id ? '#' . $row->question_id : '—' ) ); ?></td>
							<td><?php echo esc_html( $row->interface_language ?: '—' ); ?></td>
							<td><code><?php echo esc_html( mb_substr( (string) $row->metadata_json, 0, 220 ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function delete_events(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_delete_events' );
		global $wpdb;
		$events = Diastoles_DB::table( 'events' );
		$scope = sanitize_key( $_POST['delete_scope'] ?? 'period' );
		$deleted = 0;
		if ( 'all' === $scope ) {
			$deleted = (int) $wpdb->query( "DELETE FROM $events" );
		} else {
			$from = sanitize_text_field( wp_unslash( $_POST['from'] ?? '' ) );
			$to   = sanitize_text_field( wp_unslash( $_POST['to'] ?? '' ) );
			$where = array();
			$params = array();
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
				$where[] = 'created_at >= %s';
				$params[] = $from . ' 00:00:00';
			}
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
				$where[] = 'created_at <= %s';
				$params[] = $to . ' 23:59:59';
			}
			if ( $where ) {
				$sql = "DELETE FROM $events WHERE " . implode( ' AND ', $where );
				$deleted = (int) $wpdb->query( $wpdb->prepare( $sql, ...$params ) );
			}
		}
		wp_safe_redirect( add_query_arg( 'deleted_events', $deleted, admin_url( 'admin.php?page=diastoles-events' ) ) );
		exit;
	}

	public static function export_events(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_export_events' );
		$rows = self::event_export_rows();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="diastoles-events-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$handle = fopen( 'php://output', 'w' );
		if ( $handle ) {
			if ( $rows ) {
				fputcsv( $handle, array_keys( $rows[0] ) );
				foreach ( $rows as $row ) {
					fputcsv( $handle, $row );
				}
			} else {
				fputcsv( $handle, array( 'created_at', 'event_type', 'screen', 'participant_public_id', 'question_id', 'question_label', 'question_theme', 'interface_language', 'metadata_json', 'ip_hash', 'user_agent_hash' ) );
			}
			fclose( $handle );
		}
		exit;
	}

	private static function event_export_rows(): array {
		global $wpdb;
		$events = Diastoles_DB::table( 'events' );
		$participants = Diastoles_DB::table( 'participants' );
		return $wpdb->get_results(
			"SELECT e.created_at, e.event_type, e.screen, p.public_id AS participant_public_id,
				e.question_id, e.question_label, e.question_theme, e.interface_language,
				e.metadata_json, e.ip_hash, e.user_agent_hash
			FROM $events e
			LEFT JOIN $participants p ON p.id = e.participant_id
			ORDER BY e.created_at ASC",
			ARRAY_A
		);
	}

	public static function render_connections(): void {
		self::require_admin();
		global $wpdb;
		$threshold_changes = Diastoles_Matcher::synchronize_connection_thresholds();
		$auto_threshold    = Diastoles_Matcher::auto_approve_threshold();
		$manual_threshold  = Diastoles_Matcher::manual_approval_threshold();
		$connections       = Diastoles_DB::table( 'connections' );
		$responses         = Diastoles_DB::table( 'responses' );
		$questions         = Diastoles_DB::table( 'questions' );
		$status_labels     = array(
			'pending'  => 'Pending review',
			'approved' => 'Approved',
			'rejected' => 'Rejected',
			'held'     => 'Held / below manual threshold',
		);
		$active_status     = sanitize_key( wp_unslash( $_GET['status'] ?? 'pending' ) );
		$active_status     = isset( $status_labels[ $active_status ] ) ? $active_status : 'pending';
		$relation_filter   = sanitize_text_field( wp_unslash( $_GET['relation_type'] ?? '' ) );
		$theme_filter      = sanitize_text_field( wp_unslash( $_GET['question_theme'] ?? '' ) );
		$search_filter     = sanitize_text_field( wp_unslash( $_GET['connection_search'] ?? '' ) );
		$min_score_filter  = '' !== (string) ( $_GET['min_score'] ?? '' ) ? max( 0, min( 1, (float) wp_unslash( $_GET['min_score'] ) ) ) : null;
		$max_score_filter  = '' !== (string) ( $_GET['max_score'] ?? '' ) ? max( 0, min( 1, (float) wp_unslash( $_GET['max_score'] ) ) ) : null;
		$sort_filter       = sanitize_key( wp_unslash( $_GET['connection_sort'] ?? 'newest' ) );
		$sort_filter       = in_array( $sort_filter, array( 'newest', 'score_desc', 'score_asc' ), true ) ? $sort_filter : 'newest';
		$paged             = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) );
		$per_page          = 25;
		$offset            = ( $paged - 1 ) * $per_page;
		$count_rows        = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM $connections GROUP BY status" );
		$status_counts     = array_fill_keys( array_keys( $status_labels ), 0 );
		foreach ( $count_rows as $count_row ) {
			if ( isset( $status_counts[ $count_row->status ] ) ) {
				$status_counts[ $count_row->status ] = (int) $count_row->total;
			}
		}
		$relation_types = $wpdb->get_col( "SELECT DISTINCT relation_type FROM $connections WHERE relation_type <> '' ORDER BY relation_type ASC" );
		$question_themes = $wpdb->get_col( "SELECT DISTINCT theme FROM $questions WHERE theme <> '' ORDER BY theme ASC" );
		$where_clauses = array( 'c.status = %s' );
		$where_values  = array( $active_status );
		if ( '' !== $relation_filter ) {
			$where_clauses[] = 'c.relation_type = %s';
			$where_values[]  = $relation_filter;
		}
		if ( '' !== $theme_filter ) {
			$where_clauses[] = '(qa.theme = %s OR qb.theme = %s)';
			$where_values[]  = $theme_filter;
			$where_values[]  = $theme_filter;
		}
		if ( null !== $min_score_filter ) {
			$where_clauses[] = 'c.score >= %f';
			$where_values[]  = $min_score_filter;
		}
		if ( null !== $max_score_filter ) {
			$where_clauses[] = 'c.score <= %f';
			$where_values[]  = $max_score_filter;
		}
		if ( '' !== $search_filter ) {
			$like = '%' . $wpdb->esc_like( $search_filter ) . '%';
			$where_clauses[] = '(a.original_text LIKE %s OR a.translation_en LIKE %s OR b.original_text LIKE %s OR b.translation_en LIKE %s OR qa.prompt LIKE %s OR qb.prompt LIKE %s OR c.explanation_json LIKE %s)';
			array_push( $where_values, $like, $like, $like, $like, $like, $like, $like );
		}
		$where_sql   = implode( ' AND ', $where_clauses );
		$count_query = "SELECT COUNT(*)
			FROM $connections c
			INNER JOIN $responses a ON a.id = c.response_a_id
			INNER JOIN $responses b ON b.id = c.response_b_id
			INNER JOIN $questions qa ON qa.id = a.question_id
			INNER JOIN $questions qb ON qb.id = b.question_id
			WHERE $where_sql";
		$total_rows  = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $where_values ) );
		$total_pages = max( 1, (int) ceil( $total_rows / $per_page ) );
		$row_values  = array_merge( $where_values, array( $per_page, $offset ) );
		$order_by    = match ( $sort_filter ) {
			'score_desc' => 'c.score DESC, COALESCE(c.moderated_at, c.created_at) DESC, c.created_at DESC',
			'score_asc'  => 'c.score ASC, COALESCE(c.moderated_at, c.created_at) DESC, c.created_at DESC',
			default      => 'COALESCE(c.moderated_at, c.created_at) DESC, c.created_at DESC',
		};
		$rows        = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, a.original_text AS text_a, a.translation_en AS translation_a,
					b.original_text AS text_b, b.translation_en AS translation_b,
					qa.prompt AS question_a, qa.short_label AS question_label_a, qa.theme AS question_theme_a,
					qb.prompt AS question_b, qb.short_label AS question_label_b, qb.theme AS question_theme_b
				FROM $connections c
				INNER JOIN $responses a ON a.id = c.response_a_id
				INNER JOIN $responses b ON b.id = c.response_b_id
				INNER JOIN $questions qa ON qa.id = a.question_id
				INNER JOIN $questions qb ON qb.id = b.question_id
				WHERE $where_sql
				ORDER BY $order_by
				LIMIT %d OFFSET %d",
				$row_values
			)
		);
		$filter_args = array(
			'page'              => 'diastoles-connections',
			'status'            => $active_status,
			'relation_type'     => $relation_filter,
			'question_theme'    => $theme_filter,
			'connection_search' => $search_filter,
			'min_score'         => null === $min_score_filter ? '' : $min_score_filter,
			'max_score'         => null === $max_score_filter ? '' : $max_score_filter,
			'connection_sort'   => $sort_filter,
		);
		?>
		<div class="wrap">
			<h1>Connection review</h1>
			<p>
				Connections scoring <?php echo esc_html( number_format_i18n( $auto_threshold, 2 ) ); ?> or higher
				are approved automatically. Scores from <?php echo esc_html( number_format_i18n( $manual_threshold, 2 ) ); ?>
				up to the automatic threshold are shown in <strong>Pending review</strong>.
				<strong>Held / below manual threshold</strong> means the AI saved a possible connection, but its score is below the manual review threshold:
				it is stored for export and later inspection, but it is not shown publicly.
				Rejected connections are never auto-approved again by threshold changes; you can still approve them manually.
			</p>
			<?php if ( $threshold_changes['approved'] > 0 ) : ?>
				<div class="notice notice-success inline">
					<p><?php echo esc_html( $threshold_changes['approved'] ); ?> existing connection(s) met the threshold and were approved automatically.</p>
				</div>
			<?php endif; ?>
			<?php if ( $threshold_changes['pending'] > 0 || $threshold_changes['held'] > 0 ) : ?>
				<div class="notice notice-info inline">
					<p>
						The new thresholds moved <?php echo esc_html( $threshold_changes['pending'] ); ?> connection(s) into review
						and <?php echo esc_html( $threshold_changes['held'] ); ?> outside the queue.
					</p>
				</div>
			<?php endif; ?>
			<nav class="nav-tab-wrapper" aria-label="Connection status">
				<?php foreach ( $status_labels as $status_key => $status_label ) : ?>
					<a
						class="nav-tab <?php echo $active_status === $status_key ? 'nav-tab-active' : ''; ?>"
						data-diastoles-status-tab="<?php echo esc_attr( $status_key ); ?>"
						href="<?php echo esc_url( add_query_arg( array( 'page' => 'diastoles-connections', 'status' => $status_key ), admin_url( 'admin.php' ) ) ); ?>"
					>
						<?php echo esc_html( $status_label ); ?>
						<span class="count">(<span data-diastoles-status-count="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( number_format_i18n( $status_counts[ $status_key ] ) ); ?></span>)</span>
					</a>
				<?php endforeach; ?>
			</nav>
			<p>
				Showing <?php echo esc_html( $status_labels[ $active_status ] ); ?>.
				Original words cannot be edited here; moderation only changes whether the connection is visible.
			</p>
			<form method="get" style="background:#fff;border:1px solid #ccd0d4;padding:12px 16px;max-width:900px;margin:16px 0;">
				<input type="hidden" name="page" value="diastoles-connections">
				<input type="hidden" name="status" value="<?php echo esc_attr( $active_status ); ?>">
				<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end;">
					<label>
						<span>Relationship</span>
						<select name="relation_type" style="width:100%;">
							<option value="">All relationships</option>
							<?php foreach ( $relation_types as $relation_type ) : ?>
								<option value="<?php echo esc_attr( $relation_type ); ?>" <?php selected( $relation_filter, $relation_type ); ?>>
									<?php echo esc_html( ucfirst( $relation_type ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span>Question theme</span>
						<select name="question_theme" style="width:100%;">
							<option value="">All themes</option>
							<?php foreach ( $question_themes as $question_theme ) : ?>
								<option value="<?php echo esc_attr( $question_theme ); ?>" <?php selected( $theme_filter, $question_theme ); ?>>
									<?php echo esc_html( ucwords( $question_theme ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span>Min score</span>
						<input type="number" name="min_score" value="<?php echo esc_attr( null === $min_score_filter ? '' : $min_score_filter ); ?>" min="0" max="1" step="0.01" placeholder="0.00" style="width:100%;">
					</label>
					<label>
						<span>Max score</span>
						<input type="number" name="max_score" value="<?php echo esc_attr( null === $max_score_filter ? '' : $max_score_filter ); ?>" min="0" max="1" step="0.01" placeholder="1.00" style="width:100%;">
					</label>
					<label>
						<span>Search</span>
						<input type="search" name="connection_search" value="<?php echo esc_attr( $search_filter ); ?>" placeholder="Words, questions, evidence…" style="width:100%;">
					</label>
					<label>
						<span>Sort</span>
						<select name="connection_sort" style="width:100%;">
							<option value="newest" <?php selected( $sort_filter, 'newest' ); ?>>Newest first</option>
							<option value="score_desc" <?php selected( $sort_filter, 'score_desc' ); ?>>Highest score first</option>
							<option value="score_asc" <?php selected( $sort_filter, 'score_asc' ); ?>>Lowest score first</option>
						</select>
					</label>
					<div>
						<button class="button button-primary">Filter</button>
						<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'diastoles-connections', 'status' => $active_status ), admin_url( 'admin.php' ) ) ); ?>">Reset</a>
					</div>
				</div>
			</form>
			<p><strong data-diastoles-filtered-count><?php echo esc_html( number_format_i18n( $total_rows ) ); ?></strong> connection(s) match the current filters.</p>
			<?php if ( empty( $rows ) ) : ?>
				<div class="notice notice-info inline"><p>No connections found in this status.</p></div>
			<?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<?php $explanation = json_decode( $row->explanation_json, true ) ?: array(); ?>
				<div class="card diastoles-admin-connection-card" style="max-width: 900px; margin-top: 16px; transition: opacity .16s ease, transform .16s ease;" data-diastoles-connection-card>
					<h2>
						<?php echo esc_html( ucfirst( $row->relation_type ) ); ?>
						· <?php echo esc_html( number_format_i18n( (float) $row->score, 2 ) ); ?>
						· <?php echo esc_html( $status_labels[ $row->status ] ?? ucfirst( $row->status ) ); ?>
					</h2>
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
						<div>
							<p><strong>Voice A</strong></p>
							<p>
								<?php if ( $row->question_theme_a ) : ?>
									<small>Theme: <?php echo esc_html( ucwords( $row->question_theme_a ) ); ?></small><br>
								<?php endif; ?>
								<strong>Question:</strong>
								<?php echo esc_html( $row->question_a ); ?>
							</p>
							<blockquote>
								<?php echo esc_html( $row->text_a ); ?>
								<?php if ( $row->translation_a && $row->translation_a !== $row->text_a ) : ?>
									<br><em><?php echo esc_html( $row->translation_a ); ?></em>
								<?php endif; ?>
							</blockquote>
						</div>
						<div>
							<p><strong>Voice B</strong></p>
							<p>
								<?php if ( $row->question_theme_b ) : ?>
									<small>Theme: <?php echo esc_html( ucwords( $row->question_theme_b ) ); ?></small><br>
								<?php endif; ?>
								<strong>Question:</strong>
								<?php echo esc_html( $row->question_b ); ?>
							</p>
							<blockquote>
								<?php echo esc_html( $row->text_b ); ?>
								<?php if ( $row->translation_b && $row->translation_b !== $row->text_b ) : ?>
									<br><em><?php echo esc_html( $row->translation_b ); ?></em>
								<?php endif; ?>
							</blockquote>
						</div>
					</div>
					<p><strong>Evidence:</strong> <?php echo esc_html( implode( ' · ', (array) ( $explanation['evidence'] ?? array() ) ) ); ?></p>
					<?php if ( ! empty( $explanation['selection_context']['selection_reasons'] ) ) : ?>
						<p>
							<strong>Candidate selection:</strong>
							<?php
							echo esc_html(
								( $explanation['selection_context']['selection_bucket'] ?? 'semantic_overlap' )
								. ' · '
								. implode(
									' · ',
									array_map(
										static fn( mixed $reason ): string => is_array( $reason ) ? ( $reason['type'] ?? 'signal' ) . ': ' . ( $reason['label'] ?? '' ) . ' +' . ( $reason['weight'] ?? 0 ) : (string) $reason,
										array_slice( (array) $explanation['selection_context']['selection_reasons'], 0, 8 )
									)
								)
							);
							?>
						</p>
					<?php endif; ?>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-diastoles-moderation-form>
						<input type="hidden" name="action" value="diastoles_moderate">
						<input type="hidden" name="connection_id" value="<?php echo esc_attr( $row->public_id ); ?>">
						<input type="hidden" name="return_status" value="<?php echo esc_attr( $active_status ); ?>">
						<?php wp_nonce_field( 'diastoles_moderate_' . $row->public_id ); ?>
						<?php if ( 'approved' !== $row->status ) : ?>
							<button class="button button-primary" name="decision" value="approve">Approve</button>
						<?php endif; ?>
						<?php if ( 'rejected' !== $row->status ) : ?>
							<button class="button" name="decision" value="reject">Reject</button>
						<?php endif; ?>
					</form>
				</div>
			<?php endforeach; ?>
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						echo paginate_links(
							array(
								'base'      => add_query_arg(
									array(
										'page'   => 'diastoles-connections',
										'status' => $active_status,
										'paged'  => '%#%',
									) + array_filter( $filter_args, static fn( $value ) => '' !== $value && null !== $value ),
									admin_url( 'admin.php' )
								),
								'format'    => '',
								'current'   => $paged,
								'total'     => $total_pages,
								'prev_text' => '‹',
								'next_text' => '›',
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
			<script>
				(() => {
					const parseCount = (text) => Number(String(text || '').replace(/[^\d]/g, '')) || 0;
					const writeCount = (node, value) => {
						if (node) node.textContent = new Intl.NumberFormat().format(Math.max(0, value));
					};
					document.addEventListener('submit', async (event) => {
						const form = event.target.closest('[data-diastoles-moderation-form]');
						if (!form || !window.ajaxurl) return;
						event.preventDefault();
						const card = form.closest('[data-diastoles-connection-card]');
						const submitter = event.submitter;
						const data = new FormData(form);
						data.set('action', 'diastoles_moderate_connection_ajax');
						if (submitter && submitter.name) data.set(submitter.name, submitter.value);
						form.querySelectorAll('button').forEach((button) => button.disabled = true);
						try {
							const response = await fetch(window.ajaxurl, {
								method: 'POST',
								credentials: 'same-origin',
								body: data,
							});
							const result = await response.json();
							if (!response.ok || !result.success) {
								throw new Error(result?.data?.message || 'Moderation failed.');
							}
							if (card) {
								card.style.opacity = '0';
								card.style.transform = 'translateY(-6px)';
								setTimeout(() => card.remove(), 180);
							}
							const activeStatus = data.get('return_status');
							const activeCount = document.querySelector(`[data-diastoles-status-count="${activeStatus}"]`);
							const filteredCount = document.querySelector('[data-diastoles-filtered-count]');
							writeCount(activeCount, parseCount(activeCount?.textContent) - 1);
							writeCount(filteredCount, parseCount(filteredCount?.textContent) - 1);
							if (result.data?.status) {
								const destinationCount = document.querySelector(`[data-diastoles-status-count="${result.data.status}"]`);
								writeCount(destinationCount, parseCount(destinationCount?.textContent) + 1);
							}
						} catch (error) {
							form.querySelectorAll('button').forEach((button) => button.disabled = false);
							window.alert(error.message || 'Moderation failed.');
						}
					});
				})();
			</script>
		</div>
		<?php
	}

	public static function render_questions(): void {
		self::require_admin();
		global $wpdb;

		$allowed_tabs = array( 'add', 'library', 'import-export' );
		$active_tab   = sanitize_key( wp_unslash( $_GET['tab'] ?? 'library' ) );
		$active_tab   = in_array( $active_tab, $allowed_tabs, true ) ? $active_tab : 'library';
		$translation_locale = Diastoles_I18n::normalize( $_GET['lang'] ?? 'es' );
		$translation_locale = $translation_locale && 'en' !== $translation_locale ? $translation_locale : ( array_key_first( array_diff_key( Diastoles_I18n::active_languages(), array( 'en' => true ) ) ) ?: 'es' );
		$table     = Diastoles_DB::table( 'questions' );
		$questions = $wpdb->get_results( "SELECT * FROM $table ORDER BY active DESC, id ASC" );
		$saved     = isset( $_GET['saved'] );
		$grouped   = array();
		foreach ( $questions as $question ) {
			$theme = mb_strtolower( trim( (string) $question->theme ) ) ?: 'unthemed';
			$grouped[ $theme ][] = $question;
		}
		uksort( $grouped, 'strnatcasecmp' );
		$requested_theme = mb_strtolower( sanitize_text_field( wp_unslash( $_GET['theme'] ?? '' ) ) );
		$active_theme    = isset( $grouped[ $requested_theme ] ) ? $requested_theme : (string) array_key_first( $grouped );
		?>
		<div class="wrap">
			<h1>Questions</h1>
			<p>
				All participant-facing questions are written in English. Participants may answer in any language.
				Deactivating a question preserves existing responses.
			</p>
			<nav class="nav-tab-wrapper" aria-label="Question management">
				<a class="nav-tab <?php echo 'add' === $active_tab ? 'nav-tab-active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( 'lang', $translation_locale, admin_url( 'admin.php?page=diastoles-questions&tab=add' ) ) ); ?>">Add a question</a>
				<a class="nav-tab <?php echo 'library' === $active_tab ? 'nav-tab-active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( 'lang', $translation_locale, admin_url( 'admin.php?page=diastoles-questions&tab=library' ) ) ); ?>">Question library</a>
				<a class="nav-tab <?php echo 'import-export' === $active_tab ? 'nav-tab-active' : ''; ?>"
					href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-questions&tab=import-export' ) ); ?>">Import / Export</a>
			</nav>
			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Question saved.</p></div>
			<?php endif; ?>
			<?php if ( 'import-export' !== $active_tab && count( Diastoles_I18n::active_languages() ) > 1 ) : ?>
				<form method="get" style="margin: 18px 0">
					<input type="hidden" name="page" value="diastoles-questions"><input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
					<?php if ( $active_theme ) : ?><input type="hidden" name="theme" value="<?php echo esc_attr( $active_theme ); ?>"><?php endif; ?>
					<label><strong>Translation fields:</strong> <select name="lang" onchange="this.form.submit()">
					<?php foreach ( Diastoles_I18n::active_languages() as $code => $language ) : if ( 'en' === $code ) continue; ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $translation_locale, $code ); ?>><?php echo esc_html( $language['name'] ); ?></option>
					<?php endforeach; ?>
					</select></label>
				</form>
			<?php endif; ?>

			<?php if ( 'add' === $active_tab ) : ?>
				<h2>Add a question</h2>
				<?php self::render_question_form( null, $translation_locale ); ?>
			<?php elseif ( 'import-export' === $active_tab ) : ?>
				<?php self::render_questions_import_export(); ?>
			<?php else : ?>
				<h2>Question library</h2>
				<?php if ( $grouped ) : ?>
					<nav class="nav-tab-wrapper" aria-label="Question themes" style="margin-bottom: 20px">
						<?php foreach ( $grouped as $theme => $theme_questions ) : ?>
							<a class="nav-tab <?php echo $theme === $active_theme ? 'nav-tab-active' : ''; ?>"
								href="<?php echo esc_url( add_query_arg( array( 'page' => 'diastoles-questions', 'tab' => 'library', 'theme' => $theme, 'lang' => $translation_locale ), admin_url( 'admin.php' ) ) ); ?>">
								<?php echo esc_html( ucwords( $theme ) . ' (' . count( $theme_questions ) . ')' ); ?>
							</a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
				<?php foreach ( (array) ( $grouped[ $active_theme ] ?? array() ) as $question ) : ?>
					<?php self::render_question_form( $question, $translation_locale ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_questions_import_export(): void {
		$imported = absint( $_GET['imported'] ?? 0 );
		$updated  = absint( $_GET['updated'] ?? 0 );
		$removed  = absint( $_GET['removed'] ?? 0 );
		$archived = absint( $_GET['archived'] ?? 0 );
		if ( isset( $_GET['questions_imported'] ) ) {
			?>
			<div class="notice notice-success is-dismissible"><p>
				Import completed: <?php echo esc_html( $imported ); ?> added, <?php echo esc_html( $updated ); ?> updated,
				<?php echo esc_html( $removed ); ?> removed and <?php echo esc_html( $archived ); ?> retained as inactive to preserve existing responses.
			</p></div>
			<?php
		}
		?>
		<h2>Import / Export questions</h2>
		<div class="card" style="max-width: 920px; padding: 20px">
			<h3>CSV format</h3>
			<p>The first row should contain these columns. Column order may vary; <code>skip_count</code> is optional on import:</p>
			<p><code>id,prompt,short_label,followup,theme,intensity,active,skip_count,created_at</code></p>
			<p>Every active non-English language adds three optional columns, for example <code>prompt_es,short_label_es,followup_es</code>. Export a CSV first to obtain the exact current template.</p>
			<ul style="list-style: disc; padding-left: 24px">
				<li><code>prompt</code> is the question's identity. An exact matching prompt updates the existing question.</li>
				<li><code>id</code> is exported for reference and ignored during import.</li>
				<li><code>intensity</code> must be 1, 2 or 3. <code>active</code> must be 1 or 0. <code>skip_count</code> is exported for reporting and ignored during import.</li>
				<li><code>created_at</code> uses <code>YYYY-MM-DD HH:MM:SS</code> in UTC and is used only for newly added questions.</li>
				<li>Use UTF-8 CSV. Fields containing commas, quotes or line breaks must use standard CSV quoting.</li>
			</ul>
		</div>

		<div class="card" style="max-width: 920px; margin-top: 16px; padding: 20px">
			<h3>Export</h3>
			<p>Download every question and every database column in a reusable CSV file.</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="diastoles_export_questions">
				<?php wp_nonce_field( 'diastoles_export_questions' ); ?>
				<?php submit_button( 'Download questions CSV', 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<div class="card" style="max-width: 920px; margin-top: 16px; padding: 20px">
			<h3>Import: add or update</h3>
			<p>Add new prompts and update questions whose prompt matches exactly. Other existing questions are left unchanged.</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="diastoles_import_questions">
				<input type="hidden" name="import_mode" value="merge">
				<?php wp_nonce_field( 'diastoles_import_questions_merge' ); ?>
				<input type="file" name="questions_csv" accept=".csv,text/csv" required>
				<?php submit_button( 'Add or update questions', 'primary', 'submit', false ); ?>
			</form>
		</div>

		<div class="card" style="max-width: 920px; margin-top: 16px; padding: 20px; border-left: 4px solid #d63638">
			<h3>Import: replace the question library</h3>
			<p>
				Make the imported CSV the complete question library. Existing questions absent from the file are removed.
				If a question already has participant responses, it is retained as inactive so those traces keep their original question.
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="diastoles_import_questions">
				<input type="hidden" name="import_mode" value="replace">
				<?php wp_nonce_field( 'diastoles_import_questions_replace' ); ?>
				<p><input type="file" name="questions_csv" accept=".csv,text/csv" required></p>
				<label><input type="checkbox" name="confirm_replace" value="1" required> I understand that this replaces the current question library.</label>
				<?php submit_button( 'Replace question library', 'delete', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public static function render_texts(): void {
		self::require_admin();
		$tab     = sanitize_key( wp_unslash( $_GET['tab'] ?? 'texts' ) );
		$tab     = in_array( $tab, array( 'texts', 'languages' ), true ) ? $tab : 'texts';
		$locale  = Diastoles_I18n::normalize( $_GET['lang'] ?? Diastoles_I18n::settings()['default'] ) ?: 'en';
		$values  = 'en' === $locale ? Diastoles_Texts::all() : Diastoles_I18n::raw_interface_locale( $locale );
		$english = Diastoles_Texts::all();
		?>
		<div class="wrap">
			<h1>Interface texts</h1>
			<p>
				Texts are rendered directly in the selected language. Google Translate is not used inside the Diástoles experience.
			</p>
			<nav class="nav-tab-wrapper" aria-label="Interface language management">
				<a class="nav-tab <?php echo 'texts' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-texts&tab=texts' ) ); ?>">Interface texts</a>
				<a class="nav-tab <?php echo 'languages' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-texts&tab=languages' ) ); ?>">Languages</a>
			</nav>
			<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success inline"><p>Changes saved.</p></div><?php endif; ?>
			<?php if ( 'languages' === $tab ) : ?>
				<?php $settings = Diastoles_I18n::settings(); ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="diastoles_save_languages">
					<?php wp_nonce_field( 'diastoles_save_languages' ); ?>
					<table class="widefat striped" style="max-width: 920px; margin-top: 20px">
						<thead><tr><th>Active</th><th>Language</th><th>Code</th><th>Interface complete</th><th>Default</th></tr></thead>
						<tbody>
						<?php foreach ( Diastoles_I18n::languages() as $code => $language ) : ?>
							<tr>
								<td><input type="checkbox" name="active[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $settings['active'], true ) ); ?> <?php disabled( 'en' === $code ); ?>></td>
								<td><strong><?php echo esc_html( $language['name'] ); ?></strong><br><small><?php echo esc_html( $language['english_name'] ); ?></small></td>
								<td><code><?php echo esc_html( $code ); ?></code><?php echo 'rtl' === $language['dir'] ? ' · RTL' : ''; ?></td>
								<td><?php echo esc_html( Diastoles_I18n::translation_progress( $code ) . '%' ); ?></td>
								<td><input type="radio" name="default" value="<?php echo esc_attr( $code ); ?>" <?php checked( $settings['default'], $code ); ?>></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">English remains active as the source and fallback language. Empty translations fall back to English without a later browser rewrite.</p>
					<?php submit_button( 'Save languages' ); ?>
				</form>
			<?php else : ?>
				<form method="get" style="margin: 20px 0">
					<input type="hidden" name="page" value="diastoles-texts"><input type="hidden" name="tab" value="texts">
					<label for="diastoles-edit-locale"><strong>Editing language:</strong></label>
					<select id="diastoles-edit-locale" name="lang" onchange="this.form.submit()">
						<?php foreach ( Diastoles_I18n::active_languages() as $code => $language ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $locale, $code ); ?>><?php echo esc_html( $language['name'] . ' — ' . Diastoles_I18n::translation_progress( $code ) . '%' ); ?></option>
						<?php endforeach; ?>
					</select>
				</form>
			<form action="<?php echo esc_url( 'en' === $locale ? 'options.php' : admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php if ( 'en' === $locale ) : settings_fields( 'diastoles_texts' ); else : ?>
					<input type="hidden" name="action" value="diastoles_save_interface_locale"><input type="hidden" name="locale" value="<?php echo esc_attr( $locale ); ?>">
					<?php wp_nonce_field( 'diastoles_save_interface_locale_' . $locale ); ?>
				<?php endif; ?>
				<?php foreach ( Diastoles_Texts::fields() as $section => $fields ) : ?>
					<h2><?php echo esc_html( $section ); ?></h2>
					<table class="form-table" role="presentation">
						<?php foreach ( $fields as $key => $field ) : ?>
							<tr>
								<th scope="row">
									<label for="diastoles-text-<?php echo esc_attr( $key ); ?>">
										<?php echo esc_html( $field['label'] ); ?>
									</label>
								</th>
								<td>
									<?php if ( 'textarea' === ( $field['type'] ?? '' ) ) : ?>
										<textarea
											class="large-text"
											id="diastoles-text-<?php echo esc_attr( $key ); ?>"
											name="<?php echo 'en' === $locale ? 'diastoles_interface_texts' : 'translations'; ?>[<?php echo esc_attr( $key ); ?>]"
											rows="3"
										><?php echo esc_textarea( $values[ $key ] ); ?></textarea>
									<?php else : ?>
										<input
											class="large-text"
											id="diastoles-text-<?php echo esc_attr( $key ); ?>"
											name="<?php echo 'en' === $locale ? 'diastoles_interface_texts' : 'translations'; ?>[<?php echo esc_attr( $key ); ?>]"
											value="<?php echo esc_attr( $values[ $key ] ); ?>"
										>
									<?php endif; ?>
									<?php if ( 'en' !== $locale ) : ?><p class="description"><strong>English:</strong> <?php echo esc_html( $english[ $key ] ); ?></p><?php endif; ?>
									<?php if ( ! empty( $field['description'] ) ) : ?>
										<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>
				<?php submit_button( 'Save interface texts' ); ?>
			</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function save_languages(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_save_languages' );
		update_option( 'diastoles_language_settings', Diastoles_I18n::sanitize_settings( array(
			'active' => wp_unslash( $_POST['active'] ?? array() ),
			'default' => wp_unslash( $_POST['default'] ?? 'en' ),
		) ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=diastoles-texts&tab=languages&saved=1' ) );
		exit;
	}

	public static function save_interface_locale(): void {
		self::require_admin();
		$locale = Diastoles_I18n::normalize( $_POST['locale'] ?? '' );
		check_admin_referer( 'diastoles_save_interface_locale_' . $locale );
		Diastoles_I18n::save_interface_locale( $locale, (array) ( $_POST['translations'] ?? array() ) );
		wp_safe_redirect( admin_url( 'admin.php?page=diastoles-texts&tab=texts&lang=' . rawurlencode( $locale ) . '&saved=1' ) );
		exit;
	}

	public static function render_recalculation(): void {
		self::require_admin();
		$job     = get_option( self::RECALCULATION_OPTION, array() );
		$message = sanitize_key( wp_unslash( $_GET['diastoles_recalculation'] ?? '' ) );
		$active  = is_array( $job ) && in_array( $job['status'] ?? '', array( 'queued', 'running' ), true );
		$done    = (int) ( $job['cursor'] ?? 0 );
		$total   = (int) ( $job['total'] ?? 0 );
		?>
		<div class="wrap">
			<h1>Recalculate concepts and relationships</h1>
			<?php if ( 'queued' === $message ) : ?>
				<div class="notice notice-success inline"><p>The recalculation job has been queued.</p></div>
			<?php elseif ( 'active' === $message ) : ?>
				<div class="notice notice-warning inline"><p>A recalculation job is already running.</p></div>
			<?php elseif ( 'empty' === $message ) : ?>
				<div class="notice notice-info inline"><p>No responses matched the selected period.</p></div>
			<?php elseif ( 'confirmation' === $message ) : ?>
				<div class="notice notice-error inline"><p>Confirm the replacement of affected relationships before continuing.</p></div>
			<?php elseif ( 'invalid_range' === $message ) : ?>
				<div class="notice notice-error inline"><p>Choose a valid custom date range. The end date must be the same as or later than the start date.</p></div>
			<?php endif; ?>

			<?php if ( is_array( $job ) && ! empty( $job['id'] ) ) : ?>
				<div class="card" style="max-width: 760px; padding: 20px; margin: 16px 0">
					<h2>Latest job</h2>
					<p><strong>Status:</strong> <?php echo esc_html( ucfirst( (string) ( $job['status'] ?? 'unknown' ) ) ); ?></p>
					<p><strong>Period:</strong> <?php echo esc_html( self::recalculation_period_label( (string) ( $job['period'] ?? '30' ), (array) ( $job['date_range'] ?? array() ) ) ); ?></p>
					<p><strong>Selected responses:</strong> <?php echo esc_html( (string) (int) ( $job['selected_responses'] ?? 0 ) ); ?></p>
					<p><strong>Progress:</strong> <?php echo esc_html( $done . ' / ' . $total . ' work items' ); ?></p>
					<p><strong>Errors:</strong> <?php echo esc_html( (string) (int) ( $job['errors'] ?? 0 ) ); ?></p>
					<p><strong>Relationships removed before rebuilding:</strong> <?php echo esc_html( (string) (int) ( $job['removed_connections'] ?? 0 ) ); ?></p>
					<?php if ( ! empty( $job['last_error'] ) ) : ?>
						<p><strong>Last error:</strong> <?php echo esc_html( (string) $job['last_error'] ); ?></p>
					<?php endif; ?>
					<?php if ( $active ) : ?>
						<p class="description">The queue processes one response per WP-Cron run. Normal site visits keep it moving.</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="card" style="max-width: 760px; padding: 20px">
				<input type="hidden" name="action" value="diastoles_recalculate">
				<?php wp_nonce_field( 'diastoles_recalculate' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="diastoles-recalculation-mode">What to recalculate</label></th>
						<td>
							<select id="diastoles-recalculation-mode" name="mode">
								<option value="both">Concepts and relationships (recommended)</option>
								<option value="translations">Translations only — responses and scent concepts, keep relationships</option>
								<option value="concepts">Concepts only — remove affected relationships</option>
								<option value="relationships">Relationships only — reuse stored concepts</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="diastoles-recalculation-period">Period</label></th>
						<td>
							<select id="diastoles-recalculation-period" name="period">
								<option value="7">Last 7 days</option>
								<option value="30" selected>Last 30 days</option>
								<option value="90">Last 90 days</option>
								<option value="365">Last 365 days</option>
								<option value="all">All responses</option>
								<option value="custom">Custom date range</option>
							</select>
							<div style="margin-top: 12px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center">
								<label>From <input type="date" name="date_from" value="<?php echo esc_attr( gmdate( 'Y-m-d', time() - 7 * DAY_IN_SECONDS ) ); ?>"></label>
								<label>To <input type="date" name="date_to" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></label>
							</div>
							<p class="description">Choose “Custom date range” to use these dates. Dates are inclusive and interpreted in the site timezone.</p>
						</td>
					</tr>
				</table>
				<p><strong>Important:</strong> concept/relationship recalculations delete affected relationships before rebuilding them. Translation-only jobs do not remove relationships.</p>
				<p><strong>Translation-only jobs skip translations that are already complete.</strong> They only request Anthropic for missing translations, stale pending translations, failed translations, and newly detected scent-concept labels that do not already have a completed translation for that language.</p>
				<p>In Live mode, concept recalculation sends every selected response to Anthropic again. Translation-only jobs fill gaps for active languages while preserving originals, relationships, scores and approved/rejected moderation states.</p>
				<p style="margin: 18px 0 24px">
					<label>
						<input type="checkbox" name="confirm_replacement" value="1" required>
						I understand that Live mode may incur API usage. Translation-only jobs skip completed translations; concept or relationship recalculations may replace affected relationships.
					</label>
				</p>
				<?php submit_button( 'Queue recalculation', 'primary', 'submit', false, $active ? array( 'disabled' => 'disabled' ) : array() ); ?>
			</form>
		</div>
		<?php
	}

	public static function queue_recalculation(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_recalculate' );
		$redirect = admin_url( 'admin.php?page=diastoles-recalculate' );
		$job      = get_option( self::RECALCULATION_OPTION, array() );
		if ( is_array( $job ) && in_array( $job['status'] ?? '', array( 'queued', 'running' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'diastoles_recalculation', 'active', $redirect ) );
			exit;
		}
		if ( empty( $_POST['confirm_replacement'] ) ) {
			wp_safe_redirect( add_query_arg( 'diastoles_recalculation', 'confirmation', $redirect ) );
			exit;
		}

		$mode    = sanitize_key( wp_unslash( $_POST['mode'] ?? 'both' ) );
		$period  = sanitize_key( wp_unslash( $_POST['period'] ?? '30' ) );
		$mode    = in_array( $mode, array( 'concepts', 'relationships', 'translations', 'both' ), true ) ? $mode : 'both';
		$period  = in_array( $period, array( '7', '30', '90', '365', 'all', 'custom' ), true ) ? $period : '30';
		$date_range = 'custom' === $period ? self::custom_recalculation_range_from_request() : array();
		if ( 'custom' === $period && empty( $date_range ) ) {
			wp_safe_redirect( add_query_arg( 'diastoles_recalculation', 'invalid_range', $redirect ) );
			exit;
		}
		$response_ids = self::response_ids_for_recalculation( $mode, $period, $date_range );
		if ( ! $response_ids ) {
			wp_safe_redirect( add_query_arg( 'diastoles_recalculation', 'empty', $redirect ) );
			exit;
		}

		$tasks = array();
		if ( in_array( $mode, array( 'concepts', 'both' ), true ) ) {
			foreach ( $response_ids as $response_id ) {
				$tasks[] = array( 'response_id' => $response_id, 'mode' => 'concepts' );
			}
		}
		if ( in_array( $mode, array( 'relationships', 'both' ), true ) ) {
			foreach ( $response_ids as $response_id ) {
				$tasks[] = array( 'response_id' => $response_id, 'mode' => 'relationships' );
			}
		}
		if ( 'translations' === $mode ) {
			foreach ( $response_ids as $response_id ) {
				$tasks[] = array( 'response_id' => $response_id, 'mode' => 'translations' );
			}
		}

		$job = array(
			'id'                  => wp_generate_uuid4(),
			'mode'                => $mode,
			'period'              => $period,
			'date_range'          => $date_range,
			'response_ids'        => $response_ids,
			'tasks'               => $tasks,
			'cursor'              => 0,
			'total'               => count( $tasks ),
			'selected_responses'  => count( $response_ids ),
			'errors'              => 0,
			'last_error'          => '',
			'removed_connections' => 0,
			'connections_cleared' => false,
			'status'              => 'queued',
			'created_at'          => current_time( 'mysql', true ),
			'updated_at'          => current_time( 'mysql', true ),
		);
		delete_option( self::RECALCULATION_OPTION );
		add_option( self::RECALCULATION_OPTION, $job, '', false );
		wp_schedule_single_event( time() + 1, 'diastoles_run_recalculation_job', array( $job['id'] ) );
		wp_safe_redirect( add_query_arg( 'diastoles_recalculation', 'queued', $redirect ) );
		exit;
	}

	public static function run_recalculation_job( string $job_id ): void {
		if ( get_transient( self::RECALCULATION_LOCK ) ) {
			return;
		}
		set_transient( self::RECALCULATION_LOCK, $job_id, 5 * MINUTE_IN_SECONDS );
		try {
			$job = get_option( self::RECALCULATION_OPTION, array() );
			if ( ! is_array( $job ) || ( $job['id'] ?? '' ) !== $job_id || ! in_array( $job['status'] ?? '', array( 'queued', 'running' ), true ) ) {
				return;
			}
			if ( empty( $job['connections_cleared'] ) && 'translations' !== ( $job['mode'] ?? '' ) ) {
				$job['removed_connections'] = self::delete_connections_for_responses( (array) $job['response_ids'] );
				$job['connections_cleared'] = true;
				$job['updated_at']          = current_time( 'mysql', true );
				update_option( self::RECALCULATION_OPTION, $job, false );
			}
			$cursor = (int) ( $job['cursor'] ?? 0 );
			if ( $cursor >= (int) ( $job['total'] ?? 0 ) ) {
				$job['status']     = 'complete';
				$job['updated_at'] = current_time( 'mysql', true );
				update_option( self::RECALCULATION_OPTION, $job, false );
				return;
			}

			$job['status'] = 'running';
			$task          = $job['tasks'][ $cursor ] ?? array();
			$response_id   = (int) ( $task['response_id'] ?? 0 );
			$task_mode     = (string) ( $task['mode'] ?? $job['mode'] );
			$success       = Diastoles_Plugin::instance()->recalculate_response( $response_id, $task_mode );
			if ( ! $success ) {
				$job['errors']     = (int) ( $job['errors'] ?? 0 ) + 1;
				$job['last_error'] = 'Response ' . $response_id . ' could not complete the ' . $task_mode . ' phase. Check processing events and the debug log.';
			}
			$job['cursor']     = $cursor + 1;
			$job['updated_at'] = current_time( 'mysql', true );
			if ( $job['cursor'] >= $job['total'] ) {
				$job['status'] = 'complete';
			} else {
				wp_schedule_single_event( time() + 1, 'diastoles_run_recalculation_job', array( $job_id ) );
			}
			update_option( self::RECALCULATION_OPTION, $job, false );
		} finally {
			delete_transient( self::RECALCULATION_LOCK );
		}
	}

	private static function response_ids_for_recalculation( string $mode, string $period, array $date_range = array() ): array {
		global $wpdb;
		$table = Diastoles_DB::table( 'responses' );
		$where = 'withdrawn = 0';
		$args  = array();
		if ( in_array( $mode, array( 'relationships', 'translations' ), true ) ) {
			$where .= " AND processing_status = 'complete' AND allow_network = 1";
		}
		if ( 'custom' === $period ) {
			$where .= ' AND created_at >= %s AND created_at <= %s';
			$args[] = $date_range['from_utc'] ?? '1970-01-01 00:00:00';
			$args[] = $date_range['to_utc'] ?? current_time( 'mysql', true );
		} elseif ( 'all' !== $period ) {
			$where .= ' AND created_at >= %s';
			$args[] = gmdate( 'Y-m-d H:i:s', time() - ( (int) $period * DAY_IN_SECONDS ) );
		}
		$sql = "SELECT id FROM $table WHERE $where ORDER BY created_at ASC";
		if ( $args ) {
			$sql = $wpdb->prepare( $sql, ...$args );
		}
		return array_map( 'intval', $wpdb->get_col( $sql ) );
	}

	private static function custom_recalculation_range_from_request(): array {
		$from = sanitize_text_field( wp_unslash( $_POST['date_from'] ?? '' ) );
		$to   = sanitize_text_field( wp_unslash( $_POST['date_to'] ?? '' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			return array();
		}
		$timezone = wp_timezone();
		try {
			$from_local = new DateTimeImmutable( $from . ' 00:00:00', $timezone );
			$to_local   = new DateTimeImmutable( $to . ' 23:59:59', $timezone );
		} catch ( Exception $exception ) {
			return array();
		}
		if ( $to_local < $from_local ) {
			return array();
		}
		return array(
			'from'     => $from,
			'to'       => $to,
			'from_utc' => $from_local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
			'to_utc'   => $to_local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
		);
	}

	private static function recalculation_period_label( string $period, array $date_range = array() ): string {
		if ( 'custom' === $period ) {
			$from = sanitize_text_field( (string) ( $date_range['from'] ?? '' ) );
			$to   = sanitize_text_field( (string) ( $date_range['to'] ?? '' ) );
			return $from && $to ? "$from to $to" : 'Custom date range';
		}
		if ( 'all' === $period ) {
			return 'All responses';
		}
		return 'Last ' . (int) $period . ' days';
	}

	private static function delete_connections_for_responses( array $response_ids ): int {
		global $wpdb;
		$table   = Diastoles_DB::table( 'connections' );
		$deleted = 0;
		foreach ( array_chunk( array_map( 'intval', $response_ids ), 200 ) as $chunk ) {
			$placeholders = implode( ', ', array_fill( 0, count( $chunk ), '%d' ) );
			$query        = "DELETE FROM $table WHERE response_a_id IN ($placeholders) OR response_b_id IN ($placeholders)";
			$deleted     += (int) $wpdb->query( $wpdb->prepare( $query, ...array_merge( $chunk, $chunk ) ) );
		}
		return $deleted;
	}

	private static function render_question_form( ?object $question = null, string $translation_locale = 'es' ): void {
		$id        = $question ? (int) $question->id : 0;
		$is_active = ! $question || (bool) $question->active;
		$translation_locale = Diastoles_I18n::normalize( $translation_locale ) ?: 'es';
		$translation = $id ? Diastoles_I18n::question_translation( $id, $translation_locale ) : array();
		$translation_language = Diastoles_I18n::languages()[ $translation_locale ] ?? array( 'name' => strtoupper( $translation_locale ) );
		?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"
			class="card" style="max-width: 920px; margin: 12px 0; padding: 20px">
			<input type="hidden" name="action" value="diastoles_save_question">
			<input type="hidden" name="question_id" value="<?php echo esc_attr( $id ); ?>">
			<input type="hidden" name="translation_locale" value="<?php echo esc_attr( $translation_locale ); ?>">
			<?php wp_nonce_field( 'diastoles_save_question_' . $id ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="prompt-<?php echo esc_attr( $id ); ?>">Question</label></th>
					<td>
						<textarea class="large-text" id="prompt-<?php echo esc_attr( $id ); ?>"
							name="prompt" rows="2" required maxlength="1000"><?php
							echo esc_textarea( $question->prompt ?? '' );
						?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="followup-<?php echo esc_attr( $id ); ?>">Optional follow-up</label></th>
					<td>
						<textarea class="large-text" id="followup-<?php echo esc_attr( $id ); ?>"
							name="followup" rows="2" maxlength="1000"><?php
							echo esc_textarea( $question->followup ?? '' );
						?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="short-label-<?php echo esc_attr( $id ); ?>">Short map label</label></th>
					<td>
						<input class="large-text" id="short-label-<?php echo esc_attr( $id ); ?>" name="short_label"
							value="<?php echo esc_attr( $question->short_label ?? '' ); ?>" maxlength="160">
						<p class="description">A short version of the question shown beside recently shared fragments and in map connections.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="theme-<?php echo esc_attr( $id ); ?>">Theme</label></th>
					<td>
						<input class="regular-text" id="theme-<?php echo esc_attr( $id ); ?>" name="theme"
							value="<?php echo esc_attr( $question->theme ?? 'memory' ); ?>" maxlength="60" required>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="intensity-<?php echo esc_attr( $id ); ?>">Intensity</label></th>
					<td>
						<select id="intensity-<?php echo esc_attr( $id ); ?>" name="intensity">
							<option value="1" <?php selected( (int) ( $question->intensity ?? 1 ), 1 ); ?>>1 — gentle</option>
							<option value="2" <?php selected( (int) ( $question->intensity ?? 1 ), 2 ); ?>>2 — reflective</option>
							<option value="3" <?php selected( (int) ( $question->intensity ?? 1 ), 3 ); ?>>3 — emotionally intense</option>
						</select>
						<label style="margin-left: 16px">
							<input type="checkbox" name="active" value="1" <?php checked( $is_active ); ?>>
							Active
						</label>
					</td>
				</tr>
				<?php if ( $id ) : ?>
					<tr>
						<th scope="row">Skipped</th>
						<td>
							<strong><?php echo esc_html( number_format_i18n( (int) ( $question->skip_count ?? 0 ) ) ); ?></strong>
							<p class="description">How many times participants have skipped this stimulus.</p>
						</td>
					</tr>
				<?php endif; ?>
			</table>
			<?php if ( count( Diastoles_I18n::active_languages() ) > 1 ) : ?>
				<h3>Manual translation — <?php echo esc_html( $translation_language['name'] ); ?></h3>
				<p class="description">Empty fields fall back to the English source. Participant answers may still be written in any language.</p>
				<table class="form-table" role="presentation" style="border-top:1px solid #dcdcde">
					<tr><th>Question</th><td><textarea class="large-text" rows="2" maxlength="1000" name="translations[<?php echo esc_attr( $translation_locale ); ?>][prompt]"><?php echo esc_textarea( $translation['prompt'] ?? '' ); ?></textarea></td></tr>
					<tr><th>Optional follow-up</th><td><textarea class="large-text" rows="2" maxlength="1000" name="translations[<?php echo esc_attr( $translation_locale ); ?>][followup]"><?php echo esc_textarea( $translation['followup'] ?? '' ); ?></textarea></td></tr>
					<tr><th>Short map label</th><td><input class="large-text" maxlength="160" name="translations[<?php echo esc_attr( $translation_locale ); ?>][short_label]" value="<?php echo esc_attr( $translation['short_label'] ?? '' ); ?>"></td></tr>
				</table>
			<?php endif; ?>
			<?php submit_button( $id ? 'Save changes' : 'Add question', 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	public static function render_settings(): void {
		self::require_admin();
		$has_key = Diastoles_Anthropic::has_api_key();
		$test     = sanitize_key( wp_unslash( $_GET['diastoles_test'] ?? '' ) );
		?>
		<div class="wrap">
			<h1>Diastoles AI settings</h1>
			<?php if ( 'success' === $test ) : ?>
				<div class="notice notice-success inline"><p>The configured AI mode responded successfully.</p></div>
			<?php elseif ( 'failed' === $test ) : ?>
				<div class="notice notice-error inline"><p>The connection test failed. Check the WordPress debug log for details.</p></div>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'diastoles_ai' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="diastoles_ai_mode">Mode</label></th>
						<td>
							<select id="diastoles_ai_mode" name="diastoles_ai_mode">
								<option value="mock" <?php selected( Diastoles_Anthropic::mode(), 'mock' ); ?>>Mock — no external calls</option>
								<option value="live" <?php selected( Diastoles_Anthropic::mode(), 'live' ); ?>>Live — Anthropic API</option>
							</select>
							<p class="description">Use Mock while testing the complete local flow without API costs.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="diastoles_processing_model">Routine processing model</label></th>
						<td>
							<select id="diastoles_processing_model" name="diastoles_processing_model">
								<?php foreach ( Diastoles_Anthropic::model_choices() as $model_id => $label ) : ?>
									<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( Diastoles_Anthropic::processing_model(), $model_id ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								Recommended: Claude Haiku 4.5. Used for routine work: participant-response translations,
								source-language detection, scent concepts, matices, semantic fields and basic response analysis.
								Translations are cached per response and language, so changing this mainly affects missing future translations or manual recalculations.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="diastoles_connection_model">Connection ranking model</label></th>
						<td>
							<select id="diastoles_connection_model" name="diastoles_connection_model">
								<?php foreach ( Diastoles_Anthropic::model_choices() as $model_id => $label ) : ?>
									<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( Diastoles_Anthropic::connection_model(), $model_id ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								Recommended: Claude Sonnet 5. Used only for the final relationship decision between shortlisted candidates:
								affinity, tension, complementarity or continuity, with evidence from the original human words.
								Claude Opus 4.8 is available as a higher-cost option but is not selected by default.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Embedding model</th>
						<td>
							<p><code>Disabled for now</code></p>
							<p class="description">
								Embeddings are not active in this version. When enabled later, this setting will choose the model
								that turns each response into a semantic vector for cheaper historical candidate search before the final AI ranking.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="diastoles_connection_auto_approve_threshold">Automatic approval threshold</label>
						</th>
						<td>
							<input
								id="diastoles_connection_auto_approve_threshold"
								name="diastoles_connection_auto_approve_threshold"
								type="number"
								min="0"
								max="1"
								step="0.01"
								value="<?php echo esc_attr( Diastoles_Matcher::auto_approve_threshold() ); ?>"
							>
							<p class="description">
								Connections with an AI score equal to or above this value are immediately visible
								to both participants. Scores below this value are handled according to the manual
								approval threshold below.
								Default: 0.50.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="diastoles_connection_manual_approval_threshold">Manual approval threshold</label>
						</th>
						<td>
							<input
								id="diastoles_connection_manual_approval_threshold"
								name="diastoles_connection_manual_approval_threshold"
								type="number"
								min="0"
								max="1"
								step="0.01"
								value="<?php echo esc_attr( Diastoles_Matcher::manual_approval_threshold() ); ?>"
							>
							<p class="description">
								Connections with an AI score equal to or above this value, but below the automatic
								threshold, appear in Connections for manual approval. Lower scores remain stored
								outside the queue and can reappear if this threshold is lowered. This value cannot
								exceed the automatic threshold. Current default: 0.00.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="diastoles_api_key_input">Anthropic API key</label></th>
						<td>
							<input class="regular-text" id="diastoles_api_key_input" name="diastoles_api_key_input"
								type="password" autocomplete="new-password" value="">
							<p class="description">
								<?php echo $has_key ? 'A key is configured. Leave blank to keep it.' : 'No key is configured.'; ?>
								The key is encrypted before it is stored.
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="diastoles_test_anthropic">
				<?php wp_nonce_field( 'diastoles_test_anthropic' ); ?>
				<?php submit_button( 'Test connection', 'secondary', 'submit', false ); ?>
			</form>
			<p>For stricter hosting setups, define <code>DIASTOLES_ANTHROPIC_API_KEY</code> in server configuration instead.</p>
		</div>
		<?php
	}

	private static function matching_presets(): array {
		return array(
			'default' => array(
				'name'        => 'Balanced default',
				'summary'     => 'Good general-purpose balance between obvious affinities, semantic breadth and cost control.',
				'explanation' => 'Use this for live events when you want dependable matches without making the map too strange. It keeps 12 candidates for the final AI call and gives exact matices the strongest signal.',
				'values'      => array(
					'diastoles_matching_candidate_history_limit'           => 200,
					'diastoles_matching_ai_candidate_limit'                => 12,
					'diastoles_matching_max_connections'                   => 3,
					'diastoles_matching_local_semantic_vector_enabled'     => 1,
					'diastoles_matching_local_vector_weight'               => 6,
					'diastoles_matching_shared_nuance_weight'              => 8,
					'diastoles_matching_shared_context_weight'             => 5,
					'diastoles_matching_shared_semantic_field_weight'      => 3,
					'diastoles_matching_shared_emotional_tone_weight'      => 3,
					'diastoles_matching_profile_match_weight'              => 1,
					'diastoles_matching_profile_diversity_weight'          => 1,
					'diastoles_matching_same_theme_adjustment'             => 0.12,
					'diastoles_matching_different_theme_adjustment'        => -0.07,
				),
			),
			'conservative' => array(
				'name'        => 'Conservative / clear affinities',
				'summary'     => 'Prioritises safer, more legible connections and keeps AI cost slightly lower.',
				'explanation' => 'Use this if moderation is overloaded or the map feels too speculative. It sends 9 candidates to the final AI call, strengthens exact matices and reduces the influence of looser poetic signals.',
				'values'      => array(
					'diastoles_matching_candidate_history_limit'           => 150,
					'diastoles_matching_ai_candidate_limit'                => 9,
					'diastoles_matching_max_connections'                   => 2,
					'diastoles_matching_local_semantic_vector_enabled'     => 1,
					'diastoles_matching_local_vector_weight'               => 4,
					'diastoles_matching_shared_nuance_weight'              => 10,
					'diastoles_matching_shared_context_weight'             => 4,
					'diastoles_matching_shared_semantic_field_weight'      => 2,
					'diastoles_matching_shared_emotional_tone_weight'      => 2,
					'diastoles_matching_profile_match_weight'              => 0.5,
					'diastoles_matching_profile_diversity_weight'          => 0.5,
					'diastoles_matching_same_theme_adjustment'             => 0.15,
					'diastoles_matching_different_theme_adjustment'        => -0.12,
				),
			),
			'poetic' => array(
				'name'        => 'Strange & poetic',
				'summary'     => 'Increases the chance of rarer, more oblique connections across emotional tone, fields and distance.',
				'explanation' => 'Use this when you want the map to feel less literal: fewer smoke-smoke matches, more climate, tension and unexpected kinship. It does not add AI calls beyond the 12-candidate default, but it may send more unusual candidates to the final model.',
				'values'      => array(
					'diastoles_matching_candidate_history_limit'           => 300,
					'diastoles_matching_ai_candidate_limit'                => 12,
					'diastoles_matching_max_connections'                   => 3,
					'diastoles_matching_local_semantic_vector_enabled'     => 1,
					'diastoles_matching_local_vector_weight'               => 9,
					'diastoles_matching_shared_nuance_weight'              => 5,
					'diastoles_matching_shared_context_weight'             => 5,
					'diastoles_matching_shared_semantic_field_weight'      => 4,
					'diastoles_matching_shared_emotional_tone_weight'      => 5,
					'diastoles_matching_profile_match_weight'              => 1,
					'diastoles_matching_profile_diversity_weight'          => 2,
					'diastoles_matching_same_theme_adjustment'             => 0.08,
					'diastoles_matching_different_theme_adjustment'        => -0.02,
				),
			),
		);
	}

	private static function matching_setting_rows(): array {
		return array(
			'Candidate search' => array(
				'diastoles_matching_candidate_history_limit' => array(
					'label' => 'Candidate history limit',
					'value' => Diastoles_Matcher::candidate_history_limit(),
					'min' => 20,
					'max' => 1000,
					'step' => 1,
					'default' => 200,
					'what' => 'How many eligible historical responses are considered before the local prefilter chooses the strongest candidates.',
					'example' => 'If someone writes “chlorine at a municipal pool”, a higher limit gives the system more chances to find an older “hospital disinfectant” or “freshly mopped stairs” response.',
					'result' => 'Higher values increase historical recall and reduce recency bias. Lower values make matches more recent and predictable.',
					'cost' => 'No direct AI token cost. It is local SQL/PHP work. Very high values may make recalculation slower.',
				),
				'diastoles_matching_ai_candidate_limit' => array(
					'label' => 'Candidates sent to final AI',
					'value' => Diastoles_Matcher::ai_candidate_limit(),
					'min' => 4,
					'max' => 40,
					'step' => 1,
					'default' => 12,
					'what' => 'How many shortlisted candidates are sent to the connection ranking model.',
					'example' => '9 is leaner; 12 gives the AI more room to find a less obvious resonance; 20 can be richer but more expensive.',
					'result' => 'Higher values may find more unusual or subtle relationships. Lower values keep only the strongest local candidates.',
					'cost' => 'Direct AI cost impact. Reducing 12 to 9 usually cuts final-ranking input tokens by about 20–25%.',
				),
				'diastoles_matching_max_connections' => array(
					'label' => 'Max connections per response',
					'value' => Diastoles_Matcher::max_connections_per_response(),
					'min' => 1,
					'max' => 10,
					'step' => 1,
					'default' => 3,
					'what' => 'Maximum saved connections created from one newly processed response.',
					'example' => 'A response about “BBQ smoke” might connect to summer, fire and meat, but this setting limits how many links are actually saved.',
					'result' => 'Higher values make a denser map. Lower values keep the experience quieter.',
					'cost' => 'No meaningful extra AI cost after ranking; it mainly changes how many returned links are stored and shown.',
				),
			),
			'Local semantic vector' => array(
				'diastoles_matching_local_vector_weight' => array(
					'label' => 'Local vector weight',
					'value' => Diastoles_Matcher::local_vector_weight(),
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
					'default' => 6,
					'what' => 'Adds points from a cheap local cosine similarity built from matices, fields, emotional tones and context.',
					'example' => '“Wet pavement after summer rain” and “olor a tierra mojada” may score close because their local vectors share earth/weather/context signals.',
					'result' => 'Higher values favour broader semantic kinship. Lower values rely more on exact overlaps.',
					'cost' => 'No external AI cost. It uses analysis already stored for each response.',
				),
			),
			'Prefilter weights' => array(
				'diastoles_matching_shared_nuance_weight' => array(
					'label' => 'Shared nuance / matiz',
					'value' => Diastoles_Matcher::shared_nuance_weight(),
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
					'default' => 8,
					'what' => 'Weight for exact shared scent terms or anchors.',
					'example' => '“smoke” ↔ “smoke”, “coffee” ↔ “coffee”, “earth” ↔ “wet earth”.',
					'result' => 'Higher values produce clearer, more literal affinities. Lower values leave more room for strange or emotional matches.',
					'cost' => 'No direct AI cost, but it influences which candidates consume final-ranking tokens.',
				),
				'diastoles_matching_shared_context_weight' => array(
					'label' => 'Shared context',
					'value' => Diastoles_Matcher::shared_context_weight(),
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
					'default' => 5,
					'what' => 'Weight for supported states, places or relationship labels that appear with evidence in the participant text.',
					'example' => 'Two different smells may both carry “home”, “departure”, “childhood” or “waiting”.',
					'result' => 'Higher values make the map more narrative and less material-only.',
					'cost' => 'No direct AI cost; may shift final-ranking candidates toward contextual links.',
				),
				'diastoles_matching_shared_semantic_field_weight' => array(
					'label' => 'Shared semantic field',
					'value' => Diastoles_Matcher::shared_semantic_field_weight(),
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
					'default' => 3,
					'what' => 'Weight for broad scent families such as plant, food, smoke and fire, body, cleaning product or water and weather.',
					'example' => '“jasmine” and “lavender” share plant; “ammonia” and “bleach” share cleaning product / synthetic.',
					'result' => 'Higher values create family resemblances. Lower values demand more specific overlaps.',
					'cost' => 'No direct AI cost.',
				),
				'diastoles_matching_shared_emotional_tone_weight' => array(
					'label' => 'Shared emotional tone',
					'value' => Diastoles_Matcher::shared_emotional_tone_weight(),
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
					'default' => 3,
					'what' => 'Weight for tones such as nostalgia, longing, calm, fear, tenderness, discomfort, grief or wonder.',
					'example' => '“sun-warmed sheets” and “coffee before leaving home” may both carry tenderness or nostalgia without sharing an aroma.',
					'result' => 'Higher values increase poetic/affective connections. Too high may make matches feel less grounded in smell.',
					'cost' => 'No extra AI calls, but emotional tones must exist in analysis; older responses may need recalculation to benefit.',
				),
				'diastoles_matching_profile_match_weight' => array(
					'label' => 'Profile match',
					'value' => Diastoles_Matcher::profile_match_weight(),
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
					'default' => 1,
					'what' => 'Weight for optional profile coordinates that match, when participants provided them for matching.',
					'example' => 'Same country, same native language, same environment or similar work/hobby coordinates.',
					'result' => 'Higher values make social similarity more influential. Lower values keeps matching focused on text.',
					'cost' => 'No AI cost.',
				),
				'diastoles_matching_profile_diversity_weight' => array(
					'label' => 'Profile diversity max bonus',
					'value' => Diastoles_Matcher::profile_diversity_weight(),
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
					'default' => 1,
					'what' => 'Bonus for different optional profile coordinates, to avoid the map collapsing into only similar people.',
					'example' => 'Same scent atmosphere, but one person is rural and another urban, or from different continents.',
					'result' => 'Higher values increase cross-background resonances. Lower values makes profile diversity less relevant.',
					'cost' => 'No AI cost.',
				),
			),
			'Final score theme adjustments' => array(
				'diastoles_matching_same_theme_adjustment' => array(
					'label' => 'Same theme adjustment',
					'value' => Diastoles_Matcher::same_theme_adjustment(),
					'min' => -1,
					'max' => 1,
					'step' => 0.01,
					'default' => 0.12,
					'what' => 'Added to the final AI semantic score when both responses come from questions with the same theme.',
					'example' => 'Two answers from “distance” get a small boost after the AI score.',
					'result' => 'Higher values cluster the map by theme. Lower values lets cross-theme links survive more easily.',
					'cost' => 'No AI cost.',
				),
				'diastoles_matching_different_theme_adjustment' => array(
					'label' => 'Different theme adjustment',
					'value' => Diastoles_Matcher::different_theme_adjustment(),
					'min' => -1,
					'max' => 1,
					'step' => 0.01,
					'default' => -0.07,
					'what' => 'Added to the final AI semantic score when responses come from different question themes.',
					'example' => 'A “future” answer and a “home” answer receive this adjustment after ranking.',
					'result' => 'Less negative values create more cross-theme bridges. More negative values keep neighbourhoods separated.',
					'cost' => 'No AI cost.',
				),
			),
		);
	}

	public static function render_matching_settings(): void {
		self::require_admin();
		$tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'settings' ) );
		$tab = in_array( $tab, array( 'settings', 'presets' ), true ) ? $tab : 'settings';
		$applied_preset = sanitize_key( wp_unslash( $_GET['preset_applied'] ?? '' ) );
		?>
		<div class="wrap">
			<h1>Diastoles Matching settings</h1>
			<p>
				These settings control how Diástoles chooses candidate responses before the final AI ranking.
				The final relationship decision still happens in <a href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-ai' ) ); ?>">AI settings</a>
				using the configured connection ranking model.
			</p>
			<?php if ( $applied_preset ) : ?>
				<div class="notice notice-success inline"><p>Preset applied. Future responses and recalculations will use these values.</p></div>
			<?php endif; ?>
			<nav class="nav-tab-wrapper" aria-label="Matching settings sections">
				<a class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-matching&tab=settings' ) ); ?>">Settings</a>
				<a class="nav-tab <?php echo 'presets' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=diastoles-matching&tab=presets' ) ); ?>">Presets</a>
			</nav>
			<?php if ( 'presets' === $tab ) : ?>
				<h2>Presets</h2>
				<p>Presets are starting points. They change the numeric settings, but they do not recalculate old responses by themselves.</p>
				<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;max-width:1100px;">
					<?php foreach ( self::matching_presets() as $preset_id => $preset ) : ?>
						<div class="card" style="max-width:none;">
							<h2><?php echo esc_html( $preset['name'] ); ?></h2>
							<p><strong><?php echo esc_html( $preset['summary'] ); ?></strong></p>
							<p><?php echo esc_html( $preset['explanation'] ); ?></p>
							<ul style="margin-left:1.2em;list-style:disc;">
								<li>History: <?php echo esc_html( $preset['values']['diastoles_matching_candidate_history_limit'] ); ?></li>
								<li>Final AI candidates: <?php echo esc_html( $preset['values']['diastoles_matching_ai_candidate_limit'] ); ?></li>
								<li>Max connections: <?php echo esc_html( $preset['values']['diastoles_matching_max_connections'] ); ?></li>
								<li>Matiz weight: <?php echo esc_html( $preset['values']['diastoles_matching_shared_nuance_weight'] ); ?></li>
								<li>Emotional tone weight: <?php echo esc_html( $preset['values']['diastoles_matching_shared_emotional_tone_weight'] ); ?></li>
								<li>Local vector weight: <?php echo esc_html( $preset['values']['diastoles_matching_local_vector_weight'] ); ?></li>
							</ul>
							<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
								<input type="hidden" name="action" value="diastoles_apply_matching_preset">
								<input type="hidden" name="preset" value="<?php echo esc_attr( $preset_id ); ?>">
								<?php wp_nonce_field( 'diastoles_apply_matching_preset_' . $preset_id ); ?>
								<?php submit_button( 'Apply preset', 'secondary', 'submit', false ); ?>
							</form>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<form action="options.php" method="post">
					<?php settings_fields( 'diastoles_matching' ); ?>
					<?php foreach ( self::matching_setting_rows() as $section => $rows ) : ?>
						<h2><?php echo esc_html( $section ); ?></h2>
						<?php if ( 'Local semantic vector' === $section ) : ?>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row">Use local semantic vector</th>
									<td>
										<label>
											<input type="hidden" name="diastoles_matching_local_semantic_vector_enabled" value="0">
											<input type="checkbox" name="diastoles_matching_local_semantic_vector_enabled" value="1" <?php checked( Diastoles_Matcher::local_semantic_vector_enabled() ); ?>>
											Use a local vector built from matices, semantic fields, emotional tones and supported context.
										</label>
										<p class="description">Example: “wet pavement after summer rain” and “olor a tierra mojada” can be considered close because their local vectors share earth/weather/context signals.</p>
										<p class="description"><strong>Impact:</strong> increases broader semantic kinship without external embeddings. <strong>AI cost:</strong> none; it uses data already extracted during routine processing.</p>
									</td>
								</tr>
							</table>
						<?php endif; ?>
						<table class="form-table" role="presentation">
							<?php foreach ( $rows as $name => $row ) : ?>
								<tr>
									<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $row['label'] ); ?></label></th>
									<td>
										<input id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" type="number" min="<?php echo esc_attr( $row['min'] ); ?>" max="<?php echo esc_attr( $row['max'] ); ?>" step="<?php echo esc_attr( $row['step'] ); ?>" value="<?php echo esc_attr( $row['value'] ); ?>">
										<p class="description"><strong>Default:</strong> <?php echo esc_html( $row['default'] ); ?></p>
										<p class="description"><strong>What it does:</strong> <?php echo esc_html( $row['what'] ); ?></p>
										<p class="description"><strong>Example:</strong> <?php echo esc_html( $row['example'] ); ?></p>
										<p class="description"><strong>Impact on result:</strong> <?php echo esc_html( $row['result'] ); ?></p>
										<p class="description"><strong>Impact on AI cost:</strong> <?php echo esc_html( $row['cost'] ); ?></p>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					<?php endforeach; ?>

					<p class="description">
						Selection reasons are stored inside each created connection's <code>explanation_json.selection_context</code>,
						including selection score, bucket and contributing signals.
					</p>
					<?php submit_button( 'Save matching settings' ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function apply_matching_preset(): void {
		self::require_admin();
		$preset_id = sanitize_key( wp_unslash( $_POST['preset'] ?? '' ) );
		$presets   = self::matching_presets();
		if ( ! isset( $presets[ $preset_id ] ) ) {
			wp_die( esc_html__( 'Invalid matching preset.', 'diastoles' ) );
		}
		check_admin_referer( 'diastoles_apply_matching_preset_' . $preset_id );
		foreach ( $presets[ $preset_id ]['values'] as $name => $value ) {
			update_option( $name, $value, false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=diastoles-matching&tab=presets&preset_applied=' . rawurlencode( $preset_id ) ) );
		exit;
	}

	public static function moderate_connection(): void {
		self::require_admin();
		$public_id = sanitize_text_field( wp_unslash( $_POST['connection_id'] ?? '' ) );
		check_admin_referer( 'diastoles_moderate_' . $public_id );
		$decision = sanitize_key( wp_unslash( $_POST['decision'] ?? '' ) );
		self::update_connection_moderation( $public_id, $decision );

		$return_status = sanitize_key( wp_unslash( $_POST['return_status'] ?? 'pending' ) );
		if ( ! in_array( $return_status, array( 'pending', 'approved', 'rejected', 'held' ), true ) ) {
			$return_status = 'pending';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'diastoles-connections',
					'status' => $return_status,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function moderate_connection_ajax(): void {
		self::require_admin();
		$public_id = sanitize_text_field( wp_unslash( $_POST['connection_id'] ?? '' ) );
		check_admin_referer( 'diastoles_moderate_' . $public_id );
		$decision = sanitize_key( wp_unslash( $_POST['decision'] ?? '' ) );
		$status   = self::update_connection_moderation( $public_id, $decision );

		wp_send_json_success(
			array(
				'status' => $status,
			)
		);
	}

	private static function update_connection_moderation( string $public_id, string $decision ): string {
		if ( ! in_array( $decision, array( 'approve', 'reject' ), true ) ) {
			wp_die( esc_html__( 'Invalid moderation decision.', 'diastoles' ) );
		}

		$status = 'approve' === $decision ? 'approved' : 'rejected';

		global $wpdb;
		$updated = $wpdb->update(
			Diastoles_DB::table( 'connections' ),
			array(
				'status'       => $status,
				'moderated_at' => current_time( 'mysql', true ),
			),
			array( 'public_id' => $public_id ),
			array( '%s', '%s' ),
			array( '%s' )
		);

		if ( false === $updated ) {
			wp_die( esc_html__( 'Could not update the connection.', 'diastoles' ) );
		}

		return $status;
	}

	public static function moderate_response(): void {
		self::require_admin();
		$response_id = absint( $_POST['response_id'] ?? 0 );
		check_admin_referer( 'diastoles_moderate_response_' . $response_id );
		$decision = sanitize_key( wp_unslash( $_POST['decision'] ?? '' ) );
		if ( ! in_array( $decision, array( 'approve', 'withdraw' ), true ) ) {
			wp_die( esc_html__( 'Invalid moderation decision.', 'diastoles' ) );
		}

		global $wpdb;
		$responses = Diastoles_DB::table( 'responses' );
		if ( 'withdraw' === $decision ) {
			$wpdb->update(
				$responses,
				array( 'withdrawn' => 1, 'allow_network' => 0, 'processing_status' => 'withdrawn' ),
				array( 'id' => $response_id ),
				array( '%d', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->update(
				$responses,
				array( 'withdrawn' => 0, 'allow_network' => 1, 'processing_status' => 'pending' ),
				array( 'id' => $response_id ),
				array( '%d', '%d', '%s' ),
				array( '%d' )
			);
			do_action( 'diastoles_process_response', $response_id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=diastoles-responses&response_id=' . $response_id ) );
		exit;
	}

	public static function save_response_translation(): void {
		self::require_admin();
		$response_id = absint( $_POST['response_id'] ?? 0 );
		$locale      = Diastoles_I18n::normalize( $_POST['locale'] ?? '' );
		$source      = sanitize_key( wp_unslash( $_POST['source'] ?? 'response' ) );
		$source      = in_array( $source, array( 'response', 'explanation' ), true ) ? $source : 'response';
		check_admin_referer( 'diastoles_save_response_translation_' . $response_id . '_' . $locale . '_' . $source );
		if ( ! $locale ) {
			wp_die( esc_html__( 'Invalid language.', 'diastoles' ) );
		}

		$response = Diastoles_DB::get_response( $response_id );
		if ( ! $response ) {
			wp_die( esc_html__( 'Response not found.', 'diastoles' ) );
		}
		$text = sanitize_textarea_field( (string) wp_unslash( $_POST['translated_text'] ?? '' ) );

		global $wpdb;
		if ( 'response' === $source && 'en' === $locale ) {
			$wpdb->update( Diastoles_DB::table( 'responses' ), array( 'translation_en' => $text ), array( 'id' => $response_id ), array( '%s' ), array( '%d' ) );
		} else {
			$source_text = 'explanation' === $source ? (string) $response->explanation_text : (string) $response->original_text;
			if ( '' === trim( $source_text ) ) {
				wp_die( esc_html__( 'There is no source text to translate.', 'diastoles' ) );
			}
			$table = Diastoles_DB::table( 'dynamic_translations' );
			$hash  = hash( 'sha256', $source_text );
			$wpdb->replace(
				$table,
				array(
					'source_hash'     => $hash,
					'language_code'   => $locale,
					'source_text'     => $source_text,
					'translated_text' => $text,
					'status'          => '' === trim( $text ) ? 'pending' : 'complete',
					'updated_at'      => current_time( 'mysql', true ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=diastoles-responses&response_id=' . $response_id ) );
		exit;
	}

	public static function save_dynamic_translation(): void {
		self::require_admin();
		$translation_id = absint( $_POST['translation_id'] ?? 0 );
		check_admin_referer( 'diastoles_save_dynamic_translation_' . $translation_id );
		global $wpdb;
		$table = Diastoles_DB::table( 'dynamic_translations' );
		$text = sanitize_textarea_field( (string) wp_unslash( $_POST['translated_text'] ?? '' ) );
		$wpdb->update(
			$table,
			array(
				'translated_text' => $text,
				'status'          => '' === trim( $text ) ? 'pending' : 'complete',
				'updated_at'      => current_time( 'mysql', true ),
			),
			array( 'id' => $translation_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		wp_safe_redirect( add_query_arg( 'saved', '1', wp_get_referer() ?: admin_url( 'admin.php?page=diastoles-dynamic-translations' ) ) );
		exit;
	}

	public static function retry_dynamic_translations(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_retry_dynamic_translations' );
		global $wpdb;
		$table = Diastoles_DB::table( 'dynamic_translations' );
		$language = Diastoles_I18n::normalize( $_POST['language'] ?? '' );
		$status = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
		$kind = sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) );
		$search = sanitize_text_field( wp_unslash( $_POST['s'] ?? '' ) );
		$where = array( "status IN ('pending','failed')" );
		$params = array();
		if ( $language ) {
			$where[] = 'language_code = %s';
			$params[] = $language;
		}
		if ( in_array( $status, array( 'pending', 'failed' ), true ) ) {
			$where[] = 'status = %s';
			$params[] = $status;
		}
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(source_text LIKE %s OR translated_text LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}
		$sql = 'SELECT id, source_hash, language_code, source_text FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC LIMIT 500';
		$rows = $wpdb->get_results( $params ? $wpdb->prepare( $sql, ...$params ) : $sql );
		$kinds = self::dynamic_translation_kind_index();
		$count = 0;
		foreach ( $rows as $row ) {
			$row_kind = $kinds[ $row->source_hash ] ?? self::infer_dynamic_translation_kind( (string) $row->source_text );
			if ( '' !== $kind && $row_kind !== $kind ) {
				continue;
			}
			$wpdb->update( $table, array( 'status' => 'pending', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $row->id ), array( '%s', '%s' ), array( '%d' ) );
			if ( ! wp_next_scheduled( 'diastoles_translate_dynamic_text', array( (string) $row->source_hash, (string) $row->language_code ) ) ) {
				wp_schedule_single_event( time() + 1, 'diastoles_translate_dynamic_text', array( (string) $row->source_hash, (string) $row->language_code ) );
			}
			$count++;
		}
		wp_safe_redirect( add_query_arg( 'retried', $count, wp_get_referer() ?: admin_url( 'admin.php?page=diastoles-dynamic-translations' ) ) );
		exit;
	}

	public static function retry_response_translations(): void {
		self::require_admin();
		$response_id = absint( $_POST['response_id'] ?? 0 );
		check_admin_referer( 'diastoles_retry_response_translations_' . $response_id );
		$response = Diastoles_DB::get_response( $response_id );
		if ( $response ) {
			Diastoles_I18n::pretranslate_response( $response, json_decode( (string) $response->analysis_json, true ) ?: array() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=diastoles-responses&response_id=' . $response_id ) );
		exit;
	}

	public static function retry_response_processing(): void {
		self::require_admin();
		$response_id = absint( $_POST['response_id'] ?? 0 );
		check_admin_referer( 'diastoles_retry_response_processing_' . $response_id );
		$response = Diastoles_DB::get_response( $response_id );
		if ( $response && ! (int) $response->withdrawn ) {
			global $wpdb;
			Diastoles_DB::record_processing_event( $response_id, 'retry', 'Manual retry requested from the WordPress admin.' );
			$wpdb->update(
				Diastoles_DB::table( 'responses' ),
				array(
					'processing_status' => 'pending',
					'allow_network'     => 1,
				),
				array( 'id' => $response_id ),
				array( '%s', '%d' ),
				array( '%d' )
			);
			do_action( 'diastoles_process_response', $response_id );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=diastoles-responses&response_id=' . $response_id ) );
		exit;
	}

	public static function test_anthropic(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_test_anthropic' );
		$result = Diastoles_Anthropic::test_connection();
		if ( is_wp_error( $result ) ) {
			error_log( 'Diastoles Anthropic test: ' . $result->get_error_message() );
		}
		$url = add_query_arg(
			'diastoles_test',
			is_wp_error( $result ) ? 'failed' : 'success',
			admin_url( 'admin.php?page=diastoles-ai' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function save_question(): void {
		self::require_admin();

		$id = absint( $_POST['question_id'] ?? 0 );
		check_admin_referer( 'diastoles_save_question_' . $id );

		$prompt      = mb_substr( sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) ), 0, 1000 );
		$followup    = mb_substr( sanitize_textarea_field( wp_unslash( $_POST['followup'] ?? '' ) ), 0, 1000 );
		$short_label = mb_substr( sanitize_text_field( wp_unslash( $_POST['short_label'] ?? '' ) ), 0, 160 );
		$theme       = mb_strtolower( sanitize_text_field( wp_unslash( $_POST['theme'] ?? 'memory' ) ) );
		$theme       = mb_substr( trim( preg_replace( '/\s+/u', ' ', $theme ) ?: $theme ), 0, 60 );
		$intensity   = min( 3, max( 1, absint( $_POST['intensity'] ?? 1 ) ) );
		$active      = isset( $_POST['active'] ) ? 1 : 0;
		$translation_locale = Diastoles_I18n::normalize( $_POST['translation_locale'] ?? 'es' ) ?: 'es';

		if ( '' === trim( $prompt ) ) {
			wp_die( esc_html__( 'The question cannot be empty.', 'diastoles' ) );
		}

		global $wpdb;
		$table = Diastoles_DB::table( 'questions' );
		$data  = array(
			'prompt'      => $prompt,
			'short_label' => $short_label,
			'followup'    => $followup,
			'theme'       => $theme ?: 'memory',
			'intensity'   => $intensity,
			'active'      => $active,
		);

		if ( $id ) {
			$wpdb->update(
				$table,
				$data,
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%s', '%d', '%d' ),
				array( '%d' )
			);
		} else {
			$data['created_at'] = current_time( 'mysql', true );
			$wpdb->insert(
				$table,
				$data,
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
			);
			$id = (int) $wpdb->insert_id;
		}
		Diastoles_I18n::save_question_translations( $id, (array) ( $_POST['translations'] ?? array() ) );
		Diastoles_I18n::pretranslate_question( $id, (object) array(
			'prompt'      => $prompt,
			'short_label' => $short_label,
			'followup'    => $followup,
		) );

		wp_safe_redirect(
			add_query_arg(
				array( 'saved' => '1', 'tab' => 'library', 'theme' => $theme, 'lang' => $translation_locale ),
				admin_url( 'admin.php?page=diastoles-questions' )
			)
		);
		exit;
	}

	public static function export_questions(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_export_questions' );

		global $wpdb;
		$table = Diastoles_DB::table( 'questions' );
		$rows  = $wpdb->get_results(
			"SELECT id, prompt, short_label, followup, theme, intensity, active, skip_count, created_at FROM $table ORDER BY id",
			ARRAY_A
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="diastoles-questions-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Could not create the questions export.', 'diastoles' ) );
		}
		fwrite( $output, "\xEF\xBB\xBF" );
		$header = array( 'id', 'prompt', 'short_label', 'followup', 'theme', 'intensity', 'active', 'skip_count', 'created_at' );
		foreach ( Diastoles_I18n::active_languages() as $locale => $_language ) {
			if ( 'en' !== $locale ) {
				array_push( $header, 'prompt_' . $locale, 'short_label_' . $locale, 'followup_' . $locale );
			}
		}
		fputcsv( $output, $header, ',', '"', '' );
		foreach ( $rows as $row ) {
			$values = array_values( $row );
			foreach ( Diastoles_I18n::active_languages() as $locale => $_language ) {
				if ( 'en' === $locale ) continue;
				$translation = Diastoles_I18n::question_translation( (int) $row['id'], $locale );
				array_push( $values, $translation['prompt'], $translation['short_label'], $translation['followup'] );
			}
			fputcsv( $output, $values, ',', '"', '' );
		}
		fclose( $output );
		exit;
	}

	public static function import_questions(): void {
		self::require_admin();
		$mode = sanitize_key( wp_unslash( $_POST['import_mode'] ?? '' ) );
		if ( ! in_array( $mode, array( 'merge', 'replace' ), true ) ) {
			wp_die( esc_html__( 'Invalid question import mode.', 'diastoles' ), 'Question import', array( 'back_link' => true ) );
		}
		check_admin_referer( 'diastoles_import_questions_' . $mode );
		if ( 'replace' === $mode && empty( $_POST['confirm_replace'] ) ) {
			wp_die( esc_html__( 'Confirm that you want to replace the question library.', 'diastoles' ), 'Question import', array( 'back_link' => true ) );
		}

		$upload = $_FILES['questions_csv'] ?? null;
		if ( ! is_array( $upload ) || UPLOAD_ERR_OK !== (int) ( $upload['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			wp_die( esc_html__( 'Choose a CSV file to import.', 'diastoles' ), 'Question import', array( 'back_link' => true ) );
		}
		if ( (int) ( $upload['size'] ?? 0 ) > 5 * MB_IN_BYTES ) {
			wp_die( esc_html__( 'The questions CSV cannot be larger than 5 MB.', 'diastoles' ), 'Question import', array( 'back_link' => true ) );
		}

		$questions = self::read_questions_csv( (string) $upload['tmp_name'] );
		if ( is_wp_error( $questions ) ) {
			wp_die( esc_html( $questions->get_error_message() ), 'Question import', array( 'back_link' => true ) );
		}

		global $wpdb;
		$table     = Diastoles_DB::table( 'questions' );
		$responses = Diastoles_DB::table( 'responses' );
		$views     = Diastoles_DB::table( 'question_views' );
		$question_translations = Diastoles_DB::table( 'question_translations' );
		$added     = 0;
		$updated   = 0;
		$removed   = 0;
		$archived  = 0;
		$wpdb->query( 'START TRANSACTION' );

		if ( 'replace' === $mode ) {
			$imported_prompts = array_fill_keys( array_column( $questions, 'prompt' ), true );
			$existing_rows    = $wpdb->get_results( "SELECT id, prompt FROM $table ORDER BY id" );
			foreach ( $existing_rows as $existing ) {
				if ( isset( $imported_prompts[ (string) $existing->prompt ] ) ) {
					continue;
				}
				$has_responses = (bool) $wpdb->get_var(
					$wpdb->prepare( "SELECT 1 FROM $responses WHERE question_id = %d LIMIT 1", (int) $existing->id )
				);
				if ( $has_responses ) {
					$result = $wpdb->update( $table, array( 'active' => 0 ), array( 'id' => (int) $existing->id ), array( '%d' ), array( '%d' ) );
					$archived++;
				} else {
					$wpdb->delete( $views, array( 'question_id' => (int) $existing->id ), array( '%d' ) );
					$wpdb->delete( $question_translations, array( 'question_id' => (int) $existing->id ), array( '%d' ) );
					$result = $wpdb->delete( $table, array( 'id' => (int) $existing->id ), array( '%d' ) );
					$removed++;
				}
				if ( false === $result ) {
					$wpdb->query( 'ROLLBACK' );
					wp_die( esc_html__( 'The existing question library could not be replaced.', 'diastoles' ), 'Question import', array( 'back_link' => true ) );
				}
			}
		}

		$existing = array();
		foreach ( $wpdb->get_results( "SELECT id, prompt FROM $table ORDER BY id" ) as $row ) {
			$existing[ (string) $row->prompt ] = (int) $row->id;
		}
		foreach ( $questions as $question ) {
			$data = array(
				'prompt'      => $question['prompt'],
				'short_label' => $question['short_label'],
				'followup'    => $question['followup'],
				'theme'       => $question['theme'],
				'intensity'   => $question['intensity'],
				'active'      => $question['active'],
			);
			if ( isset( $existing[ $question['prompt'] ] ) ) {
				$question_id = $existing[ $question['prompt'] ];
				$result = $wpdb->update(
					$table,
					$data,
					array( 'id' => $existing[ $question['prompt'] ] ),
					array( '%s', '%s', '%s', '%s', '%d', '%d' ),
					array( '%d' )
				);
				$updated++;
			} else {
				$data['created_at'] = $question['created_at'];
				$result = $wpdb->insert(
					$table,
					$data,
					array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
				);
				$question_id = (int) $wpdb->insert_id;
				$added++;
			}
			if ( false === $result ) {
				$wpdb->query( 'ROLLBACK' );
				wp_die( esc_html__( 'A question could not be imported. No changes were saved.', 'diastoles' ), 'Question import', array( 'back_link' => true ) );
			}
			Diastoles_I18n::save_question_translations( $question_id, $question['translations'] ?? array() );
		}

		$wpdb->query( 'COMMIT' );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'               => 'diastoles-questions',
					'tab'                => 'import-export',
					'questions_imported' => '1',
					'imported'           => $added,
					'updated'            => $updated,
					'removed'            => $removed,
					'archived'           => $archived,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function read_questions_csv( string $path ): array|WP_Error {
		$handle = fopen( $path, 'r' );
		if ( false === $handle ) {
			return new WP_Error( 'questions_csv_open', 'The questions CSV could not be read.' );
		}
		$header = fgetcsv( $handle, null, ',', '"', '' );
		if ( ! is_array( $header ) ) {
			fclose( $handle );
			return new WP_Error( 'questions_csv_empty', 'The questions CSV is empty.' );
		}
		$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header[0] );
		$header    = array_map( static fn( mixed $value ): string => strtolower( trim( (string) $value ) ), $header );
		$columns   = array_flip( $header );
		$required  = array( 'id', 'prompt', 'short_label', 'followup', 'theme', 'intensity', 'active', 'created_at' );
		$missing   = array_values( array_diff( $required, $header ) );
		if ( $missing ) {
			fclose( $handle );
			return new WP_Error( 'questions_csv_columns', 'Missing CSV column(s): ' . implode( ', ', $missing ) . '.' );
		}

		$questions = array();
		$seen      = array();
		$line      = 1;
		while ( false !== ( $row = fgetcsv( $handle, null, ',', '"', '' ) ) ) {
			$line++;
			if ( 1 === count( $row ) && '' === trim( (string) $row[0] ) ) {
				continue;
			}
			$value = static fn( string $name ): string => (string) ( $row[ $columns[ $name ] ] ?? '' );
			$prompt = mb_substr( sanitize_textarea_field( $value( 'prompt' ) ), 0, 1000 );
			if ( '' === trim( $prompt ) ) {
				fclose( $handle );
				return new WP_Error( 'questions_csv_prompt', "Row $line has an empty prompt." );
			}
			if ( isset( $seen[ $prompt ] ) ) {
				fclose( $handle );
				return new WP_Error( 'questions_csv_duplicate', "Row $line repeats the prompt from row {$seen[$prompt]}." );
			}
			$intensity = (int) trim( $value( 'intensity' ) );
			$active    = trim( $value( 'active' ) );
			if ( ! in_array( $intensity, array( 1, 2, 3 ), true ) ) {
				fclose( $handle );
				return new WP_Error( 'questions_csv_intensity', "Row $line has an invalid intensity; use 1, 2 or 3." );
			}
			if ( ! in_array( $active, array( '0', '1' ), true ) ) {
				fclose( $handle );
				return new WP_Error( 'questions_csv_active', "Row $line has an invalid active value; use 1 or 0." );
			}
			$created_at = trim( $value( 'created_at' ) );
			if ( '' === $created_at ) {
				$created_at = current_time( 'mysql', true );
			} elseif ( false === strtotime( $created_at . ' UTC' ) ) {
				fclose( $handle );
				return new WP_Error( 'questions_csv_created', "Row $line has an invalid created_at value." );
			} else {
				$created_at = gmdate( 'Y-m-d H:i:s', strtotime( $created_at . ' UTC' ) );
			}

			$seen[ $prompt ] = $line;
			$questions[]     = array(
				'prompt'      => $prompt,
				'short_label' => mb_substr( sanitize_text_field( $value( 'short_label' ) ), 0, 160 ),
				'followup'    => mb_substr( sanitize_textarea_field( $value( 'followup' ) ), 0, 1000 ),
				'theme'       => mb_substr( mb_strtolower( sanitize_text_field( $value( 'theme' ) ) ) ?: 'memory', 0, 60 ),
				'intensity'   => $intensity,
				'active'      => (int) $active,
				'created_at'  => $created_at,
				'translations' => array_reduce(
					array_keys( Diastoles_I18n::active_languages() ),
					static function ( array $translations, string $locale ) use ( $value, $columns ): array {
						if ( 'en' !== $locale ) {
							$translations[ $locale ] = array(
								'prompt' => isset( $columns[ 'prompt_' . $locale ] ) ? $value( 'prompt_' . $locale ) : '',
								'short_label' => isset( $columns[ 'short_label_' . $locale ] ) ? $value( 'short_label_' . $locale ) : '',
								'followup' => isset( $columns[ 'followup_' . $locale ] ) ? $value( 'followup_' . $locale ) : '',
							);
						}
						return $translations;
					},
					array()
				),
			);
		}
		fclose( $handle );
		if ( ! $questions ) {
			return new WP_Error( 'questions_csv_rows', 'The questions CSV does not contain any question rows.' );
		}
		return $questions;
	}

	public static function export_data(): void {
		self::require_admin();
		check_admin_referer( 'diastoles_export' );

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'The server does not provide ZIP support.', 'diastoles' ) );
		}

		global $wpdb;
		$datasets = self::export_datasets();
		$temp     = wp_tempnam( 'diastoles-export' );
		if ( ! $temp ) {
			wp_die( esc_html__( 'Could not create the export.', 'diastoles' ) );
		}
		@unlink( $temp );
		wp_mkdir_p( $temp );

		foreach ( $datasets as $name => $rows ) {
			self::write_csv( $temp . '/' . $name . '.csv', $rows );
		}

		$network = Diastoles_REST::get_network()->get_data();
		$manifest = array(
			'product'       => 'Diástoles',
			'version'       => DIASTOLES_VERSION,
			'exported_at'   => gmdate( DATE_ATOM ),
			'privacy_note'  => 'Session and recovery hashes are intentionally excluded.',
			'files'         => array_merge(
				array_map( static fn( string $name ): string => $name . '.csv', array_keys( $datasets ) ),
				array( 'network.json', 'complete-export.json' )
			),
			'record_counts' => array_map( 'count', $datasets ),
		);
		file_put_contents( $temp . '/manifest.json', wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		file_put_contents( $temp . '/network.json', wp_json_encode( $network, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		file_put_contents(
			$temp . '/complete-export.json',
			wp_json_encode( array( 'manifest' => $manifest, 'datasets' => $datasets, 'network' => $network ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
		);

		$zip_path = $temp . '.zip';
		$zip      = new ZipArchive();
		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			self::remove_temp_export( $temp );
			wp_die( esc_html__( 'Could not create the ZIP archive.', 'diastoles' ) );
		}
		foreach ( glob( $temp . '/*' ) ?: array() as $file ) {
			$zip->addFile( $file, basename( $file ) );
		}
		$zip->close();

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="diastoles-export-' . gmdate( 'Y-m-d' ) . '.zip"' );
		header( 'Content-Length: ' . filesize( $zip_path ) );
		readfile( $zip_path );
		self::remove_temp_export( $temp );
		@unlink( $zip_path );
		exit;
	}

	private static function export_datasets(): array {
		global $wpdb;
		$p  = Diastoles_DB::table( 'participants' );
		$pr = Diastoles_DB::table( 'participant_profiles' );
		$q  = Diastoles_DB::table( 'questions' );
		$qt = Diastoles_DB::table( 'question_translations' );
		$qv = Diastoles_DB::table( 'question_views' );
		$r  = Diastoles_DB::table( 'responses' );
		$c  = Diastoles_DB::table( 'connections' );
		$e  = Diastoles_DB::table( 'processing_events' );
		$dt = Diastoles_DB::table( 'dynamic_translations' );

		return array(
			'participants' => $wpdb->get_results(
				"SELECT public_id, pseudonym, consent_fragments, created_at, last_seen_at FROM $p ORDER BY id",
				ARRAY_A
			),
			'profiles' => $wpdb->get_results(
				"SELECT p.public_id AS participant_public_id, pr.gender, pr.age_band, pr.environment, pr.country,
					pr.continent, pr.native_languages_json, pr.other_languages_json, pr.multilingual_status,
					pr.work_areas_json, pr.hobbies_json, pr.rooted_places_json, pr.use_for_matching,
					pr.allow_context_display, pr.updated_at
				FROM $pr pr INNER JOIN $p p ON p.id = pr.participant_id ORDER BY pr.id",
				ARRAY_A
			),
			'questions' => $wpdb->get_results( "SELECT id, prompt, short_label, followup, theme, intensity, active, skip_count, created_at FROM $q ORDER BY id", ARRAY_A ),
			'question_translations' => $wpdb->get_results(
				"SELECT question_id, language_code, prompt, short_label, followup, updated_at FROM $qt ORDER BY question_id, language_code",
				ARRAY_A
			),
			'question_views' => $wpdb->get_results(
				"SELECT p.public_id AS participant_public_id, qv.question_id, qv.status, qv.created_at
				FROM $qv qv INNER JOIN $p p ON p.id = qv.participant_id ORDER BY qv.id",
				ARRAY_A
			),
			'responses' => $wpdb->get_results(
				"SELECT r.public_id, p.public_id AS participant_public_id, r.question_id, r.original_text,
					r.explanation_text, r.source_language, r.translation_en, r.analysis_json,
					r.processing_status, r.allow_network, r.withdrawn, r.created_at
				FROM $r r INNER JOIN $p p ON p.id = r.participant_id ORDER BY r.id",
				ARRAY_A
			),
			'connections' => $wpdb->get_results(
				"SELECT c.public_id, ra.public_id AS response_a_public_id, rb.public_id AS response_b_public_id,
					pa.public_id AS participant_a_public_id, pb.public_id AS participant_b_public_id,
					c.relation_type, c.score, c.explanation_json, c.status,
					c.created_at, c.moderated_at
				FROM $c c
				INNER JOIN $r ra ON ra.id = c.response_a_id INNER JOIN $r rb ON rb.id = c.response_b_id
				INNER JOIN $p pa ON pa.id = c.participant_a_id INNER JOIN $p pb ON pb.id = c.participant_b_id
				ORDER BY c.id",
				ARRAY_A
			),
			'processing_events' => $wpdb->get_results(
				"SELECT r.public_id AS response_public_id, e.event_type, e.message, e.created_at
				FROM $e e INNER JOIN $r r ON r.id = e.response_id ORDER BY e.id",
				ARRAY_A
			),
			'dynamic_translations' => $wpdb->get_results(
				"SELECT source_hash, language_code, source_text, translated_text, status, updated_at FROM $dt ORDER BY id",
				ARRAY_A
			),
			'events' => self::event_export_rows(),
		);
	}

	private static function write_csv( string $path, array $rows ): void {
		$handle = fopen( $path, 'w' );
		if ( false === $handle ) {
			return;
		}
		if ( $rows ) {
			fputcsv( $handle, array_keys( $rows[0] ) );
			foreach ( $rows as $row ) {
				fputcsv( $handle, $row );
			}
		}
		fclose( $handle );
	}

	private static function remove_temp_export( string $directory ): void {
		foreach ( glob( $directory . '/*' ) ?: array() as $file ) {
			@unlink( $file );
		}
		@rmdir( $directory );
	}

	private static function require_admin(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Diastoles.', 'diastoles' ) );
		}
	}
}
