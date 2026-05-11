<?php
/**
 * Generic archive template.
 */

get_header();

tam_render_page_intro(
	array(
		'eyebrow'     => __( 'Tin tức', 'travel-agency-modern' ),
		'title'       => wp_strip_all_tags( get_the_archive_title() ),
		'description' => get_the_archive_description() ? wp_strip_all_tags( get_the_archive_description() ) : __( 'Danh sách bài viết được lọc theo chuyên mục, thẻ hoặc kho lưu trữ hiện tại.', 'travel-agency-modern' ),
		'image'       => tam_get_hero_image_url(),
	)
);
?>
<main id="main-content" class="site-main">
	<section class="tam-section tam-section--compact">
		<div class="tam-container">
			<?php if ( have_posts() ) : ?>
				<div class="tam-blog-grid">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<?php get_template_part( 'template-parts/post-card' ); ?>
					<?php endwhile; ?>
				</div>
				<?php tam_render_pagination(); ?>
			<?php else : ?>
				<div class="tam-empty-state">
					<strong><?php esc_html_e( 'Không có nội dung nào phù hợp.', 'travel-agency-modern' ); ?></strong>
					<p><?php esc_html_e( 'Thử mở rộng phạm vi lọc hoặc quay lại trang tin tức để tiếp tục khám phá.', 'travel-agency-modern' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
