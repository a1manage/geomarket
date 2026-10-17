<?php
/**
 * Template Name: Submit Paper
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

$papers_parent = get_category_by_slug( 'papers' );
$paper_categories = $papers_parent ? get_categories(
	array(
		'parent'     => $papers_parent->term_id,
		'hide_empty' => false,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	)
) : array();
$return_category = isset( $_GET['category'] )
	? get_category_by_slug( sanitize_title( wp_unslash( $_GET['category'] ) ) )
	: null;
$submission_errors = array();

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['a1geo_submit_paper'] ) ) {
	$nonce = isset( $_POST['a1geo_submit_paper_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['a1geo_submit_paper_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'a1geo_submit_paper' ) ) {
		$submission_errors[] = __( 'The form expired. Please try again.', 'a1-geo' );
	}

	$title        = isset( $_POST['paper_title'] ) ? sanitize_text_field( wp_unslash( $_POST['paper_title'] ) ) : '';
	$excerpt      = isset( $_POST['paper_excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['paper_excerpt'] ) ) : '';
	$author_name  = isset( $_POST['submit_author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['submit_author_name'] ) ) : '';
	$author_email = isset( $_POST['author_email'] ) ? sanitize_email( wp_unslash( $_POST['author_email'] ) ) : '';
	$category_id  = isset( $_POST['paper_category'] ) ? absint( $_POST['paper_category'] ) : 0;
	$category     = $category_id ? get_category( $category_id ) : null;
	$pdf_file     = isset( $_FILES['attach_document'] ) ? $_FILES['attach_document'] : null;
	$image_file   = isset( $_FILES['paper_image'] ) ? $_FILES['paper_image'] : null;

	if ( '' === $title ) {
		$submission_errors[] = __( 'Enter a paper title.', 'a1-geo' );
	}
	if ( '' === $excerpt ) {
		$submission_errors[] = __( 'Enter a paper description.', 'a1-geo' );
	}
	if ( ! $papers_parent || ! $category || (int) $category->parent !== (int) $papers_parent->term_id ) {
		$submission_errors[] = __( 'Select a valid paper category.', 'a1-geo' );
	}
	if ( '' === $author_name ) {
		$submission_errors[] = __( 'Enter the author name.', 'a1-geo' );
	}
	if ( ! is_email( $author_email ) ) {
		$submission_errors[] = __( 'Enter a valid author email address.', 'a1-geo' );
	}

	if ( $pdf_file && UPLOAD_ERR_NO_FILE !== $pdf_file['error'] ) {
		if ( UPLOAD_ERR_OK !== $pdf_file['error'] ) {
			$submission_errors[] = __( 'The PDF could not be uploaded.', 'a1-geo' );
		} else {
		$pdf_type = wp_check_filetype_and_ext( $pdf_file['tmp_name'], $pdf_file['name'] );
		if ( 'application/pdf' !== $pdf_type['type'] || 'pdf' !== strtolower( pathinfo( $pdf_file['name'], PATHINFO_EXTENSION ) ) ) {
			$submission_errors[] = __( 'The paper attachment must be a PDF file.', 'a1-geo' );
		}
		}
	}

	if ( $image_file && UPLOAD_ERR_NO_FILE !== $image_file['error'] ) {
		if ( UPLOAD_ERR_OK !== $image_file['error'] ) {
			$submission_errors[] = __( 'The featured image could not be uploaded.', 'a1-geo' );
		} else {
			$image_type = wp_check_filetype_and_ext( $image_file['tmp_name'], $image_file['name'] );
			if ( empty( $image_type['type'] ) || 0 !== strpos( $image_type['type'], 'image/' ) ) {
				$submission_errors[] = __( 'Upload a valid featured image.', 'a1-geo' );
			}
		}
	}

	if ( empty( $submission_errors ) ) {
		$post_id = wp_insert_post(
			array(
				'post_type'     => 'post',
				'post_status'   => 'pending',
				'post_title'    => $title,
				'post_excerpt'  => $excerpt,
				'post_content'  => '',
				'post_category' => array( $papers_parent->term_id, $category->term_id ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$submission_errors[] = __( 'The paper could not be submitted. Please try again.', 'a1-geo' );
		} else {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$pdf_id   = false;
			$image_id = false;
			if ( $pdf_file && UPLOAD_ERR_NO_FILE !== $pdf_file['error'] ) {
				$pdf_id = media_handle_upload( 'attach_document', $post_id );
			}
			if ( $image_file && UPLOAD_ERR_NO_FILE !== $image_file['error'] ) {
				$image_id = media_handle_upload( 'paper_image', $post_id );
			}

			if ( is_wp_error( $pdf_id ) || is_wp_error( $image_id ) ) {
				if ( ! is_wp_error( $pdf_id ) ) {
					wp_delete_attachment( $pdf_id, true );
				}
				if ( $image_id && ! is_wp_error( $image_id ) ) {
					wp_delete_attachment( $image_id, true );
				}
				wp_delete_post( $post_id, true );
				$submission_errors[] = __( 'A file could not be uploaded. Check its type and size, then try again.', 'a1-geo' );
			} else {
				if ( function_exists( 'update_field' ) ) {
					if ( $pdf_id ) {
						update_field( 'attach_document', $pdf_id, $post_id );
					}
					update_field( 'author', $author_name, $post_id );
					update_field( 'author_email', $author_email, $post_id );
				} else {
					if ( $pdf_id ) {
						update_post_meta( $post_id, 'attach_document', $pdf_id );
					}
					update_post_meta( $post_id, 'author', $author_name );
					update_post_meta( $post_id, 'author_email', $author_email );
				}
				if ( $image_id && ! is_wp_error( $image_id ) ) {
					set_post_thumbnail( $post_id, $image_id );
				}
				wp_safe_redirect(
					add_query_arg(
						array(
							'category'  => $category->slug,
							'submitted' => '1',
						),
						get_permalink()
					)
				);
				exit;
			}
		}
	}
}

electro_get_header( $header_style );
?>

<style>
	.submit-paper-page { margin-top: -35px; padding-bottom: 56px; }
	.submit-paper-shell { max-width: 920px; padding-top: 32px; }
	.submit-paper-form { padding: 24px; border: 1px solid #d9dde2; background: #fff; }
	.submit-paper-field { margin-bottom: 20px; }
	.submit-paper-field label { display: block; margin-bottom: 7px; font-weight: 700; }
	.submit-paper-field input[type="text"],
	.submit-paper-field input[type="email"],
	.submit-paper-field select,
	.submit-paper-field textarea { width: 100%; max-width: 100%; }
	.submit-paper-field textarea { min-height: 150px; }
	.submit-paper-alert { margin-bottom: 18px; padding: 14px 18px; border: 1px solid #d9dde2; background: #fff; }
	.submit-paper-alert-error { border-color: #b42318; color: #8c1d18; }
	.submit-paper-submit { padding: 11px 20px; border: 0; background: #1d5089; color: #fff; cursor: pointer; }
	@media (max-width: 575px) { .submit-paper-form { padding: 16px; } }
</style>

<div id="primary" class="content-area submit-paper-page">
	<main id="main" class="site-main">
		<?php do_action( 'electro_page_before' ); ?>
		<div class="container submit-paper-shell">
			<h1 class="custom-page-title"><?php esc_html_e( 'Submit Paper', 'a1-geo' ); ?></h1>

			<?php if ( isset( $_GET['submitted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['submitted'] ) ) ) : ?>
				<?php
				$papers_page = get_page_by_path( 'papers' );
				$category_page_url = $papers_page ? get_permalink( $papers_page ) : home_url( '/papers/' );
				if ( $return_category ) {
					$category_page_url = add_query_arg( 'category', $return_category->slug, $category_page_url );
				}
				?>
				<div class="submit-paper-alert" role="status">
					<p><?php esc_html_e( 'Your paper is submitted, please wait for the admin to review it. It will be published after passing review.', 'a1-geo' ); ?></p>
					<a href="<?php echo esc_url( $category_page_url ); ?>">
						<?php echo esc_html( sprintf( __( 'Return to %s papers', 'a1-geo' ), $return_category ? $return_category->name : __( 'paper category', 'a1-geo' ) ) ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( $submission_errors ) : ?>
				<div class="submit-paper-alert submit-paper-alert-error" role="alert">
					<ul><?php foreach ( $submission_errors as $error ) : ?><li><?php echo esc_html( $error ); ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>

			<form class="submit-paper-form" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'a1geo_submit_paper', 'a1geo_submit_paper_nonce' ); ?>
				<input type="hidden" name="a1geo_submit_paper" value="1">
				<div class="submit-paper-field">
					<label for="paper-title"><?php esc_html_e( 'Paper title', 'a1-geo' ); ?></label>
					<input type="text" id="paper-title" name="paper_title" maxlength="200" required value="<?php echo isset( $_POST['paper_title'] ) ? esc_attr( wp_unslash( $_POST['paper_title'] ) ) : ''; ?>">
				</div>
				<div class="submit-paper-field">
					<label for="paper-category"><?php esc_html_e( 'Paper category', 'a1-geo' ); ?></label>
					<select id="paper-category" name="paper_category" required>
						<option value=""><?php esc_html_e( 'Select a category', 'a1-geo' ); ?></option>
						<?php foreach ( $paper_categories as $paper_category ) : ?>
							<?php
							$selected_category_id = isset( $_POST['paper_category'] )
								? absint( $_POST['paper_category'] )
								: ( $return_category ? (int) $return_category->term_id : 0 );
							?>
							<option value="<?php echo esc_attr( $paper_category->term_id ); ?>" <?php selected( $selected_category_id, $paper_category->term_id ); ?>><?php echo esc_html( $paper_category->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="submit-paper-field">
					<label for="paper-excerpt"><?php esc_html_e( 'Paper description', 'a1-geo' ); ?></label>
					<textarea id="paper-excerpt" name="paper_excerpt" required><?php echo isset( $_POST['paper_excerpt'] ) ? esc_textarea( wp_unslash( $_POST['paper_excerpt'] ) ) : ''; ?></textarea>
				</div>
				<div class="submit-paper-field">
					<label for="attach-document"><?php esc_html_e( 'Paper PDF', 'a1-geo' ); ?></label>
					<input type="file" id="attach-document" name="attach_document" accept="application/pdf,.pdf">
				</div>
				<div class="submit-paper-field">
					<label for="paper-image"><?php esc_html_e( 'Featured image', 'a1-geo' ); ?></label>
					<input type="file" id="paper-image" name="paper_image" accept="image/*">
				</div>
				<div class="submit-paper-field">
					<label for="author-name"><?php esc_html_e( 'Author name (will be displayed on paper list)', 'a1-geo' ); ?></label>
					<input type="text" id="author-name" name="submit_author_name" required value="<?php echo isset( $_POST['submit_author_name'] ) ? esc_attr( wp_unslash( $_POST['submit_author_name'] ) ) : ''; ?>">
				</div>
				<div class="submit-paper-field">
					<label for="author-email"><?php esc_html_e( 'Author email', 'a1-geo' ); ?></label>
					<input type="email" id="author-email" name="author_email" required value="<?php echo isset( $_POST['author_email'] ) ? esc_attr( wp_unslash( $_POST['author_email'] ) ) : ''; ?>">
				</div>
				<button class="submit-paper-submit" type="submit"><?php esc_html_e( 'Submit paper for review', 'a1-geo' ); ?></button>
			</form>
		</div>
		<?php do_action( 'electro_page_after' ); ?>
	</main>
</div>

<?php get_footer(); ?>