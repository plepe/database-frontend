global.show_table_fields = function () {
  var div = document.getElementById("Table_Fields");
  if(div.className == "hidden")
    div.className = "";
  else
    div.className = "hidden";
}

function init () {
}

module.exports = {
  init
}
