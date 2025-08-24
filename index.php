<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta
    name="description"
    content="lain.rocks, the c00l place on the internet" />
  <meta name="keywords" content="lain" />
  <meta name="author" content="tyt4real" />

  <title>lain.rocks | Home</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
    crossorigin="anonymous" />
  <link rel="icon" type="image/x-icon" href="./favicon.ico" />
  <link href="./css/config.css" rel="stylesheet" />
  <link href="./css/index.css" rel="stylesheet" />
</head>

<body>
  <header class="mt-2 card">
    <h1 id="shit">lain.rocks</h1>
    <p>The (hopefully) cool place on the internet</p>
    <p>「どこに行ったって、人はつながっているのよ。」</p>
  </header>

  <div id="app" class="container">
    <section class="mt-2 card">
      <div class="card-header">
        <h2>Welcome to <i>lainrocks</i></h2>
      </div>
      <div class="card-body">
        <ul>
          <li>
            <p>
              The current state of this website is purely temporary, it will be changing often.
            </p>
          </li>
          <li>
            <p>
              If you would like to know who the person behind this site is, you can read <a href="./aboutme.php" target="_blank">here</a>.
            </p>
          </li>
          <li>
            The XMPP service we offer, has very simple rules. No illegal content under the US and Czech law; We store uploaded files for 30 days, and MUC messages for 7 days. The server runs on an updated, latest, Ubuntu VPS in the glorious Czech Republic. We keep backups, and have contingency plans to bring back service in case of problems. We <i>STRONGLY</i> advise you to use <i>YOUR OWN</i> end-to-end encryption. <i>NEVER</i> trust your provider, doesn't matter whether it's me or someone else, use OMEMO or PGP to keep your communications safe and private from the alphabet boys.
          </li>
          <li>
            <p>
              The working commit for this page is: <?php
                                                    //64e8297
                                                    $fetchheadfile = './.git/FETCH_HEAD';
                                                    if (file_exists($fetchheadfile)) {
                                                      $headfile = file_get_contents($fetchheadfile);
                                                      $shortCommitTag = substr($headfile, 0, 6);
                                                      $fullCommitTag = substr($headfile, 0, 40);
                                                      $format = "<a href='https://github.com/tyt4real/lainrocks/commit/%s'>%s</a>";
                                                      echo sprintf($format, $fullCommitTag, $shortCommitTag);
                                                    } else {
                                                      echo ("Can't read latest commit.");
                                                    }
                                                    ?></p>
          </li>
        </ul>

      </div>
    </section>

    <section id="services" class="mt-2 card">
      <div class="card-header">
        <div class="d-flex justify-content-between">
          <div>
            <h2>Services</h2>
          </div>
          <div>
            <h2>v0.1</h2>
          </div>
        </div>
      </div>
      <div class="card-body">
        <ul>
          <li>
            Public XMPP server: click
            <a href="https://lain.rocks/xmpp/register" target="_blank">here</a>. <a href='https://compliance.conversations.im/server/lain.rocks'><img src='https://compliance.conversations.im/badge/lain.rocks'></a>
            <ul>
              <li>
                Terms of service: click
                <a href="https://lain.rocks/tos.html" target="_blank">here</a>.
              </li>
              <li>
                Converse.js XMPP web client: click
                <a href="https://lain.rocks/xmpp/conversejs/" target="_blank">here</a>.
              </li>
              <li>
                The XMPP servers has a Biboumi IRC gateway available, and a Telegram gateway will be available soon.
              </li>
            </ul>
          </li>
          <li>
            Uptime page:
            <a href="https://stats.uptimerobot.com/6eWo4s81Co" target="_blank">here</a>.
          </li>
        </ul>
      </div>
    </section>
    <!--
    <section class="mt-2 card">
      <div class="card-header">
        <h2>Textboards</h2>
      </div>
      <div class="card-body">
        <ul>
          <li>asfd</li>
          <li>fdsa</li>
          <li>qwer</li>
          <li>tyui</li>
        </ul>
      </div>
    </section> -->
    <!-- <section class="mt-2 card">
      <div class="card-header">
        <h2>Forum</h2>
      </div>
      <div class="card-body" style="text-align: center">
        <a
          href="https://forum.lain.rocks/"
          target="_blank"
          id="forumlink"
          class="glow whitelink">
          <h1>Go to the forum!</h1>
        </a>
        <p>It's not there quite yet</p>
      </div>
    </section> -->
    <section class="mt-2 card">
      <div class="card-header">
        <h2>Webring</h2>
      </div>
      <div class="card-body" style="text-align: center">
        <h1><a href="./webring.php" class="glow whitelink">You can view my copy of the lainchan webring here</a></h1>
      </div>
    </section>
    <section class="mt-2 card">
      <div class="card-header">
        <h2>Stuff I like :3</h2>
      </div>
      <div class="card-body" style="text-align: center;">
        <div class="row">
          <div class="col-sm"><a href="https://haruhi.tv" target="_blank"><img src="https://haruhi.tv/img/fanclub.jpg" border="0" alt="ハルヒ特設ファンサイト"></a></div>
          <div class="col-sm"><a href="https://lain.la"><img class="banner" src="https://0x19.org/lainring/images/lain-la.png" alt="lain.la"></a></div>
          <div class="col-sm"><a href="https://sizeof.cat"><img class="banner" src="https://0x19.org/lainring/images/sizeofcat.gif" alt="sizeof(cat)"></a></div>
          <div class="col-sm"><a href="https://agoraroad.neocities.org/"><img alt="Agora Road" src="https://forum.agoraroad.com/index.php?media/240x60-banner.3433/full"></a></div>
          <div class="col-sm"><a href="https://lainchan.org/"><img style="max-height: 60px; max-width: 240px;" src="./lainchan_tech.png" alt="Tech Focused Alt-Chan"></a></div>
        </div>
      </div>
    </section>
  </div>
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
    crossorigin="anonymous"></script>

  <!-- <div class="footer">
  <h4>Let's all love Lain (c) 2025</h4>
  <p>For all service abuse compaints, contact me here: <a href="mailto:tyt4real@protonmail.com">tyt4real@protonmail.com</a> ; if you see something, say something.</p>
</div> -->
</body>

</html>