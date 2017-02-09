<?php
/**
* Plugin Name: User Display Excel Products
* Plugin URI: http://hammed-grayaa.tn
* Description: Display a list of products for 'authorised' type of users from an excel sheet
* Author: Grayaa Hammed
* Version: 1.0
* Author URI: http://hammed-grayaa.tn
*/

/**
 * Add new fields above 'Update' button.In User Profile
 *
 * @param WP_User $user User object.
 */



/**
 * Register Admin style sheet.
 */
add_action('admin_print_styles', 'GH_add_admin_styles');
function GH_add_admin_styles() 
{
    wp_enqueue_style( 'user_products_table_admin_style', plugins_url( '/css/admin-style.css', __FILE__ ) );
}


/**
 * Register Front style sheet.
 */
add_action( 'wp_enqueue_scripts', 'GH_add_front_styles' );
function GH_add_front_styles() {
    wp_enqueue_style( 'user_products_table_front_style', plugins_url( '/css/style.css', __FILE__ ) );
}

function tm_additional_profile_fields( $user ) {

    if ( in_array( 'authorised', (array) $user->roles ) ) {
    
  ?>
      <h3>User Products</h3>

      <?php echo GH_display_user_products_table($user);
            echo '<input type="hidden" name="user_products_ids" id="user_products_ids" value="'.esc_attr( get_the_author_meta( 'user_products_ids', $user->ID ) ).'" class="regular-text" />'; 
      ?>
      <script type="text/javascript">

          jQuery('.product_id_checkbox').change(function () {
              var user_products_ids = "";
              
              jQuery.each( jQuery('table#user_products_table input[type="checkbox"]'), function( i, val ) {
                
                if ( jQuery(this).attr('checked') ) {
                  user_products_ids += jQuery(this).val() + ",";
                }
                
              });

              user_products_ids = user_products_ids.substring(0,user_products_ids.length - 1)

              jQuery('input#user_products_ids').val( user_products_ids );
          });

      </script>
  <?php
    } 
}

//add_action( 'show_user_profile', 'tm_additional_profile_fields' );
add_action( 'edit_user_profile', 'tm_additional_profile_fields' );



function GH_display_user_products_table($user){
  //  Include PHPExcel_IOFactory
  include plugin_dir_path( __FILE__ ) .'excelscript/Classes/PHPExcel/IOFactory.php';

  $inputFileName = ABSPATH .'Price Update File/Price Update File.xlsx';
  $results = excel_to_array($inputFileName);
  $table='<table id="user_products_table">';
  $table.='<tr><th>ISIN</th><th>Name</th><th>Issuer</th><th>Code</th><th>Price1</th><th>Price2</th><th>Enable</th></tr>';

  $products_list = esc_attr( get_the_author_meta( 'user_products_ids', $user->ID ) );

  $products_array = array();
  if($products_list){
    $products_array = explode(",",$products_list);
  }
  
  //var_dump($products_array);

  foreach ($results as $row) {

      if ($row["ISIN"] !="") {

         $table.='<tr>';
          foreach ($row as $key => $value) {
              $table .= '<td>'.$value.'</td>';
          }

          $is_checked = "";
          if( in_array($row["ISIN"], $products_array) ){
            $is_checked = "checked";
          } 

          $table.='<td><input '.$is_checked.' class="product_id_checkbox" type="checkbox" id="'.$row["ISIN"].'" value="'.$row["ISIN"].'"></td>';
          
          $table.='</tr>';
      }      
  }

  $table .= "</table>";

  return $table;

}

// Needs PHPExcel
// https://phpexcel.codeplex.com/
function excel_to_array($inputFileName,$row_callback=null){
    if (!class_exists('PHPExcel')) return false;
    try {
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
    } catch(Exception $e) {
        return ('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
    }
    $sheet = $objPHPExcel->getSheet(0); 
    $highestRow = $sheet->getHighestRow(); 
    $highestColumn = $sheet->getHighestColumn();
    $keys = array();
    $results = array();
    if(is_callable($row_callback)){
        for ($row = 1; $row <= $highestRow; $row++){ 
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,null,true,true);
            if ($row === 1){
                $keys = $rowData[0];
            } else {
                $record = array();
                foreach($rowData[0] as $pos=>$value) $record[$keys[$pos]] = $value; 
                $row_callback($record);           
            }
        } 
    } else {            
        for ($row = 1; $row <= $highestRow; $row++){ 
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,null,true,true);
            if ($row === 1){
                $keys = $rowData[0];
            } else {
                $record = array();
                foreach($rowData[0] as $pos=>$value) $record[$keys[$pos]] = $value; 
                $results[] = $record;           
            }
        } 
        return $results;
    }
}


add_action( 'personal_options_update', 'GH_save_extra_user_fields' );
add_action( 'edit_user_profile_update', 'GH_save_extra_user_fields' );

function GH_save_extra_user_fields( $user_id ) {

  if ( !current_user_can( 'edit_user', $user_id ) )
    return false;

  /* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
  update_usermeta( $user_id, 'user_products_ids', $_POST['user_products_ids'] );
}


function GH_display_authorised_products($user){
	//  Include PHPExcel_IOFactory
  include plugin_dir_path( __FILE__ ) .'excelscript/Classes/PHPExcel/IOFactory.php';

  $inputFileName = ABSPATH .'Price Update File/Price Update File.xlsx';
  $results = excel_to_array($inputFileName);
  $table='<table id="user_products_table">';
  $table.='<tr><th>ISIN</th><th>Name</th><th>Issuer</th><th>Code</th><th>Price1</th><th>Price2</th></tr>';

  $products_list = esc_attr( get_the_author_meta( 'user_products_ids', $user->ID ) );

  $products_array = array();
  if($products_list){
    $products_array = explode(",",$products_list);
  }else{
  	return "<p>No products To show !</p>";
  }
  
  //var_dump($products_array);

  foreach ($results as $row) {

      if ($row["ISIN"] !="" && in_array($row["ISIN"], $products_array) ){
         	$table.='<tr>';
			foreach ($row as $key => $value) {

				
				$table .= '<td>'.$value.'</td>';
				               
			}

			$table.='</tr>';      	
      }      
  }

  $table .= "</table>";

  return $table;
}