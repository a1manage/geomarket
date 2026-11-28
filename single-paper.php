<?php
/**
 * Template Name: Papers Custom Template
 * Template Post Type: page, post
 * Description: Custom layout for posts within the Papers parent category and its children.
 */

remove_action( 'electro_content_top', 'electro_breadcrumb', 10 );
get_header(); ?>



<main id="primary" class="site-main papers-template-container">
    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('paper-article'); ?>>
            
            <header class="paper-header">
                <?php the_title( '<h1 class="paper-title">', '</h1>' ); ?>
            </header>

            <div class="paper-split-layout">
                
                <!-- Left Column: Smaller Featured Image -->
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="paper-layout-left">
                        <div class="paper-featured-image">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="paper-layout-right">
                    <!-- Bordered Post Description (Excerpt) Box -->
                    <?php if ( has_excerpt() ) : ?>
                        <div class="paper-description-box">
                            <?php echo get_the_excerpt(); ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $paper_document = function_exists( 'get_field' )
                        ? get_field( 'attach_document', get_the_ID() )
                        : get_post_meta( get_the_ID(), 'attach_document', true );

                    if ( is_array( $paper_document ) && ! empty( $paper_document['url'] ) ) {
                        $paper_document_url = $paper_document['url'];
                    } elseif ( is_numeric( $paper_document ) ) {
                        $paper_document_url = wp_get_attachment_url( absint( $paper_document ) );
                    } else {
                        $paper_document_url = is_string( $paper_document ) ? $paper_document : '';
                    }
                    ?>
                    <?php
                    $post_id = get_the_ID();
                    $likes_count = function_exists( 'a1geo_paper_like_count' )
                        ? a1geo_paper_like_count( $post_id )
                        : null;
                    $acf_author    = function_exists( 'get_field' ) ? get_field( 'author', $post_id ) : '';
                    ?>
                    <div class="custom-like-container">
                        <?php if ( function_exists( 'a1geo_render_paper_like_control' ) ) : ?>
                            <?php a1geo_render_paper_like_control( $post_id, $likes_count ); ?>
                        <?php else : ?>
                            <span><?php esc_html_e( 'Likes are currently unavailable.', 'a1-geo' ); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Document Metadata Block (Moved under the title) -->
                    <div class="paper-meta">
                        <span class="paper-author"><strong>Author:</strong> <?php echo $acf_author ?></span>
                        <span class="paper-date"><strong>Published:</strong> <?php echo get_the_date('F j, Y'); ?></span>
                    </div>
                    <?php if ( $paper_document_url ) : ?>
                        <details class="paper-pdf-viewer">
                            <summary><?php esc_html_e( 'View PDF', 'a1-geo' ); ?></summary>
                            <object
                                data="<?php echo esc_url( $paper_document_url ); ?>"
                                type="application/pdf"
                                width="100%"
                                height="800px">
                                <div class="paper-pdf-fallback">
                                    <p>
                                        <?php esc_html_e( 'Your browser or device does not support viewing this PDF inline.', 'a1-geo' ); ?>
                                    </p>
                                    <a href="<?php echo esc_url( $paper_document_url ); ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                                        📥 <?php esc_html_e( 'Open or download the paper file directly', 'a1-geo' ); ?>
                                    </a>
                                </div>
                            </object>
                        </details>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Document Footer Section -->
            <footer class="paper-footer">
                <div class="paper-tags">
                    <?php the_tags( '<span class="tags-label">Keywords:</span> ', ', ', '' ); ?>
                </div>
            </footer>

        </article>        

        <nav class="paper-navigation">
            <?php
            $explorer_url = function_exists( 'a1geo_paper_explorer_url' ) ? a1geo_paper_explorer_url() : false;
            $back_label = __( 'Back to Paper Explorer', 'a1-geo' );
            $return_category = isset( $_GET['paper_return_category'] ) && is_string( $_GET['paper_return_category'] )
                ? get_category_by_slug( sanitize_title( wp_unslash( $_GET['paper_return_category'] ) ) )
                : false;
            $papers_category = get_category_by_slug( 'papers' );
            if ( $return_category && $papers_category && function_exists( 'a1geo_paper_category_url' )
                && in_array( (int) $papers_category->term_id, get_ancestors( $return_category->term_id, 'category' ), true ) ) {
                $category_explorer_pages = get_posts( array(
                    'post_type'      => 'page',
                    'post_status'    => 'publish',
                    'meta_key'       => '_wp_page_template',
                    'meta_value'     => 'template-paper-category-explorer.php',
                    'posts_per_page' => 1,
                    'orderby'        => 'ID',
                    'order'          => 'ASC',
                ) );
                if ( $category_explorer_pages ) {
                    $explorer_url = a1geo_paper_category_url( $return_category->slug, get_permalink( $category_explorer_pages[0]->ID ) );
                    $return_args = array();
                    foreach ( array( 'page', 'per_page', 'sort', 'search' ) as $key ) {
                        $input_key = 'paper_return_' . $key;
                        if ( isset( $_GET[ $input_key ] ) && is_string( $_GET[ $input_key ] ) ) {
                            $return_args[ 'paper_' . $key ] = rawurlencode( sanitize_text_field( wp_unslash( $_GET[ $input_key ] ) ) );
                        }
                    }
                    $explorer_url = add_query_arg( $return_args, $explorer_url );
                    $back_label = sprintf( __( 'Back to %s', 'a1-geo' ), $return_category->name );
                }
            }
            ?>
            <?php if ( $explorer_url ) : ?>
                <a class="paper-category-back-link" href="<?php echo esc_url( $explorer_url ); ?>">
                    <?php echo esc_html( $back_label ); ?>
                </a>
            <?php else : ?>
                <span><?php esc_html_e( 'Paper Explorer is currently unavailable.', 'a1-geo' ); ?></span>
            <?php endif; ?>
        </nav>

    <?php endwhile; ?>
</main>

<?php 
get_sidebar(); // Remove this line if you prefer a distraction-free, full-width layout
get_footer(); 
?>
