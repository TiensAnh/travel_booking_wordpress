<?php
/**
 * Front page template.
 */

get_header();

$home_tour_posts = get_posts(
	array(
		'post_type'      => 'tour',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
	)
);

usort(
	$home_tour_posts,
	static function ( $left, $right ) {
		$left_featured  = '1' === get_post_meta( $left->ID, '_tam_tour_featured', true ) ? 1 : 0;
		$right_featured = '1' === get_post_meta( $right->ID, '_tam_tour_featured', true ) ? 1 : 0;

		if ( $left_featured !== $right_featured ) {
			return $right_featured <=> $left_featured;
		}

		return strtotime( $right->post_date_gmt ?: $right->post_date ) <=> strtotime( $left->post_date_gmt ?: $left->post_date );
	}
);

$featured_tour_ids = array_slice( wp_list_pluck( $home_tour_posts, 'ID' ), 0, 3 );
$featured_tours    = new WP_Query(
	array(
		'post_type'      => 'tour',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'post__in'       => ! empty( $featured_tour_ids ) ? $featured_tour_ids : array( 0 ),
		'orderby'        => 'post__in',
	)
);

$destinations = get_terms(
	array(
		'taxonomy'   => 'tour_destination',
		'hide_empty' => true,
		'number'     => 6,
	)
);

$reviews      = tam_get_home_reviews_content();
$search_value = isset( $_GET['search_tour'] ) ? sanitize_text_field( wp_unslash( $_GET['search_tour'] ) ) : '';
?>
<main id="main-content" class="site-main">
	<?php while ( have_posts() ) : ?>
		<?php
		the_post();

		$banner_description = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 28 );

		if ( ! $banner_description ) {
			$banner_description = __( 'Khám phá những điểm đến nổi bật với giao diện rõ ràng, hành trình dễ đọc và luồng tư vấn gọn gàng để khách chốt nhanh hơn ngay từ lần ghé đầu tiên.', 'travel-agency-modern' );
		}
		?>
		<section class="tam-home-hero tam-home-hero--banner">
			<div class="tam-home-hero__media">
				<img src="<?php echo esc_url( tam_get_hero_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
				<div class="tam-home-hero__overlay"></div>
			</div>

			<div class="tam-container tam-home-hero__content">
				<div class="tam-home-hero__copy">
					<span class="tam-home-hero__eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
					<h1 class="tam-home-hero__title">
						<?php esc_html_e( 'Du lịch dễ dàng', 'travel-agency-modern' ); ?>
						<br />
						<span><?php esc_html_e( 'khắp mọi nơi', 'travel-agency-modern' ); ?></span>
					</h1>
					<p class="tam-home-hero__description"><?php echo esc_html( $banner_description ); ?></p>
					<div class="tam-home-hero__actions">
						<a class="tam-button" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>"><?php esc_html_e( 'Xem tour nổi bật', 'travel-agency-modern' ); ?></a>
						<a class="tam-button tam-button--secondary" href="<?php echo esc_url( tam_get_page_url_by_path( 'lien-he' ) ); ?>"><?php esc_html_e( 'Nhận tư vấn', 'travel-agency-modern' ); ?></a>
					</div>
				</div>
			</div>
		</section>

		<section class="tam-home-search-band">
			<div class="tam-container">
				<div class="tam-home-search-shell tam-content-card">
					<div class="tam-home-search-shell__intro">
						<div class="tam-eyebrow"><?php esc_html_e( 'Tìm kiếm', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Tìm hành trình phù hợp thật nhanh', 'travel-agency-modern' ); ?></h2>
						<p class="tam-section-subtitle"><?php esc_html_e( 'Chọn điểm đến, thời gian dự kiến và kiểu nhóm khách để đi thẳng vào danh sách tour phù hợp.', 'travel-agency-modern' ); ?></p>
					</div>

					<form class="tam-hero-search tam-hero-search--standalone" method="get" action="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>">
						<div class="tam-hero-search__field tam-hero-search__field--grow">
							<span class="tam-hero-search__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
									<path d="M12 21s-6-5.33-6-11a6 6 0 1 1 12 0c0 5.67-6 11-6 11Zm0-8.25A2.75 2.75 0 1 0 12 7.25a2.75 2.75 0 0 0 0 5.5Z" fill="currentColor"/>
								</svg>
							</span>
							<div>
								<label for="tam-home-destination"><?php esc_html_e( 'Điểm đến', 'travel-agency-modern' ); ?></label>
								<input id="tam-home-destination" type="text" name="search_tour" list="tam-home-destination-list" value="<?php echo esc_attr( $search_value ); ?>" placeholder="<?php esc_attr_e( 'Thành phố, điểm đến...', 'travel-agency-modern' ); ?>" />
							</div>
						</div>

						<div class="tam-hero-search__field">
							<span class="tam-hero-search__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
									<path d="M7 2h2v2h6V2h2v2h3a1 1 0 0 1 1 1v14a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V5a1 1 0 0 1 1-1h3V2Zm12 8H5v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9Z" fill="currentColor"/>
								</svg>
							</span>
							<div>
								<label><?php esc_html_e( 'Thời gian', 'travel-agency-modern' ); ?></label>
								<strong><?php esc_html_e( 'Quanh năm', 'travel-agency-modern' ); ?></strong>
							</div>
						</div>

						<div class="tam-hero-search__field">
							<span class="tam-hero-search__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
									<path d="M12 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0 2c4.42 0 8 2.24 8 5v1H4v-1c0-2.76 3.58-5 8-5Z" fill="currentColor"/>
								</svg>
							</span>
							<div>
								<label for="tam-home-guests"><?php esc_html_e( 'Hành khách', 'travel-agency-modern' ); ?></label>
								<select id="tam-home-guests">
									<option value="1-nguoi"><?php esc_html_e( '1 người lớn', 'travel-agency-modern' ); ?></option>
									<option value="2-nguoi" selected><?php esc_html_e( '2 người lớn', 'travel-agency-modern' ); ?></option>
									<option value="gia-dinh"><?php esc_html_e( 'Gia đình 4 người', 'travel-agency-modern' ); ?></option>
									<option value="nhom-nho"><?php esc_html_e( 'Nhóm nhỏ 6 người', 'travel-agency-modern' ); ?></option>
								</select>
							</div>
						</div>

						<button class="tam-button tam-hero-search__button" type="submit">
							<span class="tam-hero-search__icon tam-hero-search__icon--button" aria-hidden="true">
								<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
									<path d="m15.5 14 5 5-1.5 1.5-5-5V14h1.5ZM10 4a6 6 0 1 1 0 12 6 6 0 0 1 0-12Zm0 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" fill="currentColor"/>
								</svg>
							</span>
							<?php esc_html_e( 'Tìm kiếm', 'travel-agency-modern' ); ?>
						</button>

						<?php if ( ! is_wp_error( $destinations ) && ! empty( $destinations ) ) : ?>
							<datalist id="tam-home-destination-list">
								<?php foreach ( $destinations as $destination ) : ?>
									<option value="<?php echo esc_attr( $destination->name ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
						<?php endif; ?>
					</form>
				</div>
			</div>
		</section>
	<?php endwhile; ?>

	<section class="tam-section">
		<div class="tam-container">
			<div class="tam-section-head">
				<div>
					<div class="tam-eyebrow"><?php esc_html_e( 'Tour nổi bật', 'travel-agency-modern' ); ?></div>
					<h2 class="tam-section-title"><?php esc_html_e( 'Những hành trình đang được quan tâm nhiều nhất', 'travel-agency-modern' ); ?></h2>
					<p class="tam-section-subtitle"><?php esc_html_e( 'Giữ trọng tâm vào ảnh, điểm đến, thời lượng và mức giá để khách xem là hiểu ngay.', 'travel-agency-modern' ); ?></p>
				</div>
				<a class="tam-button tam-button--ghost" href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>"><?php esc_html_e( 'Xem tất cả tour', 'travel-agency-modern' ); ?></a>
			</div>

			<?php if ( $featured_tours->have_posts() ) : ?>
				<div class="tam-tour-grid">
					<?php while ( $featured_tours->have_posts() ) : ?>
						<?php $featured_tours->the_post(); ?>
						<?php get_template_part( 'template-parts/tour-card' ); ?>
					<?php endwhile; ?>
				</div>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<div class="tam-empty-state">
					<strong><?php esc_html_e( 'Chưa có tour được đăng', 'travel-agency-modern' ); ?></strong>
					<p><?php esc_html_e( 'Bạn có thể vào quản trị WordPress để thêm những tour đầu tiên và gắn thuộc tính nổi bật.', 'travel-agency-modern' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( ! is_wp_error( $destinations ) && ! empty( $destinations ) ) : ?>
		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-section-head">
					<div>
						<div class="tam-eyebrow"><?php esc_html_e( 'Địa điểm', 'travel-agency-modern' ); ?></div>
						<h2 class="tam-section-title"><?php esc_html_e( 'Điểm đến nổi bật để khám phá nhanh', 'travel-agency-modern' ); ?></h2>
						<p class="tam-section-subtitle"><?php esc_html_e( 'Mỗi địa điểm dẫn thẳng về danh sách tour đã lọc sẵn để khách không cần đi vòng qua nhiều bước.', 'travel-agency-modern' ); ?></p>
					</div>
				</div>

				<div class="tam-destination-grid">
					<?php foreach ( $destinations as $destination ) : ?>
						<?php
						$destination_url   = add_query_arg( 'destination', $destination->slug, tam_get_page_url_by_path( 'tour' ) );
						$destination_image = tam_get_destination_image_url( $destination );
						$destination_copy  = $destination->description ? $destination->description : sprintf(
							/* translators: %s: number of tours */
							_n( '%s hành trình đang mở bán', '%s hành trình đang mở bán', (int) $destination->count, 'travel-agency-modern' ),
							number_format_i18n( (int) $destination->count )
						);
						?>
						<article class="tam-destination-card">
							<a class="tam-destination-card__media" href="<?php echo esc_url( $destination_url ); ?>">
								<img src="<?php echo esc_url( $destination_image ); ?>" alt="<?php echo esc_attr( $destination->name ); ?>" loading="lazy" />
							</a>
							<div class="tam-destination-card__body">
								<span class="tam-promo-badge"><?php echo esc_html( number_format_i18n( (int) $destination->count ) ); ?> tour</span>
								<h3><a href="<?php echo esc_url( $destination_url ); ?>"><?php echo esc_html( $destination->name ); ?></a></h3>
								<p><?php echo esc_html( $destination_copy ); ?></p>
								<a class="tam-button tam-button--ghost" href="<?php echo esc_url( $destination_url ); ?>"><?php esc_html_e( 'Khám phá địa điểm', 'travel-agency-modern' ); ?></a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="tam-section tam-section--compact">
		<div class="tam-container">
			<div class="tam-review-layout">
				<div class="tam-review-summary">
					<div class="tam-eyebrow"><?php echo esc_html( $reviews['eyebrow'] ); ?></div>
					<h2 class="tam-section-title"><?php echo esc_html( $reviews['title'] ); ?></h2>
					<p class="tam-section-subtitle"><?php echo esc_html( $reviews['description'] ); ?></p>
					<div class="tam-review-score">
						<strong><?php echo esc_html( $reviews['rating'] ); ?></strong>
						<span><?php echo esc_html( $reviews['rating_note'] ); ?></span>
					</div>
					<div class="tam-review-metrics">
						<?php foreach ( $reviews['metrics'] as $metric ) : ?>
							<div class="tam-review-metric">
								<strong><?php echo esc_html( $metric['value'] ); ?></strong>
								<span><?php echo esc_html( $metric['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="tam-review-grid">
					<?php foreach ( $reviews['items'] as $review ) : ?>
						<article class="tam-review-card">
							<div class="tam-review-stars" aria-label="<?php echo esc_attr( $review['rating'] ); ?>">
								<?php echo esc_html( str_repeat( '★', 5 ) ); ?>
							</div>
							<p class="tam-review-quote">“<?php echo esc_html( $review['quote'] ); ?>”</p>
							<div class="tam-review-person">
								<div class="tam-avatar-badge" aria-hidden="true"><?php echo esc_html( tam_get_initials( $review['name'] ) ); ?></div>
								<div>
									<strong><?php echo esc_html( $review['name'] ); ?></strong>
									<span><?php echo esc_html( $review['route'] ); ?></span>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
