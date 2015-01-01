<?php require('./easybitcoin.php'); $bitcoin = new Bitcoin('cwatson','EL5dW6NLpt3A8eeE2KBA9TcFyyVbNvhXfXNBpdB7Rcey','localhost','8332'); $info = $bitcoin->getinfo();?>
<html>
<head>
  <title>BTC Test</title>
  <style>
    body { background-color: #eee; margin-top: 20px; font-family: "Trebuchet MS", Helvetica, sans-serif; }
    div#content { background-color: #dcdcdc; width: 1000px; margin: 0px auto; min-height: 340px; }
    div#inner { margin-left: 340px; padding: 0px 20px; }
    div.error { background-color: #FFD5D5; border: 1px #FF7878 solid; text-align: center }
    div.error a { color: #300; text-decoration: none; }
    img.logo { float: left; }
    h1 { text-align: center; padding-top: 30px; }
    h2 { font-size: 1.2em; text-align: center; }
  </style>
</head>
<body>
<div id="content">
  <img src="http://media.tumblr.com/tumblr_lmuxp0Byto1qznjpp.png" alt="Bitcoin Logo" class="logo"/>
  <div id="inner">
    <h1>Bitcoin Node Status</h1>
<?php if (!$info) { ?>
    <div class="error">
      <h2>There has been an error communicating with the Bitcoin Daemon</h2>
      <pre><?php echo $bitcoin->error; ?></pre>
      <p>Please report this to <a href="mailto:admin@vikingserv.net">admin@vikingserv.net</a></p>
    </div>
<?php } else { ?>
    <p><strong>Number of Connections:</strong> <?php echo $info['connections']; ?></p>
    <p><strong>Block Number:</strong> <?php echo $info['blocks']; ?></p>
    <p><strong>Difficulty:</strong> <?php echo $info['difficulty']; ?></p>
    <p><strong>IP Address:</strong> <?php echo $_SERVER['SERVER_ADDR'];?></p>
<?php } ?>
  </div>
</div>
</body>
</html>
