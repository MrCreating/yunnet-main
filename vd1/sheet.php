<!DOCTYPE html>
  <html lang="ru">
    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>Ведомости</title>
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
      </div>

      <?php
      die(var_dump(\unt\platform\DataBaseManager::getConnection()));
      ?>
    </body>
  </html>