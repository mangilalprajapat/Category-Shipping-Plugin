<?php
/**
 * Plugin Name: schooluniformdirect
* Plugin URI: 
* Description: This is the custome map plugin I ever created.
* Version: 1.0
* Author: School
* Author URI: 
**/
add_action('product_cat_add_form_fields', 'wh_taxonomy_add_new_delivery_option', 10, 1);
add_action('product_cat_edit_form_fields', 'wh_taxonomy_edit_delivery_option', 10, 1);
function wh_taxonomy_add_new_delivery_option() 
{
    ?>
    <div class="form-field">
        <label for="cat_shipping"><?php _e('Shipping', 'wh'); ?></label>
        <input type="text" name="cat_shipping" id="cat_shipping">
    </div>
    <div class="form-field">
        <label for="covid_19"><?php _e('Covid-19 (Home Delivery)', 'wh'); ?></label>
        <input type="checkbox" name="covid_19" id="covid_19" value="1">
    </div>
    <?php
}

function wh_taxonomy_edit_delivery_option($term) 
{
    $term_id = $term->term_id;
    $cat_shipping = get_term_meta($term_id, 'cat_shipping', true);
    $covid_19 = get_term_meta($term_id, 'covid_19', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="cat_shipping"><?php _e('Shipping', 'wh'); ?></label></th>
        <td>
            <input type="text" name="cat_shipping" id="cat_shipping" value="<?php echo esc_attr($cat_shipping) ? esc_attr($cat_shipping) : ''; ?>">
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="covid_19"><?php _e('Covid-19 (Home Delivery)', 'wh'); ?></label></th>
        <td>
             <input type="checkbox" name="covid_19" id="covid_19" <?php echo esc_attr($covid_19) ? "checked" : ''; ?>>
        </td>
    </tr>
    <?php
}

add_action('edited_product_cat', 'wh_save_taxonomy_custom_meta', 10, 1);
add_action('create_product_cat', 'wh_save_taxonomy_custom_meta', 10, 1);
function wh_save_taxonomy_custom_meta($term_id) 
{
    $cat_shipping = filter_input(INPUT_POST, 'cat_shipping');
    $covid_19 = filter_input(INPUT_POST, 'covid_19');
    update_term_meta($term_id, 'cat_shipping', $cat_shipping);
    update_term_meta($term_id, 'covid_19', $covid_19);
}

add_filter( 'manage_edit-product_cat_columns', 'wh_customFieldsListTitle' ); //Register Function
add_action( 'manage_product_cat_custom_column', 'wh_customFieldsListDisplay' , 10, 3); //Populating the Columns
function wh_customFieldsListTitle( $columns ) 
{
    $columns['cat_shipping'] = __( 'Shipping', 'woocommerce' );
   
    $columns['covid_19'] = __( 'Home', 'woocommerce' );
    return $columns;
}

function wh_customFieldsListDisplay( $columns, $column, $id ) 
{
    if ( 'cat_shipping' == $column ) 
    {
        $columns = esc_html( get_term_meta($id, 'cat_shipping', true) );
    }
    elseif ( 'covid_19' == $column ) 
    {
        $columns = esc_html( get_term_meta($id, 'covid_19', true) );
         if($columns == 'on')
        {
        	$columns = 1;
        }
    }
    return $columns;
}
add_filter( 'woocommerce_checkout_fields' , 'category_dropdown_display' );

function category_dropdown_display( $fields ) 
{
  $taxonomy     = 'product_cat';
  $orderby      = 'name';

  $args = array(
         'taxonomy'     => $taxonomy
  );

  $all_categories = get_categories( $args );
  $default_option2 = __( 'Please Select Club Category', 'woocommerce' );
  $cat_options = array( '' => $default_option2 );
  foreach ($all_categories as $key => $cat) 
  {
    $cat_options[$cat->term_id] = $cat->name;
  }
  
    $fields['billing']['billing_delivery_option'] = array(
    'label'       => __('Childs School', 'woocommerce'),
    'placeholder' => _x('Club Category', 'placeholder', 'woocommerce'),
    'class' => array('cc_custom_school_categories') ,
    'required'    => true,
    'clear'       => false,
    'type'        => 'select',
    'options'     =>  $cat_options,
    );
     return $fields;
}
add_action( 'wp_footer', 'cc_school_category' );
function cc_school_category() 
{
    ?>
    <script>
    jQuery("#billing_delivery_option").on('change',function()
    {
        var category_id = this.value;
        var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' )?>";
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajaxurl,
            data : {action: "delivery_category_option", cate : category_id},
            beforeSend: function() 
                {
		          	jQuery(".loading").show();
		       	},
            success: function(response) 
            {   
                jQuery(".loading").hide();
                if(response.success == '200')
                {
                    var shipping_rate = response.shipping_rate;
                    jQuery('#exampleModalCenter').modal('show');
                    jQuery(".set_shipping_rate").text(response.shipping);
                    jQuery("#shipping_rate_").val(response.shipping);
                }
                else
                {

                	jQuery('body').trigger('update_checkout');
	            	  setTimeout(function()
	               { 
	                 jQuery(".woocommerce-shipping-totals.shipping").css("display", "table-row");
	               }, 2000);
	                 
                }
            }
        });   
    });

    </script>
    <?php 
}

add_action("wp_ajax_delivery_category_option", "delivery_category_option");
add_action("wp_ajax_nopriv_delivery_category_option", "delivery_category_option");
function delivery_category_option()
{
    $json = array();
    $term_id = $_POST['cate'];
    $checkout_shipping_show = 1;
    setcookie('checkout_shipping_show', $checkout_shipping_show, time() + (86400 * 30), "/");
    
    if($term_id == '')
    {
      setcookie('shipping_rate_custom', '', time() + (86400 * 30), "/");
    }
    $cat_shipping = get_term_meta($term_id, 'cat_shipping',true);
    $covid_19 = get_term_meta($term_id, 'covid_19', true);
    if(!empty($covid_19))
    {
        $home = $covid_19;
    }
    else
    {
        $home = ''; 
    }
    if(!empty($cat_shipping) && !empty($home))
    {
        $json['success'] = 200;
    }
    else
    {
        $json['failure'] = 300; 
		if (isset($_COOKIE['shipping_rate_custom'])) 
		{
			unset($_COOKIE['shipping_rate_custom']); 
			setcookie('shipping_rate_custom', '', -1, '/'); 
	    }
    }
    $json['shipping'] =  $cat_shipping;
    echo json_encode($json);
    exit();
}

add_action("wp_ajax_home_delivery_option", "home_delivery_option");
add_action("wp_ajax_nopriv_home_delivery_option", "home_delivery_option");
function home_delivery_option()
{   
	$shipping_rate = $_POST['ship_ret'];
  setcookie('shipping_rate_custom', $shipping_rate, time() + (86400 * 30), "/");
  exit();
}

add_action( 'wp_footer', 'covid_model_popup' );
function covid_model_popup() 
{
    ?>

    <style type="text/css">
    #exampleModalCenter .modal-dialog 
    {
        -webkit-transform: translate(0,-50%);
        -o-transform: translate(0,-50%);
        transform: translate(0,-50%);
        top: 50%;
        margin: 0 auto;
    }
    /* Absolute Center Spinner */
.loading {
  position: fixed;
  z-index: 1051;
  height: 2em;
  width: 2em;
  overflow: visible;
  margin: auto;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
}

/* Transparent Overlay */
.loading:before {
  content: '';
  display: block;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.3);
}

/* :not(:required) hides these rules from IE9 and below */
.loading:not(:required) {
  /* hide "loading..." text */
  font: 0/0 a;
  color: transparent;
  text-shadow: none;
  background-color: transparent;
  border: 0;
}

.loading:not(:required):after {
  content: '';
  display: block;
  font-size: 10px;
  width: 1em;
  height: 1em;
  margin-top: -0.5em;
  -webkit-animation: spinner 1500ms infinite linear;
  -moz-animation: spinner 1500ms infinite linear;
  -ms-animation: spinner 1500ms infinite linear;
  -o-animation: spinner 1500ms infinite linear;
  animation: spinner 1500ms infinite linear;
  border-radius: 0.5em;
  -webkit-box-shadow: rgba(0, 0, 0, 0.75) 1.5em 0 0 0, rgba(0, 0, 0, 0.75) 1.1em 1.1em 0 0, rgba(0, 0, 0, 0.75) 0 1.5em 0 0, rgba(0, 0, 0, 0.75) -1.1em 1.1em 0 0, rgba(0, 0, 0, 0.5) -1.5em 0 0 0, rgba(0, 0, 0, 0.5) -1.1em -1.1em 0 0, rgba(0, 0, 0, 0.75) 0 -1.5em 0 0, rgba(0, 0, 0, 0.75) 1.1em -1.1em 0 0;
  box-shadow: rgba(0, 0, 0, 0.75) 1.5em 0 0 0, rgba(0, 0, 0, 0.75) 1.1em 1.1em 0 0, rgba(0, 0, 0, 0.75) 0 1.5em 0 0, rgba(0, 0, 0, 0.75) -1.1em 1.1em 0 0, rgba(0, 0, 0, 0.75) -1.5em 0 0 0, rgba(0, 0, 0, 0.75) -1.1em -1.1em 0 0, rgba(0, 0, 0, 0.75) 0 -1.5em 0 0, rgba(0, 0, 0, 0.75) 1.1em -1.1em 0 0;
}

/* Animation */

@-webkit-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@-moz-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@-o-keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@keyframes spinner {
  0% {
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
    </style>
    <div class="loading" style="display:none;">Loading&#8230;</div>
    <link rel="stylesheet" type="text/css" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"> <h4 align="center">(Due to Covid-19) "Home Delivery" service.</h4></div>
                <div class="modal-body">
                   
                    <div id="shipping_method_popup" class="woocommerce-shipping-methods">
                        <input type="radio" name="club_shipping_method" id="shipping_method_free_collection_popup" value="local_pickup_free" class="shipping_method_selection "  checked="checked">
                        <label for="shipping_method_free_collection_popup">Free Collection from School</label>
                        </br>
                        <input type="radio" name="club_shipping_method" id="shipping_method_home_delivery_popup" value="home_pickup_delivery" class="shipping_method_selection ">
                        <label for="shipping_method_home_delivery_popup">
                            Home Delivery: <span class="woocommerce-Price-currencySymbol">£</span><span class="set_shipping_rate"></span>
                        </label>
                        <input type="hidden" name="shipping_rate_" id="shipping_rate_" value="">
                    </div>
                </div>
            </div>
        </div>
    </div>
     <script>
    jQuery(".shipping_method_selection").click(function () 
    {
        var shipping_sel = jQuery('input:radio[name="club_shipping_method"]:checked').val();
        var shipping_rate_sel = jQuery('#shipping_rate_').val();
        jQuery("#shipping_method_free_collection_popup").attr("checked", true); 
        
        if(shipping_sel == 'home_pickup_delivery')
        {
            var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' )?>";
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : ajaxurl,
                data : {action: "home_delivery_option", ship_ret : shipping_rate_sel},
                beforeSend: function() 
                {
		          	jQuery(".loading").show();
		       	},
                success: function(response) 
                { 
                  jQuery(".loading").hide();
                  jQuery('#exampleModalCenter').modal('hide');
                  jQuery('body').trigger('update_checkout');
                  jQuery("#shipping_method_0_flat_rate5").attr("checked", true); 
                  setTimeout(function()
                   { 
                     jQuery("#shipping_method_0_local_pickup6").attr('disabled',true);
                     jQuery(".woocommerce-shipping-totals.shipping").css("display", "table-row");
                   }, 2000);
                }
            });  
        }
        else
        {   
          alert("We Don't Support free Collection, Please Select Home Delivery Option");
        }
    });
    </script>
    <?php 
}

add_action( 'wp_footer', 'cc_school_category_css' );
function cc_school_category_css() 
{
    ?>
    <style>
    	select#billing_delivery_option 
        {
            position: relative;
            margin: 0 0 15px;
            width: 54rem !important;
            padding: 12px 16px;
            font-weight: inherit;
            line-height: calc(50px - (12px * 2) - 2px);
            color: #333;
            background-color: transparent;
            border: 1px solid #e5e5e5;
            border-radius: 0;
        }
		.my_split_checkbox.cc_custome_school_heading h4 {
			margin: 18px 0;
			font-weight: 500;
			color: #000;
			font-size: 25px;
			line-height: 1.4em;
			text-transform: uppercase;
			font-family: "Barlow Condensed",sans-serif;
			border: 2px solid red;
		}
    </style>
		<?php if(isset($_COOKIE['checkout_shipping_show']) == 1) { ?>
			<style>
				tr.woocommerce-shipping-totals.shipping {
				display: table-row !important;
				}
			</style>
		<?php }
		else{ ?>

			<style>
				tr.woocommerce-shipping-totals.shipping {
				display: none;
				}
			</style> 
		 <?php } ?>
		

    <?php 
}

add_action( 'woocommerce_before_checkout_form', 'cc_deliverey_insturction' );
function cc_deliverey_insturction() 
{
    echo '<div class="my_split_checkbox cc_custome_school_heading" align="center"; text>
          <h4>' . __('Please input your childs School or Club to be given the Delivery Option', 'woocommerce') . '</h4>';
    echo '</div>';
}

add_filter('woocommerce_package_rates', 'shipping_cost_based_on_api', 12, 2);
function shipping_cost_based_on_api( $rates, $package )
{
	foreach ( $rates as $rate_key => $rate )
	{
		if( 'flat_rate' === $rate->method_id )
		{	
			if(isset($_COOKIE['shipping_rate_custom']))
			{	
				$new_cost = $_COOKIE['shipping_rate_custom'];
			}
			else
			{ 
				$new_cost = $rates[$rate_key]->cost;
			}
			$rates[$rate_key]->cost = $new_cost;
		}

	}
	return $rates;
}
add_action( 'woocommerce_checkout_update_order_review', 'refresh_shipping_methods', 10, 1 );
function refresh_shipping_methods( $post_data )
{
    $bool = true;
    if ( WC()->session->__isset('shipping_cost' ) ) $bool = false;
    foreach ( WC()->cart->get_shipping_packages() as $package_key => $package )
    {
        WC()->session->set( 'shipping_for_package_' . $package_key, $bool );
    }
    WC()->cart->calculate_shipping();
}


?>