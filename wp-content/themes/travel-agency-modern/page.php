<?php
/**
 * Default page template.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$description = has_excerpt() ? get_the_excerpt() : __( 'Nội dung trang có thể được cập nhật trực tiếp trong WordPress editor mà vẫn giữ bố cục nhất quán với toàn site.', 'travel-agency-modern' );
	tam_render_page_intro(
		array(
			'eyebrow'     => __( 'Thông tin website', 'travel-agency-modern' ),
			'title'       => get_the_title(),
			'description' => $description,
			'image'       => tam_get_hero_image_url( get_the_ID() ),
		)
	);
	?>
	<main id="main-content" class="site-main">
		<section class="tam-section tam-section--compact">
			<div class="tam-container">
				<div class="tam-content-card tam-rich-content">
					<?php the_content(); ?>
				</div>
			</div>
		</section>
	</main>
<?php endwhile; ?>
<?php
get_footer();
