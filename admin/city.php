<?php

class swlp_city_list extends WP_List_Table
{ 
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'city',
            'plural' => 'cities',
        ));
    }


    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }


    function column_city($item)
    {

        $actions = array(
            'edit' => sprintf('<a href="?page=swlp_city_form&id=%s">%s</a>', $item['id'], __('Edit', 'swlp')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'swlp')),
        );

        return sprintf('%s %s',
            $item['city'],
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
            'city' => __('City Name', 'swlp'),
            'state' => __('State Name', 'swlp'),
            'country' => __('Country Name', 'swlp'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'city' => array('city', true),
            'state' => array('state', true),
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
        $table_name = $wpdb->prefix . 'swlp_city'; 

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
        $table_name = $wpdb->prefix . 'swlp_city'; 
        $table_name_country = $wpdb->prefix . 'swlp_country'; 
        $table_name_state = $wpdb->prefix . 'swlp_state';

        $per_page = 10; 

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        $OFFSET = $paged * $per_page;
        $search_term = isset($_REQUEST['s']) ? trim($_REQUEST['s']) : "";
        if(!empty($search_term)){
            $this->items = $wpdb->get_results("SELECT ci.*, co.country, s.state 
                FROM $table_name as ci
                LEFT OUTER JOIN $table_name_state as s ON ci.state_id=s.id
                LEFT OUTER JOIN $table_name_country as co ON s.country_id=co.id
                WHERE ci.city LIKE '%".$search_term."%'
                ORDER BY ".$orderby." ".$order." LIMIT ".$per_page." OFFSET ".$OFFSET, ARRAY_A);
            $total_items = $wpdb->get_results("SELECT ci.*, co.country, s.state 
                FROM $table_name as ci
                LEFT OUTER JOIN $table_name_state as s ON ci.state_id=s.id
                LEFT OUTER JOIN $table_name_country as co ON s.country_id=co.id
                WHERE ci.city LIKE '%".$search_term."%'
                ORDER BY ".$orderby." ".$order, ARRAY_A);
            $total_items = count($total_items);
        }else{
            $this->items = $wpdb->get_results("SELECT ci.*, co.country, s.state 
                FROM $table_name as ci
                LEFT OUTER JOIN $table_name_state as s ON ci.state_id=s.id
                LEFT OUTER JOIN $table_name_country as co ON s.country_id=co.id
                ORDER BY ".$orderby." ".$order." LIMIT ".$per_page." OFFSET ".$OFFSET, ARRAY_A);
            $total_items = $wpdb->get_results("SELECT ci.*, co.country, s.state 
                FROM $table_name as ci
                LEFT OUTER JOIN $table_name_state as s ON ci.state_id=s.id
                LEFT OUTER JOIN $table_name_country as co ON s.country_id=co.id
                ORDER BY ".$orderby." ".$order, ARRAY_A);
            $total_items = count($total_items);
        }

        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
}


function swlp_city_page_handler()
{
    global $wpdb;

    $table = new swlp_city_list();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'swlp'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('City', 'swlp')?> <a class="add-new-h2"
           href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_city_form');?>"><?php _e('Add new city', 'swlp')?></a>
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


function swlp_city_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'swlp_city';

    $message = '';
    $notice = '';


    $default = array(
        'id' => 0,
        'state_id' => "",
        'name' => '',
    );


    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {

        $item = shortcode_atts($default, $_REQUEST);     

        $item_valid = swlp_validate_city($item);
        if ($item_valid === true) {
            if ($item['id'] == 0) {
                $result = $wpdb->insert($table_name, array('city'=>$item['name'], 'state_id'=>$item['state_id']));
                $item['id'] = $wpdb->insert_id;
                $message = __('Item was successfully saved', 'swlp');
            } else {
                $result = $wpdb->update($table_name, array('city'=>$item['name'], 'state_id'=>$item['state_id']), array('id' => $item['id']));
                $message = __('Item was successfully updated', 'swlp');
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

    add_meta_box('city_form_meta_box', __('City data', 'swlp'), 'swlp_city_form_meta_box_handler', 'city', 'normal', 'default');

    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('City', 'swlp')?> <a class="add-new-h2"
            href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_city');?>"><?php _e('back to list', 'swlp')?></a>
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
                        <?php do_meta_boxes('city', 'normal', $item); ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function swlp_city_form_meta_box_handler($item)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'swlp_country'; 
    $countries = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    global $wpdb;
    $table_name2 = $wpdb->prefix . 'swlp_state';

    if(isset($_GET['id'])){
        $selected_state = $wpdb->get_row("SELECT * FROM $table_name2 WHERE id=".$item['state_id'], ARRAY_A);
        $selected_country = $wpdb->get_row("SELECT * FROM $table_name WHERE id=" . $selected_state['country_id'], ARRAY_A);

        $states = $wpdb->get_results("SELECT * FROM $table_name2 where country_id=".$selected_country['id'], ARRAY_A);
    }
    ?>
    <tbody>
        <div class="formdata">
            <form>
                <p>         
                  <label for="name"><?php _e('Select Country:', 'swlp')?></label>
                  <br>
                  <select style="width: 100%" id="c_country_id" name="country_id">
                    <option value="">Select country</option>
                    <?php
                    if(!empty($countries)){
                        foreach ($countries as $key => $value) {
                            ?>
                            <option <?php echo ($value['id'] == $selected_country['id'])?"selected='selected'":''; ?> value="<?php echo ucfirst($value['id']); ?>"><?php echo ucfirst($value['country']); ?></option>
                            <?php
                        }
                    }
                    ?>
                </select>            
                <br>
                <br>
                <label for="name"><?php _e('Select State:', 'swlp')?></label>
                <br>
                <select style="width: 100%" id="c_state_id" name="state_id">
                    <option value="">Select state</option>
                    <?php
                    if(!empty($states) && isset($_GET['id'])){
                        foreach ($states as $key => $value) {
                            ?>
                            <option <?php echo ($value['id'] == $selected_state['id'])?"selected='selected'":''; ?> value="<?php echo ucfirst($value['id']); ?>"><?php echo ucfirst($value['state']); ?></option>
                            <?php
                        }
                    }
                    ?>
                </select>            
                <br>
                <br>
                <label for="name"><?php _e('City Name:', 'swlp')?></label>
                <br>  
                <input id="name" name="name" type="text" style="width: 100%" value="<?php echo esc_attr($item['city'])?>"
                required>              
                <br>
                <input type="submit" value="<?php _e('Save', 'swlp')?>" id="submit" class="button-primary" name="submit" style='margin-top: 15px;'>
            </p>
        </form>
    </div>
</tbody>
<?php
}


function swlp_validate_city($item){
    $messages = array();

    if (empty($item['name'])) $messages[] = __('Name is required', 'swlp');    
    if (empty($item['state_id'])) $messages[] = __('State is required', 'swlp');    

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}


add_action("wp_ajax_get_states", "swlp_get_states");
add_action("wp_ajax_nopriv_get_states", "swlp_get_states");

function swlp_get_states(){
    if(!empty($_POST['country_id'])){
        global $wpdb;
        $table_name2 = $wpdb->prefix . 'swlp_state';
        $states = $wpdb->get_results("SELECT * FROM $table_name2 where country_id=".$_POST['country_id'], ARRAY_A);
        $options = "<option>Select state</option>";
        foreach ($states as $key => $value) {
            $options .= "<option value=".$value['id'].">".ucfirst($value['state'])."</option>";
        }
        echo $options;
    }else{
        echo "";
    }
    die;
}