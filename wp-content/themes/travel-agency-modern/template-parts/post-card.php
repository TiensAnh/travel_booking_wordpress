<?php
/**
 * Post card template part.
 */
?>
<article <?php post_class( 'tam-card tam-content-card' ); ?>>
	<a class="tam-card__media" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'tam-blog-card' ); ?>
		<?php else : ?>
			<div class="tam-card__placeholder"><?php esc_html_e( 'Tin tức', 'travel-agency-modern' ); ?></div>
		<?php endif; ?>
	</a>
	<div class="tam-card__body">
		<div class="tam-card__meta">
			<span><?php echo esc_html( get_the_date() ); ?></span>
			<span><?php echo esc_html( get_the_author() ); ?></span>
		</div>
		<h2 class="tam-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="tam-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
		<div class="tam-card__footer">
			<div class="tam-price">
				<span><?php esc_html_e( 'Dành cho SEO và niềm tin', 'travel-agency-modern' ); ?></span>
			</div>
			<a class="tam-button tam-button--ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Đọc bài viết', 'travel-agency-modern' ); ?></a>
		</div>
	</div>
</article>
