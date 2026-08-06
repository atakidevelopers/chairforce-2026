<?php

namespace ChairforceDataNormalise\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Import_Elementor_Faqs' ) ) {
	return;
}

/**
 * Imports FAQ posts from Elementor nested-accordion widgets on a legacy page.
 */
class Import_Elementor_Faqs {

	public $batch = 10;

	public $label = 'Import FAQs from Elementor accordion page';

	public $description = 'Reads nested-accordion Q&A pairs from page #1510 (_elementor_data), creates chairforce_faq posts and chairforce_faq_category terms using Elementor category labels unchanged. Post title = plain question; post_content = accordion answer HTML. Empty answers are imported with a log warning. Idempotent via _legacy_elementor_faq_source_key meta. Safe to re-run.';

	private const SOURCE_PAGE_ID = 1510;

	private const FAQ_POST_TYPE = 'chairforce_faq';

	private const FAQ_TAXONOMY = 'chairforce_faq_category';

	private const SOURCE_META_KEY = '_legacy_elementor_faq_source_key';

	/**
	 * Parsed FAQ rows keyed by source ID.
	 *
	 * @var array<string, array{source_key: string, category_name: string, question: string, answer_html: string}>|null
	 */
	private static $parsed_faqs = null;

	/**
	 * @return string[] Source keys for BatchPress.
	 */
	public function items(): array {
		return array_keys( $this->get_parsed_faqs() );
	}

	/**
	 * @param string $source_key Legacy source identifier.
	 */
	public function process( $source_key ) {
		$source_key = (string) $source_key;
		$faqs       = $this->get_parsed_faqs();

		if ( ! isset( $faqs[ $source_key ] ) ) {
			return "{$source_key}: unknown source key — skipped.";
		}

		$faq = $faqs[ $source_key ];

		$existing_id = $this->find_existing_faq_post_id( $source_key );
		if ( $existing_id ) {
			return sprintf(
				'"%s" [%s]: already imported as FAQ #%d — skipped.',
				$faq['question'],
				$faq['category_name'],
				$existing_id
			);
		}

		$term_id = $this->ensure_category_term( $faq['category_name'] );
		if ( is_wp_error( $term_id ) ) {
			return sprintf(
				'"%s" [%s]: failed to create category "%s" — %s',
				$faq['question'],
				$faq['category_name'],
				$faq['category_name'],
				$term_id->get_error_message()
			);
		}

		$post_id = wp_insert_post(
			[
				'post_type'    => self::FAQ_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $faq['question'],
				'post_content' => $faq['answer_html'],
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return sprintf(
				'"%s" [%s]: failed to create FAQ — %s',
				$faq['question'],
				$faq['category_name'],
				$post_id->get_error_message()
			);
		}

		update_post_meta( $post_id, self::SOURCE_META_KEY, $source_key );

		$assigned = wp_set_object_terms( $post_id, [ (int) $term_id ], self::FAQ_TAXONOMY, false );
		if ( is_wp_error( $assigned ) ) {
			return sprintf(
				'FAQ #%d "%s": created but failed to assign category "%s" — %s',
				$post_id,
				$faq['question'],
				$faq['category_name'],
				$assigned->get_error_message()
			);
		}

		$warning = '' === trim( wp_strip_all_tags( $faq['answer_html'] ) )
			? ' WARNING: empty answer in Elementor source.'
			: '';

		return sprintf(
			'FAQ #%d "%s" → category "%s".%s',
			$post_id,
			$faq['question'],
			$faq['category_name'],
			$warning
		);
	}

	/**
	 * @return array<string, array{source_key: string, category_name: string, question: string, answer_html: string}>
	 */
	private function get_parsed_faqs(): array {
		if ( null !== self::$parsed_faqs ) {
			return self::$parsed_faqs;
		}

		self::$parsed_faqs = [];

		$raw = get_post_meta( self::SOURCE_PAGE_ID, '_elementor_data', true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return self::$parsed_faqs;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return self::$parsed_faqs;
		}

		$this->walk_elementor_elements( $data );

		return self::$parsed_faqs;
	}

	/**
	 * @param array<int, mixed> $elements Elementor element tree.
	 */
	private function walk_elementor_elements( array $elements ): void {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$section_id = $this->get_section_id( $element );
			if ( '' !== $section_id ) {
				$this->walk_section( $element, $section_id );
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->walk_elementor_elements( $element['elements'] );
			}
		}
	}

	/**
	 * @param array<string, mixed> $section_element Section container.
	 */
	private function walk_section( array $section_element, string $section_id ): void {
		$category_name = $this->get_category_name_from_section( $section_element );
		if ( '' === $category_name ) {
			return;
		}

		$this->collect_accordion_faqs( $section_element['elements'] ?? [], $section_id, $category_name );
	}

	/**
	 * @param array<int, mixed> $elements Elements inside a FAQ section.
	 */
	private function collect_accordion_faqs( array $elements, string $section_id, string $category_name ): void {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( ( $element['widgetType'] ?? '' ) === 'nested-accordion' ) {
				$this->extract_nested_accordion_faqs( $element, $section_id, $category_name );
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->collect_accordion_faqs( $element['elements'], $section_id, $category_name );
			}
		}
	}

	/**
	 * @param array<string, mixed> $accordion Nested accordion widget.
	 */
	private function extract_nested_accordion_faqs( array $accordion, string $section_id, string $category_name ): void {
		$items    = $accordion['settings']['items'] ?? [];
		$children = $accordion['elements'] ?? [];

		if ( ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$question_raw = (string) ( $item['item_title'] ?? '' );
			$question     = trim( wp_strip_all_tags( $question_raw ) );

			if ( '' === $question ) {
				continue;
			}

			$child     = is_array( $children[ $index ] ?? null ) ? $children[ $index ] : [];
			$answer_html = $this->get_text_editor_content( $child );

			$source_key = sprintf( '%d:%s:%d', self::SOURCE_PAGE_ID, $section_id, (int) $index );

			self::$parsed_faqs[ $source_key ] = [
				'source_key'    => $source_key,
				'category_name' => $category_name,
				'question'      => $question,
				'answer_html'   => $answer_html,
			];
		}
	}

	/**
	 * @param array<string, mixed> $container Accordion item container.
	 */
	private function get_text_editor_content( array $container ): string {
		$elements = $container['elements'] ?? [];
		if ( ! is_array( $elements ) ) {
			return '';
		}

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( ( $element['widgetType'] ?? '' ) === 'text-editor' ) {
				return (string) ( $element['settings']['editor'] ?? '' );
			}
		}

		return '';
	}

	/**
	 * Category label from the icon-list widget in this section (Elementor display name, unchanged).
	 *
	 * @param array<string, mixed> $section_element Section container.
	 */
	private function get_category_name_from_section( array $section_element ): string {
		return $this->find_icon_list_text( $section_element['elements'] ?? [] );
	}

	/**
	 * @param array<int, mixed> $elements Section descendants.
	 */
	private function find_icon_list_text( array $elements ): string {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( ( $element['widgetType'] ?? '' ) === 'icon-list' ) {
				$icon_list = $element['settings']['icon_list'] ?? [];
				if ( is_array( $icon_list ) ) {
					foreach ( $icon_list as $item ) {
						if ( ! is_array( $item ) ) {
							continue;
						}

						$text = trim( (string) ( $item['text'] ?? '' ) );
						if ( '' !== $text ) {
							return $text;
						}
					}
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$nested = $this->find_icon_list_text( $element['elements'] );
				if ( '' !== $nested ) {
					return $nested;
				}
			}
		}

		return '';
	}

	/**
	 * @param array<string, mixed> $element Elementor element.
	 */
	private function get_section_id( array $element ): string {
		if ( ( $element['elType'] ?? '' ) !== 'container' ) {
			return '';
		}

		return trim( (string) ( $element['settings']['_element_id'] ?? '' ) );
	}

	/**
	 * @param string $source_key Legacy source identifier.
	 */
	private function find_existing_faq_post_id( string $source_key ): int {
		$posts = get_posts(
			[
				'post_type'              => self::FAQ_POST_TYPE,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_key'               => self::SOURCE_META_KEY,
				'meta_value'             => $source_key,
			]
		);

		return ! empty( $posts ) ? (int) $posts[0] : 0;
	}

	/**
	 * @param string $category_name Elementor category label.
	 * @return int|\WP_Error Term ID.
	 */
	private function ensure_category_term( string $category_name ) {
		$existing = term_exists( $category_name, self::FAQ_TAXONOMY );
		if ( is_array( $existing ) && ! empty( $existing['term_id'] ) ) {
			return (int) $existing['term_id'];
		}

		if ( is_int( $existing ) ) {
			return $existing;
		}

		$created = wp_insert_term(
			$category_name,
			self::FAQ_TAXONOMY,
			[
				'slug' => sanitize_title( $category_name ),
			]
		);

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		return (int) ( $created['term_id'] ?? 0 );
	}
}
