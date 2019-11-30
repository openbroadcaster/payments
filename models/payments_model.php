<?php

class PaymentsModel extends OBFModel {

  public function ledger_overview ($data) {
    if (!isset($data['user_id'])) {
      $this->db->where('user_id', $this->user->param('id'));
    } else if ($data['user_id'] != 'all') {
      $this->db->where('user_id', $data['user_id']);
    }

    if (isset($data['filter_start'])) {
      $this->db->where('created', $data['filter_start'], '>=');
    }
    if (isset($data['filter_end'])) {
      $this->db->where('created', $data['filter_end'], '<=');
    }

    $this->db->orderby('created', 'asc');
    $result = $this->db->get('module_payments_transactions');

    foreach ($result as $id => $elem) {
      $this->db->what('username');
      $this->db->where('id', $elem['user_id']);
      $query = $this->db->get_one('users');

      if (!$query) {
        return [false, 'Failed to find one of the users in ledger table.'];
      }

      $result[$id]['username'] = $query['username'];
    }

    return [true, 'Successfully loaded ledger overview.', $result];
  }

  public function ledger_download ($data) {
    if ($data['user_id'] != 'all') {
      $this->db->where('user_id', $data['user_id']);
    }

    if (isset($data['start']) && $data['start'] != '') {
      $this->db->where('created', $data['start'], '>=');
    }
    if (isset($data['end']) && $data['end'] != '') {
      $this->db->where('created', $data['end'], '<=');
    }

    $this->db->orderby('created', 'asc');
    $result = $this->db->get('module_payments_transactions');

    $fh = fopen('php://temp', 'w+');
    $headers = [
      'ID',
      'USER_ID',
      'USERNAME',
      'AMOUNT',
      'CREATED',
      'COMMENT'
    ];
    fputcsv($fh, $headers);

    foreach ($result as $id => $elem) {
      $this->db->what('username');
      $this->db->where('id', $elem['user_id']);
      $query = $this->db->get_one('users');

      if (!$query) {
        fclose($fh);
        return [false, 'Failed to find one of the users in ledger table.'];
      }

      $line = [
        $elem['id'],
        $elem['user_id'],
        $query['username'],
        $elem['amount'],
        $elem['created'],
        strip_tags($elem['comment'])
      ];
      fputcsv($fh, $line);
    }

    $csv = stream_get_contents($fh, -1, 0);
    fclose($fh);

    return [true, 'Successfully loaded ledger CSV.', $csv];
  }

  public function ledger_validate ($data) {
    $user_model = $this->load->model('users');
    if ($data['user_id'] != 'all' && !$user_model('get_by_id', $data['user_id'])) {
      return [false, 'Unable to find user ID.'];
    }

    if ($data['start'] != '' && date('Y-m-d', strtotime($data['start'])) != $data['start']) {
      return [false, 'Start date needs to be a valid date.'];
    }

    if ($data['end'] != '' && date('Y-m-d', strtotime($data['end'])) != $data['end']) {
      return [false, 'End date needs to be a valid date.'];
    }

    return [true, 'Ledger filters validated.'];
  }

  public function transaction_validate ($data, $type) {
    if (!isset($data['type']) || !isset($data['amount'])
    || !isset($data['created']) || !isset($data['comment'])) {
      return [false, 'One or more fields not set.'];
    }

    if ($type == 'add') {
      if (!isset($data['user_id'])) {
        return [false, 'User ID required.'];
      }

      $user_model = $this->load->model('users');
      if (!$user_model('get_by_id', $data['user_id'])) {
        return [false, 'Unable to find user ID.'];
      }
    }

    if ($type == 'edit') {
      if (!isset($data['transaction_id'])) {
        return [false, 'Transaction ID required.'];
      }

      $this->db->where('id', $data['transaction_id']);
      if (!$this->db->get_one('module_payments_transactions')) {
        return [false, 'Unable to find transaction ID.'];
      }
    }

    if ($data['type'] != 'compensation' && $data['type'] != 'payment') {
      return [false, 'Invalid transaction type selected.'];
    }

    if (!is_numeric($data['amount']) || $data['amount'] < 0) {
      return [false, 'Negative values not allowed in amount field.'];
    }

    if (date('Y-m-d', strtotime($data['created'])) != $data['created']) {
      return [false, 'Creation date needs to be a valid date.'];
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
      'comment' => $data['comment']
    ));

    if (!$result) {
      return [false, 'An unknown error occurred trying to insert transaction into the database.'];
    }

    return [true, 'Successfully added transaction.'];
  }

  public function transaction_get ($data) {
    $this->db->where('id', $data['id']);
    $result = $this->db->get_one('module_payments_transactions');

    if (!$result) {
      return [false, 'Failed to load transaction.'];
    }

    return [true, 'Successfully loaded transaction.', $result];
  }

  public function transaction_edit ($data) {
    $modifier = ($data['type'] == 'compensation') ? '1' : '-1';
    $amount = $data['amount'] * $modifier;

    $this->db->where('id', $data['transaction_id']);
    $result = $this->db->update('module_payments_transactions', array(
      'created' => $data['created'],
      'amount'  => $amount,
      'comment' => $data['comment']
    ));

    if (!$result) {
      return [false, 'An unknown error occurred trying to update transaction in the database.'];
    }

    return [true, 'Successfully updated transaction.'];
  }

  public function transaction_delete ($data) {
    $this->db->where('id', $data['id']);
    if (!$this->db->get_one('module_payments_transactions')) {
        return [false, 'Transaction ID does not exist.'];
    }

    $this->db->where('id', $data['id']);
    $this->db->delete('module_payments_transactions');

    return [true, 'Successfully deleted transaction.'];
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
