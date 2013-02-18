<?php

$capabilities = array(
  'local/kent-connect:publish' => array(
    'riskbitmask' => RISK_CONFIG,
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'admin' => CAP_ALLOW
    )
  )
);
