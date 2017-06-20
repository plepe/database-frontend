<?php
class ViewBackreferenceField {
  function __construct($def, $field) {
    $this->def = $def;
    $this->id = $this->def['id'];
    $this->src_table = get_db_table($this->def['src_table']);
    $this->dest_table = get_db_table($this->def['dest_table']);
    $this->def['name'] = "Reference {$field['name']} (from {$this->dest_table->name()})";
  }

  function id() {
    return $this->def['id'];
  }

  function view_def() {
    $def = $this->def;

    $dest_table_esc = json_encode($this->def['dest_table']);
    $dest_field_esc = json_encode($this->def['dest_field']);
    $dest_table_url = urlencode($this->def['dest_table']);

    $def['format'] = <<<EOT
<ul>
{% for _ref_entry in get_entries({$dest_table_esc}, [[ {$dest_field_esc}, 'is', id ]]) %}
<li><a href='?page=show&table={$dest_table_url}&id={{ _ref_entry.id }}'>{{ entry_title({$dest_table_esc}, _ref_entry.id) }}</a></li>
{% endfor %}
</ul>
EOT;

    return $def;
  }
}
