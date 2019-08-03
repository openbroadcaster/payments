<?php

class Payments extends OBFController {
  public function __construct () {
    parent::__construct();
    $this->model = $this->load->model('Payments');
  }

  public function ledger_overview () {
    if (isset($this->data['user_id'])) {
      $manager = $this->user->check_permission('payments_module');
      if (!$manager) {
        return [false, "User is not allowed to view other ledgers."];
      }
    }

    return $this->model('ledger_overview', $this->data);
  }

}
