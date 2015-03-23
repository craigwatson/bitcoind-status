function showHidePeers () {
  if( $("div#peerstable").is(":hidden")) {
    $("div#peerstable").slideDown();
    $("a#peerslink").text("Hide");
  } else {
    $("div#peerstable").slideUp();
    $("a#peerslink").text("Show");
  }
}
