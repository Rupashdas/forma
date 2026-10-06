<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The Contact page (spec 5.2): the studio's block in Lisbon and a way to write.
 *
 * 1. Hero: an inset Panel with the H1, a line of intro and the studio's email as a giant link.
 * 2. The enquiry form: a Contact Form 7 form on a Panel tile, its fields in the Paper colour with a visible label over
 *    each, then a photograph of the street outside the studio.
 * 3. Location: an inset Panel with the Lisbon block as a study model (the studio is its dark building, called out) beside
 *    the address, hours, phone and social links.
 *
 * The form is a `wpcf7_contact_form` post created here, so the page, the form's fields and its mail settings all come from
 * code. It is found again by its `_forma_seed` marker and updated on every build, so the form is owned by this seeder:
 * edit it here, not in the Contact Form 7 screen. Defer Forms for Contact Form 7 skins the controls (its select widget,
 * checkbox and focus ring) through `--deferforms-*` custom properties, which the form's Custom CSS points at FORMA's
 * colours; the "Nice Select for WP" plugin is not installed and not needed.
 */
final class Contact extends Page {

	public const KEY = 'contact';

	/** The seed marker of the enquiry form. */
	public const FORM_SEED = 'form:enquiry';

	protected function key(): string {
		return self::KEY;
	}

	protected function summary(): string {
		return '3 sections, 1 model, enquiry form #' . $this->form_id();
	}

	protected function sections(): array {
		return array(
			$this->hero(),
			$this->enquiry(),
			$this->location(),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 1. Hero.
	// ---------------------------------------------------------------------------------------------------------------

	/** The H1, a line of intro and the studio's email, set as large as a headline and linked. */
	private function hero(): array {
		$page  = $this->site['contact'];
		$email = $this->site['studio']['email'];

		return $this->masthead(
			$page['title'],
			$page['intro'],
			array(
				Style::display(
					$email,
					'clamp(26px, 6.2vw, 100px)',
					'p',
					'ink',
					array(
						'link'       => Style::link( 'mailto:' . $email ),
						'custom_css' => <<<'CSS'
						selector .elementor-heading-title {
							letter-spacing: -0.035em;
							line-height: 1;
						}
						selector a {
							overflow-wrap: anywhere;
							text-decoration: underline;
							text-decoration-thickness: 0.05em;
							text-underline-offset: 0.12em;
							transition: color 0.4s var(--forma-ease);
						}
						selector a:hover,
						selector a:focus-visible {
							color: var(--forma-accent);
						}
						CSS,
					)
				),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. The form.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The title and the note at the left (34%), the form on its tile at the right (62%), then the photograph of the street
	 * outside the studio, rounded, across the column with its caption. Below 1024px the title and the note stack over the
	 * form.
	 */
	private function enquiry(): array {
		$page = $this->site['contact'];

		return $this->band(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								$this->heading( $page['form_title'] ),
								$this->paragraph( $page['form_note'], 'muted', 38 ),
							),
							34,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 'clamp(14px, 1.6vw, 20px)', null, 'custom' ) )
						),
						Style::cell( array( $this->form_tile() ), 62, 100, 100 ),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'flex-start',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ),
					)
				),
				$this->street(),
			),
			'clamp(24px, 3vw, 48px)',
			'clamp(8px, 1vw, 16px)',
			array( 'flex_gap' => Builder::gap( 'clamp(40px, 5vw, 80px)', null, 'custom' ) )
		);
	}

	/** The photograph of the street outside the studio, rounded, 21:9 on desktop, with its caption. */
	private function street(): array {
		$id      = $this->image_id( 'contact-01.jpg' );
		$caption = (string) get_post_field( 'post_excerpt', $id );
		$parts   = array( $this->photo( 'contact-01.jpg', array( '21 / 9', '3 / 2', '4 / 3' ), '50% 60%' ) );

		if ( '' !== $caption ) {
			$parts[] = Style::heading( $caption, 'meta', 'p', 'muted' );
		}

		return Style::stack( $parts, array( 'flex_gap' => Builder::gap( 10 ) ) );
	}

	/** A Panel tile that holds the form, with its title for assistive technology left to the section's heading. */
	private function form_tile(): array {
		return $this->tile(
			array( $this->form_widget() ),
			array(
				'border_radius' => Builder::box( 'var(--forma-r-panel)', null, null, null, 'custom' ),
				'padding'       => Builder::box( 'clamp(22px, 3vw, 44px)', 'clamp(22px, 3vw, 44px)', 'clamp(22px, 3vw, 44px)', 'clamp(22px, 3vw, 44px)', 'custom' ),
			)
		);
	}

	/** The form's shortcode in Elementor's Shortcode widget, with the styles that fit it to the tile. */
	private function form_widget(): array {
		$this->form_design();

		return Builder::widget( 'shortcode', array( 'shortcode' => $this->form_shortcode() ) );
	}

	/**
	 * The FORMA look for every Contact Form 7 form on the site, set in Defer Forms for Contact Form 7's own design
	 * settings (the same values its Design screen saves): Paper fields with a quiet border on the Panel tile, 12px
	 * corners, a Signal blue accent and focus ring, and an Ink button. The theme's site.css adds only what those settings
	 * don't cover (pill button, labels, response box).
	 */
	private function form_design(): void {
		if ( ! class_exists( \DEFERFORMS\DB\Settings_Repository::class ) ) {
			return;
		}

		( new \DEFERFORMS\DB\Settings_Repository() )->update_section(
			'design',
			array(
				'primary'          => '#2b3bff',
				'primary_contrast' => '#ffffff',
				'text'             => '#141414',
				'muted'            => '#55554f',
				'border'           => '#dcdbd5',
				'bg'               => '#f6f5f1',
				'surface_alt'      => '#e9e8e3',
				'error'            => '#b3261e',
				'radius'           => 12,
				'control_height'   => 52,
				'font_size'        => 16,
				'padding_x'        => 16,
				'padding_y'        => 14,
				'gap'              => 20,
				'ring'             => 3,
				'button_custom'    => true,
				'button_bg'        => '#141414',
				'button_text'      => '#f6f5f1',
			)
		);
	}

	/** The form's id, creating or updating it first. */
	private function form_id(): int {
		static $id = 0;

		return $id ?: $id = $this->upsert_form();
	}

	/**
	 * The shortcode that shows the form, with the class that scopes the form's own styles.
	 *
	 * @throws \RuntimeException When Contact Form 7 is not active.
	 */
	private function form_shortcode(): string {
		$form = \WPCF7_ContactForm::get_instance( $this->form_id() );

		return rtrim( $form->shortcode(), ']' ) . ' html_class="forma-form"]';
	}

	/**
	 * Create the enquiry form, or update it when it already exists: its fields, mail and messages.
	 *
	 * @throws \RuntimeException When Contact Form 7 is not active.
	 */
	private function upsert_form(): int {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			throw new \RuntimeException( 'Contact Form 7 is not active: the Contact page needs it for the enquiry form.' );
		}

		$page = $this->site['contact'];
		$ids  = get_posts(
			array(
				'post_type'      => \WPCF7_ContactForm::post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => Content::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
				'meta_value'     => self::FORM_SEED, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		$form = $ids
			? \WPCF7_ContactForm::get_instance( (int) $ids[0] )
			: \WPCF7_ContactForm::get_template(
				array(
					'title'  => $page['form_name'],
					'locale' => 'en_GB',
				)
			);

		$form->set_title( $page['form_name'] );
		$form->set_properties(
			array(
				'form'     => $this->form_markup(),
				'mail'     => $this->mail(),
				'messages' => $this->messages() + (array) $form->prop( 'messages' ),
			)
		);

		$id = (int) $form->save();

		if ( ! $id ) {
			throw new \RuntimeException( 'Contact Form 7 refused to save the enquiry form.' );
		}

		update_post_meta( $id, Content::SEED_KEY, self::FORM_SEED );

		return $id;
	}

	/**
	 * The form's template: a visible label over every field, in a div the page's CSS lays out in two columns. Each
	 * field is on one line, so Contact Form 7's paragraph handling adds no line breaks inside it. The fields are name,
	 * email, project type, budget range, site location, timeline and message, and the acceptance of the privacy note.
	 */
	private function form_markup(): string {
		$page  = $this->site['contact'];
		$types = '"' . implode( '" "', $page['project_types'] ) . '"';
		$sums  = '"' . implode( '" "', $page['budgets'] ) . '"';

		$field = static fn( string $label, string $id, string $tag ): string => sprintf( '<label for="%1$s">%2$s</label>%3$s', $id, $label, $tag );
		$row   = static fn( string ...$cols ): string => '[deferforms_row cols="' . count( $cols ) . '"]' . implode( '', array_map( static fn( string $c ): string => '[deferforms_col]' . $c . '[/deferforms_col]', $cols ) ) . '[/deferforms_row]';

		return implode(
			"

",
			array(
				$row(
					$field( 'Name', 'forma-name', '[text* your-name id:forma-name autocomplete:name]' ),
					$field( 'Email', 'forma-email', '[email* your-email id:forma-email autocomplete:email]' )
				),
				$row(
					$field( 'Project type', 'forma-type', "[select* project-type id:forma-type first_as_label \"Choose one\" {$types}]" ),
					$field( 'Budget range (optional)', 'forma-budget', "[select budget id:forma-budget first_as_label \"Choose a range\" {$sums}]" )
				),
				$row(
					$field( 'Site location (optional)', 'forma-location', '[text site-location id:forma-location autocomplete:off]' ),
					$field( 'When would you like to start? (optional)', 'forma-timeline', '[text timeline id:forma-timeline autocomplete:off]' )
				),
				$row( $field( 'Message', 'forma-message', '[textarea* your-message id:forma-message]' ) ),
				$row( '[acceptance forma-consent id:forma-consent]' . $page['consent'] . '[/acceptance]' ),
				'[submit class:forma-submit "Send message"]',
			)
		);
	}
	/** The email to the studio, with the sender's address as Reply-To so a reply goes straight to them. */
	private function mail(): array {
		$studio = $this->site['studio'];

		return array(
			'subject'            => '[_site_title] enquiry: [project-type] from [your-name]',
			'sender'             => '[_site_title] <' . \WPCF7_ContactFormTemplate::from_email() . '>',
			'recipient'          => $studio['email'],
			'additional_headers' => 'Reply-To: [your-name] <[your-email]>',
			'body'               => implode(
				"\n",
				array(
					'From: [your-name] <[your-email]>',
					'Project type: [project-type]',
					'Budget range: [budget]',
					'Site location: [site-location]',
					'Start: [timeline]',
					'',
					'[your-message]',
					'',
					'-- ',
					'Sent from the enquiry form on [_site_title] ([_site_url]).',
				)
			),
			'attachments'        => '',
			'use_html'           => 0,
			'exclude_blank'      => 1,
		) + \WPCF7_ContactFormTemplate::mail();
	}

	/**
	 * The messages the form shows after a submission, in the studio's voice; the rest stay Contact Form 7's own.
	 *
	 * @return array<string,string>
	 */
	private function messages(): array {
		$email = $this->site['studio']['email'];

		return array(
			'mail_sent_ok'     => $this->site['contact']['success'],
			'mail_sent_ng'     => "Your message could not be sent just now. Please write to us at {$email} instead.",
			'spam'             => "Your message could not be sent just now. Please write to us at {$email} instead.",
			'validation_error' => 'Some answers need attention. Please check the messages beside the fields and send it again.',
			'accept_terms'     => 'Please tick the box so that we may reply to you.',
			'invalid_required' => 'Please fill in this field.',
			'invalid_email'    => 'Please enter an email address like name@company.com.',
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 3. Location.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * An inset Panel in two columns from 1024px: the title, the note and the details at the left (40%), and the Lisbon
	 * block at the right (56%), the studio called out in it, which can be turned. Below that they stack, the model
	 * first.
	 */
	private function location(): array {
		$page = $this->site['contact'];

		return $this->panel(
			array(
				Style::row(
					array(
						Style::cell(
							array_merge(
								array(
									$this->heading( $page['visit_title'] ),
									$this->paragraph( $page['visit_note'], 'muted', 40 ),
								),
								$this->details()
							),
							40,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 'clamp(16px, 2vw, 24px)', null, 'custom' ) )
						),
						Style::cell(
							array(
								Builder::widget(
									'forma-study-model',
									array(
										'source'             => 'recipe',
										'recipe'             => 'lisbon-block',
										'camera'             => 'three-quarter',
										'view_height'        => Builder::size( 64, 'vh' ),
										'view_height_tablet' => Builder::size( 52, 'vh' ),
										'view_height_mobile' => Builder::size( 46, 'vh' ),
										'assemble'           => 'yes',
										'drag'               => 'yes',
										'callouts'           => 'yes',
									)
								),
							),
							56,
							100,
							100
						),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'center',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(24px, 4vw, 64px)', null, 'custom' ),
					)
				),
			),
			'clamp(40px, 5vw, 80px)',
			'clamp(24px, 3vw, 48px)'
		);
	}

	/**
	 * Address, hours, phone and social links, each under a hairline with its label.
	 *
	 * @return array[]
	 */
	private function details(): array {
		$studio  = $this->site['studio'];
		$address = '<p>' . implode( '<br>', array_map( 'esc_html', $studio['address'] ) ) . '</p>';
		$phone   = sprintf( '<p><a href="tel:%s">%s</a></p>', esc_attr( preg_replace( '/[^\d+]/', '', $studio['phone'] ) ), esc_html( $studio['phone'] ) );
		$social  = array();

		foreach ( $studio['social'] as $label => $url ) {
			$social[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
		}

		$items = array(
			'Address'   => $address,
			'Hours'     => '<p>' . esc_html( $studio['hours'] ) . '</p>',
			'Phone'     => $phone,
			'Elsewhere' => '<p>' . implode( '<br>', $social ) . '</p>',
		);

		$blocks = array();

		foreach ( $items as $label => $html ) {
			$blocks[] = Style::stack(
				array(
					Style::label( $label ),
					// The phone number and the profiles are links on lines of their own: a 4px pad on a mouse, 12px on a phone.
					Style::text(
						$html,
						'body',
						'ink',
						array( 'custom_css' => 'selector a { display: inline-block; padding-block: 4px; } @media (max-width: 1024px) { selector a { padding-block: 12px; } }' )
					),
				),
				array(
					'flex_gap'      => Builder::gap( 6 ),
					'padding'       => Builder::box( 14, 0, 0, 0 ),
					'border_border' => 'solid',
					'border_width'  => Builder::box( 1, 0, 0, 0 ),
					'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
				)
			);
		}

		return $blocks;
	}
}
