<?php
/**
 * Blog index template.
 */

get_header();

$posts_page_id = (int) get_option( 'page_for_posts' );
$posts_page    = $posts_page_id ? get_post( $posts_page_id ) : null;
$hero_title    = $posts_page ? get_the_title( $posts_page ) : __( 'Cẩm nang du lịch', 'travel-agency-modern' );
$hero_text     = $posts_page && has_excerpt( $posts_page ) ? get_the_excerpt( $posts_page ) : __( 'Tổng hợp bài viết chia sẻ kinh nghiệm, hành trình và mẹo nhỏ giúp phần blog vẫn đồng bộ với trải nghiệm đặt tour trên toàn site.', 'travel-agency-modern' );
$hero_image    = $posts_page ? tam_get_hero_image_url( $posts_page->ID ) : tam_get_hero_image_url();

tam_render_page_intro(
	array(
		'eyebrow'     => __( 'Blog du lịch', 'travel-agency-modern' ),
		'title'       => $hero_title,
		'description' => $hero_text,
		'image'       => $hero_image,
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
					<strong><?php esc_html_e( 'Chưa có bài viết nào được đăng.', 'travel-agency-modern' ); ?></strong>
					<p><?php esc_html_e( 'Bạn có thể bắt đầu xây dựng cẩm nang du lịch ngay trong khu vực Bài viết của WordPress.', 'travel-agency-modern' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
