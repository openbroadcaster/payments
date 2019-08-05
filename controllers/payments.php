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

  public function transaction_add () {
    if (!$this->user->check_permission('payments_module')) {
      return [false, 'User is not allowed to add transactions.'];
    }

    $validate = $this->model('transaction_validate', $this->data, 'add');
    if (!$validate[0]) {
      return $validate;
    }

    return $this->model('transaction_add', $this->data);
  }

  public function transaction_get () {
    if (!$this->user->check_permission('payments_module')) {
      return [false, 'User is not allowed to request individual transaction information.'];
    }

    return $this->model('transaction_get', $this->data);
  }

  public function transaction_edit () {
    if (!$this->user->check_permission('payments_module')) {
      return [false, 'User is not allowed to edit transactions.'];
    }

    $validate = $this->model('transaction_validate', $this->data, 'edit');
    if (!$validate[0]) {
      return $validate;
    }

    return $this->model('transaction_edit', $this->data);
  }

  public function transaction_delete () {
    if (!$this->user->check_permission('payments_module')) {
      return [false, 'User is not allowed to delete transactions.'];
    }

    return $this->model('transaction_delete', $this->data);
  }

  public function get_user_data () {
    if (!$this->user->check_permission('payments_module')) {
      return [false, 'User is not allowed to request data about other users.'];
    }

    if (!isset($this->data['user_id'])) {
      return [false, 'No user ID provided.'];
    }

    return $this->model('get_user_data', $this->data);
  }

}
