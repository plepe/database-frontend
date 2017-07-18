<?php
function random_ids_get($value, $field_def, $form) {
  $generator = getRandomIdGenerator($field_def->def['random-ids-id']);
  return $generator->get();
}
