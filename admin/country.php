<?php

class swlp_country_list extends WP_List_Table
{ 
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'country',
            'plural' => 'countries',
        ));
    }


    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }


    function column_country($item)
    {

        $actions = array(
            'edit' => sprintf('<a href="?page=swlp_country_form&id=%s">%s</a>', $item['id'], __('Edit', 'swlp')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'swlp')),
        );

        return sprintf('%s %s',
            $item['country'],
            $this->row_actions($actions)
        );
    }


    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', 
            'country' => __('Country Name', 'swlp'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'country' => array('country', true),
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'swlp_country'; 

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'swlp_country'; 

        $per_page = 10;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        // $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");


        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        $OFFSET = $paged * $per_page;
        $search_term = isset($_REQUEST['s']) ? trim($_REQUEST['s']) : "";
        if(!empty($search_term)){
            $this->items = $wpdb->get_results("SELECT * FROM $table_name WHERE country LIKE '%".$search_term."%' ORDER BY ". $orderby ." ". $order ." LIMIT ".$per_page." OFFSET ".$OFFSET, ARRAY_A);
            $total_items = $wpdb->get_results("SELECT * FROM $table_name WHERE country LIKE '%".$search_term."%' ORDER BY ". $orderby ." ". $order);
            $total_items = count($total_items);
        }else{
            $this->items = $wpdb->get_results("SELECT * FROM $table_name WHERE country LIKE '%".$search_term."%' ORDER BY ". $orderby ." ". $order ." LIMIT ".$per_page." OFFSET ".$OFFSET, ARRAY_A);
            $total_items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY $orderby $order");
            $total_items = count($total_items);
        }


        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
}

function swlp_admin_menus(){
    add_menu_page(__('Locations', 'swlp'), __('Locations', 'swlp'), 'activate_plugins', 'locations', 'swlp_country_page_handler');

    add_submenu_page('locations', __('Country', 'swlp'), __('Country', 'swlp'), 'activate_plugins', 'swlp_country', 'swlp_country_page_handler');
    add_submenu_page('locations', __('Add country', 'swlp'), __('Add country', 'swlp'), 'manage_options', 'swlp_country_form', 'swlp_country_form_page_handler');

    add_submenu_page('locations', __('State', 'swlp'), __('State', 'swlp'), 'activate_plugins', 'swlp_state', 'swlp_state_page_handler');
    add_submenu_page('locations', __('Add state', 'swlp'), __('Add state', 'swlp'), 'manage_options', 'swlp_state_form', 'swlp_state_form_page_handler');

    add_submenu_page('locations', __('City', 'swlp'), __('City', 'swlp'), 'activate_plugins', 'swlp_city', 'swlp_city_page_handler');
    add_submenu_page('locations', __('Add city', 'swlp'), __('Add city', 'swlp'), 'manage_options', 'swlp_city_form', 'swlp_city_form_page_handler');

    remove_submenu_page('locations', 'locations');
}

add_action('admin_menu', 'swlp_admin_menus');


function swlp_country_page_handler()
{
    global $wpdb;

    $table = new swlp_country_list();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'swlp'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Country', 'swlp')?> <a class="add-new-h2"
           href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_country_form');?>"><?php _e('Add new country', 'swlp')?></a>
       </h2>
       <?php echo $message; ?>

       <form id="contacts-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php 
        $table->search_box("Search Post", "search_post_id");
        $table->display();
        ?>
    </form>

</div>
<?php
}


function swlp_country_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'swlp_country'; 

    $message = '';
    $notice = '';


    $default = array(
        'id' => 0,
        'name' => '',
    );


    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {

        $item = shortcode_atts($default, $_REQUEST);     

        $item_valid = swlp_validate_country($item);
        if ($item_valid === true) {
            if ($item['id'] == 0) {
                $result = $wpdb->insert($table_name, array('country'=>$item['name']));
                $item['id'] = $wpdb->insert_id;
                $message = __('Item was successfully saved', 'swlp');
            } else {
                $result = $wpdb->update($table_name, array('country'=>$item['name']), array('id' => $item['id']));
                $message = __('Item was successfully saved', 'swlp');
            }
        } else {

            $notice = $item_valid;
        }
    }

    $item = $default;
    if (isset($_GET['id'])) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
        if (!$item) {
            $item = $default;
            $notice = __('Item not found', 'swlp');
        }
    }

    
    add_meta_box('country_form_meta_box', __('Country data', 'swlp'), 'swlp_country_form_meta_box_handler', 'country', 'normal', 'default');

    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Country', 'swlp')?> <a class="add-new-h2"
            href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_country');?>"><?php _e('back to list', 'swlp')?></a>
        </h2>

        <?php if (!empty($notice)): ?>
            <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
            <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>

            <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <?php do_meta_boxes('country', 'normal', $item); ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function swlp_country_form_meta_box_handler($item)
{
    ?>
    <tbody>
        <div class="formdata">
            <form>
                <p>			
                  <label for="name"><?php _e('Country Name:', 'swlp')?></label>
                  <br>	
                  <input id="name" name="name" type="text" style="width: 100%" value="<?php echo esc_attr($item['country'])?>"
                  required>              
                  <br>
                  <input type="submit" value="<?php _e('Save', 'swlp')?>" id="submit" class="button-primary" name="submit" style='margin-top: 15px;'>
              </p>
          </form>
      </div>
  </tbody>
  <?php
}


function swlp_validate_country($item){
    $messages = array();

    if (empty($item['name'])) $messages[] = __('Name is required', 'swlp');    

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}