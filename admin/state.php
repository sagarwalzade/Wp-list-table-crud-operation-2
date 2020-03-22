<?php

class swlp_state_list extends WP_List_Table
{ 
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'state',
            'plural' => 'states',
        ));
    }


    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }


    function column_state($item)
    {
        $actions = array(
            'edit' => sprintf('<a href="?page=swlp_state_form&id=%s">%s</a>', $item['id'], __('Edit', 'swlp')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'swlp')),
        );

        return sprintf('%s %s',
            $item['state'],
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
            'state' => __('State Name', 'swlp'),
            'country' => __('Country Name', 'swlp'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
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
        $table_name = $wpdb->prefix . 'swlp_state'; 

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
        $table_name = $wpdb->prefix . 'swlp_state'; 
        $table_name_country = $wpdb->prefix . 'swlp_country'; 
        $table_name_city = $wpdb->prefix . 'swlp_city';

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
            $this->items = $wpdb->get_results("SELECT s.*, c.country 
                FROM $table_name as s 
                LEFT OUTER JOIN $table_name_country as c 
                ON s.country_id=c.id 
                WHERE s.state LIKE '%".$search_term."%' ORDER BY ".$orderby." ". $order ." LIMIT ".$per_page." OFFSET ".$OFFSET, ARRAY_A);
            $total_items = $wpdb->get_results("SELECT s.*, c.country 
                FROM $table_name as s 
                LEFT OUTER JOIN $table_name_country as c 
                ON s.country_id=c.id 
                WHERE s.state LIKE '%".$search_term."%' ORDER BY ".$orderby." ". $order, ARRAY_A);
            $total_items = count($total_items);
        }else{
            $this->items = $wpdb->get_results($wpdb->prepare("SELECT s.*, c.country FROM $table_name as s LEFT OUTER JOIN $table_name_country as c ON s.country_id=c.id ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $OFFSET), ARRAY_A);
            $total_items = $wpdb->get_results("SELECT s.*, c.country FROM $table_name as s LEFT OUTER JOIN $table_name_country as c ON s.country_id=c.id ORDER BY $orderby $order");
            $total_items = count($total_items);
        }
        
        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
}


function swlp_state_page_handler()
{
    global $wpdb;

    $table = new swlp_state_list();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'swlp'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('State', 'swlp')?> <a class="add-new-h2"
           href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_state_form');?>"><?php _e('Add new state', 'swlp')?></a>
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


function swlp_state_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'swlp_state'; 

    $message = '';
    $notice = '';


    $default = array(
        'id' => 0,
        'country_id' => "",
        'name' => '',
    );


    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {

        $item = shortcode_atts($default, $_REQUEST);     

        $item_valid = swlp_validate_state($item);
        if ($item_valid === true) {
            if ($item['id'] == 0) {
                $result = $wpdb->insert($table_name, array('state'=>$item['name'], 'country_id'=>$item['country_id']));
                $item['id'] = $wpdb->insert_id;
                $message = __('Item was successfully saved', 'swlp');
            } else {
                $result = $wpdb->update($table_name, array('state'=>$item['name'], 'country_id'=>$item['country_id']), array('id' => $item['id']));
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

    add_meta_box('state_form_meta_box', __('State data', 'swlp'), 'swlp_state_form_meta_box_handler', 'state', 'normal', 'default');

    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('State', 'swlp')?> <a class="add-new-h2"
            href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_state');?>"><?php _e('back to list', 'swlp')?></a>
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
                        <?php do_meta_boxes('state', 'normal', $item); ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function swlp_state_form_meta_box_handler($item)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'swlp_country'; 
    $countries = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    ?>
    <tbody>
        <div class="formdata">
            <form>
                <p>         
                  <label for="name"><?php _e('Select Country:', 'swlp')?></label>
                  <br>
                  <select style="width: 100%" id="country_id" name="country_id">
                    <option value="">Select country</option>
                    <?php
                    if(!empty($countries)){
                        foreach ($countries as $key => $value) {
                            ?>
                            <option <?php echo ($item['country_id'] == $value['id'])?"selected='selected'":''; ?> value="<?php echo ucfirst($value['id']); ?>"><?php echo ucfirst($value['country']); ?></option>
                            <?php
                        }
                    }
                    ?>
                </select>            
                <br>
                <br>
                <label for="name"><?php _e('State Name:', 'swlp')?></label>
                <br>  
                <input id="name" name="name" type="text" style="width: 100%" value="<?php echo esc_attr($item['state'])?>"
                required>              
                <br>
                <input type="submit" value="<?php _e('Save', 'swlp')?>" id="submit" class="button-primary" name="submit" style='margin-top: 15px;'>
            </p>
        </form>
    </div>
</tbody>
<?php
}


function swlp_validate_state($item){
    $messages = array();

    if (empty($item['name'])) $messages[] = __('Name is required', 'swlp');    
    if (empty($item['country_id'])) $messages[] = __('Country is required', 'swlp');    

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}