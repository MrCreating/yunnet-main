<nav>
    <!--                                      -->
    <div class="nav-wrapper white">
        <!--                                      -->
        <a href="#" data-target="slide-out" class="sidenav-trigger show-on-large">
            <i style="color: #7F1E2F !important" class="material-icons">menu</i>
        </a>
        <a class="brand-logo center">
            <!--                                      -->
            <font color=#7F1E2F>
                <!--                                      -->
                Единый деканат
                <!--                                      -->
            </font>
            <!--                                      -->
        </a>
        <!--                                      -->
        <a href="https://stankin.ru/" style="margin-right: 1%" class="right">
            <!--                                      -->
            <img src="/vd_sources/img/stankin_logo.png">
            <!--                                      -->
        </a>
        <!--                                      -->
    </div>
    <!--                                      -->
</nav>

<ul id="slide-out" class="sidenav">
    <li>
        <div class="user-view">
            <div class="background">
                <img src="/vd_sources/img/stankin_foto.jpg">
            </div>

            <a id="lk-account-icon" href="/lk">
                <!----------------------------------------------- Тут аватарка пользователя, id элемента lk-account-icon -->
                <img class="circle" src="/vd_sources/img/material-icon-account.png">
            </a>

            <a id="lk-account-name" href="/lk"><span class="white-text name"><!----------------- Тут имя пользователя, id элемента lk-account-name -->
              <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
            </span></a>

            <a id="lk-account-email" href="/lk"><span class="white-text email"><!-------------- Тут эл. почта, id элемента lk-account-email -->
              <?php
                $status_string = 'Студент';
                switch ($_SESSION['access_level']) {
                    case 3:
                        $status_string = 'Администратор';
                        break;
                    case 2:
                        $status_string = 'Преподаватель';
                        break;
                    default:
                        break;
                }
                echo $status_string;
              ?>
            </span></a>
        </div>

    </li>

    <?php if ($_SESSION['access_level'] >= 1): ?>
    <li><a href="/schedule">
            <i class="material-icons">date_range</i>
            Моё расписание
        </a></li>
    <?php endif; ?>

    <?php if ($_SESSION['access_level'] >= 2): ?>
    <li><a href="/sheet">
            <i class="material-icons">description</i>
            Ведомости
        </a></li>
    <?php endif; ?>

    <?php if ($_SESSION['access_level'] >= 3): ?>
        <li><a href="/events">
            <i class="material-icons">assessment</i>
            Мероприятия
        </a></li>
    <?php endif; ?>

    <?php if ($_SESSION['access_level'] >= 3): ?>
        <li><a href="/groups">
                <i class="material-icons">group</i>
                Группы
            </a></li>
    <?php endif; ?>

    <li>
        <div class="divider"></div>
    </li>
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