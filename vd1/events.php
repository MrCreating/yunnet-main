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
        <nav>                                                                                 <!--                                      -->
          <div class="nav-wrapper white">                                                     <!--                                      -->
            <a href="#" data-target="slide-out" class="sidenav-trigger show-on-large">
              <i style="color: #7F1E2F !important" class="material-icons">menu</i>
            </a>
            <a class="brand-logo center">                                                     <!--                                      -->
              <font color=#7F1E2F>                                                            <!--                                      -->
                Единый деканат                                                                <!--                                      -->
              </font>                                                                         <!--                                      -->
            </a>                                                                              <!--                                      -->
            <a href="https://stankin.ru/" style="margin-right: 1%" class="right">             <!--                                      -->
              <img src="/vd_sources/img/stankin_logo.png">                                                <!--                                      -->
            </a>                                                                              <!--                                      -->
          </div>                                                                              <!--                                      -->
        </nav>






        <ul id="slide-out" class="sidenav">
          <li><div class="user-view">
            <div class="background">
              <img src="/vd_sources/img/stankin_foto.jpg">
            </div>

            <a id="lk-account-icon"><!----------------------------------------------- Тут аватарка пользователя, id элемента lk-account-icon -->
              <img class="circle" src="/vd_sources/img/material-icon-account.png">
            </a>

            <a id="lk-account-name"><span class="white-text name"><!----------------- Тут имя пользователя, id элемента lk-account-name -->
              Пользователь
            </span></a>

            <a id="lk-account-email"><span class="white-text email"><!-------------- Тут эл. почта, id элемента lk-account-email -->
              example@gmail.com
            </span></a>
          </div>

            </li>

              <li><a href="/schedule">
                <i class="material-icons">date_range</i>
                Моё расписание
              </a></li>

              <li><a href="/sheet">
                <i class="material-icons">description</i>
                Ведомости
              </a></li>

              <li><a href="/events">
                <i class="material-icons">group</i>
                Мероприятия
              </a></li>

              <li><div class="divider"></div></li>
              <li><a class="subheader">Обратная связь</a></li>

              <li><a href="https://edu.stankin.ru/">
                ЭОС
              </a></li>
            <li>
                <a href="/logout">
                    Выйти из аккаунта
                </a>
            </li>
        </ul>
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