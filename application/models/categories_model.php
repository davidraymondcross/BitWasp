<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Categories_model extends CI_Model {

	public function __construct() { 
		parent::__construct();
		
	}
	
	/**
	 * Add
	 * 
	 * Add a category to the table.
	 *	$category = array(	'name' => '...',
	 *						'hash' => '...'),
	 *						'parent_id' => '...');
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */			
	public function add($category) {
		return ($this->db->insert('categories', $category) == TRUE) ? TRUE : FALSE;
	}
	
	/**
	 * Rename
	 * 
	 * Rename $category_id to $new_name
	 *
	 * @access	public
	 * @param	int
	 * @param	string
	 * @return	bool
	 */				
	public function rename($category_id, $new_name) {
		$this->db->where('id', $category_id);
		return ($this->db->update('categories', array('name' => $new_name)) == TRUE) ? TRUE : FALSE;
	}
	
	/**
	 * Delete
	 * 
	 * Delete a category as specified by the ID.
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */				
	public function delete($category_id) {
		$this->db->where('id', $category_id);
		return ($this->db->delete('categories') == TRUE) ? TRUE : FALSE;
	}
	
	/**
	 * Get
	 * 
	 * Loads a category based on $cat['id'] or ['hash']
	 *
	 * @access	public
	 * @param	array
	 * @return	array / FALSE
	 */				
	public function get(array $cat) {
		$this->db->select('id, name, hash, parent_id');

		if (isset($cat['hash'])) {
			$this->db->select('id, name, hash, parent_id');			// Select statement repeated to avoid annoying errors.
			$query = $this->db->get_where('categories', array('hash' => $cat['hash']));
		} elseif (isset($cat['id'])) {
			$this->db->select('id, name, hash, parent_id');
			$query = $this->db->get_where('categories', array('id' => $cat['id']));
		} else {
			return FALSE;
		}
		
		if($query->num_rows() > 0){
			$row = $query->row_array();
			$this->db->where('category', $row['id']);
			$query = $this->db->get('items');
			$row['count_items'] = $query->num_rows();
			return $row;
		}
		return FALSE;
	}
	
	/**
	 * List All
	 * 
	 * List all categories in a general list.
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */					
	public function list_all() {
		$this->db->select('id, hash, name, parent_id');
		$this->db->order_by('name', 'asc');
		$query = $this->db->get('categories');
		return ($query->num_rows() > 0) ? $query->result_array() : FALSE;
	}
	
	/**
	 * Get Children
	 * 
	 * Load the direct children of the specified parent ID.
	 *
	 * @access	public
	 * @param	int
	 * @return	bool
	 */				
	public function get_children($category_id) {
		$this->db->where('parent_id', $category_id);
		$query = $this->db->get('categories');
		$result = $query->result_array();
		$result['count'] = $query->num_rows();
		return $result;
	}
	
	/**
	 * 
	 * Update Items Category
	 * 
	 * Move items from one category ID to another.
	 *
	 * @access	public
	 * @param	int
	 * @param	int
	 * @return	bool
	 */				
	public function update_items_category($current_id, $new_id) {
		$this->db->where('category', $current_id);
		return ($this->db->update('items', array('category' => $new_id)) == TRUE) ? TRUE : FALSE;
	}
	
	/**
	 * Update Parent Category
	 * 
	 * Move categorys with the parent category of $current_id to category $new_id.
	 *
	 * @access	public
	 * @param	int
	 * @param	int
	 * @return	bool
	 */				
	public function update_parent_category($current_id, $new_id) {
		$this->db->where('parent_id', $current_id);
		return ($this->db->update('categories', array('parent_id' => $new_id)) == TRUE) ? TRUE : FALSE;
	}

	/**
	 * Menu
	 * 
	 * Prepare categories in a multidimensional array.
	 * 
	 * @access	public
	 * @return	array
	 */				
	public function menu(){
		
		$this->db->select('id, description, name, hash, parent_id');
		//Load all categories and sort by parent category
		$this->db->order_by("parent_id asc, name asc");
		$query = $this->db->get('categories');
		$menu = array();
	
		if($query->num_rows() == 0) 
			return array();
			
		// Add all categories to $menu[] array.
		foreach($query->result() as $result){
      		$this->db->where('category',"{$result->id}");
      		$this->db->where('hidden !=', '1');
			$products = $this->db->get('items');
			
			$menu[$result->id] = array(	'id' => $result->id,
										'name' => $result->name,
										'description' => $result->description,
										'hash' => $result->hash,
										'count' => $products->num_rows(),
										'parent_id' => $result->parent_id
									);
		}
		
		// Store all child categories as an array $menu[parentID]['children']
		foreach($menu as $ID => &$menuItem){
			if($menuItem['parent_id'] !== '0')								
				$menu[$menuItem['parent_id']]['children'][$ID] = &$menuItem;
		}

		// Remove child categories from the first level of the $menu[] array.
		foreach(array_keys($menu) as $ID){
			if($menu[$ID]['parent_id'] != "0")
				unset($menu[$ID]);
		}
		// Return constructed menu.
		return $menu;
	}
};

/* End of file Categories_model.php */
