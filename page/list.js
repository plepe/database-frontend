function Page_list() {
}

Page_list.prototype.content = function(param) {
  var ret = {
    template: "list.html"
  };
  
  ret.content = "FOOBAR";

  return ret;
}
