<?php
/**
 * Blog index template.
 */

get_header();

$posts_page_id = (int) get_option( 'page_for_posts' );
$posts_page    = $posts_page_id ? get_post( $posts_page_id ) : null;
$hero_title    = $posts_page ? get_the_title( $posts_page ) : __( 'Tin tức', 'travel-agency-modern' );
if ( 'Cẩm nang du lịch' === trim( wp_strip_all_tags( $hero_title ) ) ) {
	$hero_title = __( 'Tin tức', 'travel-agency-modern' );
}
$hero_text     = $posts_page && has_excerpt( $posts_page ) ? get_the_excerpt( $posts_page ) : __( 'Tổng hợp kinh nghiệm du lịch, gợi ý lịch trình, mẹo chuẩn bị hành lý và những lưu ý nhỏ giúp bạn tự tin hơn trước mỗi chuyến đi.', 'travel-agency-modern' );
$hero_image    = $posts_page ? tam_get_hero_image_url( $posts_page->ID ) : tam_get_hero_image_url();

tam_render_page_intro(
	array(
		'eyebrow'     => __( 'Tin tức', 'travel-agency-modern' ),
		'title'       => $hero_title,
		'description' => $hero_text,
		'image'       => $hero_image,
	)
);
?>
<main id="main-content" class="site-main">
	<section class="tam-section tam-section--compact">
		<div class="tam-container">
			<div class="tam-content-card tam-rich-content tam-guide-intro">
				<p><?php esc_html_e( 'Tin tức là nơi ADN Travel gom lại những kinh nghiệm thực tế trước mỗi chuyến đi: chọn thời điểm đẹp, chuẩn bị hành lý, dự trù chi phí, lưu ý văn hóa địa phương và cách sắp xếp lịch trình sao cho nhẹ nhàng hơn.', 'travel-agency-modern' ); ?></p>
				<p><?php esc_html_e( 'Mỗi bài viết được viết ngắn gọn, dễ tra cứu để bạn có thể nhanh chóng tìm được gợi ý phù hợp cho gia đình, nhóm bạn hoặc chuyến đi công ty.', 'travel-agency-modern' ); ?></p>
			</div>

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
					<p><?php esc_html_e( 'Bạn có thể bắt đầu xây dựng mục tin tức ngay trong khu vực Bài viết của WordPress.', 'travel-agency-modern' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
