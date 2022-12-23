<?php
    $student_id = (int) \unt\objects\Request::get()->data['id'];

    if ($student_id === 0)
        $student_id = $_SESSION['vd_user_id'];

    $entity_type = (int) \unt\objects\Request::get()->data['ent_m'];
    if ($entity_type === 0)
        $entity_type = $_SESSION['access_level'];

    $entity = get_user($student_id, $entity_type);
    if (!$entity)
        die('Профиль не найден!');
?>

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
    <script type="text/javascript" src="/vd_sources/js/schedule.js"></script>
</head>

<body>
<style>
    html, body {
        background-color: #F2F2F2;
    }

    .card-panel {
        width: 450px;
        margin: 15px auto;
    }
</style>

<div>                                                                 <!--      Белая панелька сверху           -->
    <div>                                                                 <!--      Белая панелька сверху           -->
        <?php require __DIR__ . '/components/sidenav.php'; ?>
    </div>

    <div>
        <div style="width: 100%; padding: 20px 50px;" class="valign-wrapper">
            <div class="valign-wrapper" style="width: 100%; padding: 28px; margin-left: 20px">
                <div>
                    <img src="/vd_sources/img/material-icon-account.png" class="circle" height="200" width="200">
                </div>

                <div class="page-footer white card" style="width: 100%; margin-left: 20px">
                    <div class="container">
                        <div class="row">
                            <div class="col l5 s12">
                                <h5 class="black-text"><?php echo $entity['last_name'] . ' ' . $entity['first_name']; ?></h5>
                                <p class="black-text text-lighten-4">
                                    <?php
                                        $status_string = 'Студент';
                                        switch ($entity['access_level']) {
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
                                </p>
                            </div>
                            <div class="col l4 offset-l2 s12">
                                <h5 class="black-text">Ваши контакты</h5>
                                <ul>
                                    <h6 class="black-text">Телефон</h6>
                                    <li><a href="#!">Не указан</a></li>
                                    <h6 class="black-text">Почта</h6>
                                    <li><a href="#!">Не указана</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</body>
</html>