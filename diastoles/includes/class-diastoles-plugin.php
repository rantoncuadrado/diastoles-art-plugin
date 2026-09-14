<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diastoles_Plugin {
	private static ?Diastoles_Plugin $instance = null;

	public static function instance(): Diastoles_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( 'Diastoles_DB', 'maybe_upgrade' ) );
		add_action( 'plugins_loaded', array( 'Diastoles_Texts', 'maybe_upgrade' ) );
		add_action( 'plugins_loaded', array( 'Diastoles_I18n', 'maybe_seed_bundled_translations' ) );
		add_action( 'init', array( $this, 'ensure_privacy_page' ), 5 );
		add_action( 'rest_api_init', array( 'Diastoles_REST', 'register_routes' ) );
		add_action( 'admin_menu', array( 'Diastoles_Admin', 'register_menu' ) );
		add_action( 'admin_init', array( 'Diastoles_Admin', 'register_settings' ) );
		add_action( 'admin_post_diastoles_moderate', array( 'Diastoles_Admin', 'moderate_connection' ) );
		add_action( 'wp_ajax_diastoles_moderate_connection_ajax', array( 'Diastoles_Admin', 'moderate_connection_ajax' ) );
		add_action( 'admin_post_diastoles_delete_participant', array( 'Diastoles_Admin', 'delete_participant' ) );
		add_action( 'admin_post_diastoles_moderate_response', array( 'Diastoles_Admin', 'moderate_response' ) );
		add_action( 'admin_post_diastoles_save_response_translation', array( 'Diastoles_Admin', 'save_response_translation' ) );
		add_action( 'admin_post_diastoles_save_dynamic_translation', array( 'Diastoles_Admin', 'save_dynamic_translation' ) );
		add_action( 'admin_post_diastoles_retry_dynamic_translations', array( 'Diastoles_Admin', 'retry_dynamic_translations' ) );
		add_action( 'admin_post_diastoles_retry_response_translations', array( 'Diastoles_Admin', 'retry_response_translations' ) );
		add_action( 'admin_post_diastoles_retry_response_processing', array( 'Diastoles_Admin', 'retry_response_processing' ) );
		add_action( 'admin_post_diastoles_test_anthropic', array( 'Diastoles_Admin', 'test_anthropic' ) );
		add_action( 'admin_post_diastoles_save_question', array( 'Diastoles_Admin', 'save_question' ) );
		add_action( 'admin_post_diastoles_export_questions', array( 'Diastoles_Admin', 'export_questions' ) );
		add_action( 'admin_post_diastoles_import_questions', array( 'Diastoles_Admin', 'import_questions' ) );
		add_action( 'admin_post_diastoles_export_events', array( 'Diastoles_Admin', 'export_events' ) );
		add_action( 'admin_post_diastoles_delete_events', array( 'Diastoles_Admin', 'delete_events' ) );
		add_action( 'admin_post_diastoles_save_interface_locale', array( 'Diastoles_Admin', 'save_interface_locale' ) );
		add_action( 'admin_post_diastoles_save_languages', array( 'Diastoles_Admin', 'save_languages' ) );
		add_action( 'admin_post_diastoles_export', array( 'Diastoles_Admin', 'export_data' ) );
		add_action( 'admin_post_diastoles_recalculate', array( 'Diastoles_Admin', 'queue_recalculation' ) );
		add_action( 'admin_post_diastoles_apply_matching_preset', array( 'Diastoles_Admin', 'apply_matching_preset' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'diastoles_process_response', array( $this, 'process_response' ) );
		add_action( 'diastoles_translate_dynamic_text', array( 'Diastoles_I18n', 'process_dynamic_translation' ), 10, 2 );
		add_action( 'diastoles_run_recalculation_job', array( 'Diastoles_Admin', 'run_recalculation_job' ) );
		add_filter( 'template_include', array( $this, 'experience_template' ), 99 );
		add_filter( 'document_title_parts', array( $this, 'experience_title' ) );
		add_filter( 'script_loader_tag', array( $this, 'block_external_translation_script' ), 20, 2 );
		add_shortcode( 'diastoles_experience', array( $this, 'render_shortcode' ) );
	}

	public function register_assets(): void {
		wp_register_style(
			'diastoles',
			DIASTOLES_URL . 'public/diastoles.css',
			array(),
			DIASTOLES_VERSION . '.' . filemtime( DIASTOLES_DIR . 'public/diastoles.css' )
		);
		wp_register_script(
			'diastoles',
			DIASTOLES_URL . 'public/diastoles.js',
			array(),
			DIASTOLES_VERSION . '.' . filemtime( DIASTOLES_DIR . 'public/diastoles.js' ),
			true
		);

		if ( $this->is_privacy_page() ) {
			wp_enqueue_style( 'diastoles' );
		}
	}

	public function render_shortcode(): string {
		wp_enqueue_style( 'diastoles' );
		wp_enqueue_script( 'diastoles' );
		wp_localize_script(
			'diastoles',
			'DiastolesConfig',
			array(
				'interfaceText'       => Diastoles_I18n::interface_texts( Diastoles_I18n::settings()['default'] ),
				'interfaceCatalogs'   => Diastoles_I18n::interface_catalogs(),
				'languages'           => Diastoles_I18n::active_languages(),
				'defaultLanguage'     => Diastoles_I18n::settings()['default'],
				'privacyUrl'          => $this->privacy_page_url(),
			)
		);

		$initial_photo = DIASTOLES_URL . 'public/images/steps-togetherness.webp?ver=' . DIASTOLES_VERSION;

		return '<div id="diastoles-background" class="diastoles-background" aria-hidden="true" style="--dia-photo:url(\''
			. esc_url( $initial_photo ) . '\')" data-photo="steps-togetherness.webp"></div>'
			. '<div id="diastoles-app" class="diastoles-shell notranslate" translate="no" aria-live="polite" data-photo="steps-togetherness.webp" data-rest-url="'
			. esc_url( rest_url( 'diastoles/v1/' ) ) . '" data-assets-url="'
			. esc_url( DIASTOLES_URL . 'public/images/' ) . '" data-assets-version="'
			. esc_attr( DIASTOLES_VERSION ) . '">'
			. '<header class="diastoles-header diastoles-boot-header" aria-label="Diástoles">'
			. '<span class="diastoles-wordmark">Diástoles</span>'
			. '</header>'
			. '</div>';
	}

	public function ensure_privacy_page(): void {
		$page_id = (int) get_option( 'diastoles_privacy_page_id', 0 );
		if ( $page_id && 'trash' !== get_post_status( $page_id ) ) {
			return;
		}

		$existing = get_page_by_path( 'diastoles-privacy', OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			update_option( 'diastoles_privacy_page_id', $existing->ID, false );
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => 'diastoles-privacy',
				'post_title'   => 'Diástoles Privacy Notice',
				'post_content' => $this->privacy_page_content(),
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( 'diastoles_privacy_page_id', (int) $page_id, false );
		}
	}

	private function privacy_page_url(): string {
		$page_id = (int) get_option( 'diastoles_privacy_page_id', 0 );
		$url     = $page_id ? get_permalink( $page_id ) : false;
		return $url ? $url : home_url( '/diastoles-privacy/' );
	}

	private function is_privacy_page(): bool {
		$page_id = (int) get_option( 'diastoles_privacy_page_id', 0 );
		return $page_id > 0 && is_page( $page_id );
	}

	private function is_experience_page(): bool {
		if ( ! is_page() ) {
			return false;
		}
		$post = get_queried_object();
		return $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'diastoles_experience' );
	}

	public function block_external_translation_script( string $tag, string $handle ): string {
		return $this->is_experience_page() && str_starts_with( $handle, 'gt_widget_script_' ) ? '' : $tag;
	}

	private function privacy_page_content(): string {
		return <<<'HTML'
<!-- wp:paragraph -->
<p>Last updated: 31 August 2026</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Who is responsible?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Paco Santamaría and Raúl Antón Cuadrado are responsible for the Diástoles creative project. You can contact us at <a href="mailto:raulanton@gmail.com">raulanton@gmail.com</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What information do we collect?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We store your contributions, a private anonymous-session identifier and any pseudonym or optional profile details you choose to provide. If you provide an email address, we store it only so that we can send you one publication notice. Our hosting provider may also process ordinary technical information such as IP address, browser type and access time.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Why do we use it?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We use this information to operate Diástoles, translate contributions, identify semantic relationships, enrich and diversify matches using optional profile details, and display contributions and connections anonymously as part of the creative project.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How is AI used?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Contribution text may be sent to Anthropic’s API for translation, scent analysis and relationship matching. The AI does not rewrite your contribution and does not make decisions that have legal or similarly significant effects. Your email address is never sent to the AI.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Legal basis</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The legal basis is your consent. Participation, your pseudonym, your email address and every profile detail are optional. You may withdraw your consent at any time.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Who processes the information?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>WordPress.com/Automattic provides hosting and Anthropic processes contribution text through its API. Interface and question translations are stored directly by Diástoles and are not sent to a browser-side translation provider. These providers may process information outside the European Economic Area under the safeguards included in their applicable data-processing terms.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What becomes public?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Visitors may see your contribution, its translation and relationships with other fragments. They will not see your email address, private session link or optional profile details.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How long do we keep it?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We keep contributions and pseudonymous project information while the creative project remains active and delete or irreversibly anonymise them when they are no longer needed. An optional email address is deleted after the publication notice is sent. You may withdraw a contribution earlier using your private link or contact us to exercise your rights.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Your rights</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>You may ask to access, correct or delete your information, restrict or object to its processing, request portability, or withdraw your consent by writing to <a href="mailto:raulanton@gmail.com">raulanton@gmail.com</a>. You may also lodge a complaint with the <a href="https://www.aepd.es/" target="_blank" rel="noreferrer noopener">Spanish Data Protection Agency</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Essential storage</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Diástoles uses the storage necessary to maintain and recover your private anonymous session and to remember your selected interface language. Keep your private return link confidential: anyone with that link can access your anonymous session.</p>
<!-- /wp:paragraph -->
HTML;
	}

	public function experience_template( string $template ): string {
		if ( $this->is_privacy_page() ) {
			return DIASTOLES_DIR . 'templates/privacy.php';
		}

		if ( ! is_page() ) {
			return $template;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! has_shortcode( (string) $post->post_content, 'diastoles_experience' ) ) {
			return $template;
		}

		return DIASTOLES_DIR . 'templates/experience.php';
	}

	public function experience_title( array $title ): array {
		if ( $this->is_privacy_page() ) {
			return array( 'title' => 'Diástoles Privacy Notice' );
		}

		if ( is_page() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'diastoles_experience' ) ) {
				return array( 'title' => 'Diástoles' );
			}
		}
		return $title;
	}

	public function process_response( int $response_id ): void {
		$response = Diastoles_DB::get_response( $response_id );
		if ( ! $response || 'pending' !== $response->processing_status ) {
			return;
		}
		if ( $this->analyze_response( $response_id ) ) {
			Diastoles_Matcher::create_candidates( $response_id );
		}
	}

	public function recalculate_response( int $response_id, string $mode ): bool {
		$mode = in_array( $mode, array( 'concepts', 'relationships', 'translations', 'both' ), true ) ? $mode : 'both';
		if ( in_array( $mode, array( 'concepts', 'both' ), true ) && ! $this->analyze_response( $response_id ) ) {
			return false;
		}
		if ( 'translations' === $mode ) {
			$response = Diastoles_DB::get_response( $response_id );
			if ( ! $response || 'complete' !== $response->processing_status ) {
				return false;
			}
			Diastoles_I18n::pretranslate_response( $response, json_decode( (string) $response->analysis_json, true ) ?: array() );
			return true;
		}
		if ( in_array( $mode, array( 'relationships', 'both' ), true ) ) {
			$response = Diastoles_DB::get_response( $response_id );
			if ( ! $response || 'complete' !== $response->processing_status ) {
				return false;
			}
			Diastoles_Matcher::create_candidates( $response_id );
		}
		return true;
	}

	private function analyze_response( int $response_id ): bool {
		$response = Diastoles_DB::get_response( $response_id );
		if ( ! $response ) {
			return false;
		}

		Diastoles_DB::update_response_status( $response_id, 'processing' );
		$analysis = Diastoles_Anthropic::analyze_response(
			$response->original_text,
			$response->explanation_text
		);

		if ( is_wp_error( $analysis ) ) {
			Diastoles_DB::record_processing_error( $response_id, $analysis->get_error_message() );
			Diastoles_DB::mark_response_failed( $response_id );
			return false;
		}

		Diastoles_DB::save_analysis( $response_id, $analysis );
		$translated_response = Diastoles_DB::get_response( $response_id );
		if ( $translated_response ) {
			Diastoles_I18n::pretranslate_response( $translated_response, $analysis, false );
		}
		return true;
	}
}
