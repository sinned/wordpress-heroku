<?php

// ******************************** GROUP CLASS ********************************

class wps_group {

	public function __construct($id='', $avatar_size=36) {
		global $wpdb;
		$id != '' ? $id = $id : $id = $current_user->ID;
		$this->id = $id;													// Set the ID of this member
		$user_info = get_userdata($id);

		if ($id) {

			$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_groups WHERE gid = %d";
			$group = $wpdb->get_row($wpdb->prepare($sql, $id));

			$this->name = stripslashes($group->name);							// Group name
			$this->description = stripslashes($group->description);				// Group description
			$this->last_activity = $group->last_activity;						// Last activity
			$this->private = $group->private;									// Is it private?
			$this->created = $group->created;									// When created
			$this->avatar = '';													// Avatar (use get_avatar)
	
		}
		
	}
	
	
	/* Following methods provide get/set functionality ______________________________________ */
	
	// Group ID
    function get_id() {
		return $this->id;
    }	
	
	// Group Name
    function get_name() {
		return $this->name;
    }	

	// Group Name
    function get_avatar($size=64) {
		return __wps__get_group_avatar($this->id, $size);
    }	



   
	/* Following methods check for various conditions and return boolean value ______________________________________ */
	

}

/* Single functions to reduce duplication above ____________________________________________________________________________ */



?>
