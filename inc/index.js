var jump_links_width

window.onload = function() {
  call_hooks("init");

  resize()
  window.addEventListener('resize', resize)
}

function resize () {
  document.body.className = 'wide'
  let Jump_links = document.getElementById('Jump_links_wide')
  if (Jump_links) {
    jump_links_width = Jump_links.scrollWidth

    if (document.body.clientWidth < jump_links_width) {
      document.body.className = 'narrow'
    }
  }
}
