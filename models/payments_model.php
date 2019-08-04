<?php

class PaymentsModel extends OBFModel {

  public function ledger_overview ($data) {
    if (!isset($data['user_id'])) {
      $user_id = $this->user->param('id');
    } else {
      $user_id = $data['user_id'];
    }

    $this->db->where('user_id', $user_id);
    $result = $this->db->get('module_payments_transactions');

    return [true, 'Successfully loaded ledger overview.', $result];
  }

  public function transaction_validate ($data) {
    if (!isset($data['user_id']) || !isset($data['type'])
    || !isset($data['amount']) || !isset($data['created'])) {
      return [false, 'One or more fields not set.'];
    }

    $user_model = $this->load->model('users');
    if (!$user_model('get_by_id', $data['user_id'])) {
      return [false, 'Unable to find user ID.'];
    }

    if ($data['type'] != 'compensation' && $data['type'] != 'payment') {
      return [false, 'Invalid transaction type selected.'];
    }

    if (!is_numeric($data['amount']) || $data['amount'] < 0) {
      return [false, 'Negative values not allowed in amount field.'];
    }

    if (!is_numeric($data['created']) || $data['created'] < 0) {
      return [false, 'Creation date needs to be a valid timestamp.'];
    }

    return [true, 'Transaction input validated.'];
  }

  public function transaction_add ($data) {
    $modifier = ($data['type'] == 'compensation') ? '1' : '-1';
    $amount = $data['amount'] * $modifier;

    $result = $this->db->insert('module_payments_transactions', array(
      'user_id' => $data['user_id'],
      'created' => $data['created'],
      'amount'  => $amount,
      'comment' => '<placeholder comment>'
    ));

    if (!$result) {
      return [false, 'An unknown error occurred trying to insert transaction into the database.'];
    }

    return [true, 'Successfully added transaction.'];
  }

  public function get_user_data ($data) {
    $user_model = $this->load->model('users');
    $result = $user_model('get_by_id', $data['user_id']);

    if (!$result) {
      return [false, 'Unable to find user by ID.'];
    }

    return [true, 'Successfully acquired user data.', $result];
  }
}
