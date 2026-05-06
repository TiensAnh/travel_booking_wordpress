<?php
/**
 * Fallback index template.
 */

get_header();
?>
<main id="main-content" class="site-main">
	<section class="tam-section">
		<div class="tam-container">
			<?php if ( have_posts() ) : ?>
				<div class="tam-blog-grid">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<?php if ( 'post' === get_post_type() ) : ?>
							<?php get_template_part( 'template-parts/post-card' ); ?>
						<?php elseif ( 'tour' === get_post_type() ) : ?>
							<?php get_template_part( 'template-parts/tour-card' ); ?>
						<?php else : ?>
							<article class="tam-content-card">
								<h2 class="tam-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="tam-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
							</article>
						<?php endif; ?>
					<?php endwhile; ?>
				</div>
				<?php tam_render_pagination(); ?>
			<?php else : ?>
				<div class="tam-empty-state">
					<strong><?php esc_html_e( 'Chưa có nội dung để hiển thị.', 'travel-agency-modern' ); ?></strong>
					<p><?php esc_html_e( 'Bạn có thể bắt đầu bằng việc tạo tour, bài viết hoặc các trang thông tin cơ bản.', 'travel-agency-modern' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
