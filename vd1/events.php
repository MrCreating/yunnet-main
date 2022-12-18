<!DOCTYPE html>
  <html lang="ru">
    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>Мероприятия</title>
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <link type="text/css" rel="stylesheet" href="/vd_sources/css/materialize.css" media="screen,projection"/>
      <script type="text/javascript" src="/vd_sources/js/materialize.js"></script>
      <script type="text/javascript" src="/vd_sources/js/schedule.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    </head>

    <body>
      <style>
        html, body
        {
          background-color: #F2F2F2;
        }

        .card-panel
        {
          width: 450px;
          margin: 15px auto;
        }   
      </style>

      <div>                                                                 <!--      Белая панелька сверху           -->
        <?php require __DIR__ . '/components/sidenav.php'; ?>
      </div>                                                                               <!--                                      -->
      
      <div class="row">
        <div class="col s12 m5">
          <div class="card">
            <div class="card-image">
              <img src="/vd_sources/img/uits.png">
              <span class="card-title">Собрание кафедры УИТС</span>
            </div>
            <div class="card-content">
              <p>I am a very simple card. I am good at containing small bits of information.
              I am convenient because I require little markup to use effectively.</p>
            </div>
          </div>
        </div>
      </div>

    </body>
  </html>