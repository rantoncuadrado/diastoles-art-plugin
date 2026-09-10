<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Native, origin-rendered translations for Diástoles. */
final class Diastoles_I18n {
	private const SETTINGS_OPTION = 'diastoles_language_settings';
	private const TEXTS_OPTION = 'diastoles_interface_translations';
	private const BUNDLED_VERSION_OPTION = 'diastoles_bundled_translations_version';
	private const BUNDLED_VERSION = '0.15.3';
	private static int $scheduled_this_request = 0;
	private static ?array $bundled_interface = null;
	private static ?array $bundled_questions = null;

	public static function languages(): array {
		return array(
			'ar' => array( 'name' => 'العربية', 'english_name' => 'Arabic', 'dir' => 'rtl' ),
			'eu' => array( 'name' => 'Euskara', 'english_name' => 'Basque', 'dir' => 'ltr' ),
			'bn' => array( 'name' => 'বাংলা', 'english_name' => 'Bengali', 'dir' => 'ltr' ),
			'ca' => array( 'name' => 'Català', 'english_name' => 'Catalan', 'dir' => 'ltr' ),
			'zh-cn' => array( 'name' => '简体中文', 'english_name' => 'Chinese (Simplified)', 'dir' => 'ltr' ),
			'en' => array( 'name' => 'English', 'english_name' => 'English', 'dir' => 'ltr' ),
			'fr' => array( 'name' => 'Français', 'english_name' => 'French', 'dir' => 'ltr' ),
			'gl' => array( 'name' => 'Galego', 'english_name' => 'Galician', 'dir' => 'ltr' ),
			'de' => array( 'name' => 'Deutsch', 'english_name' => 'German', 'dir' => 'ltr' ),
			'hi' => array( 'name' => 'हिन्दी', 'english_name' => 'Hindi', 'dir' => 'ltr' ),
			'id' => array( 'name' => 'Bahasa Indonesia', 'english_name' => 'Indonesian', 'dir' => 'ltr' ),
			'it' => array( 'name' => 'Italiano', 'english_name' => 'Italian', 'dir' => 'ltr' ),
			'ja' => array( 'name' => '日本語', 'english_name' => 'Japanese', 'dir' => 'ltr' ),
			'ms' => array( 'name' => 'Bahasa Melayu', 'english_name' => 'Malay', 'dir' => 'ltr' ),
			'fa' => array( 'name' => 'فارسی', 'english_name' => 'Persian', 'dir' => 'rtl' ),
			'pl' => array( 'name' => 'Polski', 'english_name' => 'Polish', 'dir' => 'ltr' ),
			'pt' => array( 'name' => 'Português', 'english_name' => 'Portuguese', 'dir' => 'ltr' ),
			'ru' => array( 'name' => 'Русский', 'english_name' => 'Russian', 'dir' => 'ltr' ),
			'es' => array( 'name' => 'Español', 'english_name' => 'Spanish', 'dir' => 'ltr' ),
		);
	}

	public static function normalize( mixed $locale ): string {
		$locale = strtolower( str_replace( '_', '-', sanitize_text_field( (string) $locale ) ) );
		if ( str_starts_with( $locale, 'zh' ) ) {
			$locale = 'zh-cn';
		} else {
			$locale = explode( '-', $locale )[0] ?? '';
		}
		return isset( self::languages()[ $locale ] ) ? $locale : '';
	}

	public static function settings(): array {
		$defaults = array( 'default' => 'en', 'active' => array_keys( self::languages() ) );
		$stored   = get_option( self::SETTINGS_OPTION, array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$active   = array_values( array_filter( array_map( array( self::class, 'normalize' ), (array) ( $stored['active'] ?? $defaults['active'] ) ) ) );
		if ( ! in_array( 'en', $active, true ) ) {
			$active[] = 'en';
		}
		$default = self::normalize( $stored['default'] ?? 'en' ) ?: 'en';
		return array( 'default' => in_array( $default, $active, true ) ? $default : 'en', 'active' => array_values( array_unique( $active ) ) );
	}

	public static function active_languages(): array {
		$all = self::languages();
		return array_intersect_key( $all, array_flip( self::settings()['active'] ) );
	}

	public static function request_locale( ?WP_REST_Request $request = null ): string {
		$candidate = $request ? $request->get_param( 'lang' ) : ( $_GET['lang'] ?? '' );
		$locale    = self::normalize( $candidate );
		return $locale && isset( self::active_languages()[ $locale ] ) ? $locale : self::settings()['default'];
	}

	public static function direction( string $locale ): string {
		return self::languages()[ self::normalize( $locale ) ]['dir'] ?? 'ltr';
	}

	public static function interface_texts( string $locale ): array {
		$locale  = self::normalize( $locale ) ?: 'en';
		$english = Diastoles_Texts::all();
		if ( 'en' === $locale ) {
			return $english;
		}
		$bundled = self::bundled_interface_locale( $locale );
		$stored = get_option( self::TEXTS_OPTION, array() );
		$values = is_array( $stored[ $locale ] ?? null ) ? $stored[ $locale ] : array();
		return array_merge(
			$english,
			array_filter( $bundled, static fn( mixed $value ): bool => '' !== trim( (string) $value ) ),
			array_filter( $values, static fn( mixed $value ): bool => '' !== trim( (string) $value ) )
		);
	}

	public static function interface_catalogs(): array {
		$catalogs = array();
		foreach ( self::active_languages() as $locale => $_language ) {
			$catalogs[ $locale ] = self::interface_texts( $locale );
		}
		return $catalogs;
	}

	public static function raw_interface_locale( string $locale ): array {
		$locale = self::normalize( $locale );
		if ( 'en' === $locale ) {
			return Diastoles_Texts::all();
		}
		$stored = get_option( self::TEXTS_OPTION, array() );
		return array_merge(
			self::bundled_interface_locale( $locale ),
			is_array( $stored[ $locale ] ?? null )
				? array_filter( $stored[ $locale ], static fn( mixed $value ): bool => '' !== trim( (string) $value ) )
				: array()
		);
	}

	public static function save_interface_locale( string $locale, array $input ): void {
		$locale = self::normalize( $locale );
		if ( ! $locale || 'en' === $locale ) {
			return;
		}
		$clean = array();
		foreach ( Diastoles_Texts::fields() as $fields ) {
			foreach ( $fields as $key => $field ) {
				$value         = wp_unslash( $input[ $key ] ?? '' );
				$clean[ $key ] = 'textarea' === ( $field['type'] ?? '' ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
			}
		}
		$stored            = get_option( self::TEXTS_OPTION, array() );
		$stored            = is_array( $stored ) ? $stored : array();
		$stored[ $locale ] = $clean;
		update_option( self::TEXTS_OPTION, $stored, false );
	}

	public static function sanitize_settings( array $input ): array {
		$active = array_values( array_filter( array_map( array( self::class, 'normalize' ), (array) ( $input['active'] ?? array() ) ) ) );
		if ( ! in_array( 'en', $active, true ) ) {
			$active[] = 'en';
		}
		$default = self::normalize( $input['default'] ?? 'en' ) ?: 'en';
		return array( 'default' => in_array( $default, $active, true ) ? $default : 'en', 'active' => array_values( array_unique( $active ) ) );
	}

	public static function question_translation( int $question_id, string $locale, ?object $fallback = null ): array {
		$locale = self::normalize( $locale ) ?: 'en';
		if ( 'en' !== $locale ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare(
				'SELECT prompt, short_label, followup FROM ' . Diastoles_DB::table( 'question_translations' ) . ' WHERE question_id = %d AND language_code = %s',
				$question_id,
				$locale
			) );
			if ( $row ) {
				return array(
					'prompt' => '' !== trim( (string) $row->prompt ) ? $row->prompt : (string) ( $fallback->prompt ?? '' ),
					'short_label' => '' !== trim( (string) $row->short_label ) ? $row->short_label : (string) ( $fallback->short_label ?? '' ),
					'followup' => '' !== trim( (string) $row->followup ) ? $row->followup : (string) ( $fallback->followup ?? '' ),
				);
			}
		}
		return array(
			'prompt' => (string) ( $fallback->prompt ?? '' ),
			'short_label' => (string) ( $fallback->short_label ?? '' ),
			'followup' => (string) ( $fallback->followup ?? '' ),
		);
	}

	public static function save_question_translations( int $question_id, array $translations ): void {
		global $wpdb;
		$table = Diastoles_DB::table( 'question_translations' );
		foreach ( $translations as $requested_locale => $value ) {
			$locale = self::normalize( $requested_locale );
			if ( ! $locale || 'en' === $locale || ! isset( self::active_languages()[ $locale ] ) ) {
				continue;
			}
			$value = is_array( $value ) ? $value : array();
			$data  = array(
				'question_id' => $question_id,
				'language_code' => $locale,
				'prompt' => mb_substr( sanitize_textarea_field( wp_unslash( $value['prompt'] ?? '' ) ), 0, 1000 ),
				'short_label' => mb_substr( sanitize_text_field( wp_unslash( $value['short_label'] ?? '' ) ), 0, 160 ),
				'followup' => mb_substr( sanitize_textarea_field( wp_unslash( $value['followup'] ?? '' ) ), 0, 1000 ),
				'updated_at' => current_time( 'mysql', true ),
			);
			$wpdb->replace( $table, $data, array( '%d', '%s', '%s', '%s', '%s', '%s' ) );
		}
	}

	public static function pretranslate_question( int $question_id, object $question ): void {
		if ( 'live' !== Diastoles_Anthropic::mode() || ! Diastoles_Anthropic::has_api_key() ) {
			return;
		}

		global $wpdb;
		$table = Diastoles_DB::table( 'question_translations' );
		foreach ( self::active_languages() as $locale => $language ) {
			if ( 'en' === $locale ) {
				continue;
			}
			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT prompt, short_label, followup FROM $table WHERE question_id = %d AND language_code = %s", $question_id, $locale )
			);
			$fields = array(
				'prompt'      => (string) ( $existing->prompt ?? '' ),
				'short_label' => (string) ( $existing->short_label ?? '' ),
				'followup'    => (string) ( $existing->followup ?? '' ),
			);

			foreach ( array( 'prompt', 'short_label', 'followup' ) as $field ) {
				if ( '' !== trim( $fields[ $field ] ) || '' === trim( (string) ( $question->{$field} ?? '' ) ) ) {
					continue;
				}
				$result = Diastoles_Anthropic::translate_text( (string) $question->{$field}, $locale );
				if ( ! is_wp_error( $result ) ) {
					$fields[ $field ] = mb_substr( (string) $result, 0, 'short_label' === $field ? 160 : 1000 );
				}
			}

			if ( array_filter( array_map( 'trim', $fields ) ) ) {
				$wpdb->replace(
					$table,
					array(
						'question_id'   => $question_id,
						'language_code' => $locale,
						'prompt'        => $fields['prompt'],
						'short_label'   => $fields['short_label'],
						'followup'      => $fields['followup'],
						'updated_at'    => current_time( 'mysql', true ),
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s' )
				);
			}
		}
	}

	public static function translation_progress( string $locale ): int {
		if ( 'en' === $locale ) {
			return 100;
		}
		$values = self::raw_interface_locale( $locale );
		$source = array_filter( Diastoles_Texts::defaults(), static fn( mixed $value ): bool => '' !== trim( (string) $value ) );
		$total  = count( $source );
		$done   = 0;
		foreach ( array_keys( $source ) as $key ) {
			if ( '' !== trim( (string) ( $values[ $key ] ?? '' ) ) ) {
				$done++;
			}
		}
		return $total ? (int) round( 100 * $done / $total ) : 0;
	}

	public static function maybe_seed_bundled_translations(): void {
		if ( self::BUNDLED_VERSION === get_option( self::BUNDLED_VERSION_OPTION ) ) {
			return;
		}
		self::seed_bundled_question_translations();
		update_option( self::BUNDLED_VERSION_OPTION, self::BUNDLED_VERSION, false );
	}

	private static function bundled_interface_locale( string $locale ): array {
		$locale = self::normalize( $locale );
		if ( ! $locale || 'en' === $locale ) {
			return array();
		}
		if ( null === self::$bundled_interface ) {
			$path = DIASTOLES_DIR . 'data/interface-translations.json';
			self::$bundled_interface = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array();
			self::$bundled_interface = is_array( self::$bundled_interface ) ? self::$bundled_interface : array();
		}
		return is_array( self::$bundled_interface[ $locale ] ?? null ) ? self::$bundled_interface[ $locale ] : array();
	}

	private static function bundled_question_catalogs(): array {
		if ( null === self::$bundled_questions ) {
			$path = DIASTOLES_DIR . 'data/question-translations.json';
			self::$bundled_questions = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array();
			self::$bundled_questions = is_array( self::$bundled_questions ) ? self::$bundled_questions : array();
		}
		return self::$bundled_questions;
	}

	private static function seed_bundled_question_translations(): void {
		global $wpdb;
		$questions = $wpdb->get_results( 'SELECT id, prompt FROM ' . Diastoles_DB::table( 'questions' ) );
		if ( ! $questions ) {
			return;
		}
		$table    = Diastoles_DB::table( 'question_translations' );
		$catalogs = self::bundled_question_catalogs();
		foreach ( $questions as $question ) {
			foreach ( $catalogs as $locale => $catalog ) {
				$locale = self::normalize( $locale );
				if ( ! $locale || 'en' === $locale || ! is_array( $catalog ) ) {
					continue;
				}
				$translation = $catalog[ $question->prompt ] ?? null;
				if ( ! is_array( $translation ) ) {
					continue;
				}
				$existing = $wpdb->get_row( $wpdb->prepare(
					"SELECT prompt, short_label, followup FROM $table WHERE question_id = %d AND language_code = %s",
					(int) $question->id,
					$locale
				) );
				$data = array(
					'question_id'    => (int) $question->id,
					'language_code'  => $locale,
					'prompt'         => mb_substr( sanitize_textarea_field( $translation['prompt'] ?? '' ), 0, 1000 ),
					'short_label'    => mb_substr( sanitize_text_field( $translation['short_label'] ?? '' ), 0, 160 ),
					'followup'       => mb_substr( sanitize_textarea_field( $translation['followup'] ?? '' ), 0, 1000 ),
					'updated_at'     => current_time( 'mysql', true ),
				);
				if ( $existing ) {
					$has_manual_text = '' !== trim( (string) $existing->prompt )
						|| '' !== trim( (string) $existing->short_label )
						|| '' !== trim( (string) $existing->followup );
					if ( $has_manual_text ) {
						continue;
					}
					$wpdb->update(
						$table,
						array(
							'prompt'      => $data['prompt'],
							'short_label' => $data['short_label'],
							'followup'    => $data['followup'],
							'updated_at'  => $data['updated_at'],
						),
						array( 'question_id' => (int) $question->id, 'language_code' => $locale ),
						array( '%s', '%s', '%s', '%s' ),
						array( '%d', '%s' )
					);
					continue;
				}
				$wpdb->insert(
					$table,
					$data,
					array( '%d', '%s', '%s', '%s', '%s', '%s' )
				);
			}
		}
	}

	public static function dynamic_translation( string $text, string $locale, string $source_language = '' ): string {
		$text   = trim( $text );
		$locale = self::normalize( $locale ) ?: 'en';
		$source = self::normalize( $source_language );
		if ( '' === $text || is_numeric( $text ) || ( $source && $source === $locale ) ) {
			return $text;
		}
		global $wpdb;
		$table = Diastoles_DB::table( 'dynamic_translations' );
		$hash  = hash( 'sha256', $text );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT translated_text, status, updated_at FROM $table WHERE source_hash = %s AND language_code = %s", $hash, $locale ) );
		if ( $row && 'complete' === $row->status ) {
			return (string) $row->translated_text;
		}
		$participant = Diastoles_Session::current();
		if ( ! $row && ! $participant ) {
			return '';
		}
		if ( ! $row ) {
			$wpdb->insert( $table, array(
				'source_hash' => $hash,
				'language_code' => $locale,
				'source_text' => $text,
				'translated_text' => '',
				'status' => 'pending',
				'updated_at' => current_time( 'mysql', true ),
			), array( '%s', '%s', '%s', '%s', '%s', '%s' ) );
		}
		$is_stale = $row && in_array( $row->status, array( 'pending', 'failed' ), true )
			&& strtotime( $row->updated_at . ' UTC' ) < time() - MINUTE_IN_SECONDS;
		if ( $participant && self::$scheduled_this_request < 30 && ( $is_stale || ! wp_next_scheduled( 'diastoles_translate_dynamic_text', array( $hash, $locale ) ) ) ) {
			wp_schedule_single_event( time() + 1, 'diastoles_translate_dynamic_text', array( $hash, $locale ) );
			self::$scheduled_this_request++;
		}
		return '';
	}

	public static function ensure_dynamic_translation( string $text, string $locale, string $source_language = '', bool $process_now = false ): void {
		$text   = trim( $text );
		$locale = self::normalize( $locale ) ?: 'en';
		$source = self::normalize( $source_language );
		if ( '' === $text || is_numeric( $text ) || ( $source && $source === $locale ) ) {
			return;
		}

		global $wpdb;
		$table = Diastoles_DB::table( 'dynamic_translations' );
		$hash  = hash( 'sha256', $text );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM $table WHERE source_hash = %s AND language_code = %s", $hash, $locale ) );
		if ( $row && 'complete' === $row->status ) {
			return;
		}
		if ( ! $row ) {
			$wpdb->insert( $table, array(
				'source_hash' => $hash,
				'language_code' => $locale,
				'source_text' => $text,
				'translated_text' => '',
				'status' => 'pending',
				'updated_at' => current_time( 'mysql', true ),
			), array( '%s', '%s', '%s', '%s', '%s', '%s' ) );
		} else {
			$wpdb->update( $table, array( 'status' => 'pending', 'updated_at' => current_time( 'mysql', true ) ), array( 'source_hash' => $hash, 'language_code' => $locale ), array( '%s', '%s' ), array( '%s', '%s' ) );
		}

		if ( $process_now ) {
			self::process_dynamic_translation( $hash, $locale );
		} elseif ( ! wp_next_scheduled( 'diastoles_translate_dynamic_text', array( $hash, $locale ) ) ) {
			wp_schedule_single_event( time() + 1, 'diastoles_translate_dynamic_text', array( $hash, $locale ) );
		}
	}

	public static function pretranslate_response( object $response, array $analysis = array() ): void {
		$source = self::normalize( $response->source_language ?? '' );
		foreach ( self::active_languages() as $locale => $language ) {
			if ( 'en' === $locale || ( $source && $source === $locale ) ) {
				continue;
			}
			self::ensure_dynamic_translation( (string) $response->original_text, $locale, $source, true );
			foreach ( Diastoles_Anthropic::smell_concepts( $analysis ) as $concept ) {
				self::ensure_dynamic_translation( (string) ( $concept['phrase'] ?? '' ), $locale, 'en', true );
				foreach ( array_filter( array_map( 'strval', (array) ( $concept['anchors'] ?? array() ) ) ) as $anchor ) {
					self::ensure_dynamic_translation( $anchor, $locale, 'en', true );
				}
			}
		}
	}

	public static function process_dynamic_translation( string $hash, string $locale ): void {
		global $wpdb;
		$table = Diastoles_DB::table( 'dynamic_translations' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT source_text FROM $table WHERE source_hash = %s AND language_code = %s", $hash, $locale ) );
		if ( ! $row ) {
			return;
		}
		$result = Diastoles_Anthropic::translate_text( (string) $row->source_text, $locale );
		$wpdb->update( $table, array(
			'translated_text' => is_wp_error( $result ) ? '' : $result,
			'status' => is_wp_error( $result ) ? 'failed' : 'complete',
			'updated_at' => current_time( 'mysql', true ),
		), array( 'source_hash' => $hash, 'language_code' => $locale ), array( '%s', '%s', '%s' ), array( '%s', '%s' ) );
	}
}
