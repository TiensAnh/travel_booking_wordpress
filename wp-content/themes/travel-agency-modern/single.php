<?php
/**
 * Single post template.
 */

get_header();

while ( have_posts() ) :
	the_post();

	tam_render_page_intro(
		array(
			'eyebrow'     => __( 'Cẩm nang du lịch', 'travel-agency-modern' ),
			'title'       => get_the_title(),
			'description' => has_excerpt() ? get_the_excerpt() : __( 'Bài viết chi tiết được trình bày theo cùng visual system với phần tour để trải nghiệm đọc nhất quán hơn.', 'travel-agency-modern' ),
			'image'       => tam_get_hero_image_url( get_the_ID() ),
		)
	);
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact">
			<div class="tam-container tam-layout">
				<article class="tam-article">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="tam-post-hero-image">
							<?php the_post_thumbnail( 'tam-hero-large' ); ?>
						</div>
					<?php endif; ?>

					<div class="tam-content-card">
						<div class="tam-post-meta">
							<span><?php echo esc_html( get_the_date() ); ?></span>
							<span><?php echo esc_html( get_the_author() ); ?></span>
							<?php if ( get_the_category_list( ', ' ) ) : ?>
								<span><?php echo wp_kses_post( get_the_category_list( ', ' ) ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<div class="tam-content-card tam-rich-content">
						<?php the_content(); ?>
						<?php the_tags( '<div class="tam-chip-list">', '', '</div>' ); ?>
					</div>

					<div class="tam-post-navigation">
						<?php $prev_post = get_previous_post(); ?>
						<?php $next_post = get_next_post(); ?>
						<div>
							<?php if ( $prev_post ) : ?>
								<a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>">
									<span><?php esc_html_e( 'Bài trước', 'travel-agency-modern' ); ?></span>
									<?php echo esc_html( get_the_title( $prev_post ) ); ?>
								</a>
							<?php endif; ?>
						</div>
						<div>
							<?php if ( $next_post ) : ?>
								<a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>">
									<span><?php esc_html_e( 'Bài tiếp', 'travel-agency-modern' ); ?></span>
									<?php echo esc_html( get_the_title( $next_post ) ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( comments_open() || get_comments_number() ) : ?>
						<?php comments_template(); ?>
					<?php endif; ?>
				</article>

				<aside class="tam-sidebar-stack">
					<div class="tam-summary-card">
						<div class="tam-eyebrow"><?php esc_html_e( 'Điều hướng nhanh', 'travel-agency-modern' ); ?></div>
						<ul class="tam-summary-list">
							<li><span><?php esc_html_e( 'Danh mục', 'travel-agency-modern' ); ?></span><strong><?php echo wp_kses_post( get_the_category_list( ', ' ) ); ?></strong></li>
							<li><span><?php esc_html_e( 'Xem thêm tour', 'travel-agency-modern' ); ?></span><strong><a href="<?php echo esc_url( tam_get_page_url_by_path( 'tour' ) ); ?>"><?php esc_html_e( 'Trang Tour', 'travel-agency-modern' ); ?></a></strong></li>
							<li><span><?php esc_html_e( 'Cần tư vấn', 'travel-agency-modern' ); ?></span><strong><a href="<?php echo esc_url( tam_get_page_url_by_path( 'lien-he' ) ); ?>"><?php esc_html_e( 'Trang Liên hệ', 'travel-agency-modern' ); ?></a></strong></li>
						</ul>
					</div>
				</aside>
			</div>
		</section>
	</main>
<?php endwhile; ?>
<?php
get_footer();
