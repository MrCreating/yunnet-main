<!DOCTYPE html>
  <html lang="ru">
    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>Виртуальный деканат</title>
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <link type="text/css" rel="stylesheet" href="/vd_sources/css/materialize.css" media="screen,projection"/>
      <script type="text/javascript" src="/vd_sources/js/materialize.js"></script>
      <script type="text/javascript" src="/vd_sources/js/lk.js"></script>
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

      <div style="width: 90%; margin: 5%;">
          <span style="font-size: 200%;"><b>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['last_name']); ?>!</b></span>
          <div class="valign-wrapper">
              <div class="halign-wrapper">
                  <div class="card-panel white">
                      <span style="color: #7F1E2F; font-size: 150%" class="card-title">Уведомления</span>
                      <p>
                          Перейдите по ссылке для получения последних уведомлений
                      </p>
                      <div class="divider"></div>
                      <div class="card-action">
                          <a href="https://edu.stankin.ru/">Перейти к Уведомлениям</a>
                      </div>
                  </div>
              </div>
              <?php if ($_SESSION['access_level'] >= 2): ?>
                  <div class="halign-wrapper" style="margin-left: 15px">
                      <div class="card-panel white">
                          <span style="color: #7F1E2F; font-size: 150%" class="card-title">Ведомости</span>
                          <p>
                              Перейдите по ссылке для просмотра вашего списка ведомостей
                          </p>
                          <div class="divider"></div>
                          <div class="card-action">
                              <a href="/sheet">Перейти к Ведомостям</a>
                          </div>
                      </div>
                  </div>
              <?php endif; ?>

              <?php if ($_SESSION['access_level'] >= 1): ?>
                  <div class="halign-wrapper" style="margin-left: 15px">
                      <div class="card-panel white">
                          <span style="color: #7F1E2F; font-size: 150%" class="card-title">Расписание</span>
                          <p>
                              Перейдите по ссылке для просмотра информации о вашем расписании
                          </p>
                          <div class="divider"></div>
                          <div class="card-action">
                              <a href="/schedule">Перейти к Расписанию</a>
                          </div>
                      </div>
                  </div>
              <?php endif; ?>
          </div>
      </div>
    </body>
  </html>
