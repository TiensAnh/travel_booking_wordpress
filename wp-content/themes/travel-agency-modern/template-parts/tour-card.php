<?php
/**
 * Tour card template part.
 */

$tour_meta     = tam_get_tour_meta( get_the_ID() );
$destinations  = tam_get_tour_destinations( get_the_ID() );
$primary_term  = ! empty( $destinations ) ? $destinations[0]->name : __( 'Tour du lịch', 'travel-agency-modern' );
$price_display = tam_format_tour_price( $tour_meta['price_from'] );
$visual_url    = tam_get_tour_image_url( get_the_ID(), 'tam-tour-card' );
?>
<article <?php post_class( 'tam-card tam-content-card' ); ?>>
	<a class="tam-card__media" href="<?php the_permalink(); ?>">
		<?php if ( $visual_url ) : ?>
			<img src="<?php echo esc_url( $visual_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
		<?php else : ?>
			<div class="tam-card__placeholder"><?php echo esc_html( $primary_term ); ?></div>
		<?php endif; ?>
	</a>
	<div class="tam-card__body">
		<div class="tam-card__meta">
			<span><?php echo esc_html( $primary_term ); ?></span>
			<?php if ( $tour_meta['duration'] ) : ?>
				<span><?php echo esc_html( $tour_meta['duration'] ); ?></span>
			<?php endif; ?>
			<?php if ( $tour_meta['departure'] ) : ?>
				<span><?php echo esc_html( $tour_meta['departure'] ); ?></span>
			<?php endif; ?>
		</div>
		<h2 class="tam-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="tam-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
		<div class="tam-card__footer">
			<div class="tam-price">
				<span><?php esc_html_e( 'Giá tham khảo', 'travel-agency-modern' ); ?></span>
				<strong><?php echo esc_html( $price_display ); ?></strong>
			</div>
			<a class="tam-button tam-button--ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Xem chi tiết', 'travel-agency-modern' ); ?></a>
		</div>
	</div>
</article>
