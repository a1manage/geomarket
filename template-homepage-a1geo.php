<?php
/**
 * Template Name: A1Geo homepage
 *
 * @package electro
 */

remove_action( 'electro_content_top', 'electro_breadcrumb', 10 );

do_action( 'electro_before_homepage_v11' );

$home_v11_header_option	= electro_acf_home_v11_get_header_style();
$header_style	= 'v11';

if ( $home_v11_header_option ) {
	$header_style = $home_v11_header_option;
}


electro_get_header( $header_style ); ?>

	<div id="primary" class="entry-content">
		<main id="main" class="site-main" style="margin-top:-35px;">

		<?php 

		//while ( have_posts() ) : the_post(); 

			do_action( 'electro_page_before' );

			$page_description = a1geo_get_page_description( get_queried_object_id() );

			//get_template_part( 'templates/contents/content', 'page' );
			// ZZZZZ
			get_template_part( 'templates/contents/content', 'page' );
			?>
			<div class="stretch-full-width slider-block no-lazyload" style="background-image:url(<?php echo esc_url( get_theme_file_uri( 'assets/img/bg.jpg' ) ); ?>); background-position: center bottom; background-size: cover;">
				<div class="container">
					<div class="pb-4 pt-3">
						<!-- justify-content-end pushes the content column to the right side automatically -->
						<div class="row justify-content-end">
							
							<!-- USE THIS REPLACED COLUMN STRUCTURE -->
							<div class="col-12 col-sm-12 col-md-6 col-lg-6 pt-3">
								<p class="home-p-1"><b>The leading buyer’s guide for geophysicists and geologists worldwide.</b></p>
							</div>
							
						</div>						
					</div>	
				</div>
			</div>
			
			<div class="row" style="margin-top:10px;">						
				<div class="col col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 d-flex flex-column float-right">
					<p><span class="mobile-instruction-text" style="font-size: 14px;color:red;">
							<b>For a better view, please rotate your phone to landscape mode.</b>
						</span>
					</p>		
					<?php if ( '' !== trim( wp_strip_all_tags( $page_description ) ) ) : ?>
						<div class="custom-page-description">
							<?php echo wp_kses_post( $page_description ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>	
			
			<div class="row g-2 gx-2 row-cols-1 row-cols-md-2 rows-cols-lg-4 row-cols-xl-4" style="margin-top:10px;">
				<div class="col d-flex flex-column" >										
					<div class="custom-brand-with-category cat-instrument">
						<div class="category-title"><h4 class="me-2 mb-0 fw-bold cat-title">Instruments</h4></div>
						<div class="category-image"><img class="img-fluid h" style="width:100%;" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/cate_instrument.jpg' ) ); ?>" alt="Instruments" loading="eager" ></div>						
						
						<div class="py-4 position-relative col categories">
							<ul class="mb-0 list-unstyled">
								<?php
									$args = array('parent' => 22,
												 'taxonomy' => 'product_cat',
												 'hide_empty' => 0,
												 'orderby' => 'id');
									$categories = get_categories($args);
									$category_order = array_flip(array(
										'seismic-instruments', 'resistivity-instruments', 'tem-instruments',
										'em-instruments', 'gravity-instruments', 'drilling-devices', 'magnetic-instruments',
										'radioactivity-instruments', 'borehole-instruments', 'airborne-instruments',
										'ground-penetrating-radar', 'general-data-recording',
										'positioning-topographic-devices', 'other-geo-instruments'
									));
									usort($categories, function ($category_a, $category_b) use ($category_order) {
										$order_a = $category_order[$category_a->slug] ?? PHP_INT_MAX;
										$order_b = $category_order[$category_b->slug] ?? PHP_INT_MAX;
										return $order_a <=> $order_b;
									});
								
									foreach($categories as $category) {
										echo '<li>';										
										$product_text = ' instruments';
										if ($category->count <= 1) {
											$product_text = ' instrument';
										}
										echo '<div class="sub-cat-name"><a class="category-name" href="' . get_site_url() . '/'. $category->slug . '/" class="fw-bold mb-4">' . $category->name . '</a><br/><span class="a-list-item item-count">' . $category->count . $product_text . ' </span></div>';
										echo '</li>';
									}
								?>																	
							</ul>
						</div>
					</div>
				</div>
				<div class="col d-flex flex-column">					
					<div class="custom-brand-with-category cat-service">
						<div class="category-title"><h4 class="me-2 mb-0 fw-bold cat-title">Geophysics Services</h4></div>
						<div class="category-image"><img class="img-fluid h" style="width:100%;" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/cate_service.jpg' ) ); ?>" alt="Services" loading="lazy"></div>							
						<div class="py-4 position-relative col categories">
							<ul class="mb-0 list-unstyled">
								<?php
									$args = array('parent' => 24,
												 'taxonomy' => 'product_cat',
												 'hide_empty' => 0,
												 'orderby' => 'id');
									$categories = get_categories($args);
									$category_order = array_flip(array(
										'seismic-services', 'resistivity-services', 'em-services', 'tem-services',
										'gravity-services', 'drilling-services', 'magnetic-services',
										'radioactivity-services', 'borehole-geophysics-services',
										'airborne-services', 'ground-pen-radar-services',
										'data-processing-seismic', 'data-processing-r-ip-em-tem',
										'data-processing-others', 'comprehensive-survey-services',
										'positioning-topographic-services', 'other-geo-services'
									));
									usort($categories, function ($category_a, $category_b) use ($category_order) {
										$order_a = $category_order[$category_a->slug] ?? PHP_INT_MAX;
										$order_b = $category_order[$category_b->slug] ?? PHP_INT_MAX;
										return $order_a <=> $order_b;
									});
					
					foreach($categories as $category) {
						echo '<li>';						
						$product_text = ' services';
						if ($category->count <= 1) {
							$product_text = ' service';
						}
						echo '<div class="sub-cat-name"><a class="category-name" href="' . get_site_url() . '/'. $category->slug . '/" class="fw-bold mb-4">' . $category->name . '</a><br/><span class="a-list-item" style="font-style: italic">' . $category->count . $product_text . ' </span></div>';
						echo '</li>';
					}
				?>																	
							</ul>
						</div>
					</div>
				</div>
				<div class="col d-flex flex-column">					
					<div class="custom-brand-with-category cat-application">
						<div class="category-title"><h4 class="me-2 mb-0 fw-bold cat-title">Application services</h4></div>
						<div class="category-image"><img class="img-fluid h" style="width:100%;" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/cate_applications.jpg' ) ); ?>" alt="Applications" loading="lazy"></div>									
						<div class="py-4 position-relative col categories">
							<ul class="mb-0 list-unstyled">
								<?php
									$args = array('parent' => 88,
												 'taxonomy' => 'product_cat',
												 'hide_empty' => 0,
												 'orderby' => 'id');
									$categories = get_categories($args);
									
									$tmpCategory = $categories[12];
									$categories[12] = $categories[11];
									$categories[11] = $tmpCategory;

									foreach($categories as $category) {
										echo '<li>';
										$product_text = ' items';
										if ($category->count <= 1) {
											$product_text = ' item';
										}
										echo '<div class="sub-cat-name"><a class="category-name" href="' . get_site_url() . '/'. $category->slug . '/" class="fw-bold mb-4">' . $category->name . '</a><br/><span class="a-list-item" style="font-style: italic">' . $category->count . $product_text . ' </span></div>';
										echo '</li>';
									}
								?>																	
							</ul>
						</div>
					</div>
				</div>
				<div class="col d-flex flex-column">				
					<div class="custom-brand-with-category cat-other" >
						<div class="category-title"><h4 class="me-2 mb-0 fw-bold cat-title">Others</h4></div>
						<div class="category-image"><img class="img-fluid h" style="width:100%;" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/cate_others.jpg' ) ); ?>" alt="Others" loading="lazy"></div>										
						<div class="py-4 position-relative col categories">
							<ul class="mb-0 list-unstyled">
								<?php
									$args = array('parent' => 26,
												 'taxonomy' => 'product_cat',
												 'hide_empty' => 0,
												 'orderby' => 'id');
									$categories = get_categories($args);
					$category_order = array_flip(array(
						'geophysics-software-seismic',
						'geophysics-software-r-ip-em-tem',
						'geophysics-software-others',
						'rented-instruments', 'secondhand-instruments', 'cables',
						'geophones-hydrophones', 'tablet-laptop', 'winches', 'reels',
						'seismic-sources', 'sensors', 'batteries', 'other-devices'
					));
					usort($categories, function ($category_a, $category_b) use ($category_order) {
						$order_a = $category_order[$category_a->slug] ?? PHP_INT_MAX;
						$order_b = $category_order[$category_b->slug] ?? PHP_INT_MAX;
						return $order_a <=> $order_b;
					});
								
									foreach($categories as $category) {
										echo '<li>';										
										$product_text = ' products';
										if ($category->count <= 1) {
											$product_text = ' product';
										}
										echo '<div class="sub-cat-name"><a class="category-name" href="' . get_site_url() . '/'. $category->slug . '/" class="fw-bold mb-4">' . $category->name . '</a><br/><span class="a-list-item" style="font-style: italic">' . $category->count . $product_text . ' </span></div>';
										echo '</li>';
									}
								?>																	
							</ul>
						</div>
					</div>
				</div>
			</div>
			
			<div class="row" style="margin-top:10px;">
				<div class="col col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 d-flex flex-column">
					<h3>Geophysical Instruments</h3>
					<p>Encompasses hardware and devices across various methods, including seismic instruments, resistivity/IP instruments, TEM instruments, EM instruments, magnetometers, radiometric equipment, GPR units, airborne geophysical systems, and well logging tools.</p>

					<h3>Geophysical Survey Services</h3>
					<p>Covers field acquisition and technical consulting across all methodologies, including seismic, resistivity/IP, TEM, EM, magnetic, radiometric, GPR, airborne, and well logging services, as well as associated geophysical data processing and interpretation.</p>

					<h3>Geophysical Applications</h3>
					<p>Outlines the geological and engineering problems addressed using geophysical techniques. Applications include groundwater exploration, civil engineering geology, mineral exploration, archaeological investigations, environmental and landfill site investigations, geohazard assessments, geological and infrastructure mapping, oil and gas exploration, thermal and geothermal surveys, structural and dam monitoring, and geophysical education and training.</p>

					<h3>Others</h3>
					<p>Covers geophysical products and services beyond the core categories, including geophysical software, instrument rentals, secondhand or used equipment, geophysical cables, geophones, seismic energy sources, winches, and auxiliary field accessories.</p>
				</div>
			</div>
			<div class="row" style="margin-top:10px;">
				<div class="col col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 d-flex flex-column">
					<p><span style="font-size: 14px;">If you notice any inaccurate or unlawful information on this website, please use the “Feedback” link at the top of the page to report it. We will review and correct the issue promptly. <br/></span></p>
					
				</div>
			</div>				
		<?php	
			/**
			 * @hooked electro_display_comments - 10
			 */
			//do_action( 'electro_page_after' );

		//endwhile; // end of the loop.
			do_action('')
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

	<?php 
get_footer(); ?>
