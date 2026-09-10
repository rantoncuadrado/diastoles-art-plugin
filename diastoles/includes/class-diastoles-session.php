<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_Session {
	private const COOKIE = 'diastoles_session';
	private static string $request_recovery_token = '';

	public static function use_request_recovery_token( string $token ): void {
		$token = sanitize_text_field( $token );
		self::$request_recovery_token = 64 === strlen( $token ) && ctype_xdigit( $token )
			? $token
			: '';
	}

	public static function current(): ?object {
		if ( ! empty( $_COOKIE[ self::COOKIE ] ) ) {
			$participant = self::find_by_hash(
				self::hash_token( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) ),
				'session_hash'
			);
			if ( $participant ) {
				return $participant;
			}
		}

		$recovery_token = self::$request_recovery_token;
		if ( ! $recovery_token ) {
			$recovery_token = sanitize_text_field(
				wp_unslash( $_SERVER['HTTP_X_DIASTOLES_RECOVERY'] ?? '' )
			);
		}
		if ( 64 !== strlen( $recovery_token ) || ! ctype_xdigit( $recovery_token ) ) {
			return null;
		}

		return self::find_by_hash( self::hash_token( $recovery_token ), 'recovery_hash' );
	}

	public static function create( string $pseudonym, bool $consent_fragments ): array {
		global $wpdb;

		$session_token  = bin2hex( random_bytes( 32 ) );
		$recovery_token = bin2hex( random_bytes( 32 ) );
		$now            = current_time( 'mysql', true );

		$wpdb->insert(
			Diastoles_DB::table( 'participants' ),
			array(
				'public_id'         => wp_generate_uuid4(),
				'session_hash'      => self::hash_token( $session_token ),
				'recovery_hash'     => self::hash_token( $recovery_token ),
				'pseudonym'         => mb_substr( sanitize_text_field( $pseudonym ), 0, 80 ),
				'consent_fragments' => $consent_fragments ? 1 : 0,
				'created_at'        => $now,
				'last_seen_at'      => $now,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		self::set_cookie( $session_token );

		return array(
			'participant_id' => (int) $wpdb->insert_id,
			'recovery_token' => $recovery_token,
		);
	}

	public static function recover( string $recovery_token ): bool {
		$participant = self::find_by_hash( self::hash_token( $recovery_token ), 'recovery_hash' );
		if ( ! $participant ) {
			return false;
		}

		global $wpdb;
		$session_token = bin2hex( random_bytes( 32 ) );
		$wpdb->update(
			Diastoles_DB::table( 'participants' ),
			array(
				'session_hash' => self::hash_token( $session_token ),
				'last_seen_at' => current_time( 'mysql', true ),
			),
			array( 'id' => (int) $participant->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		self::set_cookie( $session_token );
		return true;
	}

	private static function find_by_hash( string $hash, string $column ): ?object {
		global $wpdb;
		$table = Diastoles_DB::table( 'participants' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE $column = %s", $hash ) );

		if ( $row ) {
			$wpdb->update(
				$table,
				array( 'last_seen_at' => current_time( 'mysql', true ) ),
				array( 'id' => (int) $row->id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return $row ?: null;
	}

	private static function hash_token( string $token ): string {
		return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	private static function set_cookie( string $token ): void {
		$options = array(
			'expires'  => time() + ( 30 * DAY_IN_SECONDS ),
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		);
		setcookie( self::COOKIE, $token, $options );
		$_COOKIE[ self::COOKIE ] = $token;
	}
}
