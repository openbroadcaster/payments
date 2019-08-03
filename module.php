<?php

class PaymentsModule extends OBFModule {
  public $name = 'Payments';
  public $description = 'Add eCommerce functionality, providing monetary incentive to users to complete media creation and management tasks. Integrates with Task Tracker module.';

  public function callbacks () {

  }

  public function install () {

    // Add module permissions to database.
    $this->db->insert('users_permissions', array(
      'category'    => 'administration',
      'description' => 'payments module',
      'name'        => 'payments_module'
    ));

    // Add transactions table.
    $this->db->query('CREATE TABLE IF NOT EXISTS `module_payments_transactions` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `user_id` int(11) unsigned NOT NULL,
      `created` int(11) unsigned NOT NULL,
      `amount` decimal(13, 2) NOT NULL,
      `comment` text,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1; ');

    return true;
  }

  public function uninstall () {

    // Purge module permissions from database.
    $this->db->where('name', 'payments_module');
    $permission = $this->db->get_one('users_permissions');

    $this->db->where('permission_id', $permission['id']);
    $this->db->delete('users_permissions_to_groups');

    $this->db->where('id', $permission['id']);
    $this->db->delete('users_permissions');

    return true;
  }
}
