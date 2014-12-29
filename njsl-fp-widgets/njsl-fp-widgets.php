<?php

/*
Plugin Name: Widgets for the 2014 NJSL Home Page
Plugin URI: http://www.njstatelib.org
Description: widgets
Version: 1.4.2
Author: David Dean for NJSL
Author URI: http://www.njstatelib.org
*/

/**
 * Widgets for the modular NJSL home page
 */

abstract class NJSL_Widget extends WP_Widget {
	
	public static function register() {
		return register_widget( get_called_class() );
	}
	
	public function widget( $args, $instance ) {
		echo $args['before_widget'] . $this->content( $args, $instance ) . $args['after_widget'];
	}
}

class Banner_Widget extends NJSL_Widget {
	
	function __construct() {
		parent::__construct(
			'njsl-banner',
			__( 'NJSL Banner', 'njsl-2014' )
		);
	}
	
	function content( $args, $instance ) {
		
		if( ! empty( $instance['images'] ) ) {
			$selected = rand( 0, count( $instance['images'] ) - 1 );
			$image = $instance['images'][$selected];
			
			if( false !== strpos( $image, ':', 6 ) ) {
				
				$URL = substr( $image, strpos( $image, ':', 6 ) + 1 );
				$image = substr( $image, 0, strpos( $image, ':', 6 ) );
			}
			
		} else {
			$image = get_stylesheet_directory_uri() .'/images/banners/colonial-wide.jpg';
		}
		
		$class = '';
		if( ! empty( $instance['hidden-xs'] ) )
			$class = ' hidden-xs';
		
		ob_start();
		?>
	<section class="search-tab njsl-banner<?php echo $class ?>">
		<?php if( ! empty( $URL ) ) { ?><a href="<?php echo esc_url( $URL ) ?>" title="<?php _e( 'Click to find out more about this item', 'njsl-2014' ) ?>"><?php } ?>
		<img src="<?php echo esc_url( $image ) ?>" style="width: 100%; border: 1px solid #ccc;">
		<?php if( ! empty( $URL ) ) { ?></a><?php } ?>
	</section>
		<?php
		return ob_get_clean();
	}
	
	public function form( $instance ) {
		
		if( ! empty( $instance['images'] ) ) {
			$images = join( "\n", $instance['images'] );
		} else {
			$images = '';
		}
		
		if( isset( $instance['hidden-xs'] ) ) {
			$hidden_xs = $instance['hidden-xs'];
		} else {
			$hidden_xs = false;
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('images') ?>"><?php _e('Image URLs:') ?></label>
			<textarea 
				class="widefat" 
				id="<?php echo $this->get_field_id('images') ?>" 
				name="<?php echo $this->get_field_name('images') ?>"
				title="<?php _e( 'Paste image URLs here (one per line)', 'njsl-2014' ) ?>"
				placeholder="<?php _e( 'Paste image URLs here (one per line)', 'njsl-2014' ) ?>"
			><?php echo esc_attr( $images ) ?></textarea>
			<small><?php printf( __( 'Use %s to create a link.', 'njsl-2014' ), sprintf( '<code>%s:%s</code>', __( 'Image-URL', 'njsl-2014' ), __( 'Link-URL', 'njsl-2014' ) ) ) ?></small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('hidden-xs') ?>"><?php _e( 'Hide images on very small screens?:', 'njsl-2014' ) ?></label>
			<input 
				type="checkbox" 
				class="widefat" 
				id="<?php echo $this->get_field_id('hidden-xs') ?>" 
				name="<?php echo $this->get_field_name('hidden-xs') ?>" 
				<? checked( $hidden_xs, 'on' ) ?>"
			>
		</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		
		if( ! empty( $new_instance['images'] ) ) {
			$instance['images'] = explode( "\n", $new_instance['images'] );
		} else {
			$instance['images'] = '';
		}

		if( isset( $new_instance['hidden-xs'] ) ) {
			$instance['hidden-xs'] = $new_instance['hidden-xs'];
		} else {
			$instance['hidden-xs'] = false;
		}
		
		return $instance;
		
	}
	
}

class Search_Widget extends NJSL_Widget {
	
	private $item_count = 0;
	
	function __construct() {
		parent::__construct(
			'njsl-search',
			__( 'NJSL Tabbed Search Box', 'njsl-2014' )
		);
		
		register_nav_menu( 'nj_information', __( 'New Jersey Information Menu', 'njsl-2014' ) );
		register_nav_menu( 'research_tools', __( 'Research Tools Menu', 'njsl-2014' ) );
		
		do_action( 'njsl_search_widget_loaded' );
		
		add_filter( 'nav_menu_css_class', array( &$this, 'add_menu_css_class' ), 10, 3 );
		add_filter( 'wp_nav_menu_objects', array( &$this, 'count_menu_objects' ), 10, 2 );
		
	}
	
	/**
	 * Display widget contents
	 */
	public function content( $args, $instance ) {
		ob_start();
		?>
	<section id="search-panel" class="search-tab">
		<ul class="nav nav-tabs responsive" id="responsive-tabs">
		<!--	<li style="padding: 10px; font-weight: bold">Search:</li> -->
			<li class="active"><a href="#catalog" data-toggle="tab"><?php _e( 'NJSL Catalog', 'njsl-2014' ) ?></a></li>
			<li><a href="#research_tools" data-toggle="tab"><?php _e( 'Research Tools', 'njsl-2014' ) ?></a></li>
			<li><a href="#nj_information" data-toggle="tab"><?php _e( 'NJ Information', 'njsl-2014' ) ?></a></li>
			<li><a href="#tbbc" data-toggle="tab"><?php _e( 'TBBC Catalog', 'njsl-2014' ) ?></a></li>
		</ul>
		
		<!-- Tab panes -->
		<div class="tab-content responsive">
			<div id="catalog" class="tab-pane active">
				<form 
					method="GET" 
					action="http://capemay.njstatelib.org/ipac20/ipac.jsp" 
					class="form-horizontal" 
					role="form"
				>
					
					<!-- Extra info for Horizon -->
					<input type="hidden" name="menu" value="search">
					<input type="hidden" name="profile" value="njl--1">
			
					<!-- Search field -->
					<div class="form-group">
						<label for="term" class="col-sm-2 control-label"><?php _e( 'Search', 'njsl-2014' ) ?></label>
						<div class="col-sm-8">
							<input 
								type="search" 
								name="term" 
								id="term" 
								class="form-control"
								placeholder="<?php _e('Search the catalog', 'njsl-2014' ) ?>"
							>
						</div>
						<div class="col-sm-2">
							<button 
								type="submit" 
								name="menu" 
								class="form-control btn btn-primary"
								value="search"
							><?php _e( 'Search', 'njsl-2014' ) ?></button>
						</div>
					</div>
			
					<!-- Search type select box -->
					<div class="form-group">
						<label for="index" class="col-sm-2 control-label"><?php _e( 'Search Type', 'njsl-2014' ) ?></label>
						<div class="col-sm-4">
							<select
								name="index" 
								id="index" 
								class="form-control"
							>
								<option value="ALLTITL"><?php _e( 'All Title Browse', 'njsl-2014' ) ?></option>
								<option value=".TW"    ><?php _e( 'Title Keyword', 'njsl-2014' ) ?></option>
								<option value="PAUTHOR"><?php _e( 'Author Browse', 'njsl-2014' ) ?></option>
								<option value=".AW"    ><?php _e( 'Author Keyword', 'njsl-2014' ) ?></option>
								<option value=".SW"    ><?php _e( 'Subject Keyword', 'njsl-2014' ) ?></option>
								<option value="STITL"  ><?php _e( 'Serial Title Browse', 'njsl-2014' ) ?></option>
								<option value="PSUBJ"  ><?php _e( 'Subject Browse', 'njsl-2014' ) ?></option>
								<option value=".NJ"    ><?php _e( 'New Jersey Subject Keyword', 'njsl-2014' ) ?></option>
								<option value=".GW"    ><?php _e( 'General Keyword', 'njsl-2014' ) ?></option>
								<option value=".SE"    ><?php _e( 'Series Keyword', 'njsl-2014' ) ?></option>
								<option value="PSERIES"><?php _e( 'Series Browse', 'njsl-2014' ) ?></option>
								<option value="CALLDD" ><?php _e( 'Dewey Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLFND"><?php _e( 'Foundation Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLGEN"><?php _e( 'Genealogy Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLJER"><?php _e( 'Jerseyana Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLLC" ><?php _e( 'Library of Congress Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLNJ" ><?php _e( 'New Jersey Docs Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLREF"><?php _e( 'Reference Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLSTD"><?php _e( 'State Doc Call Numbers', 'njsl-2014' ) ?></option>
								<option value="CALLSD" ><?php _e( 'Superintendent of Document Call Numbers', 'njsl-2014' ) ?></option>
								<option value="BC"     ><?php _e( 'Barcode', 'njsl-2014' ) ?></option>
								<option value="BIB"    ><?php _e( 'Bib No.', 'njsl-2014' ) ?></option>
								<option value="ISBNEX" ><?php _e( 'ISBN/ISSN Exact Match', 'njsl-2014' ) ?></option>
								<option value="LCCNEX" ><?php _e( 'LCCN Exact Match', 'njsl-2014' ) ?></option>
								<option value="CNTRLEX"><?php _e( 'Control Number Exact Match', 'njsl-2014' ) ?></option>
								<option value=".NW"    ><?php _e( 'Name Keyword', 'njsl-2014' ) ?></option>
							</select>
						</div>
						<div class="col-sm-4">
						<a class="btn btn-link form-control" href="http://capemay.njstatelib.org/ipac20/ipac.jsp?profile=njl--1&menu=search&submenu=subtab26"><?php _e( 'Advanced Search', 'njsl-2014' ) ?></a>
						</div>
					</div>
				</form>
				<hr style="margin-top: 0; margin-bottom: 0">
				<ul class="list-inline">
					<li>
						<a href="<?php echo home_url( 'research_library/electronic_resources/ebooks-and-audiobooks' ) ?>" class="btn btn-link">
							<?php _e( 'eBooks and Audiobooks', 'njsl-2014' ) ?>
						</a>
					</li>
					<li class="pull-right">
						<a href="http://capemay.njstatelib.org/ipac20/ipac.jsp?profile=njl--1&amp;menu=account" class="btn btn-link">
							<?php _e( 'My Library Card Account', 'njsl-2014' ) ?>
						</a>
					</li>
				</ul>
				
			</div>
			<div id="tbbc" class="tab-pane">
				<form 
					method="GET" 
					action="http://opac.njlbh.org/opacnj/dbSearchAll.aspx" 
					class="form-horizontal" 
					role="form"
				>
					
					<!-- Search field -->
					<div class="form-group">
						<label for="tbbc-term" class="col-sm-2 control-label"><?php _e( 'Search', 'njsl-2014' ) ?></label>
						<div class="col-sm-8">
							<input 
								type="search" 
								name="qsSearchAll" 
								id="tbbc-term" 
								class="form-control"
								placeholder="<?php _e( 'Search the TBBC Catalog', 'njsl-2014' ) ?>"
							>
						</div>
						<div class="col-sm-2">
							<button 
								type="submit" 
								name="menu" 
								class="form-control btn btn-primary"
								value="search"
							><?php _e( 'Search', 'njsl-2014' ) ?></button>
						</div>
					</div>
			
					<!-- Search type select box -->
					<div class="form-group">
						<div class="col-sm-4">
						<a class="btn btn-link form-control" href="http://opac.njlbh.org/opacnj/"><?php _e( 'Advanced Search', 'njsl-2014' ) ?></a>
						</div>
					</div>
				</form>
			</div>
			<div id="research_tools" class="tab-pane">
				<?php if( has_nav_menu( 'research_tools' ) ) : ?>
					<?php wp_nav_menu(
						array(
							'menu'           => 'research_tools',
							'theme_location' => 'research_tools',
							'items_wrap'     => '%3$s',
							'depth'          => 1,
							'walker'         => new BSCol_Walker(),
						)
					) ?>
				<?php else: ?>
				<div class="form-group">
					<div class="col-sm-6 col-xs-12"><a href="<?php echo home_url('electronic_resources/databases') ?>"           >Browse All Databases</a></div>
					<div class="col-sm-6 col-xs-12"><a href="http://www.tdnet.com/njstatelib/"                            >Search Journals by Title</a></div>
					<div class="col-sm-6 col-xs-12"><a href="<?php echo home_url( 'resources/new_jersey_information/' ) ?>"      >New Jersey Information</a></div>
					<div class="col-sm-6 col-xs-12"><a href="<?php echo home_url( 'resources/websites_and_research_guides/' ) ?>">Research and Web Guides</a></div>
				</div>
				<?php endif; ?>
			</div>
			<div id="nj_information" class="tab-pane">
				<?php if( has_nav_menu( 'nj_information' ) ) : ?>
					<?php wp_nav_menu(
						array(
							'menu'           => 'nj_information',
							'theme_location' => 'nj_information',
							'items_wrap'     => '%3$s',
							'depth'          => 1,
							'walker'         => new BSCol_Walker(),
						)
					) ?>
				<?php else: ?>
				<?php endif; ?>
			</div>
		</div>
	</section>

		<?php
		return ob_get_clean();
	}

	/**
	 * Space out items based on their number
	 */
	public function add_menu_css_class( $classes, $item, $args ) {
		
		if( empty( $args ) || ! isset( $args->menu ) )
			return $classes;
		
		if( $args->menu != 'nj_information' && $args->menu != 'research_tools' )
			return $classes;
		
		/** If there are more than 6 items, split into two columns */
		if( 6 < $this->item_count ) {
			$classes[] = 'col-sm-6 col-xs-12';
		} else {
			$classes[] = 'col-sm-12';
		}
		
		return $classes;
		
	}
	
	/**
	 * Set the item_count property for use in add_menu_css_class (above)
	 */
	public function count_menu_objects( $sorted_menu_items, $args ) {
		
		if( empty( $args ) || ! isset( $args->menu ) )
			return $sorted_menu_items;
		
		if( $args->menu != 'nj_information' && $args->menu != 'research_tools' )
			return $sorted_menu_items;
		
		$this->item_count = count( $sorted_menu_items );
		return $sorted_menu_items;
	}
	
}

class ContactUs_Widget extends NJSL_Widget {
	
	function __construct() {
		parent::__construct(
			'njsl-contact-us',
			__( 'NJSL Contact Us Box', 'njsl-2014' )
		);
	}
	
	/**
	 * Display widget contents
	 */
	public function content( $args, $instance ) {
		ob_start();
		?>
		<section id="contact-us-panel" class="homepage-box about-section">
			<h4 class="nav-header"><?php _e( 'Contact Us', 'njsl-2014' ) ?></h4>
			<table class="table responsive">
				<thead>
					<th scope="col">
						<span class="sr-only"><?php _e( 'Directions to each location', 'njsl-2014' ) ?></span>
					</th>
					<th scope="col">
						<a 
							href="<?php echo home_url( 'about/visit-us/directions/' ) ?>" 
							title="<?php _e( 'Directions to the New Jersey State Library', 'njsl-2014' ) ?>"
						><?php _e( 'New Jersey State Library', 'njsl-2014' ) ?></a>
					</th>
					<th scope="col">
						<a 
							href="<?php echo home_url( 'about/visit-us/talking_books_and-braille/' ) ?>" 
							title="<?php _e( 'Directions to the Talking Book and Braille Center', 'njsl-2014' ) ?>"
						><?php _e( 'Talking Book and Braille Center', 'njsl-2014' ) ?></a>
					</th>
				</thead>
				<tbody>
					<tr>
						<th scope="row">
							<i class="fa fa-fw fa-phone" title="<?php _e( 'Phone number', 'njsl-2014' ) ?>"></i>
							<span class="sr-only"><?php _e( 'Phone number', 'njsl-2014' ) ?></span>
						</th>
						<td>(609) 278-2640</td>
						<td>(800) 792-8322</td>
					</tr>
					<tr>
						<th scope="row">
							<i class="fa fa-fw fa-envelope" title="<?php _e( 'Email address', 'njsl-2014' ) ?>"></i>
							<span class="sr-only"><?php _e( 'Email address', 'njsl-2014' ) ?></span>
						</th>
						<td><a href="mailto:refdesk@njstatelib.org" title="<?php _e( 'Email the NJSL reference desk', 'njsl-2014' ) ?>">refdesk@njstatelib.org</a></td>
						<td><a href="mailto:tbbc@njstatelib.org" title="<?php _e( 'Email the Talking Book and Braille Center', 'njsl-2014' ) ?>">tbbc@njstatelib.org</a></td>
					</tr>
					<tr>
						<th scope="row">
							<i class="fa fa-fw fa-building-o" title="<?php _e( 'Street address', 'njsl-2014' ) ?>"></i>
							<span class="sr-only"><?php _e( 'Street address', 'njsl-2014' ) ?></span>
						</th>
						<td><address>
							185 W. State St<br>
							Trenton, NJ 08608
							</address>
						</td>
						<td>
							<address>
							2300 Stuyvesant Avenue<br>
							Trenton, NJ 08618
							</address>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<i class="fa fa-fw fa-clock-o" title="<?php _e( 'Hours', 'njsl-2014' ) ?>"></i>
							<span class="sr-only"><?php _e( 'Hours', 'njsl-2014' ) ?></span>
						</th>
						<td>Monday - Friday<br> 8:30 AM - 5:00 PM</td>
						<td>Monday - Friday<br> 8:30 AM - 4:30 PM</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td>
							<i class="fa fa-fw fa-location-arrow"></i>
							<span class="sr-only"><?php _e( 'Get directions and other information about visiting', 'njsl-2014' ) ?></span>
						</td>
						<td colspan="2">
							<a href="<?php echo home_url('about/visit-us/') ?>" style="display: block"><?php _e( 'Get directions to our facilities', 'njsl-2014' ) ?></a>
						</td>
					</tr>
				</tfoot>
			</table>
		</section>

		<?php
		return ob_get_clean();
	}
	
}

class NewsSlider_Widget extends NJSL_Widget {
	
	function __construct() {
		parent::__construct(
			'njsl-news-slider',
			__( 'NJSL News Slider', 'njsl-2014' )
		);
	}
	
	function content( $args, $instance ) {
		
		$title         = 'News';
		$default_image = get_home_url('/wp-includes/images/crystal/document.png');

		if( isset( $instance['title'] ) )
			$title = $instance['title'];
		
		if( isset( $instance['default_image'] ) )
			$default_image = $instance['default_image'];

		$post_params = array(
			'post_type'     => 'news'
		);
		
		if( ! empty( $instance['tag'] ) ) {
			$post_params = array_merge(
				$post_params,
				array(
					'tag' => $instance['tag']
				)
			);
		}
		
		$news = get_posts( $post_params );
		
		ob_start();
		?>
<section class="homepage-box news-box news-section">
	<h4><?php echo $title ?></h4>

	<div id="news-slider" class="carousel slide" data-ride="carousel" data-interval="10000">
	  <!-- Indicators -->
	  <ol class="carousel-indicators">
	  	<?php if( ! empty( $news ) ) : ?>
		  	<?php foreach( $news as $idx => $article) : ?>
		  		<li data-target="#news-slider" data-slide-to="<?php echo $idx ?>" <?php echo ( 0 == $idx ? 'class="active"' : '' ) ?>></li>
		  	<?php endforeach; ?>
		<?php else: ?>
		    <li data-target="#news-slider" data-slide-to="0" class="active"></li>
		    <li data-target="#news-slider" data-slide-to="1"></li>
		<?php endif; ?>
	  </ol>
	
	  <!-- Wrapper for slides -->
	  <div class="carousel-inner">
	  
	  	<!-- Slide loop -->
	  	<?php if( ! empty( $news ) ) : ?>
			<?php foreach( $news as $key => $article ) : ?>
				<?php
				
				global $authordata;
				
				if( ! is_object( $authordata ) )
					$authordata = (object)array();
				$authordata->ID = $article->post_author;
				
				?>
			<div class="item <?php echo ( 0 == $key ? 'active' : '' ) ?>">
				<a href="<?php echo get_permalink( $article->ID ) ?>" class="thumbnail">
					<?php
					
					$result = get_post_thumbnail_id( $article->ID );
					if( $result ) {
						$url = wp_get_attachment_url( $result );
					} else {
						$url = $default_image;
					}
					
					if( empty( $article->post_excerpt ) )
						$article->post_excerpt = $article->post_content;
					
					// Ensure the excerpt fits within the prescribed space
					if( strlen( strip_tags( $article->post_excerpt ) ) > 140 ) {
						$article->post_excerpt = substr( strip_tags( $article->post_excerpt ), 0, 137 ) . '...';
					}
					
					?>
					<img src="<?php echo esc_url( $url ) ?>" alt="" height="140" width="240"></img>
				</a>
				<div class="caption">
					<article>
						<h1><a href="<?php echo get_permalink( $article->ID ) ?>"><?php echo $article->post_title ?></a></h1>
						<div>
							<small>
								<?php echo date('M d, Y', strtotime( $article->post_date ) ) ?> 
								&mdash; 
								<a 
									href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>"
									title="<?php _e('See all posts by this author' ,'njsl-2014' ) ?>"
								><?php the_author_meta( 'display_name', get_the_author_meta( 'ID' ) ); ?></a>
							</small>
						</div>
						<p>
							<?php echo strip_tags( $article->post_excerpt ) ?>
						</p>
					</article>
				</div>
			</div>
			<?php endforeach; ?>
		<?php else: ?>
		<div class="item active">
			<a href="" class="thumbnail">
				<img src="http://www.njstatelib.org/njsl_files/imagecache/small_256_wide/disaster%20billboard.jpg" alt="Disaster Billboard">
			</a>
			<div class="caption">
				<h1>Libraries Receive Funding for Community Disaster Preparedness Programs</h1>
				<div><small>January 27, 2014 - Gary Cooper</small></div>
				<p>
					Three New Jersey libraries have received funding for their community disaster preparedness programs from National Network of ...
					<a href="">Read more</a>
				</p>
			</div>
		</div>
		
		<div class="item">
			<a href="" class="thumbnail">
				<img src="http://www.njstatelib.org/njsl_files/imagecache/small_256_wide/Alexandra%20Koerte%2C%20Ruch%20Holt.jpg" alt="Podcast">
			</a>
			<div class="caption">
				<h1>State Librarian Podcast, January 7, 2014: Affordable Care Act</h1>
				<div><small>January 8, 2014 - Tiffany McClary</small></div>
				<p>
					On January 7, 2014, State Librarian Mary Chute's podcast focused on a discussion of the Affordable Care Act with a panel of experts...
					<a href="">Read more</a>
				</p>
			</div>
		</div>
		<?php endif; ?>
	  </div>
	
	  <!-- Controls -->
	  <a class="left carousel-control" href="#news-slider" data-slide="prev">
	    <span class="glyphicon glyphicon-chevron-left"></span>
	  </a>
	  <a class="right carousel-control" href="#news-slider" data-slide="next">
	    <span class="glyphicon glyphicon-chevron-right"></span>
	  </a>
	</div>
	<hr>

	<p style="padding-left: 10px; padding-top: 5px;">
		<a href="<?php echo home_url('/news/') ?>" style="display: block"><?php _e( 'See more news', 'njsl-2014' ) ?></a>
	</p>

</section>

		<?php
		return ob_get_clean();
	}
	
	public function form( $instance ) {
		
		if( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'News from the State Library', 'njsl-2014' );
		}
		
		if( isset( $instance['tag'] ) && tag_exists( $instance['tag'] ) ) {
			$tag = $instance['tag'];
		} else {
			$tag = '';
			?>
			<p>
				You either haven't selected a tag or your tag is invalid.
			</p>
			<?php
		}
		
		if( isset( $instance['default_image'] ) ) {
			$default_image = $instance['default_image'];
		} else {
			$default_image = '';
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title') ?>"><?php _e( 'Title:', 'njsl-2014' ) ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" value="<?php echo esc_attr( $title ) ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tag') ?>"><?php _e( 'Tag for news posts on the front page:', 'njsl-2014' ) ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('tag') ?>" name="<?php echo $this->get_field_name('tag') ?>" value="<?php echo esc_attr( $tag ) ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('default_image') ?>"><?php _e( 'Default image for news items:', 'njsl-2014' ) ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('default_image') ?>" name="<?php echo $this->get_field_name('default_image') ?>" value="<?php echo esc_attr( $default_image ) ?>">
		</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		
		if( ! empty( $new_instance['title'] ) ) {
			$instance['title'] = $new_instance['title'];
		} else if( isset( $old_instance['title']) ) {
			$instance['title'] = $old_instance['title'];
		} else {
			$instance['title'] = '';
		}

		if( tag_exists( $new_instance['tag'] ) ) {
			$instance['tag'] = $new_instance['tag'];
		} else if( isset( $old_instance['tag']) ) {
			$instance['tag'] = $old_instance['tag'];
		} else {
			$instance['tag'] = '';
		}

		if( isset( $new_instance['default_image'] ) ) {
			$instance['default_image'] = $new_instance['default_image'];
		} else if( isset( $old_instance['default_image']) ) {
			$instance['default_image'] = $old_instance['default_image'];
		} else {
			$instance['default_image'] = '';
		}
		
		return $instance;
		
	}
	
}

// This widget is unused and may be incomplete
class Grants_Widget extends NJSL_Widget {
	
	function __construct() {
		parent::__construct(
			'njsl-grant-slider',
			__( 'NJSL Grants List', 'njsl-2014' )
		);
	}
	
	function content( $args, $instance ) {
		
		$title = __( 'Ongoing Opportunities', 'njsl-2014' );
		if( isset( $instance['title'] ) )
			$title = $instance['title'];
		
		$title = apply_filters( 'widget_title', $title );
		
		$tag = 'grants';
		if( isset( $instance['tag'] ) )
			$tag = $instance['tag'];
		
		$news = get_posts(
			array(
				'tag' => $tag
			)
		);
		
		if( empty( $news ) )
			return;
		
		ob_start();
		
		?>
<section class="homepage-box event-box programs-section">
	<h4><a href=""><?php echo $title ?></a></h4>

	<?php if( ! empty( $news ) ) : ?>
		<?php foreach( $news as $article ) : ?>
		<article>
			<h1>
				<a href="<?php echo get_permalink( $article->ID ) ?>"><?php echo $article->post_title ?></a>
				<small><?php echo date('M d, Y', strtotime( $article->post_date ) ) ?></small>
			</h1>
			<p>
				<small>
					<?php echo substr($article->post_excerpt,0,140) ?>
				</small>
			</p>
		</article>
		<?php endforeach; ?>
		
	<?php else: ?>
		<!-- Sample Data -->
		<article>
			<h1>Digital Literacy Grant Open <small>3 more weeks</small></h1>
			<p><small>
				The State Library is accepting applications for the 2014 Digital...
				<a href="">Read more</a>
			</small></p>
		</article>
	<?php endif; ?>
</section>
		<?php
		return ob_get_clean();
	}
	
	public function form( $instance ) {
		
		if( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Ongoing Opportunities', 'njsl-2014' );
		}
		
		if( isset( $instance['tag'] ) && tag_exists( $instance['tag'] ) ) {
			$tag = $instance['tag'];
		} else {
			$tag = '';
			?>
			<p>
				<?php _e( "You either haven't selected a tag or your tag is invalid.", 'njsl-2014' ) ?>
			</p>
			<?php
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title:') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" value="<?php echo esc_attr( $title ) ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tag') ?>"><?php _e('Tag for news posts on the front page:','') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('tag') ?>" name="<?php echo $this->get_field_name('tag') ?>" value="<?php echo esc_attr( $tag ) ?>">
		</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		
		$instance['title'] = strip_tags( $new_instance['title'] );
		
		if( tag_exists( $new_instance['tag'] ) ) {
			$instance['tag'] = $new_instance['tag'];
		} else if( isset( $old_instance['tag']) ) {
			$instance['tag'] = $old_instance['tag'];
		} else {
			$instance['tag'] = '';
		}
		
		return $instance;
		
	}
	
}

class Events_Widget extends NJSL_Widget {
	
	function __construct() {
		parent::__construct(
			'njsl-events',
			__( 'NJSL Events List', 'njsl-2014' )
		);
	}
	
	function content( $args, $instance ) {
		
		$title = __( 'Upcoming Events', 'njsl-2014' );
		if( isset( $instance['title'] ) )
			$title = $instance['title'];
		
		$title = apply_filters( 'widget_title', $title );
		
		$tag = 'advocacy';
		if( isset( $instance['tag'] ) )
			$tag = $instance['tag'];
		
		$news = tribe_get_events(
			array(
				'eventDisplay'   => 'upcoming',
				'posts_per_page' => 3,
				'tag'            => $tag
			)
		);
		
		ob_start();
		?>
<section class="homepage-box event-box news-section">
	<h4>
		<?php echo $title ?> 
		<span class="pull-right">
			<a class="btn btn-default btn-xs" title="<?php _e('Browse events by month', 'njsl-2014' ) ?>" href="<?php echo home_url('events/month/') ?>">
				<?php _e( 'Calendar', 'njsl-2014' ) ?> <i class="fa fa-calendar"></i>
			</a>
			&nbsp;
		</span>
	</h4>
	
	<?php foreach( $news as $article ) : ?>
	<?php
	
		if( empty( $article->post_excerpt ) )
			$article->post_excerpt = $article->post_content;
		
		// Ensure the excerpt fits within the prescribed space
		if( strlen( strip_tags( $article->post_excerpt ) ) > 210 ) {
			$article->post_excerpt = substr( strip_tags( $article->post_excerpt ), 0, 207 ) . '...';
		}
	
	?>
	<article>
		<h1>
			<a href="<?php echo get_permalink( $article->ID ) ?>"><?php echo $article->post_title ?></a>
			<small>
				<?php echo tribe_get_start_date( $article->ID ) ?>
				&mdash;
				<?php echo tribe_get_end_date( $article->ID ) ?>
			</small>
		</h1>
		<p>
			<small>
				<?php echo $article->post_excerpt ?>
				<a href="<?php echo get_permalink( $article->ID ) ?>"><?php _e( 'Read more', 'njsl-2014' ) ?></a>
				<?php
					$reg_url = get_post_meta( $article->ID, '_EventURL', true );
					if( ! empty( $reg_url ) ) :
				?>
				 | <a rel="external" href="<?php echo esc_url( $reg_url ) ?>"><?php _e( 'Register now', 'njsl-2014' ) ?></a>
				<?php endif; ?>
			</small>
		</p>
	</article>
	<?php endforeach; ?>

	<p style="padding-left: 10px; padding-top: 5px;">
		<a href="<?php echo home_url('/events/') ?>" style="display: block"><?php _e( 'See more events', 'njsl-2014' ) ?></a>
	</p>

</section>
		<?php
		return ob_get_clean();
	}
	
	public function form( $instance ) {
		
		if( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Upcoming Events', 'njsl-2014' );
		}
		
		if( isset( $instance['tag'] ) && tag_exists( $instance['tag'] ) ) {
			$tag = $instance['tag'];
		} else {
			$tag = '';
			?>
			<p>
				<?php _e( "You either haven't selected a tag or your tag is invalid.", 'njsl-2014' ) ?>
			</p>
			<?php
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title:') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" value="<?php echo esc_attr( $title ) ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tag') ?>"><?php _e('Tag for events on the front page:','') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('tag') ?>" name="<?php echo $this->get_field_name('tag') ?>" value="<?php echo esc_attr( $tag ) ?>">
		</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		
		$instance['title'] = strip_tags( $new_instance['title'] );
		
		if( tag_exists( $new_instance['tag'] ) ) {
			$instance['tag'] = $new_instance['tag'];
		} else if( isset( $old_instance['tag']) ) {
			$instance['tag'] = $old_instance['tag'];
		} else {
			$instance['tag'] = '';
		}
		
		return $instance;
		
	}
	
}

class Closing_Widget extends NJSL_Widget {
	
	function __construct() {
		parent::__construct(
			'njsl-closing-notice',
			__( 'NJSL Notices', 'njsl-2014' )
		);
	}
	
	function content( $args, $instance ) {
		
		$tag = 'closing';
		if( isset( $instance['tag'] ) )
			$tag = $instance['tag'];
		
		$news = tribe_get_events(
			array(
				'eventDisplay'   => 'upcoming',
				'posts_per_page' => 1,
				'tag'            => $tag
			)
		);
		
		// Don't display anything if there are no upcoming notices
		if( 0 == count( $news ) )
			return;
		
		ob_start();
		?>
<section class="homepage-box event-box notice-box">
	
	<?php foreach( $news as $article ) : ?>
	<?php
	
		if( empty( $article->post_excerpt ) )
			$article->post_excerpt = $article->post_content;
		
		// Ensure the excerpt fits within the prescribed space
		if( strlen( strip_tags( $article->post_excerpt ) ) > 210 ) {
			$article->post_excerpt = substr( strip_tags( $article->post_excerpt ), 0, 207 ) . '...';
		}
	
	?>
	<article class="alert alert-danger">
		<h1>
			<a href="<?php echo get_permalink( $article->ID ) ?>" class="alert-link"><?php echo $article->post_title ?></a><br>
			<small>
				<?php echo date('M d, Y', strtotime( tribe_get_start_date( $article->ID, false ) ) ) ?>
				&mdash;
				<?php echo date('M d, Y', strtotime( tribe_get_end_date( $article->ID, false ) ) ) ?>
			</small>
		</h1>
		<?php if( ! empty( $article->post_excerpt ) ) : ?>
		<p>
			<small>
				<?php echo $article->post_excerpt ?>
				<a href="<?php echo get_permalink( $article->ID ) ?>" class="alert-link"><?php _e( 'Read more', 'njsl-2014' ) ?></a>
			</small>
		</p>
		<?php endif; ?>
	</article>
	<?php endforeach; ?>

</section>
		<?php
		return ob_get_clean();
	}
	
	public function form( $instance ) {
		
		if( isset( $instance['tag'] ) && tag_exists( $instance['tag'] ) ) {
			$tag = $instance['tag'];
		} else {
			$tag = '';
			?>
			<p>
				<?php _e( "You either haven't selected a tag or your tag is invalid.", 'njsl-2014' ) ?>
			</p>
			<?php
		}
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('tag') ?>"><?php _e( 'Tag for events on the front page:', 'njsl-2014' ) ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('tag') ?>" name="<?php echo $this->get_field_name('tag') ?>" value="<?php echo esc_attr( $tag ) ?>">
		</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		
		if( term_exists( $new_instance['tag'] ) ) {
			$instance['tag'] = $new_instance['tag'];
		} else if( isset( $old_instance['tag']) ) {
			$instance['tag'] = $old_instance['tag'];
		} else {
			$instance['tag'] = '';
		}
		
		return $instance;
		
	}
	
}

class QuickAction_Widget extends NJSL_Widget {
	
	private $item_count = 0;
	
	public function __construct() {
		parent::__construct(
			'njsl-quick-actions',
			__( 'NJSL Quick Action Widget', 'njsl-2014' )
		);
		
		register_nav_menu( 'quick_actions', __( 'Home Page Quick Action Menu', 'njsl-2014' ) );
		add_filter( 'nav_menu_css_class', array( &$this, 'add_menu_css_class' ), 10, 3 );
		add_filter( 'wp_nav_menu_objects', array( &$this, 'count_menu_objects' ), 10, 2 );
		
	}
	
	public function content( $args, $instance ) {
		
		ob_start();
		?>
		<section class="homepage-box action-box text-center">
			<h1 class="sr-only"><?php _e( 'Quick Links', 'njsl-2014' ) ?></h1>
			<div class="row" style="position: relative">
				<?php if( has_nav_menu( 'quick_actions' ) ) : ?>
					<?php wp_nav_menu(
						array(
							'menu'           => 'quick_actions',
							'theme_location' => 'quick_actions',
							'container' => false,
							'items_wrap' => '%3$s',
							'depth'     => 1,
							'walker'    => new BSCol_Walker()
						)
					) ?>
				<?php else: ?>
				<!-- Sample Data -->
				<div class="col-md-6">
					<a href="<?php echo home_url( 'research_library/get_a_library_card' ) ?>" style="display: block">
						<i class="fa fa-fw fa-credit-card"></i>
						Get a library card
					</a>
					<small>Sign up for a library card online</small>
				</div>
				<div class="col-md-6">
					<a href="<?php echo home_url( 'research_library/request_books_and_articles/request-a-book-or-article' ) ?>" style="display: block">
						<i class="fa fa-fw fa-book"></i>
						Request a book or article
					</a>
				</div>
				<div class="col-md-6">
					<a href="<?php echo home_url( 'research_library/ask_a_librarian' ) ?>" style="display: block">
						<i class="fa fa-fw fa-user"></i>
						Ask a librarian
					</a>
					<small>Get help with a reference question</small>
				</div>
				<div class="col-md-6">
					<a href="<?php echo home_url( 'services_for_libraries/resources/directories_of_libraries' ) ?>" style="display: block">
						<i class="fa fa-fw fa-building-o"></i>
						Find a local library
					</a>
					<small>Locate a public library in your area</small>
				</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Space out items based on their number
	 */
	public function add_menu_css_class( $classes, $item, $args ) {
		
		if( empty( $args ) || ! isset( $args->menu ) )
			return $classes;
		
		if( $args->menu != 'quick_actions' )
			return $classes;
		
		if( 0 === $this->item_count % 3 ) {
			$classes[] = 'col-sm-4';
		} else if( 0 === $this->item_count % 2 ) {
			$classes[] = 'col-md-6 col-sm-3';
		} else {
			$classes[] = 'col-sm-12';
		}
		
		return $classes;
		
	}
	
	public function count_menu_objects( $sorted_menu_items, $args ) {
		
		if( empty( $args ) || ! isset( $args->menu ) )
			return $sorted_menu_items;
		
		if( $args->menu != 'quick_actions' )
			return $sorted_menu_items;
		
		$this->item_count = count( $sorted_menu_items );
		return $sorted_menu_items;
	}
	
}

/**
 * Walker class for widgets - displays items in BS3 column divs rather than a list
 */
class BSCol_Walker extends Walker_Nav_Menu {
	
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<div' . $id . $class_names .'>';

		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']   = ! empty( $item->url )        ? $item->url        : '';

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}
		
		// Something is very wrong, like the menu was deleted without removing location reference
		if( ! is_object( $args ) )
			return;
		
		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		/** This filter is documented in wp-includes/post-template.php */
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		
	}
	
	function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= "</div>\n";
	}
	
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= '';
	}
}

class Spotlight_Widget extends NJSL_Widget {
	
	function __construct() {
		parent::__construct(
			'njsl-spotlight',
			__( 'NJSL Spotlight', 'njsl-2014' )
		);
	}
	
	function content( $args, $instance ) {
		
		$news = get_posts(
			array(
				'post_type'      => 'spotlight',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'     => 'spotlight_start_date',
						'value'   => time(),
						'compare' => '<='
					),
					array(
						'key'     => 'spotlight_end_date',
						'value'   => strtotime( '+1 day' ),
						'compare' => '>='
					)
				)
			)
		);
		
		// Don't display anything if there are no spotlight items
		if( 0 == count( $news ) )
			return;
		
		if( empty( $news[0]->post_excerpt ) )
			return;
		
		if( ! get_post_meta( $news[0]->ID, 'spotlight_link_url', true ) )
			return;
		
		ob_start();
		?>
<section class="homepage-box event-box about-section">
	<h4><?php _e( 'Spotlight', 'njsl-2014' ) ?></h4>
	<?php foreach( $news as $article ) : ?>
	<?php
	
		if( empty( $article->post_excerpt ) )
			$article->post_excerpt = $article->post_content;
		
		// Ensure the excerpt fits within the prescribed space
		if( strlen( strip_tags( $article->post_excerpt ) ) > 210 ) {
			$article->post_excerpt = substr( strip_tags( $article->post_excerpt ), 0, 140 ) . '...';
		}
		
		$link = get_post_meta( $article->ID, 'spotlight_link_url', true );
	
	?>
	<article>
		<h1>
			<a href="<?php echo esc_url( $link ) ?>"><?php echo $article->post_title ?></a>
		</h1>
		<p>
			<small>
				<?php echo $article->post_excerpt ?>
				<a href="<?php echo esc_url( $link ) ?>">Read more</a>
			</small>
		</p>
	</article>
	<?php endforeach; ?>

</section>
		<?php
		return ob_get_clean();
	}
	
	public function form( $instance ) {
			// Preview the spotlight item in the dashboard
		?>
		<p>
			<?php _e( 'Current item:', 'njsl-2014' ) ?>
			<?php
			
				$item = get_posts(
					array(
						'post_type'      => 'spotlight',
						'posts_per_page' => 1,
						'meta_query'     => array(
							array(
								'key'     => 'spotlight_start_date',
								'value'   => time(),
								'compare' => '<='
							),
							array(
								'key'     => 'spotlight_end_date',
								'value'   => strtotime( '+1 day' ),
								'compare' => '>='
							)
						)
					)
				);
				
				if( 0 == count( $item ) )
					_e( 'No spotlight item displayed', 'njsl-2014' );
				elseif( empty( $item[0]->post_excerpt ) )
					_e( 'Spotlight item missing required excerpt', 'njsl-2014' );
				elseif( ! get_post_meta( $item[0]->ID, 'spotlight_link_url', true ) )
					_e( 'Spotlight item missing required Link URL', 'njsl-2014' );
				else
					echo $item[0]->post_title;
			?>
		</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		
		if( term_exists( $new_instance['tag'] ) ) {
			$instance['tag'] = $new_instance['tag'];
		} else if( isset( $old_instance['tag']) ) {
			$instance['tag'] = $old_instance['tag'];
		} else {
			$instance['tag'] = '';
		}
		
		return $instance;
		
	}
	
}


/**
 * Add each widget to the site
 */

add_action( 'widgets_init', array( 'Banner_Widget', 'register' ) );
add_action( 'widgets_init', array( 'Search_Widget', 'register' ) );
add_action( 'widgets_init', array( 'ContactUs_Widget', 'register' ) );
add_action( 'widgets_init', array( 'NewsSlider_Widget', 'register' ) );
add_action( 'widgets_init', array( 'Grants_Widget', 'register' ) );
add_action( 'widgets_init', array( 'Events_Widget', 'register' ) );
add_action( 'widgets_init', array( 'QuickAction_Widget', 'register' ) );
add_action( 'widgets_init', array( 'Closing_Widget', 'register' ) );
add_action( 'widgets_init', array( 'Spotlight_Widget', 'register' ) );

?>