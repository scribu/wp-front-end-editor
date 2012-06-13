<?php
// Template Name: FEE Debug

get_header();

fee_inject_dummy_post();

the_post();
?>
		<div id="primary">
			<div id="content" role="main">

				<?php get_template_part( 'content', 'page' ); ?>

<strong>Input post meta:</strong><br>
<div><?php editable_post_meta(get_the_ID(), 'price', 'input' ); ?></div>

<strong>Rich post meta:</strong><br>
<?php echo wpautop( editable_post_meta(get_the_ID(), 'degrees', 'rich', false ) ); ?>

<strong>Dropdown post meta:</strong><br>
<div><?php editable_post_meta( get_the_ID(), 'my_key', array(
	'type' => 'select',
	'values' => array(
		'val_1' => 'Title 1',
		'val_2' => 'Title 2'
	)
) );
?></div>

<strong>Input option:</strong><br>
<div><?php editable_option( array(
	'key' => 'price',
	'type' => 'input',
) ); ?></div>

<strong>Dropdown option:</strong><br>
<div><?php editable_option( array(
	'key' => 'country',
	'type' => 'select',
	'values' => array(
		'fr' => 'France',
		'it' => 'Italy',
		'ch' => 'China',
	),
) ); ?></div>

<strong>Post excerpt:</strong><br>
<?php the_excerpt(); ?>

<div style="overflow:hidden">
<strong>Editable image:</strong><br>
<?php editable_image( 'test', 'http://wp.dev/wp-content/themes/twentyeleven/images/headers/wheel-thumbnail.jpg' ); ?>
</div>

<?php
if ( function_exists( 'get_the_post_thumbnail' ) ) {
	echo '<strong>Post thumbnail:</strong><br>';
	echo get_the_post_thumbnail( get_queried_object_id() );
}
?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>
