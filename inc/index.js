var jump_links_width

window.onload = function() {
  call_hooks("init");

  resize()
  window.addEventListener('resize', resize)
}

function resize () {
  document.body.className = 'wide'
  jump_links_width = document.getElementById('Jump_links_wide').scrollWidth

  if (document.body.clientWidth < jump_links_width) {
    document.body.className = 'narrow'
  }
}
