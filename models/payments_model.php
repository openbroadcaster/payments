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
}
