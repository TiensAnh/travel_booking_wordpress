<?php
/**
 * Search template.
 */

get_header();

tam_render_page_intro(
	array(
		'eyebrow'     => __( 'Tìm kiếm', 'travel-agency-modern' ),
		'title'       => sprintf( __( 'Kết quả cho "%s"', 'travel-agency-modern' ), get_search_query() ),
		'description' => __( 'Kết quả có thể bao gồm bài viết, trang thông tin và tour du lịch được đăng trên website.', 'travel-agency-modern' ),
		'image'       => tam_get_hero_image_url(),
	)
);
?>
<main id="main-content" class="site-main">
	<section class="tam-section tam-section--compact">
		<div class="tam-container">
			<?php if ( have_posts() ) : ?>
				<div class="tam-grid">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<?php if ( 'tour' === get_post_type() ) : ?>
							<?php get_template_part( 'template-parts/tour-card' ); ?>
						<?php elseif ( 'post' === get_post_type() ) : ?>
							<?php get_template_part( 'template-parts/post-card' ); ?>
						<?php else : ?>
							<article class="tam-content-card">
								<div class="tam-eyebrow"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></div>
								<h2 class="tam-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="tam-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
								<a class="tam-button tam-button--ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Xem chi tiết', 'travel-agency-modern' ); ?></a>
							</article>
						<?php endif; ?>
					<?php endwhile; ?>
				</div>
				<?php tam_render_pagination(); ?>
			<?php else : ?>
				<div class="tam-empty-state">
					<strong><?php esc_html_e( 'Không tìm thấy kết quả phù hợp.', 'travel-agency-modern' ); ?></strong>
					<p><?php esc_html_e( 'Thử tìm bằng từ khóa ngắn hơn hoặc chuyển sang trang Tour để duyệt theo điểm đến.', 'travel-agency-modern' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
