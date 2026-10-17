<?php
/**
 * Template Name: Post Category Table
 *
 * @package electro
 */

remove_action( 'electro_content_top', 'electro_breadcrumb', 10 );

do_action( 'electro_before_homepage_v11' );

$header_style = 'v11';

if ( function_exists( 'electro_acf_home_v11_get_header_style' ) ) {
	$header_option = electro_acf_home_v11_get_header_style();

	if ( $header_option ) {
		$header_style = $header_option;
	}
}

$category_slug = isset( $_GET['category'] )
	? sanitize_title( wp_unslash( $_GET['category'] ) )
	: sanitize_title( get_post_field( 'post_name', get_the_ID() ) );

$selected_category = $category_slug ? get_category_by_slug( $category_slug ) : null;

$post_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => -1,
		'cat'                 => $selected_category ? $selected_category->term_id : 0,
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	)
);

function a1geo_post_table_meta_value( $post_id, $keys ) {
	foreach ( $keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );

		if ( '' !== $value && null !== $value ) {
			return absint( $value );
		}
	}

	return 0;
}

function a1geo_post_table_get_views( $post_id ) {
	if ( function_exists( 'pvc_get_post_views' ) ) {
		return absint( pvc_get_post_views( $post_id, 'total' ) );
	}

	return a1geo_post_table_meta_value( $post_id, array( 'post_views', '_post_views', 'views', 'view_count' ) );
}

function a1geo_post_table_attachment_url( $post_id, $field_name ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$attachment = get_field( $field_name, $post_id );

	if ( is_array( $attachment ) && ! empty( $attachment['url'] ) ) {
		return $attachment['url'];
	}

	if ( is_numeric( $attachment ) ) {
		return wp_get_attachment_url( absint( $attachment ) );
	}

	return is_string( $attachment ) ? $attachment : '';
}

electro_get_header( $header_style );
?>

<style>
	.post-category-table-page {
		padding: 0 0 56px;
		margin-top: -35px;
	}

	.post-category-table-shell {
		padding-top: 32px;
	}

	.post-category-table-wrap {
		overflow-x: auto;
		margin-top: 24px;
	}

	.post-category-table {
		width: 100%;
		border-collapse: collapse;
		background: #fff;
	}

	.post-category-table th,
	.post-category-table td {
		padding: 16px;
		border: 1px solid #e3e5e8;
		vertical-align: middle;
		text-align: left;
	}

	.post-category-table th {
		background: #1d5089;
		color: #fff;
		font-weight: 700;
	}

	.post-category-table th:first-child {
		width: 12%;
	}

	.post-category-table th:nth-child(2) {
		width: 18%;
	}

	.post-category-table th:nth-child(3) {
		width: 15%;
	}

	.post-category-table th:nth-child(4) {
		width: 40%;
	}

	.post-category-table th:nth-child(5) {
		width: 15%;
	}

	.post-category-table-image {
		display: block;
		width: 100%;
		height: 100px;
		object-fit: contain;
		object-position: left center;
	}

	.post-category-table-image-placeholder {
		color: #777;
		font-style: italic;
	}

	.post-category-table-author,
	.post-category-table-id {
		white-space: nowrap;
	}

	.post-category-table-id-views {
		display: block;
		margin-top: 4px;
		color: #595959;
		font-size: 0.85rem;
	}

	.post-category-table-title {
		display: block;
		flex: 1 1 auto;
		color: #333;
		font-size: 1.05rem;
		font-weight: 700;
		text-decoration: none;
	}

	.post-category-table-title:hover {
		color: #0b63ce;
	}

	.post-category-table-stats {
		display: inline-flex;
		flex: 0 0 auto;
		align-items: center;
		justify-content: flex-end;
		gap: 10px;
		color: #595959;
		font-size: 0.9rem;
	}

	.post-category-table-title-row {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
	}

	.post-category-table-title-content {
		min-width: 0;
	}

	.post-category-table-download {
		display: flex;
		align-items: center;
		margin-top: 4px;
		font-size: 0.85rem;
		text-decoration: none;
	}

	.post-category-table-download img {
		width: 24px;
		height: 24px;
		margin-right: 5px;
	}

	.post-category-table-stats a {
		color: #595959;
		text-decoration: none;
	}

	.post-category-table-stats a:hover {
		color: #0b63ce;
	}

	.post-category-table-stats i {
		margin-right: 3px;
	}

	.post-category-table-stats .screen-reader-text {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}

	.post-category-table-description {
		margin: 0;
		line-height: 1.6;
	}

	.post-category-table-author {
		color: #333;
		font-weight: 600;
	}

	.post-category-table-empty {
		margin-top: 24px;
		padding: 18px;
		border: 1px solid #e3e5e8;
		background: #fff;
	}

	.post-category-submit-link {
		margin-top: 18px;
		font-style: italic;
	}

	.post-category-submit-link a {
		color: #0b63ce;
	}

	@media (max-width: 767px) {
		.post-category-table th,
		.post-category-table td {
			min-width: 220px;
		}
	}
</style>

<div id="primary" class="content-area post-category-table-page">
	<main id="main" class="site-main">
		<?php do_action( 'electro_page_before' ); ?>

		<div class="container post-category-table-shell">
			<h1 class="custom-page-title"><?php echo esc_html( $selected_category ? $selected_category->name : get_the_title() ); ?></h1>

			<?php if ( $selected_category ) : ?>
				<div class="post-category-table-wrap">
					<table class="post-category-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ID', 'a1-geo' ); ?></th>
								<th><?php esc_html_e( 'Title', 'a1-geo' ); ?></th>
								<th><?php esc_html_e( 'Image', 'a1-geo' ); ?></th>
								<th><?php esc_html_e( 'Description', 'a1-geo' ); ?></th>
								<th><?php esc_html_e( 'Author', 'a1-geo' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( $post_query->have_posts() ) : ?>
								<?php while ( $post_query->have_posts() ) : $post_query->the_post(); ?>
								<?php
								$post_id       = get_the_ID();
								$views         = a1geo_post_table_get_views( $post_id );
								$likes         = function_exists( 'a1geo_get_post_likes_count' ) ? a1geo_get_post_likes_count( $post_id ) : 0;
								$document_url  = a1geo_post_table_attachment_url( $post_id, 'attach_document' );
								$description   = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 100, '...' );
								$acf_author    = function_exists( 'get_field' ) ? get_field( 'author', $post_id ) : '';
								$paper_id      = $post_id; // Assuming the paper ID is the same as the post ID. Adjust if needed.

								if ( is_array( $acf_author ) ) {
									$acf_author = isset( $acf_author['display_name'] ) ? $acf_author['display_name'] : ( isset( $acf_author['name'] ) ? $acf_author['name'] : '' );
								} elseif ( $acf_author instanceof WP_User ) {
									$acf_author = $acf_author->display_name;
								}
								?>
								<tr>
									<td class="post-category-table-id">
										<?php echo esc_html( number_format_i18n( $paper_id ) ); ?>
										<span class="post-category-table-id-views">
											<?php
											esc_html_e( 'Views:', 'a1-geo' );
											echo ' ' . esc_html( number_format_i18n( $views ) );
											?>
										</span>
										<span class="post-category-table-id-views">
											<?php esc_html_e( 'Likes:', 'a1-geo' ); ?> <?php echo esc_html( number_format_i18n( $likes ) ); ?>
										</span>
									</td>
									<td>
										<div class="post-category-table-title-row">
											<div class="post-category-table-title-content">
												<a class="post-category-table-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
												<?php if ( $document_url ) : ?>
													<a class="post-category-table-download" href="<?php echo esc_url( $document_url ); ?>" title="<?php esc_attr_e( 'Download paper', 'a1-geo' ); ?>" download>
														<img src="<?php echo esc_url( includes_url( 'images/pdf-icon.png' ) ); ?>" alt="">														
													</a>
												<?php endif; ?>
											</div>
									</div>
									</td>
									<td>
										<?php if ( has_post_thumbnail( $post_id ) ) : ?>
											<a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
												<?php echo get_the_post_thumbnail( $post_id, 'full', array( 'class' => 'post-category-table-image' ) ); ?>
											</a>
										<?php else : ?>
											<span class="post-category-table-image-placeholder"><?php esc_html_e( 'No image', 'a1-geo' ); ?></span>
										<?php endif; ?>
									</td>
									<td><p class="post-category-table-description"><?php echo esc_html( $description ); ?></p></td>
									<td class="post-category-table-author"><?php echo esc_html( $acf_author ? $acf_author : '—' ); ?></td>
								</tr>
								<?php endwhile; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" class="post-category-table-empty">
										<?php esc_html_e( 'No posts found in this category.', 'a1-geo' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				<?php
				$submit_paper_page = get_page_by_path( 'submit-paper' );
			$submit_paper_url  = $submit_paper_page ? get_permalink( $submit_paper_page ) : home_url( '/submit-paper/' );
			$submit_paper_url  = add_query_arg( 'category', $selected_category->slug, $submit_paper_url );
				?>
				<p class="post-category-submit-link">
					<a href="<?php echo esc_url( $submit_paper_url ); ?>">
						<?php esc_html_e( 'Click here if you want to submit your paper', 'a1-geo' ); ?>
					</a>
				</p>
			<?php elseif ( ! $selected_category ) : ?>
				<p class="post-category-table-empty"><?php esc_html_e( 'The selected category could not be found. Set the page slug to the category slug or add a category query parameter.', 'a1-geo' ); ?></p>
			<?php else : ?>
				<p class="post-category-table-empty"><?php esc_html_e( 'No posts found in this category.', 'a1-geo' ); ?></p>
			<?php endif; ?>
		</div>

		<?php wp_reset_postdata(); ?>
		<?php do_action( 'electro_page_after' ); ?>
	</main>
</div>

<?php get_footer(); ?>
