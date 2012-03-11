<?php
// Template Name: FEE Debug

get_header();

function fee_test_input_types() {
	echo '<strong>Input post meta:</strong><br>';
	echo html( 'div', editable_post_meta(get_the_ID(), 'price', 'input', false ) );

	echo '<strong>Rich post meta:</strong><br>';
	echo wpautop( editable_post_meta(get_the_ID(), 'degrees', 'rich', false ) );

	echo '<strong>Dropdown post meta:</strong><br>';
	echo html( 'div', editable_post_meta( get_the_ID(), 'my_key', array(
		'type' => 'select',
		'values' => array(
			'val_1' => 'Title 1',
			'val_2' => 'Title 2'
		),
		false
	) ) );

	echo '<strong>Input option:</strong><br>';
	echo html( 'div', editable_option( array(
		'key' => 'price',
		'type' => 'input',
		'echo' => false
	) ) );

	echo '<strong>Dropdown option:</strong><br>';
	echo html( 'div', editable_option( array(
		'key' => 'country',
		'type' => 'select',
		'values' => array(
			'fr' => 'France',
			'it' => 'Italy',
			'ch' => 'China',
		),
		'echo' => false
	) ) );

	echo '<strong>Post excerpt:</strong><br>';
	the_excerpt();

	echo '<div style="overflow:hidden">';
	echo '<strong>Editable image:</strong><br>';
	editable_image( 'test', 'http://wp.dev/wp-content/themes/twentyeleven/images/headers/wheel-thumbnail.jpg' );
	echo '</div>';

	if ( function_exists( 'get_the_post_thumbnail' ) ) {
		echo '<strong>Post thumbnail:</strong><br>';
		echo get_the_post_thumbnail( get_the_ID() );
	}
}

fee_inject_dummy_post();
?>
		<div id="primary">
			<div id="content" role="main">

				<?php while ( have_posts() ) : the_post(); ?>

					<?php get_template_part( 'content', 'page' ); ?>

					<?php fee_test_input_types(); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>
