<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_DB {
	private const DB_VERSION = '0.10.1';

	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'diastoles_' . $name;
	}

	public static function activate(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$sql     = array();

		$sql[] = 'CREATE TABLE ' . self::table( 'participants' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			session_hash char(64) NOT NULL,
			recovery_hash char(64) NOT NULL,
			pseudonym varchar(80) NOT NULL DEFAULT '',
			publication_email varchar(254) NOT NULL DEFAULT '',
			consent_fragments tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY session_hash (session_hash),
			UNIQUE KEY recovery_hash (recovery_hash)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'participant_profiles' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			participant_id bigint(20) unsigned NOT NULL,
			gender varchar(40) NOT NULL DEFAULT '',
			age_band varchar(20) NOT NULL DEFAULT '',
			environment varchar(30) NOT NULL DEFAULT '',
			country varchar(100) NOT NULL DEFAULT '',
			continent varchar(40) NOT NULL DEFAULT '',
			native_languages_json longtext NOT NULL,
			other_languages_json longtext NOT NULL,
			multilingual_status varchar(30) NOT NULL DEFAULT '',
			work_areas_json longtext NOT NULL,
			hobbies_json longtext NOT NULL,
			rooted_places_json longtext NOT NULL,
			use_for_matching tinyint(1) NOT NULL DEFAULT 0,
			allow_context_display tinyint(1) NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY participant_id (participant_id)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'questions' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			prompt text NOT NULL,
			short_label varchar(160) NOT NULL DEFAULT '',
			followup text NOT NULL,
			theme varchar(60) NOT NULL,
			intensity tinyint(2) NOT NULL DEFAULT 1,
			active tinyint(1) NOT NULL DEFAULT 1,
			skip_count bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY active (active)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'question_translations' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			question_id bigint(20) unsigned NOT NULL,
			language_code varchar(12) NOT NULL,
			prompt text NOT NULL,
			short_label varchar(160) NOT NULL DEFAULT '',
			followup text NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY question_language (question_id,language_code),
			KEY language_code (language_code)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'dynamic_translations' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_hash char(64) NOT NULL,
			language_code varchar(12) NOT NULL,
			source_text longtext NOT NULL,
			translated_text longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY source_language (source_hash,language_code),
			KEY status (status)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'question_views' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			participant_id bigint(20) unsigned NOT NULL,
			question_id bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'seen',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY participant_question (participant_id,question_id)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'responses' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			participant_id bigint(20) unsigned NOT NULL,
			question_id bigint(20) unsigned NOT NULL,
			original_text longtext NOT NULL,
			content_hash char(64) NOT NULL DEFAULT '',
			explanation_text longtext NOT NULL,
			source_language varchar(20) NOT NULL DEFAULT '',
			translation_en longtext NOT NULL,
			analysis_json longtext NOT NULL,
			processing_status varchar(20) NOT NULL DEFAULT 'pending',
			allow_network tinyint(1) NOT NULL DEFAULT 0,
			withdrawn tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY participant_id (participant_id),
			KEY participant_content (participant_id,content_hash),
			KEY processing_status (processing_status)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'connections' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			response_a_id bigint(20) unsigned NOT NULL,
			response_b_id bigint(20) unsigned NOT NULL,
			participant_a_id bigint(20) unsigned NOT NULL,
			participant_b_id bigint(20) unsigned NOT NULL,
			relation_type varchar(30) NOT NULL,
			score decimal(5,4) NOT NULL DEFAULT 0,
			explanation_json longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			moderated_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY response_pair (response_a_id,response_b_id),
			KEY participant_a_id (participant_a_id),
			KEY participant_b_id (participant_b_id),
			KEY status (status)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'processing_events' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			response_id bigint(20) unsigned NOT NULL,
			event_type varchar(30) NOT NULL,
			message text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY response_id (response_id)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . self::table( 'events' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			participant_id bigint(20) unsigned NULL,
			session_token_hash char(64) NOT NULL DEFAULT '',
			event_type varchar(40) NOT NULL,
			screen varchar(60) NOT NULL DEFAULT '',
			question_id bigint(20) unsigned NULL,
			question_label varchar(255) NOT NULL DEFAULT '',
			question_theme varchar(120) NOT NULL DEFAULT '',
			interface_language varchar(16) NOT NULL DEFAULT '',
			metadata_json longtext NULL,
			ip_hash char(64) NOT NULL DEFAULT '',
			user_agent_hash char(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY participant_id (participant_id),
			KEY event_type (event_type),
			KEY question_id (question_id),
			KEY created_at (created_at)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		self::backfill_content_hashes();
		self::seed_questions();
		self::import_curated_question_collection();
		self::migrate_question_themes();
		self::backfill_question_short_labels();
		self::hide_failed_responses_from_network();
		update_option( 'diastoles_db_version', self::DB_VERSION );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( 'diastoles_db_version' ) !== self::DB_VERSION ) {
			self::activate();
		}
	}

	private static function seed_questions(): void {
		global $wpdb;
		$table = self::table( 'questions' );
		if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) > 0 ) {
			return;
		}

		$questions = array(
			array( 'What smell makes you feel at home?', 'What makes that smell feel like belonging?', 'belonging', 1 ),
			array( 'What smell belongs to a person you miss?', 'Where does that smell take you?', 'intimacy', 2 ),
			array( 'What smell tells you that something is beginning?', 'What changes when you notice it?', 'time experience', 1 ),
			array( 'Which smell makes time slow down?', 'What becomes visible in that slower time?', 'time experience', 1 ),
			array( 'What smell reminds you of a future you once imagined?', 'Does that future still feel possible?', 'time experience', 2 ),
			array( 'Which smell makes you want to leave?', 'What are you moving away from?', 'distance', 2 ),
			array( 'What smell brings you back to your body?', 'How do you notice the return?', 'self-experience', 1 ),
			array( 'What smell carries a language you no longer speak?', 'What remains when the words disappear?', 'memory', 2 ),
			array( 'Which smell means safety to you?', 'Who or what made it safe?', 'belonging', 1 ),
			array( 'What smell contains a contradiction?', 'What two things coexist inside it?', 'tension', 2 ),
			array( 'Which artificial smell feels strangely intimate?', 'How did it become part of you?', 'self-experience', 1 ),
			array( 'What smell would you preserve for someone in the future?', 'Why should they encounter it?', 'time experience', 1 ),
			array( 'Which smell connects you to a place you cannot return to?', 'What is still rooted there?', 'belonging', 2 ),
			array( 'What smell changes when you are alone?', 'How does solitude alter it?', 'distance', 2 ),
			array( 'Which smell makes another person feel close?', 'What kind of closeness is it?', 'intimacy', 1 ),
			array( 'What smell belongs to this moment of your life?', 'What does it say about where you are?', 'time experience', 1 ),
			array( 'Which smell do you share with strangers?', 'What might it reveal that you have in common?', 'relationships', 1 ),
			array( 'What smell would you use to describe disconnection?', 'Where do you feel that disconnection?', 'distance', 2 ),
			array( 'What smell makes you feel welcome somewhere unfamiliar?', 'What allowed you to belong there?', 'belonging', 1 ),
			array( 'Which smell changed after someone left your life?', 'What did it mean before, and what does it mean now?', 'relationships', 2 ),
			array( 'What does waiting smell like to you?', 'What were you waiting for?', 'time experience', 1 ),
			array( 'Which smell reminds you of being truly seen by someone?', 'How did that recognition change you?', 'relationships', 2 ),
			array( 'What smell belongs to your digital life?', 'How does it connect—or disconnect—you from your body?', 'self-experience', 2 ),
			array( 'What forgotten smell has returned to you unexpectedly?', 'What did it bring back with it?', 'memory', 1 ),
			array( 'Which smell do you share with others but experience differently?', 'What makes its meaning yours?', 'tension', 1 ),
			array( 'What smell tells you that the working day is over?', 'What part of yourself returns at that moment?', 'everyday life', 1 ),
		);

		foreach ( $questions as $question ) {
			$wpdb->insert(
				$table,
				array(
					'prompt'     => $question[0],
					'followup'   => $question[1],
					'theme'      => $question[2],
					'intensity'  => $question[3],
					'active'     => 1,
					'created_at' => current_time( 'mysql', true ),
				),
				array( '%s', '%s', '%s', '%d', '%d', '%s' )
			);
		}
	}

	private static function import_curated_question_collection(): void {
		if ( get_option( 'diastoles_curated_questions_0_10_24_imported' ) ) {
			return;
		}

		global $wpdb;
		$table     = self::table( 'questions' );
		$questions = array(
			array( 'What does love smell like when it is present but unspoken?', 'How do you recognize love in that silence?', 'Love in silence', 'intimacy', 1 ),
			array( 'Which smell reminds you of the first time you truly trusted someone?', 'What allowed you to let your guard down?', 'First trust', 'intimacy', 2 ),
			array( 'What smell belongs to a way of being cared for?', 'What did that care teach you about love?', 'Being cared for', 'relationships', 1 ),
			array( 'Which smell changed its meaning because you fell in love?', 'What does it carry for you now?', 'A smell changed by love', 'intimacy', 2 ),
			array( 'What smell reveals love hiding in ordinary life?', 'What small moment does it bring back?', 'Love in ordinary life', 'everyday life', 2 ),
			array( 'Which smell carries a kind of love you once struggled to name?', 'How would you name it today?', 'A love once unnamed', 'relationships', 3 ),
			array( 'What smell would you want someone you love to remember you by?', 'What part of you would remain in it?', 'How love remembers you', 'self-experience', 3 ),
			array( 'What did your first kiss smell like?', 'What else was held in the air around it?', 'The scent of a first kiss', 'intimacy', 3 ),
			array( 'What scent vanished from the landscape of your days after a heartbreak?', 'When did you first notice its absence?', 'A scent that vanished', 'distance', 2 ),
			array( 'Which smell did you avoid because it brought someone back too clearly?', 'Has its hold on you changed over time?', 'A smell you avoided', 'memory', 3 ),
			array( 'What familiar smell became unfamiliar after a goodbye?', 'What changed: the smell, the place, or you?', 'After a goodbye', 'distance', 3 ),
			array( 'Which smell carries words you never got to say?', 'What would you say if you could return to that moment?', 'Words left unsaid', 'relationships', 3 ),
			array( 'What smell marks the distance between missing someone and moving on?', 'Where do you feel you are now?', 'Missing and moving on', 'time experience', 2 ),
			array( 'Which smell did you reclaim after it stopped belonging to “us”?', 'How did you make it yours again?', 'A smell reclaimed', 'self-experience', 1 ),
			array( 'What smell reminds you that healing is not the same as forgetting?', 'What have you kept, and what have you released?', 'Healing without forgetting', 'memory', 3 ),
			array( 'What smell tells your body that it is time to begin?', 'What changes in you when you notice it?', 'Time to begin', 'self-experience', 1 ),
			array( 'Which smell belongs to effort before there is any result?', 'What keeps you going in that moment?', 'Effort before results', 'self-experience', 2 ),
			array( 'What smell reminds you of discovering what your body could do?', 'Did that discovery change how you saw yourself?', 'What your body could do', 'self-experience', 1 ),
			array( 'Which smell turns nervousness into focus for you?', 'What are you preparing to face?', 'Nervousness into focus', 'tension', 2 ),
			array( 'What smell makes a group of people feel like a team?', 'When did you first feel that you belonged among them?', 'Becoming a team', 'belonging', 1 ),
			array( 'Which smell makes defeat feel real?', 'What did that defeat ask you to learn?', 'When defeat feels real', 'tension', 3 ),
			array( 'What smell remains after your body has given everything?', 'What do you feel when the effort is over?', 'After giving everything', 'self-experience', 2 ),
			array( 'What does speed smell like when you experience it exactly the way you love?', 'Where does that scent place your body: in the road, the air, the machine, or somewhere else?', 'The scent of speed', 'self-experience', 2 ),
			array( 'What smell makes it easier to talk to someone you do not know?', 'What kind of openness does it create?', 'Talking to a stranger', 'relationships', 1 ),
			array( 'Which shared smell can turn a crowd into a temporary community?', 'What brings those strangers together?', 'A temporary community', 'belonging', 1 ),
			array( 'What smell tells you that you can lower your guard around someone?', 'What makes that person feel safe?', 'Lowering your guard', 'intimacy', 2 ),
			array( 'Which smell belongs to a ritual you share with other people?', 'What would be missing if you experienced it alone?', 'A shared ritual', 'relationships', 1 ),
			array( 'What smell reminds you of someone who made room for you?', 'How did they make you feel included?', 'Someone made room for you', 'belonging', 2 ),
			array( 'Which smell has helped you feel close to a life different from your own?', 'What did it allow you to understand?', 'Close to another life', 'relationships', 2 ),
			array( 'What smell could connect two people who do not share a language?', 'What might they understand without words?', 'Connection without words', 'relationships', 3 ),
			array( 'What scent brings forbidden things to mind?', 'What becomes possible—or dangerous—inside that scent?', 'The scent of the forbidden', 'tension', 3 ),
			array( 'What smell takes you back to a song you experienced with your whole body?', 'What did the music awaken in you?', 'A song felt in the body', 'memory', 1 ),
			array( 'Which smell belongs to the breath just before the music begins?', 'What are you waiting to feel?', 'Before the music begins', 'time experience', 1 ),
			array( 'What smell reminds you of losing yourself in a crowd at a concert?', 'Did you feel more anonymous or more connected?', 'Lost in a concert crowd', 'belonging', 2 ),
			array( 'Which smell belongs to a music festival you never wanted to end?', 'What made that temporary world feel complete?', 'A festival without an end', 'belonging', 1 ),
			array( 'What smell carries the memory of someone you once listened to music with?', 'Which part of that connection remains in the music?', 'Someone in the music', 'relationships', 3 ),
			array( 'Which smell makes a particular song feel like a place you can return to?', 'What do you find there each time?', 'A song as a place', 'memory', 3 ),
			array( 'When the crowd has gone and the music has fallen silent, what scent is left behind?', 'What stays with you after the silence returns?', 'After the music stops', 'time experience', 3 ),
		);

		$failed = false;
		foreach ( $questions as $question ) {
			$existing_id = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM $table WHERE prompt = %s LIMIT 1", $question[0] )
			);
			if ( $existing_id ) {
				continue;
			}

			$inserted = $wpdb->insert(
				$table,
				array(
					'prompt'      => $question[0],
					'followup'    => $question[1],
					'short_label' => $question[2],
					'theme'       => $question[3],
					'intensity'   => $question[4],
					'active'      => 1,
					'created_at'  => current_time( 'mysql', true ),
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
			);
			if ( false === $inserted ) {
				$failed = true;
			}
		}

		if ( ! $failed ) {
			update_option( 'diastoles_curated_questions_0_10_24_imported', 1, false );
		}
	}

	private static function migrate_question_themes(): void {
		if ( get_option( 'diastoles_theme_taxonomy_0_6_migrated' ) ) {
			return;
		}
		global $wpdb;
		$table = self::table( 'questions' );
		$themes = array(
			'What smell makes you feel at home?' => array( 'belonging', array( 'belonging' ) ),
			'What smell belongs to a person you miss?' => array( 'intimacy', array( 'relationships' ) ),
			'What smell tells you that something is beginning?' => array( 'time experience', array( 'change' ) ),
			'Which smell makes time slow down?' => array( 'time experience', array( 'time' ) ),
			'What smell reminds you of a future you once imagined?' => array( 'time experience', array( 'future' ) ),
			'Which smell makes you want to leave?' => array( 'distance', array( 'departure' ) ),
			'What smell brings you back to your body?' => array( 'self-experience', array( 'body' ) ),
			'What smell carries a language you no longer speak?' => array( 'memory', array( 'language' ) ),
			'Which smell means safety to you?' => array( 'belonging', array( 'safety' ) ),
			'What smell contains a contradiction?' => array( 'tension', array( 'tension' ) ),
			'Which artificial smell feels strangely intimate?' => array( 'self-experience', array( 'technology' ) ),
			'What smell would you preserve for someone in the future?' => array( 'time experience', array( 'future' ) ),
			'Which smell connects you to a place you cannot return to?' => array( 'belonging', array( 'place' ) ),
			'What smell changes when you are alone?' => array( 'distance', array( 'solitude' ) ),
			'Which smell makes another person feel close?' => array( 'intimacy', array( 'connection' ) ),
			'What smell belongs to this moment of your life?' => array( 'time experience', array( 'present' ) ),
			'Which smell do you share with strangers?' => array( 'relationships', array( 'collective' ) ),
			'What smell would you use to describe disconnection?' => array( 'distance', array( 'disconnection' ) ),
			'What smell makes you feel welcome somewhere unfamiliar?' => array( 'belonging', array( 'belonging' ) ),
			'Which smell changed after someone left your life?' => array( 'relationships', array( 'relationships' ) ),
			'What does waiting smell like to you?' => array( 'time experience', array( 'time' ) ),
			'Which smell reminds you of being truly seen by someone?' => array( 'relationships', array( 'connection' ) ),
			'What smell belongs to your digital life?' => array( 'self-experience', array( 'technology' ) ),
			'What forgotten smell has returned to you unexpectedly?' => array( 'memory', array( 'memory' ) ),
			'Which smell do you share with others but experience differently?' => array( 'tension', array( 'difference' ) ),
			'What smell tells you that the working day is over?' => array( 'everyday life', array( 'rhythm' ) ),
		);
		foreach ( $themes as $prompt => list( $theme, $legacy_themes ) ) {
			foreach ( $legacy_themes as $legacy_theme ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $table SET theme = %s WHERE prompt = %s AND theme = %s",
						$theme,
						$prompt,
						$legacy_theme
					)
				);
			}
		}
		update_option( 'diastoles_theme_taxonomy_0_6_migrated', 1, false );
	}

	private static function backfill_content_hashes(): void {
		global $wpdb;
		$table = self::table( 'responses' );
		$rows  = $wpdb->get_results( "SELECT id, original_text FROM $table WHERE content_hash = '' LIMIT 1000" );
		foreach ( $rows as $row ) {
			$normalized = mb_strtolower( trim( (string) $row->original_text ) );
			$normalized = preg_replace( '/\s+/u', ' ', $normalized ) ?: $normalized;
			$wpdb->update(
				$table,
				array( 'content_hash' => hash( 'sha256', $normalized ) ),
				array( 'id' => (int) $row->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	private static function backfill_question_short_labels(): void {
		global $wpdb;
		$table = self::table( 'questions' );
		$rows  = $wpdb->get_results( "SELECT id, prompt FROM $table WHERE short_label = ''" );
		foreach ( $rows as $row ) {
			$prompt = trim( preg_replace( '/\s+/u', ' ', (string) $row->prompt ) );
			$label  = mb_strlen( $prompt ) > 90 ? rtrim( mb_substr( $prompt, 0, 89 ) ) . '…' : $prompt;
			$wpdb->update(
				$table,
				array( 'short_label' => $label ),
				array( 'id' => (int) $row->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	private static function hide_failed_responses_from_network(): void {
		global $wpdb;
		$table = self::table( 'responses' );
		$wpdb->query( "UPDATE $table SET allow_network = 0 WHERE processing_status = 'failed'" );
	}

	public static function get_response( int $response_id ): ?object {
		global $wpdb;
		$table = self::table( 'responses' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $response_id ) ) ?: null;
	}

	public static function update_response_status( int $response_id, string $status ): void {
		global $wpdb;
		$wpdb->update(
			self::table( 'responses' ),
			array( 'processing_status' => $status ),
			array( 'id' => $response_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function mark_response_failed( int $response_id ): void {
		global $wpdb;
		$wpdb->update(
			self::table( 'responses' ),
			array(
				'processing_status' => 'failed',
				'allow_network'     => 0,
			),
			array( 'id' => $response_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);
	}

	public static function save_analysis( int $response_id, array $analysis ): void {
		global $wpdb;
		$analysis['smells'] = Diastoles_Anthropic::smell_concepts( $analysis );
		$wpdb->update(
			self::table( 'responses' ),
			array(
				'source_language'   => sanitize_key( $analysis['source_language'] ),
				'translation_en'    => (string) $analysis['translation_en'],
				'analysis_json'     => wp_json_encode( $analysis ),
				'processing_status' => 'complete',
			),
			array( 'id' => $response_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function record_processing_error( int $response_id, string $message ): void {
		self::record_processing_event( $response_id, 'error', $message );
	}

	public static function record_processing_event( int $response_id, string $event_type, string $message ): void {
		global $wpdb;
		$wpdb->insert(
			self::table( 'processing_events' ),
			array(
				'response_id' => $response_id,
				'event_type'  => mb_substr( sanitize_key( $event_type ), 0, 30 ),
				'message'     => mb_substr( $message, 0, 1000 ),
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	public static function increment_question_skip_count( int $question_id ): void {
		global $wpdb;
		$table = self::table( 'questions' );
		$wpdb->query( $wpdb->prepare( "UPDATE $table SET skip_count = skip_count + 1 WHERE id = %d", $question_id ) );
	}

	public static function record_event( string $event_type, array $data = array() ): void {
		$allowed = array(
			'skip',
			'response_submitted',
			'tab_opened',
			'connection_viewed',
			'language_changed',
			'load_more',
			'map_variant_opened',
		);
		if ( ! in_array( $event_type, $allowed, true ) ) {
			return;
		}

		global $wpdb;
		$participant = $data['participant'] ?? Diastoles_Session::current();
		$question_id = absint( $data['question_id'] ?? 0 );
		$question    = null;
		if ( $question_id ) {
			$question = $wpdb->get_row(
				$wpdb->prepare( 'SELECT prompt, short_label, theme FROM ' . self::table( 'questions' ) . ' WHERE id = %d', $question_id )
			);
		}

		$metadata = $data['metadata'] ?? array();
		if ( ! is_array( $metadata ) ) {
			$metadata = array( 'value' => $metadata );
		}
		$metadata = self::sanitize_event_metadata( $metadata );
		$session_hash = '';
		if ( $participant && isset( $participant->public_id ) ) {
			$session_hash = hash_hmac( 'sha256', (string) $participant->public_id, wp_salt( 'auth' ) );
		}

		$ip         = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		$wpdb->insert(
			self::table( 'events' ),
			array(
				'participant_id'      => $participant ? (int) $participant->id : null,
				'session_token_hash'  => $session_hash,
				'event_type'          => $event_type,
				'screen'              => self::limited_text( $data['screen'] ?? '', 60 ),
				'question_id'         => $question_id ?: null,
				'question_label'      => $question ? self::limited_text( $question->short_label ?: $question->prompt, 255 ) : '',
				'question_theme'      => $question ? self::limited_text( $question->theme, 120 ) : '',
				'interface_language'  => self::limited_text( $data['interface_language'] ?? '', 16 ),
				'metadata_json'       => wp_json_encode( $metadata, JSON_UNESCAPED_UNICODE ),
				'ip_hash'             => $ip ? hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ) : '',
				'user_agent_hash'     => $user_agent ? hash_hmac( 'sha256', $user_agent, wp_salt( 'auth' ) ) : '',
				'created_at'          => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	private static function sanitize_event_metadata( array $metadata ): array {
		$clean = array();
		foreach ( $metadata as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_event_metadata( $value );
			} elseif ( is_bool( $value ) ) {
				$clean[ $key ] = $value;
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$clean[ $key ] = $value;
			} else {
				$clean[ $key ] = mb_substr( sanitize_text_field( (string) $value ), 0, 500 );
			}
		}
		return $clean;
	}

	public static function get_profile( int $participant_id ): ?object {
		global $wpdb;
		$table = self::table( 'participant_profiles' );
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE participant_id = %d", $participant_id )
		) ?: null;
	}

	public static function save_publication_email( int $participant_id, string $email ): void {
		global $wpdb;
		$wpdb->update(
			self::table( 'participants' ),
			array( 'publication_email' => mb_substr( sanitize_email( $email ), 0, 254 ) ),
			array( 'id' => $participant_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function save_profile( int $participant_id, array $profile ): void {
		global $wpdb;
		$table = self::table( 'participant_profiles' );
		$data  = array(
			'participant_id'          => $participant_id,
			'gender'                  => self::limited_text( $profile['gender'] ?? '', 40 ),
			'age_band'                => self::limited_text( $profile['age_band'] ?? '', 20 ),
			'environment'             => self::limited_text( $profile['environment'] ?? '', 30 ),
			'country'                 => self::limited_text( $profile['country'] ?? '', 100 ),
			'continent'               => self::limited_text( $profile['continent'] ?? '', 40 ),
			'native_languages_json'   => wp_json_encode( self::list_value( $profile['native_languages'] ?? array() ) ),
			'other_languages_json'    => wp_json_encode( self::list_value( $profile['other_languages'] ?? array() ) ),
			'multilingual_status'     => self::limited_text( $profile['multilingual_status'] ?? '', 30 ),
			'work_areas_json'         => wp_json_encode( self::list_value( $profile['work_areas'] ?? array() ) ),
			'hobbies_json'            => wp_json_encode( self::list_value( $profile['hobbies'] ?? array() ) ),
			'rooted_places_json'      => wp_json_encode( self::list_value( $profile['rooted_places'] ?? array() ) ),
			'use_for_matching'        => ! empty( $profile['use_for_matching'] ) ? 1 : 0,
			'allow_context_display'   => ! empty( $profile['allow_context_display'] ) ? 1 : 0,
			'updated_at'              => current_time( 'mysql', true ),
		);
		$formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' );

		if ( self::get_profile( $participant_id ) ) {
			unset( $data['participant_id'] );
			array_shift( $formats );
			$wpdb->update(
				$table,
				$data,
				array( 'participant_id' => $participant_id ),
				$formats,
				array( '%d' )
			);
			return;
		}

		$wpdb->insert( $table, $data, $formats );
	}

	public static function profile_payload( ?object $profile ): array {
		if ( ! $profile ) {
			return array(
				'gender' => '', 'age_band' => '', 'environment' => '', 'country' => '',
				'continent' => '', 'native_languages' => array(), 'other_languages' => array(),
				'multilingual_status' => '', 'work_areas' => array(), 'hobbies' => array(),
				'rooted_places' => array(), 'use_for_matching' => false, 'allow_context_display' => false,
			);
		}

		return array(
			'gender'                => $profile->gender,
			'age_band'              => $profile->age_band,
			'environment'           => $profile->environment,
			'country'               => $profile->country,
			'continent'             => $profile->continent,
			'native_languages'      => json_decode( $profile->native_languages_json, true ) ?: array(),
			'other_languages'       => json_decode( $profile->other_languages_json, true ) ?: array(),
			'multilingual_status'   => $profile->multilingual_status,
			'work_areas'            => json_decode( $profile->work_areas_json, true ) ?: array(),
			'hobbies'               => json_decode( $profile->hobbies_json, true ) ?: array(),
			'rooted_places'         => json_decode( $profile->rooted_places_json, true ) ?: array(),
			'use_for_matching'      => (bool) $profile->use_for_matching,
			'allow_context_display' => (bool) $profile->allow_context_display,
		);
	}

	private static function limited_text( mixed $value, int $length ): string {
		return mb_substr( sanitize_text_field( (string) $value ), 0, $length );
	}

	private static function list_value( mixed $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$items = array_map(
			static fn( mixed $item ): string => mb_substr( sanitize_text_field( (string) $item ), 0, 100 ),
			$value
		);
		return array_values( array_slice( array_unique( array_filter( array_map( 'trim', $items ) ) ), 0, 20 ) );
	}
}
