<?php
/**
 * Template Name: Papers Custom Template
 * Post Type: post
 * Description: Custom layout for posts within the Papers parent category and its children.
 */

get_header(); ?>

<main id="primary" class="site-main papers-template-container">
    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('paper-article'); ?>>
            
            <!-- Two-Column Main Layout Wrapper -->
            <div class="paper-split-layout">
                
                <!-- Left Column: Smaller Featured Image -->
                <div class="paper-layout-left">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="paper-featured-image">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Title, Metadata, Excerpt Box, and Main Content -->
                <div class="paper-layout-right">
                    <header class="paper-header">
                        <?php 
                        // Main Title
                        the_title('<h1 class="paper-title">', '</h1>'); 
                        ?>

                    </header>

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
                    <!-- Integrated Like Button Counter System (Moved here under title/description) -->
                    <?php
                    $post_id = get_the_ID();
                    $likes_count = function_exists( 'a1geo_get_post_likes_count' )
                        ? a1geo_get_post_likes_count( $post_id )
                        : 0;
                    $acf_author    = function_exists( 'get_field' ) ? get_field( 'author', $post_id ) : '';
                    ?>
                    <div class="custom-like-container">
                        <button class="custom-like-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                            👍 Like
                        </button>
                        <span class="custom-like-count" id="like-count-<?php echo esc_attr( $post_id ); ?>">
                            <?php echo esc_html( $likes_count ); ?> Likes
                        </span>
                    </div>

                    <!-- Document Metadata Block (Moved under the title) -->
                    <div class="paper-meta">
                        <span class="paper-author"><strong>Author:</strong> <?php echo $acf_author ?></span>
                        <span class="paper-date"><strong>Published:</strong> <?php echo get_the_date('F j, Y'); ?></span>
                    </div>
                    
                    <!-- The Gated Voter Form Modal Pop-up -->
                    <div id="like-form-modal" class="like-modal">
                        <div class="like-modal-content">
                            <span class="like-modal-close">&times;</span>
                            <h3>Support this paper!</h3>
                            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Please confirm your details to submit your vote.</p>
                            
                            <form id="voter-gate-form">
                                <label for="voter-name">Your Name</label>
                                <input type="text" id="voter-name" required placeholder="John Doe">

                                <label for="voter-email">Email Address</label>
                                <input type="email" id="voter-email" required placeholder="john@example.com">

                                <div id="like-error-msg" style="color: red; font-size: 13px; margin-bottom: 10px; display: none;"></div>

                                <button type="submit" class="like-modal-submit">Like</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDF Viewer Built with HTML5 Object Tag (Option 1 Alternative) -->
            <?php if ( $paper_document_url ) : ?>
                <div class="paper-pdf-viewer">
                    <object 
                        data="<?php echo esc_url( $paper_document_url ); ?>" 
                        type="application/pdf" 
                        width="100%" 
                        height="800px"
                        style="border: 1px solid #d9dde2; border-radius: 4px;">
                        
                        <!-- Fallback block only fires if the specific device cannot display live PDFs -->
                        <div class="paper-pdf-fallback" style="padding: 20px; background: #f8f9fa; border: 1px solid #d9dde2; border-radius: 4px; text-align: center;">
                            <p style="margin-bottom: 10px; color: #333;">
                                <?php esc_html_e( 'Your browser or device does not support viewing this PDF inline.', 'a1-geo' ); ?>
                            </p>
                            <a href="<?php echo esc_url( $paper_document_url ); ?>" class="btn btn-primary" target="_blank" rel="noopener" style="display: inline-block; padding: 8px 16px; text-decoration: none; font-weight: bold;">
                                📥 <?php esc_html_e( 'Open or download the paper file directly', 'a1-geo' ); ?>
                            </a>
                        </div>
                    </object>
                </div>
            <?php endif; ?>

            <!-- Document Footer Section -->
            <footer class="paper-footer">
                <div class="paper-tags">
                    <?php the_tags( '<span class="tags-label">Keywords:</span> ', ', ', '' ); ?>
                </div>
            </footer>

        </article>        

        <!-- Return to the paper category list -->
        <nav class="paper-navigation">
            <?php
            $papers_page       = get_page_by_path( 'papers' );
            $papers_list_url   = $papers_page ? get_permalink( $papers_page ) : home_url( '/papers/' );
            $papers_parent_cat = get_category_by_slug( 'papers' );
            $paper_category_slug = '';
            $paper_category_name = __( 'Papers', 'a1-geo' );

            if ( $papers_parent_cat ) {
                foreach ( get_the_category() as $post_category ) {
                    if ( (int) $post_category->parent === (int) $papers_parent_cat->term_id ) {
                        $paper_category_slug = $post_category->slug;
                        $paper_category_name = $post_category->name;
                        break;
                    }
                }
            }

            if ( $paper_category_slug ) {
                $papers_list_url = add_query_arg( 'category', $paper_category_slug, $papers_list_url );
            }
            ?>
            <a class="paper-category-back-link" href="<?php echo esc_url( $papers_list_url ); ?>">
                <?php echo esc_html( sprintf( __( 'Back to %s', 'a1-geo' ), $paper_category_name ) ); ?>
            </a>
        </nav>

    <?php endwhile; ?>
</main>

<?php 
get_sidebar(); // Remove this line if you prefer a distraction-free, full-width layout
get_footer(); 
?>
