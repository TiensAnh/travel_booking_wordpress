<?php
/**
 * Template Name: Danh sách tour
 *
 * @package Travel_Agency_Modern
 */

get_header();

$search_term         = isset( $_GET['search_tour'] ) ? sanitize_text_field( wp_unslash( $_GET['search_tour'] ) ) : '';
$selected_dest       = isset( $_GET['destination'] ) ? sanitize_title( wp_unslash( $_GET['destination'] ) ) : '';
$paged               = max(
	1,
	(int) get_query_var( 'paged' ),
	(int) get_query_var( 'page' ),
	isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 0
);
$current_page_post   = null;
$contact             = tam_get_contact_details();
$page_url            = '';
$tour_results_markup = '';
$tour_status_summary = '';

while ( have_posts() ) :
	the_post();
	$current_page_post = get_post();
endwhile;

$page_url = $current_page_post ? get_permalink( $current_page_post ) : tam_get_page_url_by_path( 'tour' );

$backend_tour_payload = function_exists( 'tam_backend_api_get_tour_archive_payload' )
	? tam_backend_api_get_tour_archive_payload( $search_term, $selected_dest, $paged, $page_url )
	: array(
		'success' => false,
	);

if ( ! empty( $backend_tour_payload['success'] ) ) {
	$tour_status_summary = isset( $backend_tour_payload['summary'] ) ? (string) $backend_tour_payload['summary'] : '';
	$tour_results_markup = isset( $backend_tour_payload['html'] ) ? (string) $backend_tour_payload['html'] : '';
} else {
	$tour_query          = new WP_Query( tam_get_tour_query_args( $search_term, $selected_dest, $paged ) );
	$tour_status_summary = $tour_query->found_posts
		? sprintf(
			/* translators: %s is the number of matching tours. */
			_n( '%s tour phu hop', '%s tour phu hop', $tour_query->found_posts, 'travel-agency-modern' ),
			number_format_i18n( $tour_query->found_posts )
		)
		: __( 'Không tìm thấy tour phù hợp', 'travel-agency-modern' );
	$tour_results_markup = tam_get_tour_results_markup(
		$tour_query,
		array(
			'search_term'   => $search_term,
			'selected_dest' => $selected_dest,
			'base_url'      => $page_url,
			'current_page'  => $paged,
		)
	);
}

$destinations = get_terms(
	array(
		'taxonomy'   => 'tour_destination',
		'hide_empty' => true,
	)
);

tam_render_page_intro(
	array(
		'eyebrow'     => __( 'Danh sách tour', 'travel-agency-modern' ),
		'title'       => $current_page_post ? get_the_title( $current_page_post ) : __( 'Tour', 'travel-agency-modern' ),
		'description' => $current_page_post && has_excerpt( $current_page_post ) ? get_the_excerpt( $current_page_post ) : __( 'Khám phá các hành trình nổi bật và chọn tour phù hợp theo điểm đến, thời gian và nhu cầu của bạn.', 'travel-agency-modern' ),
		'image'       => $current_page_post ? tam_get_hero_image_url( $current_page_post->ID ) : tam_get_hero_image_url(),
	)
);
?>
<main id="main-content" class="site-main">
	<section class="tam-section tam-section--compact">
		<div class="tam-container">
			<?php if ( $current_page_post && ! empty( trim( $current_page_post->post_content ) ) ) : ?>
				<div class="tam-content-card tam-rich-content" style="margin-bottom:24px;">
					<?php echo apply_filters( 'the_content', $current_page_post->post_content ); ?>
				</div>
			<?php endif; ?>

			<div class="tam-tours-shell">
				<aside class="tam-tours-sidebar">
					<div class="tam-filter-bar tam-filter-bar--stack">
						<form class="tam-filter-bar__form tam-filter-bar__form--stack" method="get" action="<?php echo esc_url( $page_url ); ?>" data-tour-filter-form data-page-url="<?php echo esc_url( $page_url ); ?>" autocomplete="off">
							<div class="tam-field">
								<label for="tam-search-tour"><?php esc_html_e( 'Tìm theo tên tour', 'travel-agency-modern' ); ?></label>
								<input type="text" id="tam-search-tour" name="search_tour" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Vi du: Ha Giang, Phu Quoc...', 'travel-agency-modern' ); ?>" data-tour-search-input />
							</div>
							<div class="tam-field">
								<label for="tam-destination"><?php esc_html_e( 'Lọc theo điểm đến', 'travel-agency-modern' ); ?></label>
								<select id="tam-destination" name="destination" data-tour-destination-select>
									<option value=""><?php esc_html_e( 'Tất cả điểm đến', 'travel-agency-modern' ); ?></option>
									<?php if ( ! is_wp_error( $destinations ) ) : ?>
										<?php foreach ( $destinations as $destination ) : ?>
											<option value="<?php echo esc_attr( $destination->slug ); ?>" <?php selected( $selected_dest, $destination->slug ); ?>>
												<?php echo esc_html( $destination->name ); ?>
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
							<div class="tam-filter-bar__actions tam-filter-bar__actions--stack">
								<p class="tam-filter-bar__status" data-tour-filter-status aria-live="polite"><?php echo esc_html( $tour_status_summary ); ?></p>
								<button class="tam-button tam-button--ghost" type="button" data-tour-filter-reset <?php disabled( '' === $search_term && '' === $selected_dest ); ?>>
									<?php esc_html_e( 'Xóa bộ lọc', 'travel-agency-modern' ); ?>
								</button>
							</div>
						</form>
					</div>

					<div class="tam-promo-card tam-promo-card--aside">
						<span class="tam-promo-badge"><?php esc_html_e( 'Gợi ý nhanh', 'travel-agency-modern' ); ?></span>
						<h3><?php esc_html_e( 'Khách chưa biết đi đâu thường bắt đầu từ điểm đến', 'travel-agency-modern' ); ?></h3>
						<p><?php esc_html_e( 'Lọc nhanh theo điểm đến để tìm hành trình phù hợp chỉ trong vài thao tác.', 'travel-agency-modern' ); ?></p>
					</div>

					<div class="tam-value-card">
						<strong><?php esc_html_e( 'Cần hỗ trợ chọn tour?', 'travel-agency-modern' ); ?></strong>
						<p><?php esc_html_e( 'Neu khach van dang phan van giua nhieu tuyen, hay dua ho sang trang lien he hoặc goi hotline de rut ngan qua trinh chon.', 'travel-agency-modern' ); ?></p>
						<div class="tam-sidebar-actions">
							<a class="tam-button tam-button--ghost" href="<?php echo esc_url( tam_get_page_url_by_path( 'lien-he' ) ); ?>"><?php esc_html_e( 'Nhận tư vấn', 'travel-agency-modern' ); ?></a>
							<a class="tam-button" href="<?php echo esc_url( $contact['tel_url'] ); ?>"><?php esc_html_e( 'Goi hotline', 'travel-agency-modern' ); ?></a>
						</div>
					</div>
				</aside>

				<div class="tam-tours-results" data-tour-results>
					<div class="tam-tours-results__surface" data-tour-results-surface aria-live="polite" aria-busy="false">
						<div class="tam-tours-results__content is-ready" data-tour-results-content>
							<?php echo $tour_results_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div class="tam-tours-results__overlay" data-tour-results-overlay aria-hidden="true">
							<div class="tam-tours-results__loading">
								<span class="tam-spinner" aria-hidden="true"></span>
								<strong><?php esc_html_e( 'Đang cập nhật tour...', 'travel-agency-modern' ); ?></strong>
								<p><?php esc_html_e( 'Hệ thống đang làm mới kết quả để phản hồi theo tìm kiếm của bạn.', 'travel-agency-modern' ); ?></p>
							</div>
							<div class="tam-tour-grid tam-tour-grid--skeleton" aria-hidden="true">
								<?php for ( $skeleton_index = 0; $skeleton_index < 6; $skeleton_index++ ) : ?>
									<article class="tam-card tam-content-card tam-card--skeleton">
										<div class="tam-card__media"></div>
										<div class="tam-card__body">
											<div class="tam-card__meta">
												<span class="tam-skeleton-line tam-skeleton-line--pill"></span>
												<span class="tam-skeleton-line tam-skeleton-line--pill"></span>
											</div>
											<div class="tam-skeleton-line tam-skeleton-line--title"></div>
											<div class="tam-skeleton-line"></div>
											<div class="tam-skeleton-line tam-skeleton-line--short"></div>
											<div class="tam-card__footer">
												<div class="tam-price">
													<span class="tam-skeleton-line tam-skeleton-line--tiny"></span>
													<span class="tam-skeleton-line tam-skeleton-line--price"></span>
												</div>
												<span class="tam-skeleton-button"></span>
											</div>
										</div>
									</article>
								<?php endfor; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
