function showHidePeers () {
  if( $("div#peerstable").is(":hidden")) {
    $("div#peerstable").show("slow");
    $("a#peerslink").text("Hide");
  } else {
    $("div#peerstable").slideUp();
    $("a#peerslink").text("Show");
  }
}
